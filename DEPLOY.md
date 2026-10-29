# Deploying the updated backend to the live server

The live server still runs the old code: `/api/taxi/*` and `/api/media/*` return 404, `/api/user/resend-otp`
redirects, and OTP fails. Nothing here is needed on the phone; it is all server side. The apps' URLs do not change.

## 0. Requirements on the server
- PHP **8.1 or newer** with extensions: `curl`, `gd` (JPEG/PNG/WebP), `exif`, `fileinfo`, `mbstring`, `openssl`,
  `pdo_mysql`, `sodium`, `zip`. Check: `php -m`.
- `upload_max_filesize` and `post_max_size` of at least 12M.
- A writable `runtime/` and `uploads/`, and a media folder (`MEDIA_STORAGE_PATH`, outside the web root).
- MySQL 5.7+/8 or MariaDB 10.3+. Only if you use the `db` media driver: `max_allowed_packet` >= 8 MB.

## Ready-made package

`rubia-backend-release-20261013.zip` (next to this file) contains the `app/` folder with a production `vendor/` already installed (`composer install --no-dev`), the migrations and the console tools, but **no** secrets, uploads, runtime, published assets or backups. With it you can skip the `composer install` in step 2. Unzip it over the live `app/` folder.

## 1. Before anything
0. (Developer machine, before building the package) `php yii docs/reference && php yii docs/annotate --apply && php yii docs/check` so the generated documentation matches the code being shipped.
1. Back up the database: `mysqldump --single-transaction --routines --triggers <db> > before_release.sql`
2. Back up the current code folder.

## 2. Code
1. Copy the new `app/` folder over the live one (keep the live `config/secrets.php`, `uploads/`, `runtime/`).
2. `composer install --no-dev --optimize-autoloader` in `app/` (uses `composer.lock`; do not run `update`).
3. Make sure these files exist on the server and are **not** in the web root's public listing:
   - `config/secrets.php`  (database, cookie key, SendGrid key, OneSignal, Google Maps key `googleMapsKey`) - copy the local one and edit, or
     set the environment variables `DB_DSN`, `DB_USER`, `DB_PASSWORD`, `COOKIE_VALIDATION_KEY`,
     `SENDGRID_API_KEY`, `ONESIGNAL_APP_ID`, `ONESIGNAL_API_KEY`, `PUSH_DRIVER=onesignal`,
     `GOOGLE_MAPS_SERVER_KEY` (a server-side key; **Directions API** or **Distance Matrix API** must be enabled for it, either one is enough) and `GOOGLE_MAPS_BROWSER_KEY` (Maps JavaScript key for the admin map pages; visible in page source, so restrict it by HTTP referrer)
   - `config/jwt-secret.php` (or `JWT_SECRET`). Use the same value on every server, and keep it: changing it
     logs every app user out.
4. Leave `YII_ENV` unset (production). Both the website and `php yii ...` default to production, so errors are not shown to visitors and the dev tools (Gii/Debug) are never loaded.

## 3. Database (short maintenance window)
```
php yii db/status        # shows what is pending
php yii db/up --yes      # 001 media + page tables, 002 skipper -> taxi names, 003 booking numbers, 004 OneSignal settings rows, 005 keystore reminder, 006 removes the stored Firebase key
php yii audit/users      # read-only data report
```
The code reads the renamed tables and roles, so run `db/up` straight after the code is in place.

## 4. Check it works
| Request | Expected |
|---|---|
| `POST /api/user/send-otp` with `contact_no` | `status OK` (and the SMS arrives) |
| `POST /api/user/resend-otp` with `contact_no` | `status OK`, not a redirect |
| `POST /api/user/verify-otp` | `status OK`, `auth_code` starting `eyJ`, no `password_hash` / `auth_key` in `details` |
| `POST /api/user/get-estimate?saveBooking=true` (with a session), twice in a row | two `OK` replies (each booking has its own `booking_number`) |
| `POST /api/user/get-estimate` (with a session) | a fare estimate. `NOK` with "We could not calculate the distance right now" means the Google key is missing or lacks the Distance Matrix API; the exact Google status is in `runtime/logs/app.log` (category `geo`) |
| `POST /api/taxi/new-request` (no `auth_code`) | `NOK` "Session Not Found" (not a 404) |
| `POST /api/media/upload` (no `auth_code`) | `NOK` "User Not Found" (not a 404) |
| `/site/login` (`/site/admin-login` redirects there) | the sign-in page; after signing in as admin, `/admin/dashboard` shows the **Operations** block. A guest opening `/admin` gets 404 by design. |

## 5. Scheduled job (required)

Without this nothing is ever offered to a driver and unanswered bookings are never cancelled. Run **every minute**:

```
* * * * *  curl -s "https://<your-api-host>/api/user/assign-booking-to-driver?token=<CRON_TOKEN>" >/dev/null
```

`CRON_TOKEN` is optional: set it (environment variable `CRON_TOKEN` or `cronToken` in `config/secrets.php`) and the
call is refused without the matching `?token=`. Without it the address stays open, as before. Optional second job
(hourly): `php yii taxi/expire-stale --apply`.

## 6. Push notifications

1. In the dashboard: **Web Settings -> Notification Setting**: paste the OneSignal **App ID** and **REST API Key**
   (the `os_v2_app_...` value). The dashboard value wins over environment variables.
2. `php yii push/check` must say the key and App ID are accepted.
3. Android delivery goes through Google FCM: in **OneSignal -> Settings -> Platforms -> Google Android (FCM)** upload the
   Firebase service-account JSON (once). That file is not part of the project or the package: it is kept in
   `_private-keep-out-of-git/` next to the project folder. Keep it out of the web root and out of git.

## 7. Other optional settings
- `PUBLIC_BASE_URL` (or `publicBaseUrl` in `secrets.php`), e.g. `https://api.example.com`: the host written into
  stored image URLs. Set it behind a proxy or load balancer.
- `booking_expiry_grace_minutes` (web setting): minutes after the pickup time before an unanswered booking is cancelled.

## 8. After
- Log in once as a rider and as a taxi driver on the phone.
- Check `runtime/logs/app.log` for lines tagged `otp`, `push`, `auth`.
- Open a booking in the dashboard: the **Manage this booking** panel (cancel, send back, assign, set state) and the
  Block button next to the customer and the driver should be there for an admin.
