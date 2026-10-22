# Rubia Services - target architecture: every service on its own, one common user app

Status: **plan + stage 1 and per-service entry points done**. Read this before moving any code. Written for the owner and for developers.

## 1. The principle

> Everything is separate; only the **user (customer) app** is common.

| Plane | What it is | Common or separate |
|---|---|---|
| **Customer** (user app) | The one app customers use for every service, its gateway API and its customer dashboard | **Common** |
| **Admin** (control plane) | Central admin: services, cities, users overview, settings, finance overview | Common (the owner's cockpit) |
| **Each service** | Halting drivers service, Taxi (car), Auto, Bike - each with its **own backend, own database, own partner app, own partner dashboard, own wallet and own billing** | **Separate**, nothing shared at runtime except through APIs |

A change, an outage or a bad release in one service must not touch another.

## 2. Target folder layout

```
/
|-- admin/                          central admin dashboard and control plane
|-- customer/                       the common user app domain
|   |-- web/                        customer website
|   |-- mobile/                     the user app (Flutter)      -> today: the separate Customer app repository
|   |-- backend/                    customer gateway: login, profile, wallet of the user, and routing to the services
|   `-- dashboard/                  customer management dashboard
|-- services/
|   |-- driver/                     halting drivers service (hire a driver for N hours)
|   |-- taxi/                       car taxi
|   |-- auto/                       auto rickshaw
|   `-- bike/                       bike taxi
|       |-- backend/                the service's own backend (bookings/rides, dispatch, wallet, billing, maps, pricing)
|       |-- database/               the service's own schema + migrations
|       |-- partner-app/            the partner (driver) app            -> today: the separate Partner app repository
|       |-- partner-dashboard/      partner and franchise management portal of this service
|       |-- customer-app/           this service's screens inside the user app (module / package)
|       `-- customer-backend/       this service's API extension that the customer gateway calls
`-- libs/                           small shared libraries only (JWT, push, SMS, geo, files) - versioned, no business data
```

`libs/` is the only shared code. Shared **data** does not exist: a service never reads another service's tables.

## 3. Where today's code goes

Today everything is one Yii2 application (`app/`). The mapping below is what moves where (module folder -> target):

| Today (`app/modules/...`) | Target |
|---|---|
| `admin` (controllers, views, models) | `admin/` (platform parts) and each service's `partner-dashboard/` (its Driver / Taxi / Auto / Bike module screens, `service-map`, billing) |
| `customerapp` (`UserController`, `BookingController`, `UserWalletController`) | `customer/backend` (profile, login, user wallet, notifications) and `services/driver/customer-backend` (estimate, book, cancel, track) |
| `taxirider` | `services/taxi|auto|bike/customer-backend` (one deployment per service, same code with the module id) |
| `haltingdriverapp` | `services/driver/backend` |
| `taxidriver` | `services/taxi|auto|bike/backend` |
| `dispatch` (`BookingExpiry`, `BookingAdmin`, `OrderDispatch`) | the owning service's `backend` (driver: booking expiry/admin; taxi family: `OrderDispatch`) |
| `geo` (maps, pincodes, `ServiceModules`, `MapDesigner`) | each service's `backend` (maps are per service) + `libs/geo` (Google client) |
| `messaging`, `auth` (JWT/OTP), `payments`, `filestorage` | `libs/` (and the customer gateway owns login) |
| `publicweb`, `comingsoon` | `customer/web` |
| `managerapp` | retire (legacy, unrelated to rides) |
| `app/database/migrations` | split: platform, customer, and one folder per service (`services/<s>/database`) |

## 4. Who owns which table

Each service owns its data and gets its own database. `user` identity stays with the customer/platform side; a service
refers to a user only by id (and asks the gateway for name / phone when it needs them).

| Owner | Tables |
|---|---|
| **Admin / platform** | `web_setting`, `city`, `states`, `banner`, `banner_categorys`, `services`, `notification`, `page`, `media`, `auth_item`, `auth_item_child`, `auth_assignment`, `auth_rule`, `migration`, `db_migration_log`, `pincode_area` (geocode cache), `map_log` |
| **Identity (platform)** | `user` (all roles), `auth`, `auth_session` |
| **Customer (common)** | `user_wallet`, `wallet`, `favourite_location`, `user_emergency_contacts`, `emergency_contact`, `fcm_notification`, `coupon`, `coupons_applied`, `user_referral_earnings`, `transaction`, `payment_logs` |
| **Halting drivers service** | `booking`, `booking_logs`, `accepted_bookings`, `driver_requests`, `driver_cancel_duplicate`, `cancel_ride_charges`, `driver_earnings`, `driver_wallet` (module 1), `driver_referral_earnings`, `driver_subscription`, `driver_subscription_logs`, `plans`, `driver_booking_ratings`, `user_booking_ratings`, `city_rate_card`, `vehicle_types`, `vehicle_verification`, `driver_vehicle_types`, `service_map` / `service_map_pincode` (module 1), partner documents: `driver_aadhar_card`, `driver_pan_cards`, `driving_license`, `driver_bank_details`, `driver_emergency_contacts` |
| **Taxi / Auto / Bike** (each gets its own copy of this set) | `ride_request`, `taxi_driver_request`, `ride_statuses`, `ride_completion_log`, `reject_log`, `taxi_rating`, `ride_earnings`, `ride_commision`, `taxi_payout`, `driver_wallet` (its module), `driver_details`, `vehicals`, `pincode`, `service_map` (its module) |

Rows that carry a `module` column today (`ride_earnings`, `driver_earnings`, `taxi_payout`, `driver_wallet`, `service_map`,
`map_log`) are already tagged, which is exactly what makes the later split a copy by `module`.

## 5. How the user app reaches a service

```
user app --> customer/backend (gateway) --> services/<service>/customer-backend --> services/<service>/backend
                 login, profile, user wallet        estimate, book, cancel, track        dispatch, wallet, billing
```

- The user app logs in once (OTP -> `auth_code`). The gateway verifies the code and calls a service with the user id and
  a short-lived service token. A service never sees the OTP or the user's password hash.
- URLs the installed apps use today (`/api/user/*`, `/api/taxi-user/*`, ...) stay: the gateway keeps them and forwards.
- Partners log in to **their service's** backend directly (own partner app, own `auth_code`, own wallet).

## 6. Billing and wallet - per service

| Item | Halting drivers service | Taxi / Auto / Bike |
|---|---|---|
| Wallet | `driver_wallet` module 1 | `driver_wallet` module 2 / 3 / 4 |
| Minimum balance to accept | `driver_min_wallet_bal` (enforced) | `taxi_rider_mim_wallet_balance` / `taxi_min_wallet_balance` / `bike_rider_mim_wallet_balance`, switch `wallet_min_enforced_<module>` (off until turned on) |
| Commission and GST | per driver / default setting | per city and service (`ride_commision`), `cgst` / `sgst` |
| Earnings ledger | `driver_earnings` | `ride_earnings`, `taxi_payout` |
| Statement | dashboard -> Halting drivers service -> Billing | dashboard -> Taxi / Auto / Bike -> Billing |

Still to do for a full billing process of its own: separate **GST registration and invoice number series** per service,
service-wise **payout cycles and settlement reports**, and **tax reports**. These need your accounting rules (see section 8).

## 7. Staged plan

| Stage | What | Risk | State |
|---|---|---|---|
| 1 | Per-service maps, pricing, franchises, billing, wallets, service dashboards **inside the current app** (everything tagged by module) | low | **done** |
| 2a | Taxi, auto and bike each get their own API address and are hard-bound to their service (`/api/bike`, `/api/auto`, `/api/car`) | low | **done** |
| 2a2 | **Own entry point per service** (`web/haltingdriver.php`, `taxi.php`, `auto.php`, `bike.php`, config in `config/services.php` + `config/service-entry.php`): answers only that service's API addresses (others 404; no admin, no website), calls the customer-app API as that service, and uses `DB_DSN_<SERVICE>` as its own database when set (shared database otherwise) | low | **done** |
| 2 | Move code into the folder layout above. Class names are kept, so the apps see no change | medium | **started: Halting drivers** (section 16) |
| 3 | Each service on its **own database** (first: **Driver** - test database built and run end to end, see section 13): schema + migrations per service, copy its rows by `module`, switch its config; the customer gateway forwards. Done one service at a time (start with Bike, the smallest) | high | planned |
| 4 | Separate deploys, separate monitoring and credentials (own keys, own cron, own queue) | medium | planned |
| 5 | Retire the shared monolith; `libs/` become versioned packages | low | planned |

Every stage ends with the same acceptance run: the full ride flow of the service, the dashboard crawl, the scenario
suite (ownership, one-ride-at-a-time, cancel rules), and a billing reconciliation (totals before = totals after).

## 8. Decisions (answered)

1. **First service to move:** the **Halting drivers service**.
2. **Billing:** every service has its **own invoices, number series, billing process and tax reports** (done in the
   current app, see BUSINESS_GUIDE section 15).
3. **Nothing stays common** between services - driver wallets, coupons, maps, billing, franchise
   operations, referrals are all per service (done in the current app, tagged by `module`). Only the **user app** is
   shared by customers.
4. **Franchise:** one franchise account runs **one service in one city** (answered; as it works today). A person who runs two
   services gets two franchise accounts, each with its own login, wallet and statements.
5. **The customer is the same for all services:** one customer account (one id, one login, one profile) in the user-app
   system, used by Driver, Taxi, Auto and Bike alike. A service never creates or keeps its own customer; it stores only
   the customer id on its bookings, rides, wallets and invoices, and asks the gateway for the person each time.
6. **One super admin dashboard for all services** (`admin/` in the target layout): the owner and sub-admins see and
   manage every service from one place. Service managers and franchises keep their own service-bound dashboard.
7. **The project is a microservices architecture.** Independently deployable services (Driver, Taxi, Auto, Bike, the
   user app, the super admin) that own their data and talk to each other only through signed HTTP APIs:
   - no service reads another service's database or tables, and there is no shared table; shared knowledge is an id;
   - every cross-service need has an API (user app gateway today; admin reporting endpoints, section 12);
   - each service has its own entry point, configuration, secrets, database credentials, cron and release;
   - a service keeps working when another is down (a failed gateway call degrades, it never blocks a ride in progress);
   - the single codebase and shared database of today are a transition step (stages 1-2), not the end state.
9. **Car taxi, auto taxi and bike taxi are each a separate service** (own backend, database, dashboard, billing, wallets),
   exactly like the Halting drivers service. **Only the Halting drivers service has a partner app today.** Car taxi, auto taxi and bike taxi have
   no partner app yet: their service side (API, database, dashboard, billing) is built and cut out the same way, the partner
   app of each is a separate piece still to be made ("partner app not built yet" on the super admin page). Their partner
   API exists and was exercised directly (Bike), not through an app.
   Cut-over order: Driver, Bike, Auto, Taxi.
8. **Money flow and wallets:** the customer pays the partner; the partner pays the platform (commission, from the partner
   wallet). **Only the partner wallet exists**, one per service. The customer wallet is switched off (code and old
   balances kept); switch on with `CUSTOMER_WALLET=on`.

## 9. Cutting out the Halting drivers service (stage 3, first service)

`php yii split/plan --service=1` already lists what moves: 21 tables outright and the rows of the tagged tables
(`module = 1`), about 81,000 rows. The steps, each with its own rollback:

1. **Freeze the contract:** the Halting drivers service keeps `/api/driver/*` (partner app) and the driver parts of `/api/user/*`
   (customer app) exactly as they are.
2. **Own database and entry point:** a second Yii2 entry point for the service with its own DB config; it creates the
   service's schema from `services/driver/database` (the tables above plus a small replica of the identity rows it needs:
   its own partner accounts and the customer name/phone it shows).
