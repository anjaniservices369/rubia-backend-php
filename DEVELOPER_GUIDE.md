# Rubia Services backend - Developer Guide

Yii2 2.0.55 on PHP 8.2 (needs PHP >= 8.1 with `sodium`, `gd`, `exif`, `pdo_mysql`), MySQL/MariaDB.
App root: `app/`. Business-level description: `BUSINESS_GUIDE.md`. Deployment: `DEPLOY.md`.

Other docs: `MODULES.md` (module map), `MEDIA_STORAGE.md` (uploads), `MOBILE_APP_CHANGES.md` (what the apps must do),
`app/database/README.md` (migrations).

---

## 1. Shape of the system

```
 Customer app ─┐                         ┌─ admin dashboard (browser, /admin/*)
 Partner app  ─┼─ HTTPS JSON /api/* ─────┤
 Manager app  ─┘                         └─ cron  (php yii ..., /api/user/assign-booking-to-driver)
                      │
              Yii2 application  ── MySQL (one schema)
                      │
        2factor.in (SMS/OTP) · OneSignal (push) · Google Maps · payment gateways
```

Two products share one database:

| Product | Tables | API modules | State machine |
|---|---|---|---|
| **Halting drivers service** (hire a driver for N hours) | `booking`, `driver_requests`, `booking_logs`, `driver_earnings`, `driver_wallet`, `cancel_ride_charges`, ratings | `customer-app` (`/api/user/*`), `haltingdriver-app` (`/api/driver/*`) | `Booking::STATUS_*` |
| **Taxi** (bike / auto / car, instant rides) | `ride_request`, `taxi_driver_request`, `taxi_payout`, `taxi_rating`, `ride_earnings`, `ride_commision` | `cartaxirider` / `autotaxirider` / `biketaxirider` (`/api/car-user/*`, `/api/auto-user/*`, `/api/bike-user/*`), `cartaxidriver` / `autotaxidriver` / `biketaxidriver` (`/api/car/*`, `/api/auto/*`, `/api/bike/*`) | `RideRequest::STATUS_*` |

Back office: `modules/admin` (controllers, views, models, search models). All ActiveRecord models live in
`modules/admin/models` (`base/` = generated, `Xxx.php` = extension points); plus `app/models/User.php`.

## 2. Request lifecycle (API)

1. `index.php` -> `config/web.php` (+ `config/rbac.php`). `YII_ENV` defaults to **prod**; set `YII_ENV=dev`,
   `YII_DEBUG=1` only on a development machine.
2. `config/api-routes.php` maps `api/<prefix>/<action>` to `<module>/<controller>/<action>`.
3. Every API controller extends `BKController` (`modules/api`): JSON output, `sendJsonResponse()` (adds `status`,
   `url`; scrubs SQL/stack text from `error`), `authUserId()`.
4. The app sends its login code as header (or query parameter) **`auth_code`**.
   `AuthSettings::getAuthSession($code)` returns the user id or null. Most actions then do:

   ```php
   $user_id = (new AuthSettings())->getAuthSession($headers);
   if (empty($user_id)) { ... NOK 'User Not Found' }
   ```
5. Response shape: `{status: "OK"|"NOK", details|detail|error, url}`. Legacy actions return `details`, a few
   `detail`; NOK carries the text in `error` or (older) `details`. Do not change shapes: the installed apps depend on
   them. See `MOBILE_APP_CHANGES.md`.

### Authentication

- Login: `send-otp` -> `verify-otp` (2factor.in). Verified -> user found/created -> `AuthSession::newSession()` ->
  a **JWT (HS256, lcobucci/jwt 5)** returned as `auth_code`, stored in `auth_session`.
  Secret: `JWT_SECRET` env or `config/jwt-secret.php` (created on first use, never committed).
- One session per user: a new login replaces the old one.
- **A session only counts while the user is active.** `AuthSession::find()` returns `ActiveUserSessionQuery`,
  which adds `create_user_id IN (SELECT id FROM user WHERE status = 10)` when the SQL is built. A blocked, inactive or
  deleted account is therefore "logged out" at once, in every lookup. `verify-otp` also refuses blocked accounts.