- Old rides left "accepted" for weeks block their driver (one ride at a time): list them and cancel them from the
  booking page.

## Rollback
Restore the code backup and the database dump (or `php yii db/down --yes` for the naming change).

## 9. User-app gateway (only when the user app runs as its own system)

On the **user-app system** set `CUSTOMER_API_SECRET_HALTINGDRIVER` (and `_TAXI`, `_AUTO`, `_BIKE`) - one long random value per service.
On each **service** set `CUSTOMER_API_MODE=remote`, `CUSTOMER_API_URL=https://<user-app-host>`,
`CUSTOMER_API_SERVICE=driver` (or taxi / auto / bike) and the same `CUSTOMER_API_SECRET_<SERVICE>`. Without a secret the gateway
refuses every call. Leave the mode at `local` while everything is still one application.

## 10. One entry point per service

Each service can be served on its own address from the same release: point its web server at the same `app/web`
folder but make the entry script `haltingdriver.php`, `cartaxi.php`, `autotaxi.php` or `biketaxi.php` (instead of `index.php`).
Such an entry answers only that service's API addresses (everything else, including `/admin`, is 404; the API
addresses are the same ones the apps already use). Per service, set in the environment:

- `CUSTOMER_API_SECRET_<SERVICE>` and, when the user app is a separate system, `CUSTOMER_API_MODE=remote`,
  `CUSTOMER_API_URL` (the service key is set by the entry script).
- `DB_DSN_<SERVICE>`, `DB_USER_<SERVICE>`, `DB_PASSWORD_<SERVICE>` for its own database (`HALTINGDRIVER`, `CARTAXI`, `AUTOTAXI`, `BIKETAXI`). Leave unset to keep using the shared database. Do not set it before the service's data has been copied
  (ARCHITECTURE_TARGET.md section 9).

The admin panel and the website stay on `index.php`. Which API addresses each entry serves: `app/config/services.php`.

`CUSTOMER_WALLET` - leave unset: the customer wallet is off (customer pays the partner, partner pays the platform). `on` re-enables it.

## 11. Halting drivers service on its own database (stage 3)

1. `php yii split/copy --service=1 --target=rubia_haltingdriver --apply` then `php yii split/complete --target=rubia_haltingdriver --service=1`
   (creates the new database; the source is only read; prints row counts and money sums of both sides).
2. Halting drivers API: entry `web/haltingdriver.php` with `DB_DSN_HALTINGDRIVER` (+ `DB_USER_HALTINGDRIVER`, `DB_PASSWORD_HALTINGDRIVER`),
   `CUSTOMER_API_MODE=remote`, `CUSTOMER_API_URL=<user app>`, `CUSTOMER_API_SECRET_HALTINGDRIVER`.
3. Halting drivers dashboard: entry `web/haltingdriver-dashboard.php` with the same `DB_DSN_HALTINGDRIVER`; log in with a staff account of that database.
4. User app + super admin (`index.php`): `CUSTOMER_API_SECRET_HALTINGDRIVER` (same value), `ADMIN_URL_HALTINGDRIVER=<Halting drivers API address>`,
   `ADMIN_SECRET_HALTINGDRIVER` (same value on the Halting drivers API), `ADMIN_DASHBOARD_URL_HALTINGDRIVER=<Halting drivers dashboard address>`.

## 12. Live cut-over runbook (per service: Driver first, then Bike, Auto, Taxi)

The mobile apps keep every address they use today; only the web server decides which process answers. A cut-over
moves a service's traffic from the shared database to its own database. It is reversible until the old tables are
removed.

### 12.1 Processes (one set per service)

| Process | Entry script | Database | Environment |
|---|---|---|---|
| User app + super admin | `index.php` (as today) | shared (`DB_DSN`) | `CUSTOMER_API_SECRET_<SVC>`, `ADMIN_URL_<SVC>`, `ADMIN_SECRET_<SVC>`, `ADMIN_DASHBOARD_URL_<SVC>` for every cut service |
| Service API | `web/<svc>.php` | `DB_DSN_<SVC>` | `CUSTOMER_API_MODE=remote`, `CUSTOMER_API_URL`, `CUSTOMER_API_SECRET_<SVC>`, `ADMIN_SECRET_<SVC>`, the Google / OneSignal keys (or their dashboard setting, copied with `web_setting`) |
| Service dashboard | `web/<svc>-dashboard.php` | `DB_DSN_<SVC>` | same database variables |

### 12.2 Routing (nginx / Apache): the customer app uses `/api/user/*` for both the user app and the Halting drivers service

User app answers (login, profile, address book, notifications - they leave with the user app):
`index logout check send-otp resend-otp verify-otp update-profile update-profile-pic update-lat-lng add-user-emergency-contacts
add-emergency-contact update-emergency-contact list-emergency-contacts delete-emergency-contact apply-referral services
list-favourite-location create-favourite-location update-favourite-location delete-favourite-location
list-fcm-notifications clear-notifications banner-with-category`
(exactly the actions left in `app/modules/customerapp/controllers/UserController`)
Halting drivers service answers every other `/api/user/*`, plus `/api/driver/*`, `/api/booking/*`, `/api/wallet/*`,
`/api/user-wallet/*`, `/api/manager/*`.
Taxi family: `/api/<bike|auto|car>-user/{send-otp,resend-otp,verify-otp,check,logout,my-profile,update-profile,
update-lat-lng,refer-user,my-referal,my-notification,clear-notification,add-to-favourite,my-favourite-location,
remove-favourite,add-to-emergency-contact,my-emergency-contacts}` -> user app; the rest of `/api/<svc>-user/*` and
`/api/<svc>/*` -> that service. `/api/customer/*` is only for the services (restrict to their addresses).

```
location ~ ^/api/user/(index|logout|check|send-otp|resend-otp|verify-otp|update-profile|update-profile-pic|update-lat-lng|add-user-emergency-contacts|add-emergency-contact|update-emergency-contact|list-emergency-contacts|delete-emergency-contact|apply-referral|services|list-favourite-location|create-favourite-location|update-favourite-location|delete-favourite-location|list-fcm-notifications|clear-notifications|banner-with-category) { proxy_pass http://user_app; }
location ~ ^/api/(user|driver|booking|wallet|user-wallet|manager)(/|$) { proxy_pass http://driver_service; }
```

### 12.3 Steps for one service (Driver shown)