3. **Copy and verify:** copy by table / `module`, then compare row counts and money totals (billing before = after).
4. **Dual run:** the old app keeps serving; the new entry point serves the same requests against the new database on a
   test address, with the scenario suite and a full ride.
5. **Cut over:** switch the service's traffic, keep the old tables read-only for a rollback window.
6. **Then** Bike, Auto, Taxi, each the same way.

Step 3 is a command: `php yii split/copy --service=1 --target=<new database>` lists what it would copy (dry run);
with `--apply` it creates the NEW database, copies the service's tables (whole tables, rows with `module` = service, and the
partner accounts of `driver_details`), and prints row counts and money sums of both sides per table. It only writes to the
target, refuses a target that already holds the service's tables, and never touches the source. Not copied: `user_wallet`
(customer wallet is off) and untagged rows (488 `driver_wallet`, 21 `driver_referral_earnings`, 8 `taxi_payout`).

Two things to settle before step 2: how the service finds the customer's identity (copy at booking time vs ask the
gateway), and the partner login (the partner accounts move with the service).

## 10. The user-app system (answered: ask its gateway each time)

The user app is a system of its own: its **own frontend, backend, authentication, notifications, database and dashboard**.
The services do not hold customer records, logins or notification channels. Instead, **every time a service needs
something about a customer it asks the customer-app API**:

