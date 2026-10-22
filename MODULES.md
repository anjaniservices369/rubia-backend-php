# Backend modules - one module per service

Every service lives in its own module under `app/modules/<name>/` with its own namespace, its own controllers
and its own services. A module can be read, changed and tested without knowing the others.
Public URLs did not change: the installed mobile apps keep calling the same paths (`config/api-routes.php`).

## API modules (they serve URLs)

| Module id | Folder | Public URLs | Used by | Contains |
|---|---|---|---|---|
| `cartaxidriver` / `autotaxidriver` / `biketaxidriver` | `modules/cartaxidriver` ... (thin; shared code in `modules/taxicore/partner`) | `/api/car/*`, `/api/auto/*`, `/api/bike/*` | Partner app of that service (not built yet) | `TaxiController`, `TaxiOfferService` (offers, accept, reject), `TaxiPresenceService` (position, online) |
| `cartaxirider` / `autotaxirider` / `biketaxirider` | `modules/cartaxirider` ... (thin; shared code in `modules/taxicore/rider`) | `/api/car-user/*`, `/api/auto-user/*`, `/api/bike-user/*` | Customer side of that service | `TaxiUserController` (book, ride status, payment, rating) |
| `haltingdriver-app` | `services/haltingdriver/backend/modules/haltingdriverapp` | `/api/driver/*` | Halting drivers partner app (the only partner app that exists) | `DriverController` (onboarding, documents, bookings, earnings, subscription) |
| `customer-app` | `modules/customerapp` | `/api/user/*`, `/api/booking/*`, `/api/wallet/*`, `/api/user-wallet/*` | Customer booking app | `UserController`, `BookingController`, `WalletController`, `UserWalletController` |
| `manager-app` | `modules/managerapp` | `/api/manager/*` | none known (legacy, temples/idols from an earlier product) | `ManagerController` |
| `file-storage` | `modules/filestorage` | `/api/media/upload`, `/api/media/view` | all apps, admin | `MediaController`, `MediaStore` (images/documents, disk or DB) |
| `public-web` | `modules/publicweb` | `/api/web/*` | website | `WebController` |
| `api` | `modules/api` | `/api/default` | - | the kernel: `ApiModule` (JSON + auth_code session) and `BKController` that all API modules build on |

## Service modules (no URLs, they provide components to the others)

| Module id | Folder | Component / entry point | What it does |
|---|---|---|---|
| `messaging` | `modules/messaging` | `Yii::$app->push`, `->sms`, `->whatsapp` | Push (OneSignal/Expo via `PushNotifier`), SMS and OTP delivery (2factor.in), WhatsApp (Interakt) |
| `auth` | `modules/auth` | `Yii::$app->otp`, `JwtService`, `AuthSettings` | OTP request handling, JWT `auth_code` issue/verify, `auth_code` -> user lookup |
| `dispatch` | `modules/dispatch` | `Yii::$app->orderDispatch` | Finds nearby taxi drivers, creates offers, expires unanswered rides |
| `payments` | `modules/payments` | `new CashFree()`, `new OpenMoney()` | Payment gateway clients |
| `geo` | `modules/geo` | `new DrivingDistance()` | Driving distance/time for fares |

Also unchanged: `modules/admin` (back office), `modules/support`, `modules/comingsoon`, `modules/media` (website).

## Rules that keep the modules separate

1. A module owns its controllers and services; other modules call it through its component or class, never
   through its controllers.
2. Service modules never depend on API modules. API modules depend on service modules.
3. Shared ActiveRecord models stay in `modules/admin/models` (one database, one schema).
4. New endpoints go into the module that owns the app, and the URL prefix is mapped once in
   `config/api-routes.php`.
5. Module-private components are reached through the module: inside a `taxi-driver` controller use
   `$this->module->offers` and `$this->module->presence`.

## Adding a service

1. `modules/<name>/Module.php` (API module: extend `app\modules\api\ApiModule`; service module: implement
   `yii\base\BootstrapInterface` and register components in `bootstrap()`).
2. Register it in `config/web.php` (`modules`, and `bootstrap` if it registers components).
3. For an API module add its URL prefix to `config/api-routes.php`.

## Compatibility kept on purpose

- The generic `/api/taxi/*`, `/api/skipper/*` and `/api/taxi-user/*` addresses were retired when the taxi family was
  split into car, auto and bike taxi (there is no generic taxi driver or rider). The old action names `skipper-status`,
  `update-skipper-city` still work under `/api/car|auto|bike/*`.
- Old PHP constant and method names (`ROLE_SKIPPER`, `STATUS_ACCEPTED_BY_SKIPPER`, `asSkipperJson`, ...) are
  deprecated aliases.
- The rating endpoint still accepts the request field `skipper_id` next to `driver_id`.

The stored names (tables, columns, role value) were renamed to `taxi` by database script 002; see
`app/database/README.md`.

## Where the modules are now

The kernel modules listed above (`api`, `auth`, `geo`, `billing`, `messaging`, `payments`, `customer`, `adminapi`, `dispatch`,
`filestorage`) live in `libs/platform/modules/<id>`; the Halting drivers partner app in
`services/haltingdriver/backend/modules/haltingdriverapp`; the taxi family in `libs/taxicore/backend` and
`services/<service>/backend/modules`. Namespaces and module ids did not change.