1. Quiet window. Stop dispatch cron (`api/user/assign-booking-to-driver`) so no new offers are made.
2. `php yii db/up --yes` on the shared database (migration 015: UUIDs).
3. `php yii split/copy --service=1 --target=rubia_haltingdriver --apply` then `php yii split/complete --target=rubia_haltingdriver --service=1`.
   Check "All tables reconcile". Keep the output with the change record.
4. Start the service API and dashboard with `DB_DSN_HALTINGDRIVER=...rubia_haltingdriver`; set the environment of 12.1 on the user app.
5. Smoke test on the service address (not yet routed): partner login, customer estimate, a booking, accept, complete.
6. Switch the web server routing (12.2) to the service; start its dispatch cron against the service address.
7. Watch: bookings, driver wallets, `service_invoice`, the "All services" page of the super admin.
8. Rollback (any time): switch routing back; the shared database still holds the service's rows as of step 3. Rides
   made after the cut-over exist only in the service database: export them (`split/copy` into the shared database is not
   built) before rolling back, so do the rollback in the first minutes or not at all.
9. After a stable week: archive and drop the service's tables from the shared database (separate change).

### 12.4 Checks before the window
- CUSTOMER_API_SECRET_<SVC> and ADMIN_SECRET_<SVC> identical on both sides; `/api/customer/*` reachable only from the services.
- The service database user has rights on its own database only.
- `google_server_key` (dashboard) set in the service database's `web_setting`: without it ride completion cannot price distance.
- Cron: each service runs its own jobs against its own address; the user app keeps customer-side jobs.

## 13. Building and deploying the Halting drivers release

`php yii service/pack --service=haltingdriver` after adding or moving a class under `services/haltingdriver`, then
`php yii service/release --service=haltingdriver` (about two minutes). The zip in `services/haltingdriver/release/dist/` is the whole
deployment of that service: unpack, point the web server at `app/web/haltingdriver.php` (API) or `app/web/haltingdriver-dashboard.php`
(dashboard), create `app/runtime` and `app/web/assets` writable, and set the environment (it carries no secrets): `DB_DSN_HALTINGDRIVER`,
`DB_USER_HALTINGDRIVER`, `DB_PASSWORD_HALTINGDRIVER`, `COOKIE_VALIDATION_KEY`, `JWT_SECRET`, `GOOGLE_MAPS_SERVER_KEY`
(or the dashboard setting), `CUSTOMER_API_SECRET_HALTINGDRIVER`, `ADMIN_SECRET_HALTINGDRIVER`, `CUSTOMER_API_MODE=remote`,
`CUSTOMER_API_URL`, push / SMS keys. Old zips in `release/dist` can be deleted at any time.

## 14. The taxi services' releases

Same procedure as section 13 per service: `php yii service/release --service=biketaxi|autotaxi|cartaxi`. Each release holds the
kernel (`app/`), `libs/taxicore` and its own `services/<svc>` folder, without secrets and without the other services'
code; entry `app/web/<svc>.php` (API) and `app/web/<svc>-dashboard.php`; environment `DB_DSN_<SVC>`, `DB_USER_<SVC>`,
`DB_PASSWORD_<SVC>`, `CUSTOMER_API_SECRET_<SVC>`, `ADMIN_SECRET_<SVC>`, `GOOGLE_MAPS_SERVER_KEY`, `COOKIE_VALIDATION_KEY`,
`JWT_SECRET`, `CUSTOMER_API_*` (`SVC` = `BIKETAXI`, `AUTOTAXI`, `CARTAXI`).

## 15. API-only releases

`php yii service/release --service=<svc> --profile=api` leaves out the dashboard (views, entry scripts, front-end packages) and
the website entry `app/web/index.php`: use it for the process that serves the partner / customer API; use the full release
for the dashboard process. About 56 MB instead of 122 MB.

## 16. The customer app service

Own release: `php yii service/release --service=customer` (full) or `--profile=api` (56 MB, API only). Entry `app/web/customer.php`
(API) and `app/web/customer-dashboard.php`. Environment: `DB_DSN_CUSTOMER`, `DB_USER_CUSTOMER`, `DB_PASSWORD_CUSTOMER`,
`CUSTOMER_API_SECRET_<SERVICE>` for each service that calls its gateway (`HALTINGDRIVER`, `BIKETAXI`, ...), `ADMIN_SECRET_CUSTOMER`,
`JWT_SECRET`, `COOKIE_VALIDATION_KEY`, push / SMS keys of the customer app. The services point `CUSTOMER_API_URL` at it
and use `CUSTOMER_API_MODE=remote`. The super admin sets `ADMIN_URL_CUSTOMER`, `ADMIN_DASHBOARD_URL_CUSTOMER`.
Routing: the customer app's `/api/user/*` actions listed in section 12.2 go to this service, the rest of `/api/user/*` to the
service that owns the booking flow.

## 17. Keeping `release/dist` tidy

`php yii service/prune --service=biketaxi --keep=1` (also `autotaxi`, `cartaxi`, `haltingdriver`, `customer`) removes older zips and
unfinished builds, keeping the newest one of each kind (full, api). Run it after building a release; releases are reproducible
from the repository.

## 18. The customer wallet

One wallet per customer, kept by the customer app service. Turn it on with `CUSTOMER_WALLET=on` in the **customer app** environment
only; the services need nothing (they call its gateway: `wallet-balance`, `wallet-debit`, `wallet-credit`, signed with their
`CUSTOMER_API_SECRET_<SERVICE>`). Route `/api/user-wallet/*` to the customer app service.

## 19. Razorpay (partner payments)

The Razorpay keys are not in code. Either paste them in the dashboard (**Payments** screen of the Halting drivers dashboard, administrator
only: key id, key secret, webhook secret) or set `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET` and `RAZORPAY_WEBHOOK_SECRET` on the server. The dashboard
value wins when both exist. A key id starting `rzp_test_` runs in test mode, `rzp_live_` in live mode; the screen shows which.

1. Apply migration 016 on every service database that has a partner app: `php yii db/up --yes` (creates `payment_order` and the settings).
2. In the Razorpay dashboard add a webhook to `https://<halting-api-host>/api/driver/razorpay-webhook` with the events `payment.captured`,
   `order.paid` and `payment.failed`, and the same secret you paste in the Payments screen. Turn on automatic capture (Settings, Payment capture).