| Service needs | Asks the gateway | Today (single app) | After the split |
|---|---|---|---|
| Who is calling | `authenticate(auth_code)`, or the gateway forwards a signed request naming the customer (`X-User-Id`) | answered from the same database | HTTPS call, nothing stored in the service |
| Customer name / phone (invoice, dashboards, partner screen) | `profile(id)` | `user` table | HTTPS call |
| Tell the customer something | `notify(id, title, body)` (the user app's own push and in-app list) | `PushService` | HTTPS call |

Calls are signed per service (`X-Service`, `X-Timestamp`, `X-Signature` = HMAC-SHA256 of
`service|timestamp|method|path|body` with `CUSTOMER_API_SECRET_<SERVICE>`; 5 minute window). A call without a valid signature
is refused (403) and, with no secret configured, the gateway is closed.
Implemented in `libs/platform/modules/customerapi` (`CustomerClient` client with `local` and `remote` modes, `ServiceSignature`,
`CustomerController` at `/api/customer/*`). Switch a service to the separate user app with
`CUSTOMER_API_MODE=remote`, `CUSTOMER_API_URL`, `CUSTOMER_API_SERVICE` and `CUSTOMER_API_SECRET_<SERVICE>`.

Admin booking and ride screens (list, invoice view, ride payloads) now take the customer from `CustomerClient::profileCached` via
`Booking::getCustomer` / `RideRequest::getCustomer` (name, phone, email, date of birth, gender; asked once per request).
The same lookup now serves the driver-cancel, taxi-rating, user-wallet, user-referral, emergency-contact and booking-rating
screens, the ride view / print page and the rider payment endpoint. Their customer filters are plain customer-id boxes
(no list of all customers is read). What remains on the `user` table is by design: partner accounts (drivers, taxi
drivers, franchise, staff and their subscriptions) which move with the service, the customer onboarding endpoints (the
user app's own) and the wallet / coupon endpoints (they stay in the service, keyed by customer id).

## 11. What belongs to each service

Each service (Driver, Taxi, Auto, Bike) is made of exactly these, and nothing in it is shared with another service:

| Part | What it is | Today (single codebase) |
|---|---|---|
| Partner app | the app its drivers / riders-partners install | the same installed app, calling the service's address |
| Backend | the service's own API and rules | its entry point (`web/<service>.php`) and API addresses (`config/services.php`) |
| Database | its bookings / rides, partners, partner wallets, coupons, referrals, maps, pricing, invoices | one shared database, every row tagged by `module`; own database via `DB_DSN_<SERVICE>` when cut out |
| Dashboard | admin screens for its own data: maps, franchise, billing, invoices, tax reports | `admin/service-map` per service; role-bound managers and franchises see only their service |

The customer is not part of this list: the customer app, backend, login, notifications, database and dashboard belong to
the user-app system and are the same for all services (section 10). The customer has **no wallet** (switched off: the customer pays the partner, the partner pays the platform; only the
partner wallet exists, `CUSTOMER_WALLET=on` brings it back). The customer's coupons, referrals and invoices **with a service**
are that service's data, keyed by the customer id.

## 12. The super admin dashboard

One dashboard (`admin/`) above the services: the owner and sub-admins switch between Driver, Taxi, Auto and Bike, see
all-services totals (rides, revenue, wallets held, invoices, tax) and open any service's maps, franchise, pricing and
billing. Today it is the admin panel on `index.php` and reads the one shared database.

When a service has its own database the dashboard must not read it directly. Plan: each service entry point exposes
**signed reporting and management endpoints** (same HMAC scheme as the customer-app API, in the other direction,
secret `ADMIN_SECRET_<SERVICE>`), and the dashboard asks each service for its numbers and forwards its actions. The
super admin has its own login; service staff logins stay in their service. Not built yet; it comes with the first
cut-over (Driver), because that is the first time the dashboard needs data it no longer holds.

## 13. Halting drivers service on its own database - what was built and tested

Built with `split/copy` + `split/complete` into the database `rubia_haltingdriver` (a copy; `rubia_website` untouched), then run
as four processes: the user app + super admin (`index.php`, shared database), the Halting drivers API (`web/haltingdriver.php`) and the
Halting drivers dashboard (`web/haltingdriver-dashboard.php`), both on `rubia_haltingdriver` with `DB_DSN_HALTINGDRIVER`.

| Part | Result |
|---|---|
| Copy | 36 tables, 81k rows; row counts and money sums equal on both sides; 5,892 partner accounts, their login sessions and device tokens; settings, cities, vehicle catalogue replicated; customers not copied |
| Customer through the gateway | login code of a customer is checked by the user app (`authenticate`); name and phone come from `profile`; the booking payload embeds the customer the same way |
| Ride | estimate, booking, dispatch, accept, arrived (OTP), start, complete: booking completed, earnings and the DRV invoice written to `rubia_haltingdriver` only |
| Service dashboard | `haltingdriver-dashboard.php`: login with a staff account of the service database, 72 admin pages open without errors; only the Halting drivers service shows (other services answer 404 and are hidden from the menu) |
| Super admin | `admin/service-map/overview`: all services side by side; the Driver numbers come from the Halting drivers service's signed `/api/admin-report/summary` (`ADMIN_URL_HALTINGDRIVER`, `ADMIN_SECRET_HALTINGDRIVER`), the others from this database |

Scenario run on the Halting drivers service database (16 checks, all passed): customer cancels own waiting booking; another
customer can neither cancel nor read it; a customer cannot hold two active bookings; a driver cancelling after acceptance
is charged the penalty and the booking goes back to waiting; a driver below the minimum wallet balance cannot accept;
one ride at a time per driver; a ride completes with earnings and the next invoice number; the shared database is not
touched by the service. Observation (same as on the shared database): any online driver of the city may accept a waiting
booking, an offer is not required. The wrong-OTP check in that run was not a real assertion and still needs one.

Identity (answered): every account has a **UUID** (`user.uuid`, migration 015; set by a database trigger on insert, backfilled
for all existing accounts, returned by the gateway profile). The UUID is the identity that is the same in every service and
in the user app. The integer id stays the local key; new accounts of a service get ids from its own range (Driver from
100,000,000) so no id is ever reused across databases. Untagged rows of deleted accounts (483 `driver_wallet`, 19 `driver_referral_earnings`, 2 `taxi_payout`) were archived to
`_backups-20261014/orphan-rows-archive-20261015.sql` and dropped (2026-10-15, owner's decision); the remaining 13 untagged rows (owner still existed: taxi driver 130,
customer 1062, admin 10, referral / payout rows) were archived to `untagged-rows-archive-20261015.sql` and dropped too. No
untagged money rows are left. Staff accounts are copied, not managed per service; the other three services are still on the shared database; the 488 untagged `driver_wallet` rows are not copied.

## 14. Bike, Taxi and Auto on their own databases

`split/copy --service=2|3|4` takes a taxi-family service's rows out of the shared ride tables: its vehicle types
(`vehicals`), its rides and everything hanging on them (`taxi_driver_request`, `ride_statuses`, `ride_completion_log`,
`reject_log`, `taxi_rating`), its commission and pincode rows, its partners (users with role `taxi` whose `driver_details`
vehicle belongs to the service; legacy vehicle ids 2 and 3 count as the module) and the rows tagged with its module.
Built and reconciled: `rubia_biketaxi` (61 rides, 11 partners), `rubia_autotaxi` (2 partners) and `rubia_cartaxi` (5 partners; its
partner app is not built yet, so the partner side cannot be exercised until it exists).

Bike on its own database, end to end (10 checks): customer through the user app gateway, ride request, accept, wrong
and right OTP, completion, cash payment received, earnings split and the BIK invoice, with the shared database untouched.
The Bike dashboard (`biketaxi-dashboard.php`) opened all 25 of its pages without errors; the super admin "All services" page
shows Driver and Bike from their own databases. Not run: ride tests of Taxi and Auto (same code; only their copies were
built and reconciled), and distance pricing of a ride (the Bike run set the final fare by hand because the route
between its test coordinates returned no result).

What the Bike run found and fixed: a customer who only exists in the user app had no identity in the service, so
`create_user_id` was null and rides failed. `ApiUser` (`app/components/ApiUser.php`, the `user` component) now resolves an
unknown `auth_code` through the gateway into a light identity for the request; the rider payloads take the customer's
name, phone and picture from the gateway.

## 15. Names (decided)

There is no generic "taxi driver" or "taxi rider". The services and the code that serves them are named:

| Service (module no.) | Title | Key | API addresses | Partner side | Customer side | Entry / dashboard | Database |
|---|---|---|---|---|---|---|---|
| 1 | Halting drivers | `haltingdriver` | `/api/driver/*` (kept: installed app), `/api/user/*`, `/api/booking/*` | `modules/haltingdriverapp` (`haltingdriver-app`) | `modules/customerapp` | `web/haltingdriver.php`, `web/haltingdriver-dashboard.php` | `rubia_haltingdriver` |
| 2 | Car taxi | `cartaxi` | `/api/car/*`, `/api/car-user/*` | `modules/cartaxidriver` | `modules/cartaxirider` | `web/cartaxi.php`, `web/cartaxi-dashboard.php` | `rubia_cartaxi` |
| 3 | Auto taxi | `autotaxi` | `/api/auto/*`, `/api/auto-user/*` | `modules/autotaxidriver` | `modules/autotaxirider` | `web/autotaxi.php`, `web/autotaxi-dashboard.php` | `rubia_autotaxi` |
| 4 | Bike taxi | `biketaxi` | `/api/bike/*`, `/api/bike-user/*` | `modules/biketaxidriver` | `modules/biketaxirider` | `web/biketaxi.php`, `web/biketaxi-dashboard.php` | `rubia_biketaxi` |

The three taxi services share their implementation in `modules/taxicore/partner` and `modules/taxicore/rider`; each service's
own module only says which service it is. Environment variables carry the key: `CUSTOMER_API_SECRET_HALTINGDRIVER`,
`DB_DSN_BIKETAXI`, ... Unchanged on purpose: the public URL `/api/driver/*` (the installed Halting drivers app), the stored
role values (`Driver`, `taxi`), table and column names, and invoice number prefixes already issued.
The generic `/api/taxi/*`, `/api/skipper/*` and `/api/taxi-user/*` addresses were retired (404). Customer-side module names
(`...rider`) were chosen by symmetry with the partner side; they are one line each to rename.

## 16. Halting drivers in its own folder, with its own release (Stage 2, first service)

What is now physically under `services/haltingdriver/` (see its README.md): the partner app API (`modules/haltingdriverapp`),
the data models of its own tables, the booking expiry / booking admin services, the admin screens (controllers and views) of
its data, the structure of its database and its release manifest. 88 items moved out of `app/`, 75 classes.

How the rest of the code still finds it: classes keep their namespaces. `services/haltingdriver/backend/classmap.php`
(generated by `php yii service/pack --service=haltingdriver`) tells the autoloader where they are; `config/services-classmap.php`
loads it and the view theme maps the dashboard views. Nothing that uses these classes changed.

Own release: `php yii service/release --service=haltingdriver` builds `services/haltingdriver/release/dist/haltingdriver-<date>.zip`
from `release/manifest.json` (the kernel in `app/`, the service folder, vendor; without secrets, runtime, uploads and
the code of the taxi services). The service entry of that release registers only the modules it serves.

Verified on an unpacked release (no secrets, no taxi code; database, gateway and keys through environment variables):
the 16 scenario checks of the Halting drivers service pass, `/api/bike` answers 404, the shared-database app and the
service dashboard still open the moved screens (34 pages, no errors), and Bike on its own database still passes its 10 checks.

Moved since (section 17): the customer-side booking endpoints (`customer-backend`).

Not moved yet (and why):
- Tables that several services use: their models moved to `libs/partnercore` (section 18).
- The kernel still refers to some moved classes (billing, dispatch); the class map makes that work, a later step turns those
  into events or interfaces.
- Other services' admin screens: left the release with section 17 (taxi screens are in `libs/taxicore`, Halting drivers
  screens in its own folder).
- Size: full release about 122 MB; the `api` profile is 56 MB (section 18).

## 17. UserController split; the taxi services in their own folders

**Customer app controller.** `modules/customerapp/controllers/UserController` (2,967 lines) held two systems' endpoints.
Now:
- `app/modules/customerapp/controllers/UserController` keeps the **user app's** actions: `index`, `logout`, `check`,
  `send-otp`, `resend-otp`, `verify-otp`, `update-profile`, `update-profile-pic`, `update-lat-lng`, the emergency-contact
  actions, `apply-referral`, `services`, the favourite-location actions, `list-fcm-notifications`, `clear-notifications`,
  `banner-with-category`.
- `services/haltingdriver/customer-backend/controllers/UserController` (module `haltingdriver-customer`) holds the
  **service's** actions: `get-estimate`, `check-boundary`, `current-city`, `coupons-list`, `vehicle-types`, `apply-coupon`,
  `remove-coupon`, `assign-booking-to-driver` (dispatch cron), `cancel-ride`, `cancellation-charges`, `ride-info`,
  `add-review`, `rating-and-reviews`, `view-booking-details`, `pay-now`, `ride-request-active|completed|cancelled`,
  `my-referral-earings`. `BookingController`, `WalletController` and `UserWalletController` moved whole.
- While both run in one process the user app's `UserController` extends the service's, so `/api/user/*` serves both
  exactly as before; the Halting drivers entry answers `/api/user/*` with its own module only (`config/services.php`
  `routes`), so the user app's actions answer 404 there. The service no longer reads a customer row for a login check
  (the customer is verified by the gateway); `current-city` only returns the city when the customer row is not local.

**Taxi family.** `libs/taxicore` holds the code the car, auto and bike taxi services share: the partner API
(`backend/partner`), the rider API (`backend/rider`) and the taxi admin screens (`dashboard`: ride requests, offers,
earnings, payouts, ratings, commission, pincodes, vehicle types). Each of `services/biketaxi`, `services/autotaxi`,
`services/cartaxi` holds its two thin modules (`<svc>driver`, `<svc>rider`), its README, database structure and release
manifest. The data models of the shared tables stay in the kernel (several services use them).
`php yii service/pack --all` regenerates every class map; `php yii service/release --service=<svc>` builds a release
(each about 120 MB; Halting drivers 19,227 files, each taxi service 19,083).

Verified on unpacked releases: Halting drivers (16 scenario checks, `/api/user/services` is 404), Bike taxi (10 ride
checks; `/api/driver` is 404); the shared-database app still opens the taxi and booking screens; the Bike dashboard 21
pages. Auto and Car taxi releases were built, not run (no partner login helper for them yet).

Still shared (the kernel): the models of tables used by more than one service, `driver_details`, `driver_wallet`, auth,
geo, billing, messaging, payments, the customer gateway, `config/`, and the admin shell with its menu.

## 18. Shared partner code, command moves, release profiles

| Item | Where it stands |
|---|---|
| Models of tables several services use (`driver_details`, `driver_wallet`, `driver_referral_earnings`, bank, Aadhaar, PAN, licence, emergency contacts) and their admin screens | `libs/partnercore` (40 classes, `dashboard`). Every service release includes it. |
| Taxi dispatch (`OrderDispatch`) and the `taxi/expire-stale` job | `libs/taxicore/dispatch`, `libs/taxicore/commands` |
| Halting drivers jobs (`booking/expire-unanswered`) | `services/haltingdriver/backend/commands` (commands run from their new place; `php yii help` lists only commands left in `app/commands`) |
| Models of the taxi tables (`ride_request`, `ride_earnings`, ...) | moved to `libs/taxicore/models` in section 19, after `DriverDetails::asJson` and `DashboardStats` were guarded |
| `User` model relations and the cross-service reporting (`InvoiceService`, `ServiceReport`, `DashboardStats`, `BillingController`) | `libs/platform` and the admin shell (section 19): cross-service by nature; they reach service classes through the class map |

**Release profiles.** `php yii service/release --service=<svc>` builds the full release (API and dashboard, about 122 MB).
`--profile=api` builds the API-only release: no dashboard views, no dashboard entry scripts, no `app/web/index.php`, no
front-end asset packages (adminlte, bootstrap, highcharts, font packs...) and no dev packages: about 56 MB for Halting
drivers (6,858 files). Verified unpacked: the 16 scenario checks pass and unknown addresses answer 404. A service's API
process answers errors with the framework's plain error page, not the website's.

## 19. Every table has an owner; the kernel is a library (`libs/platform`)

**Who owns what now**

| Owner | Where | Tables / models |
|---|---|---|
| Halting drivers | `services/haltingdriver` | booking, booking logs, driver requests, cancellations and charges, subscriptions and plans, ratings, city rate card, vehicle types / verification, driver earnings |
| Car, auto, bike taxi (shared by the three) | `libs/taxicore` (`models`, `dashboard`, `backend`, `dispatch`, `commands`) | ride request, offers, statuses, completion log, reject log, taxi rating, commission, earnings, payouts, pincode, vehicle catalogue (`vehicals`) |
| Partner data every service uses | `libs/partnercore` | driver details, wallet, referral earnings, bank / Aadhaar / PAN / licence, emergency contacts |
| Platform (user app, accounts, settings) | `libs/platform` | auth, auth session, users, cities, states, services, web settings, banners, pages, coupons and applied coupons, notifications, favourite locations, emergency contacts, customer wallet (off), referral earnings, payment logs |

**`libs/platform`** also holds the kernel modules: `api` (JSON + login code base), `auth` (OTP, JWT), `geo` (service
registry, scope, map designer, distance), `billing` (invoices, tax, reports), `messaging` (push, SMS), `payments`,
`gateway` (the customer-app gateway), `adminapi` (signed reporting), `dispatch`, `filestorage`, and the application models
(`appmodels`: `User`, ...). Namespaces are unchanged; the class maps (`php yii service/pack --all`) make them load.

`app/` is now the shell: entry scripts (`web/`), `config/`, the admin panel (controllers, views, menu, widgets), the user
app's customer module, website modules, console commands and `vendor/`. Every release contains `app/` + `libs/platform` +
`libs/partnercore` + its own folder (+ `libs/taxicore` for the taxi services).

**What had to change to separate the taxi models:** `DriverDetails::asJson` (ride and rating counts) and `DashboardStats`
(ride counters, waiting rides) answer 0 when the taxi tables' classes are not part of the release; the menu uses the service
numbers (`ServiceModules`) instead of `Vehicals` constants; the Halting drivers `ride-request-*` customer actions answer
"No history found" without the taxi models (taxi history is the taxi services' rider API, `my-rides`).

Verified after the move: Halting drivers 16 of 16 scenario checks, Bike taxi 10 of 10 ride checks, the super admin app
71 pages, the Halting drivers dashboard 29 pages, the Bike dashboard 21 pages, no errors.

## 20. The customer app as a service of its own (`customer/`)

The customer (user) app has its own backend, database, dashboard, authentication and notifications. Services never read a
customer record; they ask it (decision 7 / section 10).

| Part | Where | Notes |
|---|---|---|
| Backend | `customer/backend/modules/customerapp` (`/api/user/*`: OTP login, JWT, logout, profile, emergency contacts, favourite locations, referral code, notifications, banners, service list), `customer/backend/modules/customerapi` (the gateway: `profile`, `notify`, `authenticate`, signed) | entry `app/web/customer.php` |
| Database | `rubia_customer` (customers, their auth and sessions, device tokens, notifications, address book, customer wallet (off), banners, pages, service catalogue, cities, settings) | `php yii split/customer --target=rubia_customer --apply`, then `split/complete --target=rubia_customer --service=0`; schema in `customer/database/schema.sql` |
| Dashboard | `customer/dashboard` (banners, categories, pages, services, favourite locations, emergency contacts, customer wallet, notifications) + customers list | entry `app/web/customer-dashboard.php`; its menu shows only the customer app |
| Authentication | OTP + JWT of the customer app; the gateway's `authenticate` is how services learn who a login code belongs to | partners keep logging in to their own service |
| Notifications | the customer's push and in-app list are delivered by this service (`/api/customer/notify`, called by the services) | its own push keys |
| Reporting | `/api/admin-report/summary` (customers, logins, notifications, address book: counts only) | super admin page: "Customer app" card |

**How the code was cut.** `UserController` of the customer app is now the user app's actions only (`UserAppActions` trait);
the Halting drivers booking endpoints are in `services/haltingdriver/customer-backend`. For the shared process (`index.php`)
the glue controllers in `app/glue` answer `/api/user/*`, `/api/booking/*`, `/api/wallet/*`, `/api/user-wallet/*` with both,
exactly as before; no service release and no customer release contains them.

**Verified, all separate:** the customer service on `rubia_customer` (884 customers, 846 sessions), Halting drivers on
`rubia_haltingdriver` using it as its gateway: 16 of 16 scenario checks, with no customer row in the Halting drivers database;
the customer API release (no secrets, no service code, 56 MB) answers the customer endpoints and 404 for `/api/driver`;
the customer dashboard 6 pages; the super admin page shows the customer app, Halting drivers and Bike taxi from their own
databases.

**Open:** the customer web / mobile app source (`customer/web`, `customer/mobile`) is separate projects, only placeholders
here; the customer's own wallet and referral rewards stay switched off; the shared `web_setting` keys of the customer app
(push, SMS) must be set in the customer database (dashboard) or environment.

## 21. Ride tests of Auto taxi and Car taxi, each on its own database, customers from the customer app service

`scratchpad`-style test script `taxi_e2e.py <service> <api> <vehicle id> <module> <port> <partner id> <pincode>` (kept outside the
repository; the steps are below). Services: `rubia_autotaxi` (port 8131), `rubia_cartaxi` (port 8141), customers and their
login codes from `rubia_customer` (the customer app service, port 8120). A partner's login code is issued into the service's own
database (`jwt_issue.php <id> taxi` with `DB_DSN` set), a customer's into the customer app's.

| Check (each service) | Auto taxi | Car taxi |
|---|---|---|
| Customer has no row in the service database | pass | pass |
| Customer is known through the customer app; partner known to the service | pass | pass |
| Ride requested; belongs to the service's vehicle type (Auto 5, Taxi 6) | pass | pass |
| Another customer cannot cancel the ride | pass | pass |
| Partner accepts; wrong OTP refused; right OTP starts the ride | pass | pass |
| Ride completed; cash payment received closes it | pass | pass |
| Earnings split written with the service's module (3 / 2); commission 10% + tax = 17.40 on 120 | pass | pass |
| Invoice in the service's own series (`AUT/...`, `TAX/...`, module 3 / 2) | pass | pass |
| No customer row ever created in the service database; shared database untouched | pass | pass |

15 of 15 checks each. The distance price is not exercised (the test sets the final fare, as for Bike).

**Found and fixed by the run.** `MapDesigner::cityOfPincode` (and the pincode lists of the map screens) matched
`pincode.vehical_id` against the module's vehicle type ids (Auto 5, Taxi 6). Pincodes written before the vehicle catalogue was
renumbered hold the module number (Auto 3, Car 2), so a ride from such a pincode got no city, no commission (0%) and a wrong
earnings split. `ServiceModules::pincodeVehicleIds($module)` accepts both. After the fix: city 29 resolved, commission applied.

**Not covered:** Auto and Car taxi have no partner app, so the partner side was exercised through the partner API only; the
ride-request distance price (Google Directions) and push delivery to the partner device are not part of this run.

## 22. Bike taxi against the customer app service; the taxi releases rebuilt

- **Bike taxi** (own database `rubia_biketaxi`, customers from `rubia_customer`): the same 15 checks as Auto and Car taxi pass
  (15 of 15): customer without a row in the service database, ride, ownership, OTP, completion, earnings with module 4 and
  commission, invoice `BIK/2026-27/...`.
- **Releases rebuilt** after the moves of sections 17 to 21: `biketaxi`, `autotaxi`, `cartaxi` (full, 18,985 files, 122.3 MB each).
  Auto and Car taxi were unpacked into clean folders (no secrets, no Halting drivers code, no shared-process glue, only
  `libs/platform`, `libs/partnercore`, `libs/taxicore` and their own folder) and ran the 15 checks again from the release:
  15 of 15 each; the Halting drivers address and the Bike address answer 404 on them.
- **`php yii service/prune --service=<svc> [--keep=1]`** deletes older zips of that service's `release/dist` and unfinished
  `*.zip.<hash>` files; it only touches that folder. Run it after a release build.

## 23. One customer, one wallet (owned by the customer app)

**Decision.** A customer has **one** wallet, whichever service the money is spent in. It belongs to the customer app service
(`rubia_customer.user_wallet`); no service keeps a balance, a copy or its own wallet table rows for a customer. (Partner wallets
stay per service: a partner works in one service.) This replaces the earlier per-service customer balances.

| Piece | Where |
|---|---|
| The ledger (balance, debit, credit; refuses an overdraft; the same `ref` twice charges once) | `customer/backend/modules/customerapi/services/WalletLedger.php` |
| The calls a service makes | `CustomerClient::walletBalance / walletDebit / walletCredit` (signed): `/api/customer/wallet-balance`, `wallet-debit`, `wallet-credit` |
| The customer app's wallet API (recharge, balance, history) | `customer/backend/modules/customerapp/controllers/UserWalletController` (`/api/user-wallet/*`, moved out of the Halting drivers customer-backend) |
| Paying a taxi ride from the wallet | `libs/taxicore` partner API asks the balance and debits through the gateway on completion; rider API checks the balance when the wallet is chosen |
| Entry `module` column | only records which service an entry happened in (for that service's statement); a recharge made in the customer app has none |
| The switch | `CUSTOMER_WALLET=on` **on the customer app service only**; default off (nothing changes for the apps until it is turned on) |

**Verified (switch on in the customer app only):** recharge 500 in the customer app (entry belongs to no service); an Auto taxi
ride paid from the wallet (balance 500 to 380, debit tagged service 3); a Car taxi ride paid from the same wallet (380 to 260,
tagged service 2); a ride the wallet cannot cover is refused and nothing is charged; the Auto and Car taxi databases hold no
wallet rows; both rides are closed and invoiced in their own series. 11 of 11 checks.

**Not done / know:** the customer-app-side referral cashback (`refer-user`, `apply-referral`) still credits through its own
code path of the customer app; the taxi rider API's copy of `refer-user` belongs to the customer app and is not used on a
service database. Card / UPI recharge of the wallet is the customer app's payment-gateway work (the recharge endpoint credits
the amount it is given, as before). The Halting drivers `/api/wallet/*` is its partner-side wallet and was not touched.

## 24. Names of the customer app service (final)

The word "customer" and the word "gateway" are gone from the code. One name per thing:

| Thing | Name |
|---|---|
| The service (folder, key, entries, release) | `customer/` , key `customer` , `app/web/customer.php`, `app/web/customer-dashboard.php`, `php yii service/release --service=customer` |
| Database | `rubia_customer` |
| The customer's mobile app API (login, profile, address book, wallet) | module `customerapp` , `/api/user/*`, `/api/user-wallet/*` |
| What the other services call | module `customerapi` (id `customer-api`), **`/api/customer/*`** (`profile`, `notify`, `authenticate`, `wallet-balance`, `wallet-debit`, `wallet-credit`), signed calls only; controller `CustomerController` |
| The client a service uses | `CustomerClient` (`app\modules\customerapi\services`), signing in `ServiceSignature` |
| Environment | `CUSTOMER_API_MODE`, `CUSTOMER_API_URL`, `CUSTOMER_API_SERVICE`, `CUSTOMER_API_SECRET_<SERVICE>`; `DB_DSN_CUSTOMER`, `DB_USER_CUSTOMER`, `DB_PASSWORD_CUSTOMER`; `ADMIN_URL_CUSTOMER`, `ADMIN_SECRET_CUSTOMER`, `ADMIN_DASHBOARD_URL_CUSTOMER`; `CUSTOMER_WALLET` |
| Config | `params['customerApi']`, `params['customerDashboard']`, `secrets['customerApiSecrets']`, alias `@customer` |
| Console | `php yii split/customer --target=rubia_customer --apply`, `split/complete --service=0`, `service/pack --customer` |

Unchanged on purpose: the Halting drivers `customer-backend` folder and module `haltingdriver-customer` (the layout you gave:
`services/<service>/customer-backend`), and the public URLs the mobile apps use (`/api/user/*`, `/api/driver/*`, `/api/bike/*`, ...).

**Anything configured with the old names must change together**: the service entries and the customer app service share the
secret by name (`CUSTOMER_API_SECRET_<SERVICE>`), and the address `/api/customer/*` replaces `/api/gateway/*`. Releases built
before this rename (names `customer-*`) must not be mixed with new ones.

Verified after the rename: Halting drivers scenario 16 of 16 and Auto taxi ride 15 of 15 against the customer app service; the one
wallet 11 of 11; signed `/api/customer/profile` answers, unsigned 403, the old `/api/gateway/profile` 404. Also fixed on the
way: a leftover check in the client that looked for the wallet ledger by its old class name (it made the local wallet calls answer
"not available").

## 25. Halting drivers, the business flow end to end (every system separate)

Run with the customer app service (`rubia_customer`) and the Halting drivers service (`rubia_haltingdriver`) as separate
processes and databases; the customer is known to the booking service only through `/api/customer/*`. 46 checks, all passed.

| Step | What was checked |
|---|---|
| Setup | no customer row in the Halting drivers database; no driver row in the customer app database |
| Customer | service list from the customer app; vehicle types, city and boundary from Halting drivers |
| Quote and booking | quote with GST and fees; quoting creates nothing; the saved estimate equals the quote; a second active booking is refused |
| Dispatch | the job offers the booking; the offer is recorded; the driver's list shows it with the expected earning and the customer's name and phone (from the customer app) |
| The ride | accept; the customer sees the driver; go for pickup; a wrong OTP is refused, the right one accepted; start; another driver cannot complete it; complete with the real route distance |
| Money | one earnings row; platform share = commission + GST + fees; platform share + driver share = billed; the cash ride debits commission + GST from the driver wallet; invoice `DRV/2026-27/000001` with taxable + CGST + SGST = total, equal to the billed amount, tax equal to the booking GST; no customer wallet entry |
| After | customer rates driver; driver rates customer (customer average updated in the customer app database); ratings read back; booking history; the customer notified |
| Other paths | customer cancels a waiting booking: free; customer cancels after acceptance: charge recorded as pending, driver compensated, charge added to the customer's next quote; driver cancels after accepting: pending penalty on the driver wallet, booking waits for another driver |
| Reporting | the service report (rides, billed, platform share, invoices) equals the earnings and invoice tables |
| Dashboard (service) | booking view, bookings, earnings, wallet, invoices, billing, tax report, ratings, cancellations: all open, no errors |

**Found and fixed by this run**
1. Driver rating a customer failed (`average_rating on null`): the service tried to update the customer's own row. The service now
   reports the average to the customer app (`CustomerClient::reportRating`, `/api/customer/rating`); it never writes a customer row.
2. The booking view page of the dashboard failed (HTTP 500): the ratings block read the customer from a local table. It asks
   the customer app now (same for the driver-cancel view).

**What to know (business)**
- The customer is billed on the real distance and time at completion, not on the quote: a ride of 17 km completed in seconds was
  billed 420 against a quote of 819 (the quote includes a 2-hour usage block). Real rides take their real time.
- The customer's cancellation charge is not taken from a wallet (it is off): it is recorded as pending and added to the next quote.
- A driver's cancellation penalty is a *pending* debit (setting `driver_cancellation_charge`, 500 here); it counts against the
  wallet immediately.
- Customers' cancellation charges (`cancel_ride_charges`) now have a dashboard screen (menu: Bookings, Cancellation Charges;
  `/admin/cancel-ride-charges`): totals of waiting / collected / rejected charges with the company share, filters (status, who
  cancelled, booking, customer, driver, period), one charge in detail, the customer's name from the customer app, and a CSV
  download for the accounts. Read-only; admin, sub-admin and the service's own manager / franchise.

## 26. Night charge per night (Halting drivers pricing)

**Rule.** The night charge of the city rate card is charged **once for every night the ride touches**. A night is the window
22:00 to 06:00 (from 22:00 of a day to 06:00 of the next). Before, it was charged once at most, whatever the length of the ride, and
a multi-day ride that happened to start and end in daylight paid nothing.

| Ride | Nights |
|---|---|
| 10:00, 2 hours | 0 |
| 10:00, 12 hours (ends 22:00) | 1 |
| 23:00, 3 hours (same night) | 1 |
| 05:00, 1 hour (the night just ending) | 1 |
| 06:00, 1 hour | 0 |
| 10:00 for 24 / 48 / 72 hours | 1 / 2 / 3 |
| 23:00 for 49 hours | 3 |
| 10:00 for 7 days (168 hours, the longest accepted) | 7 |

Applies to all four price calculations (inner city and outstation, one way and round trip), to the quote and to the final fare
at completion (which uses the real start and end time). Code: `Booking::nightsTouched()` and the four `calculate...Estimate`
methods in `services/haltingdriver/backend/models/base/Booking.php`.

**Response of the quote** (additive, existing fields unchanged): `nightCount` (nights charged), `nightChargePerNight` (the rate
card value); `nightCharge` is now `nightCount x nightChargePerNight` and `isNightChargeApplicable` is `nightCount > 0`.

**Checked:** the 12 cases above; real quotes for the four ride types at 10:00 and 23:00 for 2, 3, 24, 48, 49 and 72 hours
(inner city one way 10:00: 609 / 4977 / 9723 / 14469 for 2 / 24 / 48 / 72 hours with 0 / 1 / 2 / 3 nights of 200); the business
flow still passes 46 of 46. An outstation one-way card whose night charge is 0 shows the nights but charges nothing.