- `OtpService::isBypassed()` lets configured test numbers skip the OTP **only when `YII_ENV` is dev**.
- Roles: `User::ROLE_*` (`User`, `Driver`, `taxi`, `admin`, `Subadmin`, `Manager`, `city franchise`, ...). Account
  status: `STATUS_ACTIVE=10`, `STATUS_BLOCKED=0`, `STATUS_INACTIVE=1`, `STATUS_DELETED=2`.
- Dashboard access: `config/rbac.php` returns 404 for `/admin/*` to anyone who is not admin/franchise;
  controllers add `AccessControl` per role (`User::isAdmin()`, `isSubAdmin()`, `isManager()`, `isFranchise()`).

## 3. Driver-service booking: states and who may move them

`booking.status` (`Booking` constants):

| # | Constant | Set by |
|---|---|---|
| 1 | `STATUS_WAITING_FOR_DRIVER` | customer `get-estimate?saveBooking=true`; admin "send back"; driver cancel |
| 2 | `STATUS_DRIVER_ACCEPTED` | driver `accept-ride`; admin assign |
| 4 | `STATUS_GO_FOR_PICKUP` | driver `go-for-pickup` |
| 5 | `STATUS_ARRIVED_LOCATION` | driver `arrived-location` (verifies the customer's OTP) |
| 6 | `STATUS_START_RIDE` | driver `start-ride` |
| 7 | `STATUS_RIDE_COMPLETED` | driver `complete-ride`, then `collect-cash` |
| 8 | `STATUS_USER_CANCELLED` | customer `cancel-ride` |
| 9 | `STATUS_BOOKING_AUTO_CANCELLED` | `BookingExpiry` (pickup time passed, no driver) |
| 10 | `STATUS_DRIVER_CANCELLED` | (offer status; the booking returns to 1) |
| 11 | `STATUS_CANCELLED` | admin cancel / blocked customer |

Guards you must keep (they were missing and were security holes):

- customer `cancel-ride`: booking must belong to the caller (`user_id`); status 1, 2 or 4 only.
- driver `cancel-ride`, `go-for-pickup`, `notify-arrived`, `arrived-location`, `start-ride`, `complete-ride`:
  the booking must be assigned to the caller (`driver_id`) and be in the right previous state
  (`DriverController::ownedBooking()`). `start-ride` needs `ARRIVED_LOCATION` (OTP verified).
- `accept-ride`: status must still be 1; the driver must have no booking in states 2/4/5/6; wallet >=
  `driver_min_wallet_bal`; the booking is claimed with one conditional `UPDATE ... WHERE status = 1` so two drivers
  cannot both win.
- `collect-cash`: the customer is taken from the booking, never from the request; `add-review` and
  `vehicle-verification`: only for the driver's own booking; `driver-status`: only 1 or 2; `update-lat-lng`: valid
  numbers only.
- Taxi: `cancel-ride` (rider and driver) and `verify-ride-otp` check ownership (`user_id` / `driver_id`) and state;
  `ride-request` refuses missing fields instead of storing them.
- `get-estimate?saveBooking=true`: refuses if the customer already has a booking in states 1/2/4/5/6 (after running
  `BookingExpiry` so stale waiting bookings do not block). Also validates car type, hours (0..168), city,
  pickup inside the city boundary, pickup not in the past, and computes the coupon discount on the server.

### Dispatch (halting drivers service)

`GET /api/user/assign-booking-to-driver` (run every minute from cron; optional `?token=` when `CRON_TOKEN` is set):

1. `BookingExpiry::run()` cancels waiting bookings whose pickup date+time has passed (setting
   `booking_expiry_grace_minutes`, default 0) and notifies the customer.
2. For waiting bookings created in the last 24 h: online, active drivers of the booking's city, not busy, no existing
   offer, ordered by distance to the pickup, at most 25 new offers per booking per run. Creates
   `driver_requests` rows and pushes.
3. Stops after 40 s and returns a JSON summary.

Driver `bookings` lists offers by joining `driver_requests`; `accept-ride` takes the booking atomically.

### Admin actions on a booking

`modules/dispatch/services/BookingAdmin.php` (used by `BookingController::actionManage/actionUpdateStatus`):
`cancel`, `reopen`, `assignDriver`, `setProgress`. Each writes `booking_logs` and notifies. Completing a ride is not
available there on purpose (fare/commission/wallet are posted by `complete-ride`/`collect-cash`).
Blocking: `UsersController::changeStatus()` -> `releaseBlockedUser()`.

### Money

`DriverController::actionCompleteRide` posts `driver_earnings`: `admin_earning = (total - GST - secured - platform) *
admin_per/100 + GST + secured + platform`, `driver_earning = total - admin_earning`. Cash rides debit
`admin_earning` from `driver_wallet`; wallet rides credit. Pending `cancel_ride_charges` are settled against the next
ride. Settings are in `web_setting` (`gst`, `default_admin_commission_raider`, `*_cancellation_charge`, ...).
The final fare is recomputed from actual distance/time (`Booking::calculateEstimate(isFinal)`), so it can differ
from `estimate_price`.

### Service area from pincodes
`modules/geo/services/PincodeAreas.php`: for a city, every distinct active `pincode.name` is geocoded once (Google
Geocoding, `components=postal_code:NNNNNN|country:IN`, server key) and cached in `pincode_area`. The service area is
the convex hull of the pincode rectangles (monotone chain), written to `city.boundary` in the existing format (a JSON
string holding a JSON list of `[lat, lng]`), so `check-boundary` and `get-estimate` need no change. A pincode more
than 150 km from the median of its city's pincodes is flagged an outlier and left out (usually mis-filed).
Entry points: dashboard `admin/city/pincode-area?id=` (admin and sub-admin; preview, refresh, save) and
`php yii geo/pincode-area --city=ID [--apply] [--refresh]`.
Limit: a convex outline also covers the space between far-apart pincodes of one city; keep one city's pincodes together.

### Service modules, maps and billing
Four modules, numbered as the dashboard menus and `user.vehicle_id` already number them (`ServiceModules`):
**1 halting drivers service, 2 taxi, 3 auto, 4 bike**. A vehicle type row (`vehicals`) belongs to a module through
`vehicals.vehicle_type`. Each module has its own map of a city (`MapDesigner`, `ServiceMapController`):

| Piece | Halting drivers service (1) | Taxi / auto / bike (2, 3, 4) |
|---|---|---|
| Pincodes | `service_map_pincode` (module, city, pincode) | `pincode` rows for the module's vehicle types |
| Service area | `service_map` (module, city); falls back to `city.boundary` | `service_map` (module, city); none = no restriction |
| Pricing | `city_rate_card` | `ride_commision` with `vehicle_id` = module |
| Franchise | `user.city_id` + `user.vehicle_id` = module | same |
| Billing | `driver_earnings.module = 1` | `ride_earnings.module`, `taxi_payout.module` (set by `beforeSave` from the ride's / driver's vehicle type) |

Wallets are per service too: `driver_wallet.module` (stamped in `DriverWallet::beforeSave` from the driver's role and vehicle type), `DriverWallet::availableBalance($driverId, $module)`; the taxi services' minimum balance is checked in `TaxiOfferService::accept` when `wallet_min_enforced_<module>` = 1.

A pincode's city is resolved per module (`MapDesigner::cityOfPincode`), the area is enforced per module
(`ServiceModules::boundary`/`inside`; halting drivers service in `UserController::pickupInsideCity`, taxi in
`TaxiUserController::actionRideRequest`), fares and commission are looked up by `(city, module)`
(`RideCommision` where `vehicle_id` = `ServiceModules::ofVehicle($ride->vehical_type)`; the estimate uses
`$vehicals->vehicle_type`). Dashboard: `admin/service-map/index|map|act|billing|billing-csv?module=N`
(`ServiceMapController`; admin and sub-admin edit, a manager/franchise of that module only looks).
Operations (`MapDesigner`): add (never steals a pincode active in another city *of the same module*), remove (status 2),
move, copy pricing (only into a city with none, one transaction), assign / remove franchise, save outline; each is
written to `map_log` with the module.

### Taxi, auto and bike as separate services
`config/web.php` registers six more instances of the two taxi API modules with `serviceModule` set (`bike-driver`,
`auto-driver`, `car-driver`, `bike-rider`, `auto-rider`, `car-rider`), reached at `/api/bike`, `/api/auto`, `/api/car` and
`/api/bike-user`, `/api/auto-user`, `/api/car-user` (`config/api-routes.php`). The module's `beforeAction` calls
`ServiceScope::enter()`; from then on `RideRequestQuery::prepare()` limits every `RideRequest` read to the service's vehicle
types, the vehicle list and the posted `vehical_id` / `vehical_type` are the service's own, and
`TaxiOfferService::accept` refuses a ride it cannot see. The shared `/api/taxi*` modules have no service bound and behave
as before. This is the API-level step towards separate deployables (`ARCHITECTURE_TARGET.md`, stage 2).

### Invoices, tax and the customer side
`modules/billing/services/InvoiceService.php`: one invoice per ride and service, issued from `DriverEarnings::afterSave`
(halting drivers service, at the earnings row) and `RideEarnings::afterSave` (taxi / auto / bike, when approved/paid). Own series per
(module, financial year) in `service_invoice_counter`, profile in `service_billing_profile`; total includes GST, taxable =
total - GST, split in CGST/SGST halves. Idempotent (unique per module + source), failures are logged and never stop a ride.
Dashboard: `ServiceMapController` `billing-profile`, `invoices`, `invoice` (printable), `tax-report` (+ CSV).
Customer wallet: switched off (`CustomerWallet::enabled()`, env `CUSTOMER_WALLET=on` or `params['customerWallet']`); `/api/user-wallet/*`,
wallet payment on a ride and referral cashback check it; the model and old rows stay. Per service: `coupon.module` (`CouponQuery` filters by `ServiceScope::commercial()`; the customer
app module sets it to the halting drivers service, a bound taxi address to its service), referral rewards stamped per service and
read with `ServiceModules::setting($key, $module)` (`<key>_<module>` then the legacy `<key>`).
`php yii split/plan --service=N` lists what a service owns before it is cut out (read-only).

### The user-app gateway
`libs/platform/modules/customerapi`: `CustomerClient::profile/notify/authenticate` is the only way service code should learn about a
customer or reach one (`local` mode reads this database, `remote` mode calls the user-app system's `/api/customer/*`
with `ServiceSignature`). `PushService::notifyUser` routes by who the target is: a local partner (role Driver / taxi) is
notified here, anybody else through the gateway when it is remote. `AuthSettings::getAuthSession` accepts a signed
`X-User-Id` from the gateway before looking at an auth_code. Do not add new `user`-table reads for customers.

## 4. Taxi flow (short)

`TaxiUserController` creates a `ride_request` (status `NEW_REQUEST`); `OrderDispatch` (module `dispatch`) finds nearby
**online** `taxi` drivers with the ride's vehicle type, within `auto_dispatch_radius` km (default 10), using
`auto_dispatch_type` (1 one by one, 2 all, 3 nearest group; default 2), and creates `taxi_driver_request` offers; `TaxiOfferService` accepts atomically; `TaxiPresenceService`
tracks online/position. Unanswered rides expire (`expireIfUnanswered`, `php yii taxi/expire-stale`).

## 5. Configuration and secrets

Nothing secret is in code. Lookup order is environment variable, then `config/secrets.php` (git-ignored; template
in `DEPLOY.md`), then the dashboard where noted.

| Setting | Env var | secrets.php key | Dashboard |
|---|---|---|---|
| Database | `DB_DSN`, `DB_USER`, `DB_PASSWORD` | `db` | - |
| Cookie key | `COOKIE_VALIDATION_KEY` | `cookieValidationKey` | - |
| JWT secret | `JWT_SECRET` | (file `config/jwt-secret.php`) | - |
| SMS (2factor.in) | - | - | Web Settings → `sms_api_key` |
| Push | `PUSH_DRIVER`, `ONESIGNAL_APP_ID`, `ONESIGNAL_API_KEY` (`_USER`/`_DRIVER` per app) | `push` | Web Settings → `onesignal_app_id`, `onesignal_api_key` (**the dashboard value wins**) |
| Google Maps | `GOOGLE_MAPS_SERVER_KEY`, `GOOGLE_MAPS_BROWSER_KEY` | `googleMapsKey`, `googleMapsBrowserKey` | `map_key` |
| Media | `MEDIA_DRIVER`, `MEDIA_STORAGE_PATH` | - | - |
| Public base URL for stored media URLs | `PUBLIC_BASE_URL` | `publicBaseUrl` | - |
| Cron token | `CRON_TOKEN` | `cronToken` | - |
| OTP test numbers (dev only) | `OTP_TEST_NUMBERS` | `otpTestNumbers` | - |
| Mail / WhatsApp | `SENDGRID_API_KEY`, `INTERAKT_KEY` | - | - |

## 6. Console commands (`php yii ...`)

| Command | Purpose |
|---|---|
| `db/status`, `db/up --yes`, `db/down --yes` | versioned SQL scripts in `database/migrations` |
| `booking/expire-unanswered` | cancel bookings nobody took by pickup time |
| `taxi/expire-stale [--hours=24] [--apply]` | expire old taxi requests |
| `push/check` | verify the OneSignal key + App ID (reports accepted / rejected) |
| `geo/check` | test every Google key against Directions, Distance Matrix, Geocoding, Places |
| `media/stats`, `media/move-to-disk`, `media/purge-orphans` | upload storage |
| `audit/users [--fix-safe]` | read-only report on user data problems |

## 7. Conventions

- **Do not rename request/response fields or URLs** used by the shipped apps. Add, don't change.
- Use `Yii::$app->push->notifyUser($refId, $userId, $title, $body)` (module `messaging`); it also stores the in-app
  notification. Console code has no `push` component: use `new PushService()` (see `BookingExpiry`).
- Never put SQL/exception text in a response: `sendJsonResponse` scrubs, but log with `Yii::error(..., 'api')`.
- Logs: `runtime/logs/app.log`, categories `otp push auth geo api upload`.
- Money and status changes that can race must be conditional updates (`updateAll(..., ['status' => from])`).
- Soft delete users (`STATUS_DELETED`); never hard-delete users with bookings.
- New SQL changes go to `database/migrations/NNN_name.up.sql` + `.down.sql`, never edited after release.

## 8. Known legacy / debt (so you are not surprised)

- Large controllers (`DriverController` ~4,000 lines, `UserController`) hold mixed concerns and commented-out old
  code. Behaviour is covered by the scenario list in the `Test scenarios` section below; refactor with that net.
- Inconsistent response keys (`details` / `detail` / `error` carrying messages).
- `driver/cancel-ride` still writes a `driver_cancel_duplicate` copy of the booking per cancellation.
- Driver cancellation charge is written with `STATUS_PENDING` wallet status; settlement is a manual/admin concern.
- Distance Matrix API is not enabled on the Google project; `DrivingDistance` falls back to Directions.
- `_backups-*/` (next to `app/`, not inside it) holds the file backups, the old project copy and the stray dump/zip
  files that used to sit in the web folder. Delete it once nobody needs it.
- **Firebase is fully removed** from the backend (no code, no keys, no dashboard setting; script 006 deleted the
  stored legacy key). `fcm_notification` / `FcmNotification` / `list-fcm-notifications` are historical names of the
  in-app notification list and stay so the installed apps keep working. Android push needs the Firebase service
  account uploaded **inside OneSignal** only; keep that file out of this project (it lives in
  `_private-keep-out-of-git/`).

## 9. Test scenarios worth re-running after any change

Run against a copy of the database; use real `auth_code`s (issue with `JwtService::issue($id, $role)`).

1. Estimate: invalid car type / past time / negative hours / unknown city / outside boundary / bad coupon -> NOK.
2. Book twice -> second refused; book after cancel -> allowed.
3. Dispatch -> accept by A, accept by B refused; driver with ongoing ride refused.
4. Another customer cancels my booking -> refused; another driver cancels / starts my ride -> refused.
5. Start before OTP -> refused; wrong OTP -> refused; full flow -> completed, earnings 1 row, wallet debit.
6. Waiting booking past its pickup time -> auto-cancelled, customer message stored.
7. Block a driver and a customer in the dashboard -> `check`, `bookings`, `get-estimate`, `verify-otp` refuse; unblock
   -> work again.
8. All admin pages return 200 (sidebar crawl).

## 10. Adding things

- New endpoint: add an `actionXxx` to the controller of the module that owns the app; mapping already exists by
  prefix. Authenticate with `getAuthSession`, authorise by ownership (`user_id` / `driver_id`), validate state.
- New background job: a console controller in `commands/`, document it in section 6, add it to cron.
- New dashboard page: controller in `modules/admin/controllers` with `AccessControl`; add the menu entry in the
  layout partial; remember that admin pages may only change data through the existing services (`BookingAdmin`,
  `UsersController::changeStatus`) so the apps and notifications stay consistent.

## 11. Keeping the documentation current

| What | How it stays current |
|---|---|
| `REFERENCE.md` (every URL, status code, setting, console command, migration, environment variable) | **Generated.** `php yii docs/reference` rewrites it from the code and the database. |
| The `// [doc:start] ... // [doc:end]` marker lines around every action and service method | **Generated.** `php yii docs/annotate --apply` adds and refreshes them (name, public URL, first sentence of the docblock). Do not edit them by hand. |
| Docblocks above methods and the step comments inside the core ride flows | Written by people. Change the code, change its comment. A docblock's first sentence flows into the marker and `REFERENCE.md`. |
| `BUSINESS_GUIDE.md`, this guide, `DEPLOY.md` | Written by people (intent, rules, procedures). `docs/check` cannot know when they are wrong - review them when you change a rule. |

Commands:

```
php yii docs/reference            # regenerate REFERENCE.md
php yii docs/annotate --apply     # refresh the start/end markers
php yii docs/check                # exit 1 if either is out of date - put this in CI / before packaging a release
```

Rule for contributors: a pull request that changes an endpoint, a status, a setting or a rule must also update the
docblock, run the two generators, and edit the guide paragraph that describes it.

### Comment style used in the core flows
- A docblock says WHAT the action is for, the request fields, the checks, and the effect on bookings / money.
- Inside, a comment starting with a capitalised label marks a step: `WHO IS CALLING`, `OWNERSHIP`, `ONE RIDE AT A TIME`,
  `WALLET RULE`, `COMMISSION`, `PENALTY`, `REPLY` ... Search for those words to follow a business rule through the code.
- Numbered comments (`1.`, `2.` ...) mark the stages of a longer calculation (for example `actionCompleteRide`).

### Service entry points, dashboards and reporting

`web/<service>.php` (API only, `config/service-entry.php`), `web/<service>-dashboard.php` (admin panel on the service
database, one service only, `config/dashboard-entry.php`, `params['serviceDashboard']`), `app/modules/adminapi`
(`/api/admin-report/summary`, signed with `ADMIN_SECRET_<SERVICE>`), `ServiceReport::summary()` (the numbers),
`ServiceClient::summary()` (local or remote, used by `ServiceMapController::actionOverview`). Customers on a service
database are never read from a table: `CustomerClient::profileCached()` / `userJson()`, `getCustomer()` on the models,
and `AuthSettings::getAuthSession()` asks the gateway to check a customer's login code in remote mode.

### Services in their own folders

`services/haltingdriver/` holds the Halting drivers code (README.md there). Namespaces are unchanged
(`app\modules\haltingdriverapp`, `app\modules\admin\models\Booking`, ...); `config/services-classmap.php` loads
`services/*/backend/classmap.php`, so after adding a class to a service folder run `php yii service/pack --service=<service>`.
Admin screens of a service live in `services/<service>/partner-dashboard` and are mapped over `app/modules/admin/views`
by the view theme. `php yii service/list` shows what is where; `php yii service/release` builds a service's own release.

### Shared libraries (`libs/`)

`libs/partnercore` (models and admin screens of the partner tables every service uses) and `libs/taxicore` (the taxi family's
partner / rider API, dispatch, jobs and admin screens). Same rules as a service folder: namespaces unchanged,
`php yii service/pack --all` after adding or moving a class, dashboards' views mapped over `app/modules/admin/views`.

### Where a class lives now

`app/` is the shell (entry scripts, config, admin panel, website / user-app modules, console commands). Everything else is a
folder with an owner: `services/<service>`, `libs/platform`, `libs/partnercore`, `libs/taxicore`
(ARCHITECTURE_TARGET.md section 19). To find a class: the class map files (`classmap.php`) or `grep -r "class Name"`.
After adding, moving or renaming a class run `php yii service/pack --all`.

### The customer app (`customer/`)

`customer/backend` (modules `customerapp`, `customer` gateway), `customer/models`, `customer/dashboard`, `customer/database`,
`customer/release`. `php yii service/pack --customer` (or `--all`) regenerates its class map. Do not put service logic in
`UserAppActions`: a booking flow belongs to the service (`services/<service>/customer-backend`); `app/glue` is the only place
where both are combined, for the shared process.