3. Set the joining fee in the Payments screen (0 = no fee). Money is credited only after the server has verified the payment with Razorpay,
   once, whether the app reports it or the webhook arrives first.
4. "Enforce onboarding" stays off until the drivers who joined earlier have been approved (otherwise they stop getting offers).
5. `RAZORPAY_API_BASE` is only for tests (a local stand-in for api.razorpay.com); leave it unset on a server.

### Cashfree and verification credentials

`CashFree.php` no longer holds any key. Paste them in Integrations (secrets are masked and never shown again), or set the
environment variables named after the setting key in capitals: `CASHFREE_PG_CLIENT_ID`, `CASHFREE_PG_CLIENT_SECRET`, `CASHFREE_PG_MODE` (`test` or
`production`; test uses the Cashfree sandbox), `CASHFREE_VERIFY_CLIENT_ID`, `CASHFREE_VERIFY_CLIENT_SECRET` (Aadhaar verification),
`VERIFY24X7_USERNAME`, `VERIFY24X7_PASSWORD`, `VERIFY24X7_FACEMATCH_TOKEN` (police verification and face match). Migration 017 creates the
empty settings. Until they are filled in, the Aadhaar, police-verification and face-match calls fail; the driver payments use Razorpay.

## 20. Where the photos are stored (object storage)

Photos and documents (profile photos, Aadhaar, PAN, licence, bank proof, and the selfie and car photos of every ride) are the part of
this system that grows without limit. A rough size: one ride makes about 10 photos, 50 to 120 KB each on the phone, so about 1 MB a ride.
1,000 rides a day is about 1 GB a day, about 400 GB a year, so they must not sit in the database and should not sit on the application
server's disk.

How it is built:

* The database keeps only a small row per file (id, type, size, kind, path). It never holds the bytes (the old `db` driver exists but is not used).
* `media_driver` chooses where the bytes go: `disk` (default, one server, fine for development) or `s3` (an S3-compatible object storage).
* The apps upload to `POST /api/media/upload` as before and send a `kind`: `document` (default), `profile` or `ride`.
  The backend shrinks each image by kind (document 1600 px, profile 800 px, ride 1280 px, quality 82/80/72) and strips location data.
* In the storage the files are in a folder per kind and month (`ride/2026/10/<id>.jpg`).
* `GET /api/media/view?id=...` keeps working for every URL already stored. For a file in the storage it answers with a short redirect, so
  the bytes go from the storage to the phone and never through PHP. Documents use a link that works for 15 minutes (the bucket stays
  private); profile and ride photos use `media_public_base_url` (a CDN address) when it is set.
* Ride photos are deleted after `media_ride_retention_days` (default 180) by `php yii media/prune-rides --apply` (put it in cron, daily).
  Documents and profile photos are kept.

Which storage (S3 compatible, so changing provider later is only configuration; prices are the providers' public list prices as of
October 2026, check them before you buy):

| Provider | Storage per GB-month | Download (egress) | Notes |
|---|---|---|---|
| Cloudflare R2 (recommended) | about $0.015 | free | 10 GB free; custom domain with Cloudflare CDN; no surprise bandwidth bill |
| Backblaze B2 | about $0.006 | free through Cloudflare, else about $0.01/GB | cheapest storage; put Cloudflare in front |
| DigitalOcean Spaces | about $5 a month with 250 GB included | included allowance | simple if you are on DigitalOcean already |
| AWS S3 | about $0.023 | about $0.09/GB | most features, most expensive to serve images |

For 400 GB a year R2 is about $6 a month at the end of year one, and ride photos older than 180 days are removed, so the total settles
instead of growing for ever.

Set-up (Cloudflare R2 as the example):

1. Create a bucket (for example `rubia-media`), keep it **private**. Create an API token with read and write on that bucket.
2. In Integrations (or as environment variables with the key name in capitals), fill in:
   `media_s3_endpoint` (`https://<account id>.r2.cloudflarestorage.com`), `media_s3_bucket`, `media_s3_region` (`auto`),
   `media_s3_key_id`, `media_s3_key_secret` (masked, never shown again), then `media_driver` = `s3`.
3. Keep the bucket **private** and leave `media_public_base_url` empty. Every photo is then opened through a short signed link (valid 15 minutes) on the
   `<account id>.r2.cloudflarestorage.com` address. R2 does not charge for downloads, so a CDN is not needed for cost. Do not make this bucket public:
   in R2 public access is for the whole bucket (never for one folder), and it would include the `document/` folder (identity documents). Signed links
   cannot be used on a custom domain either. If you ever want a CDN for profile and ride photos, use a second bucket with a custom domain (the `r2.dev`
   address is for development only) and put that address in `media_public_base_url`; documents then still use signed links.
4. Move the files that already exist: `php yii media/move-to-storage` (dry run), then `php yii media/move-to-storage --apply`. Each file is copied,
   compared byte for byte with the original, and only then removed from the local disk. It is safe to run again.
5. Add a daily cron: `php yii media/prune-rides --apply`.
6. Back-up: turn on the provider's versioning or a second bucket copy for the `document/` folder (identity documents are the part you cannot
   ask the partners to produce again).

Never put the storage keys in code or in a release zip.

## 21. When the payment gateway does not work: QR payments

> **Removed on 29 Oct 2026:** the manual UPI QR payment, its approval list and the `company_upi_*` settings no longer exist (see section 35). The text below is kept only as history.

The partner app pays in three tiers, so a partner is never stuck:

1. **Razorpay checkout** (cards, UPI apps, netbanking).
2. **Automated QR.** If checkout does not work the app offers "Pay by QR". The server creates a Razorpay UPI QR for the amount it decides (a single-use
   QR that expires in two hours). The partner scans it with any UPI app; the app asks the server every 4 seconds, and the server also gets
   Razorpay's webhook (`qr_code.credited`). The payment is confirmed against Razorpay (exact amount, captured) and applied once: the joining fee is marked paid,
   the wallet credited or the plan started. Nothing else for anybody to do.
   **Razorpay must switch the "UPI QR codes" feature on for your account** (write to Razorpay support, it is an on-demand feature). Until then Razorpay answers
   "URL not found" and the app goes straight to tier 3. Add `qr_code.credited` to the events of the webhook in the Razorpay dashboard.
3. **Company UPI QR with the UTR number** (when Razorpay itself is down, or QR codes are not enabled). Fill in the company UPI id (and the name shown with it)
   on the Payments screen of the dashboard; leave the id empty to switch this tier off. The app shows a QR of that UPI id for the amount, the partner pays
   and types the UTR (bank reference number). The payment waits on the Payments screen under "Waiting for your approval"; after checking your bank statement
   you tap **Approve** (credits once) or **Reject**. The app notices the approval by itself. The same UTR cannot be sent twice.

Everything is recorded in `payment_order` (gateway `razorpay`, `razorpay_qr` or `manual`), so the Payments screen lists all payments of the three kinds.

### Identity documents and profile photos: kept for life, stored apart

The driving licence, Aadhaar card, PAN card and the profile photo of a partner are kept for the partner's whole life (also after the partner leaves) and nothing
in this system deletes them. How that is built, in three layers:

1. **Stored apart.** Documents and the profile photo go to their own bucket, all under the folder `profile/<partner id>/<document type>/` (`driving_license`, `aadhar`, `pan_card`, `profile_image`):
   create a second bucket (for example `rubia-documents`), keep it private, give the same API token access to it, and put its name in `media_s3_bucket_documents`
   (Integrations > Photo and document storage). The temporary ride photos stay in the main bucket, in `ride/`. The apps send `doc_type` with the upload
   (`driving_license`, `aadhar`, `pan_card`); a profile photo is always `profile_image`; anything else goes to `other/`.
2. **Never deleted by the software.** `media/prune-rides` only ever deletes `kind=ride`; `media/purge-orphans` ignores everything but ride photos (an older version of a
   renewed licence is still a document that is kept); and the delete helper itself (`MediaStore::remove`) refuses a document or a profile photo. A partner who uploads
   a new licence keeps the old one too (the history stays).
3. **Locked at the provider.** In Cloudflare R2: bucket `rubia-documents` > Settings > **Bucket lock rules** > Add rule, leave the prefix empty (the whole bucket),
   retention **Indefinite**. R2 then refuses to overwrite or delete any object in that bucket, for everybody, including a mistake in this software or a lifecycle rule.
   You cannot empty or delete the bucket while the rule exists (remove the rule first on purpose). Do not put a lock on the ride photo bucket.

If a partner or the law requires erasing someone's documents, do it by hand: remove the lock rule, delete the objects and the rows, put the rule back. It is meant to be a
deliberate act, never an automatic one.

## 22. The keys in the dashboard: which are protected

Every key box in Integrations carries a label and says where to find the value:

* **Protected** (red label): a password, secret key or token. Razorpay key secret and webhook secret, Cashfree client secrets, the verify24x7 password and face-match
  token, the object storage secret access keys (the main one and the one of the documents bucket), and the OneSignal REST API key. A protected value is saved
  **encrypted** in the database (AES-256-GCM), hidden after saving and **never shown again**, and never sent to any app. Leave the box empty to keep the saved value; type a new one
  to replace it. The box says "saved" or "not set yet".
* **Private** (yellow label): an identifier such as a key id, a user name or the OneSignal App ID. Not a password, shown in the box, but keep it to yourself.
* **Visible** (grey label): an ordinary setting (an address, a name, a number of days).

The encryption key text is `SETTINGS_ENCRYPTION_KEY` (an environment variable on the server: a long random text, keep a copy somewhere safe). If it is not set, the application's
cookie validation key is used. **If that text is ever changed, the saved protected values cannot be read any more and must be pasted again.**
Values that were saved before encryption existed keep working; encrypt them once with `php yii settings/protect --apply` (`php yii settings/status` shows which are encrypted;
it never prints a value).

For the lifelong documents bucket use its own access key (least privilege): in Cloudflare R2 create a second API token limited to that bucket and paste its Access Key ID and Secret in
the two "documents bucket" boxes; leave them empty to use the main key for both buckets. In the bucket, files are named
`profile/<partner id>/<document type>/<id>.<ext>` (for example `profile/7106/driving_license/<id>.jpg`), so one partner's documents can be found in the bucket even without the database.


## 23. Integrations module (all third-party services)

Dashboard menu **Integrations** (address `dashboard/<service>/integrations`, for example `/dashboard/halting-drivers/integrations`, `/dashboard/car-taxi/integrations`; administrators only) has one page per third-party service: Razorpay, Cashfree payment
gateway, Cashfree Aadhaar verification, verify24x7, 2Factor SMS, OneSignal, SendGrid, Google Maps, photo and document storage (R2) and OpenMoney.
Each page shows its boxes with the Protected / Private / Visible label, where to get the keys, the webhook address (Razorpay), and a
**Test connection** button (Razorpay, Cashfree, verify24x7, SMS, OneSignal, Google Maps, storage). The index shows Ready / Needs more keys / Not set up.

- Each service of the system (Halting drivers, car, auto and bike taxi, customer app) has its own dashboard and database, so its integrations
  page holds its own keys; they are never visible to another service.
- Keys are read dashboard first, then the server environment variable named like the box in capitals (for example `SMS_API_KEY`).
- Protected boxes are saved encrypted (see section 22); leave empty to keep the saved value.
- The old places are gone: the "payment gate way" tab of Web Settings is removed, the notification tab and the Website Setting tab no longer show the SMS, push and map keys, and the Razorpay fields left Payments. Everything is in Integrations.
  The joining fee, onboarding switch and company UPI stay on Payments.
- Migration 022 seeds the settings; run `php yii db/up --yes` on each service database.


## 24. Ride photos belong to the ride

Every ride has its ride details page in the dashboard (Bookings > open a booking > Vehicle Verification). The selfie and car photos the driver takes are shown
there and lie in the bucket in the folder `ride/<booking id>/`, named `<before|after>-<side>-<id>.<ext>` (for example `ride/1000/before-front-<id>.jpg`).
The photo is filed under its ride when the app saves it against the booking (`vehicle-verification`). Ride photos are deleted after the retention
period; the documents and profile photos under `profile/<partner id>/` in the separate documents bucket are never deleted. Migration 023 adds the
`booking_id` column to `media`; run `php yii db/up --yes`.


## 25. Drivers-profile: the whole profile in the dashboard

Menu **Halting drivers > Drivers-profile** lists the partners; opening one shows `/admin/users/driver-profile?id=<driver id>` with everything the partner
entered while creating the profile in the app, each section with its own Save button: personal details (with profile photo), driving licence,
Aadhaar, PAN, bank details, emergency contacts, vehicle types, police verification (and the joining fee state), plus the onboarding checklist.
Administrators, sub-admins and managers can edit; it writes the same rows the app writes, so both always agree. Entries are checked like in the app (18+
years, valid e-mail, 12-digit Aadhaar, PAN, IFSC and UPI patterns, a licence/Aadhaar number cannot belong to two drivers, a contact id of another driver is
never touched). A new picture replaces the one shown; the old file stays in the documents bucket (kept for life) under `profile/<driver id>/<document type>/`.
Approval, wallet and rides stay on the page behind "Approval, wallet and rides".

**Mandatory fields.** A profile is complete only when all of this is filled in: personal details (first and last name, e-mail, date of birth, gender, address,
profile photo), driving licence (number, issue and expiry dates, front and back pictures; the licence must not be expired), Aadhaar (number, both pictures),
PAN (number, picture), bank details (account holder, account number, IFSC, bank name, branch), at least one emergency contact, at least one vehicle type and,
when the dashboard sets a joining fee, the joining fee paid. Optional: alternative contact, city, UPI id. The dashboard refuses to save a section while a
mandatory part of it would stay empty, and the onboarding checklist (`onboarding-status`) marks a step done only when its mandatory parts are filled; each step
lists what is `missing`.


## 26. Customers-profile: the customer profile

Menu **Customers > Customers-profile** lists the customers; opening one shows `/admin/users/user-profile?id=<customer id>`: personal details (first and last name are
mandatory, e-mail, date of birth, gender, alternative contact, city, address and photo are optional), emergency contacts, saved places (type, written
address and pincode can be corrected or a place removed; places are added in the app because they need a point on the map) and the ride summary. Each section
has its own Save button and writes the rows the customer app writes. The photo is stored like the partner photos under `profile/<customer id>/profile_image/`.


## 27. The city comes from the address (franchise operations)

The city of a partner or customer is not typed. When the address is saved on Drivers-profile or Customers-profile, the server asks the Google Geocoding API for the
point (latitude, longitude; needs the server key in Integrations > Google Maps) and the city is the active city whose boundary contains that point (the same
boundaries the booking uses), else the city named like the town Google reports. It is written to `user.lat`, `user.lng`, `user.city_id` and `user.city_name`.
If Google cannot find the address, or the point is outside every city, the address is still saved, the city stays empty and the page says why.
The profile page shows **Joined in**: the city, the coordinates and a map. The Drivers-profile list has a **City (from address)** column with a filter.
A **city franchise** now sees only the drivers of its own city (a driver belongs to a city by `user.city_id` or by the city chosen at joining,
`driver_details.city_id`); before, the franchise list was not filtered. A franchise can open the profile of a driver of its city, read-only.
For tests, `GEOCODE_API_BASE` can point to another address instead of Google.


## 28. Settings are separate for every service

Halting drivers, Car taxi, Auto taxi, Bike taxi and the Customer app each have their own dashboard, their own database and therefore their own Integrations: a key
pasted in one is not visible to, and not used by, another (the page heading names the service). The Google Maps app key (`app_google_map_api`) moved from the
Website Setting tab to Integrations > Google Maps. On a server that runs several services, an environment variable can be given to one service only by prefixing
it with the service key in capitals: `HALTINGDRIVER_RAZORPAY_KEY_ID`, `CARTAXI_SMS_API_KEY`, `AUTOTAXI_GOOGLE_MAPS_SERVER_KEY` ...; the same name without prefix applies
to every service. Order for every key: the box in that service's Integrations, then `<SERVICE>_<NAME>`, then `<NAME>`. Run `php yii db/up --yes` against each
service database so that each one has its settings rows (migrations 016-023).

**Charges and commission (Halting drivers).** Integrations of the Halting drivers dashboard has a page "Charges and commission" with the default admin commission (%),
GST (%), platform charges, secured fee, admin cancellation earning and the minimum withdrawal. They moved from Web Settings (where they are no longer shown for
this service). Values must be numbers, zero or more; the two percentages at most 100. The other services keep theirs where they were.

**Driver settings (Halting drivers).** Integrations of the Halting drivers dashboard also has a page "Driver settings": joining fee (INR, 0 = free), enforce
onboarding (a switch), minimum wallet balance, referral earning and cancellation charge of a partner. They moved from Web Settings > Driver Setting, and the
joining fee / enforce switch left the Payments screen (which now shows their values and links here). Same settings rows as before, so the app and the ride code
read them unchanged. Numbers must be zero or more; the switch is 0 or 1.

**Customer settings (Halting drivers).** Menu **Customers > Integrations** (address `dashboard/halting-drivers/customers/integrations`) holds the user settings: customer referral earning, cancellation charge,
waiting time (minutes) and notification period. They moved from Web Settings > User Setting, are the same settings rows as before (the booking code of the
customer app reads them unchanged) and must be numbers, zero or more.


## 29. Where the customers and the partners are kept

- The **Customer app** service holds the customers: its dashboard (`/dashboard/customer/...`) has the **Customers** menu (Customers-profile and Integrations). The Google
  Maps, SMS, push notification, e-mail and reset forms and storage keys of that dashboard are the customers' own, in the customers' own database, apart from the partners'.
- **Halting drivers** holds the driver partner app data (Drivers-profile, Charges and commission, Driver settings) and has no Customers menu. Its "Customer settings" page
  (referral, cancellation charge, waiting time, notification period) stays among its Integrations because the Halting drivers booking code reads those settings from its
  own database; the Customer app dashboard has its own copy for its own database.
- **E-mail and reset forms.** The SendGrid page (Integrations > SendGrid) also has the sender address, sender name, subject and wording of the password reset
  e-mail of that service (empty = the standard ones). An invalid sender address is ignored.
- A push goes through the OneSignal app of the receiver's role (`ONESIGNAL_APP_ID_USER` / `_DRIVER` can be set per role on the server).

## 30. Maps services: Google, Mapbox, OpenStreetMap (and others later)

Integrations > **Maps and routes** holds every maps service: the Google server key (also the browser key for the dashboard maps and the app key), the **Mapbox access token**
and **Order of the map services** (for example `google,mapbox,openstreetmap`; empty = that). Two questions are asked of them: *where is this address?* (the city from an
address) and *how far by road?* (the fare). The services are asked in the order set; one without a key, one that refuses (key or project not allowed) or one that does
not answer is skipped for the next, so a new key never stops bookings that another service still serves. OpenStreetMap needs no key but only finds addresses (no road
distances), and is meant for the occasional address lookup, not for bookings.

A Google key works only if its project has the **Geocoding API** (addresses) and the **Routes API** (distances) switched on in Google Cloud (older projects may still have
Directions / Distance Matrix, tried after the Routes API). Every Google key is tried in turn: Integrations, `GOOGLE_MAPS_SERVER_KEY`, `config/secrets.php`, `map_key`.
**Test connection** asks each service that has its key for an address and a road and says which one works and what to switch on.

**Using another maps service later** (HERE, Ola Maps, TomTom ...): write a class extending `MapProvider` (answers `geocode()` and `route()` in the shapes described in
that class), add its key box to Integrations > Maps and routes and list it in `MapProviders::all()`. Bookings, fares and the city-from-address lookup need no other change.
Test servers can be pointed elsewhere with `GEOCODE_API_BASE`, `MAPBOX_API_BASE`, `NOMINATIM_URL`, `ROUTES_API_URL`.


## 31. The look of the dashboards (CSS)

All dashboards share one style sheet, `app/modules/admin/assets/css/admin.css`, written to the usual standards of professional admin software. The colours, radius and font are the
variables at the top of the file; change them there and every page follows. The inline styles that used to sit in the layout and the menu are gone.

- **Accessibility (WCAG 2.1 AA).** Every text and control colour was checked for contrast (4.5:1 for text, 3:1 for field borders), in the light and the dark theme. A visible keyboard
  focus on every control, a "Skip to main content" link, words (not only colour) in every status badge, reduced motion respected, finger-sized controls on phones, a print version
  without the menu.
- **Calm and consistent.** A navy-blue sidebar (#0a2540) with white text, white cards, hairline borders, one type scale (14 px data text, system font stack, tabular figures for numbers) and one spacing scale.
  Colour is rare and means something: **one accent per service** (links, the main button, the active menu mark and the line above the menu: Halting drivers teal, Car taxi blue,
  Auto taxi green, Bike taxi violet, Customer app crimson; at the end of the file) and **soft status colours** (green, amber, red, blue: pale background, dark text) for state only.
  The number tiles of the dashboard are white with a coloured edge that tells the state, instead of solid coloured blocks.
- **Light and dark.** The moon in the top bar switches; the choice is remembered in the browser, and until then the system setting decides.
- **Fixes on the way.** The old Bootstrap 4 badges (`badge badge-success`, "Active", "Verified") were invisible on the Bootstrap 5 theme; grid headings were solid blue with dark text;
  links and table headers were red. `FontAwesomeAsset` publishes only the style sheet and the icon fonts (it used to copy thousands of SVG files, which made the first page after a
  deploy slow and, with a short time limit, left the fonts out so every icon showed as an empty box). After a deploy `web/assets` is empty and fills itself on the first visit.
- **The map.** The "Joined in" card of Drivers-profile and Customers-profile is a Leaflet map (from cdnjs, OpenStreetMap tiles) with the pin of the saved point and the boundary of the
  city drawn on it; a point the app sent without an address shows the city whose boundary holds it. Older records that hold latitude and longitude the other way round are shown
  corrected. If the map service cannot be reached the card says so and keeps the coordinates and the Google Maps link.

**Style sheet edits reach the browser.** Yii names the published copy of an asset folder after the folder's own time, and that does not change when a file inside is edited, so an edited
`admin.css` used to stay hidden behind the old published copy (and the browser kept its own copy too). `config/web.php` now names the published folder of the dashboard's own assets after
the newest file inside it and adds `?v=<time>` to every asset address, so a change shows after a normal page reload, on any server and in any `YII_ENV`.

**Cards in a flex layout, and no black.** Every page that holds two or more cards side by side in one box gets the class `rb-flex` (the layout adds it, so new pages need nothing): the cards sit
in a wrapping flex row, two to a row on wide screens and one on narrow ones, all of a row the same height. A card that holds a table, a grid or a map, or that has the class `rb-full`, takes
the whole row; cards inside Bootstrap rows keep their grid and are made the same height. No black is used anywhere: the text is a deep navy ink (`--rb-text`), the dark theme is navy, and the
black-ish defaults of the framework (body text, `text-dark`, the footer, selects, form controls, map buttons) are overridden. A script that reads the computed colours of every element
found none darker than a very dark grey on the dashboard, drivers list, driver profile, integrations and payments pages, in the light and in the dark theme.

## 32. Subscriptions: active means running today

The Halting drivers menu has **Active Subscriptions** and **Inactive Subscriptions**. A subscription is *active* only while it is marked active **and** its end date has not been reached
(`DriverSubscription::isCurrent()`); before, only the flag was read, and the flag changes only when `api/driver/auto-cancel-subscription` runs (nothing schedules it, so 38 of 41 "active"
subscriptions had ended long ago). Now: the Active list shows the running ones, the Inactive list shows the inactive ones and the expired ones the job has not moved yet (badge "Expired"),
the app's plan state (`plan_activated`, `driver_subscription_status`) is true only for a running plan, and renewing still extends a running plan or restarts an ended one. The stored flag is kept honest too, in three ways that all call `DriverSubscription::expireEnded()`:
migration **024** (run `php yii db/up --yes` on the Halting drivers database) moves the subscriptions that had already ended; a **daily self-run** (the first driver call, or the first visit to
Active Subscriptions, after midnight; remembered in `runtime/subscription-expiry.stamp`) needs no job at all; and for a fixed time schedule `php yii subscription/expire --apply` from cron
(for example `0 1 * * * php yii subscription/expire --apply`; without `--apply` it only counts). The endpoint `api/driver/auto-cancel-subscription` does the same and still works.

**The Integrations list is a flex grid of cards** (`.rb-cards` in `admin.css`) where **every card has the same size**: three to a row (two below 992 px, one below 576 px; a short last row keeps the same
width), the same height, a long description cut at four lines (the full text is the hover text). The group of a service (Payments, Messaging, Maps ...) is a small label on top of its card. The page of one
service uses the same three equal cards in a row: its keys, its webhook address and where the keys come from.

**Three equal cards in a row, on every page except the profiles.** A box holding two or more cards (`rb-flex`) now lays them out three to a row, every card the same width and the cards of a row the same
height (two to a row below 992 px, one below 576 px; a card that holds a table, a grid or a map, or has `rb-full`, takes the whole row). The profile pages (Drivers-profile and Customers-profile) keep two
cards to a row, because their sections hold wide forms: their root box has the class `rb-two`.

**Each page is arranged for what it shows** (classes at the end of the layout section of `admin.css`):
- **Dashboard** (`rb-dash`): the sections in a grid, Operations across the whole row (its five tiles on one line), then the booking sections two to a row with three number tiles to a line.
- **Drivers-profile and Customers-profile** (`rb-split`): the title card on top, then the forms in the wide column (personal details, licence, Aadhaar, PAN, bank, contacts, vehicles) and the status in the narrow
  column that stays in view while you scroll (the checklist, the map of where the person joined, police verification and joining fee; for a customer the map and the ride numbers).
- **Integration pages** (`rb-split`): the keys in the wide column (two boxes to a line when there are more than three), the webhook address and "Where the keys come from" in the narrow column.
- **Integrations list** (`rb-cards`): the services as equal cards, three to a row.
- **Payments** (`rb-split`): the UPI QR form in the wide column, the Razorpay and onboarding summary in the narrow one, then the approval list and the latest payments, each in a card.
- Other pages with several cards in one box keep the generic rule (`rb-flex`): three equal cards to a row. Both columns of a split stack on narrow screens.

## 33. Release of 27 Oct 2026: booking radius, trip changes, live tracking

1. Back up the Halting drivers database, then `php yii db/up --yes` (migrations 031-035: `page.app_key`, `booking_route_log`,
   `booking.radius_alert_at`, `booking_track` and the live-distance columns on `booking`, and `pincode_map`, the maps drawn from pincodes).
   The server's PHP needs a CA certificate bundle (`curl.cainfo`) for the pincode lookup on OpenStreetMap. Run it on the database that holds `booking` (and
   `page` for 031). `php yii db/status` shows what is pending. Each migration has a down script.
2. Review the settings (Integrations): **Booking radius (km)** (Driver settings, 10 if empty), **In-city trip radius (km)** (optional extra limit,
   empty = off), **In-city boundary buffer (km)** (0 = the city map is exact) and **Return-to-pickup tolerance (km)** (Customer settings).
   In-city means inside the city's map; check that every city you serve has its service map drawn (a city without one is never sent to outstation).
   Look for state-wide or region-sized maps among the active cities (Maintenance of Halting drivers > City maps): a locality inside one would be
   priced and judged in-city by that whole region. Each locality needs its own map; remove the region outlines.
3. The dispatch cron in section 5 is still required, but a new booking is now offered immediately when it is made; the cron only reaches
   partners who come online or move into the radius later.
4. Push: in the OneSignal dashboard the Android app needs its FCM credentials. Without them OneSignal answers 200 and delivers nothing; the
   backend log (`push not delivered: ...`) says so. Check `runtime/logs/app.log` after the first booking.
5. `booking_track` grows by one row per accepted position (about 100,000 rows for a 15,000 km ride, a few MB). Delete the points of completed
   rides older than you need them for disputes (for example 180 days, like ride photos); `booking_route_log` keeps the route of every ride
   that was billed differently from the booking.
6. After deploying, run the re-test list in DEVELOPER_GUIDE.md section 12 (items 10-14) against a copy of the database. The apps need the matching
   builds (MOBILE_APP_CHANGES.md section O); old builds keep working but do not show the route card or the trip notice.

## 34. GitLab CI/CD (from 28 Oct 2026)

The code lives in `gitlab.com/rubia-services-group/rubia-backend-php` (private); GitHub stays as a second remote. `.gitlab-ci.yml` runs:

| Stage | Branches | What it does |
|---|---|---|
| lint | every push and merge request | `php -l` on `app`, `libs`, `services`, `customer`; `composer validate` (may fail) |
| build | `develop`, `staging`, `main` | `composer install --no-dev --optimize-autoloader`, kept one day as an artifact |
| deploy | `develop` -> dev, `staging` -> staging (both automatic); `main` -> production (**manual**: press play on `deploy:production`) | FTP upload of `app/` with `lftp`, newer files only, nothing is deleted on the server |

Never uploaded: `config/secrets.php`, `config/jwt-secret.php`, `runtime/`, `web/assets/`, `uploads/`, `uploads_bkp/`. Each server keeps its own.

Set `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR` under Settings > CI/CD > Variables, once per environment (scope `dev`, `staging`, `production`; password masked). A deploy stops with "Missing FTP_* CI/CD variables" if one is absent.

The pipeline does **not** run migrations (no SSH): run `php yii db/status` and `db/up` yourself after a release that adds one (section 3). Protect `main` and `staging` so only maintainers can deploy.

Status on 28 Oct 2026: the first pipelines on `develop`, `staging` and `main` failed without creating any job. A new gitlab.com account usually has to be verified (or have CI minutes) before jobs can run; check the pipeline page. The live site `rubia.services` was still a placeholder page answering 200 for every path (including `/account-deletion`) until the backend is deployed there with its document root on `app/web`; check the page shows the deletion form before submitting the URL to Google Play.

## 35. Partner payments and withdrawals (29 Oct 2026)

- **Gateways:** the partner app pays the joining fee, wallet top-ups and the subscription through **Razorpay or Cashfree**. The server decides the price (joining fee: Integrations > Driver settings; subscription: Plans) and
  confirms every payment with the gateway before applying it (`PaymentOrders::create/confirm`, `createCashfree/confirmCashfree`; rows in `payment_order`). Cashfree uses the keys and mode
  (`cashfree_pg_mode`: `test` or `production`) set in Integrations; set it to `production` on the live server. Set the Razorpay webhook (section 19) so a payment made just before the app closes is still applied;
  there is no Cashfree webhook yet.
- **Removed:** the manual UPI QR payment (`manual-payment-submit/status`, the approval list and the `company_upi_*` settings).
- **Withdrawals:** `driver/withdraw` needs the driver's registered bank account, enforces `min_withdraw` and whole rupees, and writes the bank into the request. The office pays it from **Payments > Withdrawals** and presses *Mark paid* with the bank
  reference, or *Reject* to return the amount. `driver/withdraw-info` feeds the app's Withdraw sheet.
- **Profile approval:** `user-verify-profile` no longer fails for a driver without a referral record.
