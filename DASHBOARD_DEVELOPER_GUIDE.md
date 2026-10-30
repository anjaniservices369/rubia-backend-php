# Rubia Dashboard Developer and Database Guide

For developers maintaining the Yii2 admin application, service dashboards, database schema
and dashboard tests. The repository root contains architecture and deployment references;
the application root is `app/`.

## 1. Dashboard architecture

- Runtime: Yii2 2.0.55, PHP 8.1+ (the developer environment is PHP 8.2), MySQL/MariaDB.
- Shared site/login: `app/controllers/SiteController.php`, `app/forms/LoginForm.php`,
  `app/config/rbac.php`, `app/views/layouts/`.
- Shared admin UI: `app/modules/admin/controllers/`, `models/`, `views/`.
- Driver-service dashboard: `services/haltingdriver/partner-dashboard/`.
- Shared dispatch and booking management: `modules/dispatch/services/BookingAdmin.php`;
  services are wired through the module/class-map setup.
- Service/API topology: `app/config/services.php`, `service-entry.php`,
  `dashboard-entry.php`, `api-routes.php`.
- ActiveRecord models generally live in `app/modules/admin/models`; generated base models
  are not the preferred place for hand-written behavior.

The current deployment can still be a single Yii application and shared database even though
services have isolated API entry points and separate dashboard sections. A service-specific
database is selected only when its `DB_DSN_<SERVICE>` environment variable is configured.
Do not assume the planned target architecture is already the deployed topology; see
[ARCHITECTURE_TARGET.md](ARCHITECTURE_TARGET.md).

## 2. Login and authorization boundaries

Dashboard login is a browser session, separate from mobile JWT/OTP authentication.

- `/site/login` and `/site/admin-login` use `app/views/layouts/main-login.php`; keep them
  independent of storefront scripts or hidden-on-load wrappers.
- `LoginForm` checks active users, the fixed super-admin email allowlist for admin accounts,
  blocked mobile-only roles, and IP-based failure throttling. Preserve these controls.
- `app/config/rbac.php` redirects an HTML guest request to sign-in while preserving the
  requested URL. A JSON/API request is forbidden, not redirected into an HTML form.
- The global operator gate admits admin/sub-admin/manager/franchise roles; it does not grant
  action access. Every admin controller still needs correct `AccessControl`/RBAC rules.
- Landing path is `/admin/dashboard` for admin/sub-admin/franchise, and
  `/admin/booking/index` for manager. The dashboard controller itself does not permit manager.
- Sign-out is POST/CSRF protected. Admin and sub-admin session duration is capped at eight
  hours in `LoginForm`.

Test guest redirect, deep-link `goBack`, access for each operator, rejected mobile roles and
role-specific denied actions separately. Never test access by elevating production users.

## 3. Service and database boundaries

`app/config/services.php` maps service IDs to module numbers and database environment prefixes:

| Service id | Module | Database prefix |
|---|---:|---|
| `customer` | 0 | `CUSTOMER` |
| `haltingdriver` | 1 | `HALTINGDRIVER` |
| `cartaxi` | 2 | `CARTAXI` |
| `autotaxi` | 3 | `AUTOTAXI` |
| `biketaxi` | 4 | `BIKETAXI` |

The common database comes from `DB_DSN`, `DB_USER`, `DB_PASSWORD` or the untracked
`app/config/secrets.php`. A service entry uses its own database only when
`DB_DSN_<PREFIX>` is set; it may additionally use `DB_USER_<PREFIX>` and
`DB_PASSWORD_<PREFIX>`. Do not print these values or put them in docs, logs, screenshots,
tests, or git. Never switch DSNs as a shortcut around missing access.

In the current shared schema, service-owned rows use `module` where supported. Important
groups:

| Domain | Representative tables |
|---|---|
| Platform/identity | `user`, `auth`, `auth_session`, `web_setting`, `city`, `services`, RBAC |
| Halting driver | `booking`, `booking_logs`, `accepted_bookings`, `driver_requests`, `driver_earnings`, `driver_wallet`, `driver_subscription`, `vehicle_verification`, `driver_booking_ratings`, `user_booking_ratings` |
| Taxi/Auto/Bike | `ride_request`, `taxi_driver_request`, `ride_earnings`, `taxi_payout`, `taxi_rating`, `driver_wallet`, `service_map` |
| Customer | `user_wallet`, `wallet`, `favourite_location`, `user_emergency_contacts`, `coupon`, `coupons_applied`, referrals, `account_deletion_request` |
| Shared billing/media | `service_billing_profile`, `service_invoice_counter`, `service_invoice`, `media`, payment logs/orders |

This is a navigation aid, not a complete schema definition. The owner and future target
ownership plan are in [ARCHITECTURE_TARGET.md](ARCHITECTURE_TARGET.md). Consult actual
migrations/models for column types, nullability, indexes and constraints; never infer a
foreign key or unique booking link from a matching driver, timestamp or amount.

The customer service API and dashboard use `DB_DSN_CUSTOMER`. The main admin dashboard reads
customer account-deletion audit metadata through the separate `customerDb` connection, also
configured by `DB_DSN_CUSTOMER`; keep that credential restricted to the required customer DB.

For financial views, keep service and module filters explicit. Driver-service booking gross,
GST/commission and driver net are distinct values. Taxi payout/earning tables are not
driver-service earnings. Wallet descriptions do not always contain a unique booking ID.

## 4. Schema change lifecycle

Schema changes use ordered SQL pairs under `app/database/migrations/`:
`NNN_name.up.sql` and, where a safe reversal exists, `NNN_name.down.sql`. Applied scripts
are recorded in `db_migration_log` by `DbController`.

Before any database command that can contact a real database:

1. Establish which console config/environment and DSN are active. Never print credentials.
2. Confirm explicitly that the database is a development/test database by host and database
   name; do not rely on a terminal title or stale shell variables.
3. Use only approved disposable test/e2e data for mutation tests. Production access requires
   an explicit deployment task and owner approval.

Safe schema workflow:

1. Write a numbered up/down pair, review impact, indexes/constraints, data backfill, and
   compatibility with code already deployed. If rollback cannot be safe, document that.
2. Test against a disposable local schema copy, including existing data and rerun/failure
   behavior where applicable. MySQL DDL is not transactional.
3. On the intended target, run `php yii db/status` and review every pending migration.
4. Take and verify an encrypted pre-change backup, schedule a maintenance window, and have a
   restore plan.
5. Only after explicit authorization, run `php yii db/up --yes`. This command runs *all*
   pending scripts in order; it is not a per-file apply command.
6. Re-run `php yii db/status`, inspect the migration log, and verify application/dashboard
   behavior and representative service financial totals.

`php yii db/up` without `--yes` lists pending changes (dry run). `php yii db/down --yes`
reverts only the most recently recorded migration and requires its down file. It is not a
general rollback strategy. If a script partly executes, stop and inspect the actual schema;
do not rerun or delete the log row blindly. Restore a verified backup or make a reviewed
forward repair.

For a backup, follow the deployment policy and use `mysqldump --single-transaction
--routines --triggers` as documented in [DEPLOY.md](DEPLOY.md). `--single-transaction`
protects transactional table reads; it does not make DDL transactional or replace restore
verification. Keep backup files encrypted, access-limited and outside the web root and git.

## 5. Read-only inspection and tests

Useful commands, from `app/`:

```powershell
php yii db/status
php yii audit/users
```

`db/status` reads migration state but connects using the configured target; verify that
target first. `audit/users` is a read-only account/orphan report. `audit/users --fix-safe`
performs writes and needs explicit approval and an isolated target/backup.

From the repository root, dashboard access regression coverage is:

```powershell
php app/tests/dashboard_access_test.php
```

It does not write database records. Booking expiry tests require an isolated local database;
the test guard expects an explicitly test/e2e-labelled DSN. Do not run tests with production
secrets or live SMS/push/payment providers. A fixture should rollback its rows and replace
notifications/payments with local recorders.

Separate evidence categories in reports:

- Schema/test-database assertions.
- Authenticated HTTP response checks.
- Actual shared-browser clicks/navigation.
- Real-device behavior.

A successful HTTP response is not proof that a browser rendered or clicked the UI. A
retrievable photo URL is not proof of compliant image content.

## 6. Implementing dashboard changes

1. Find the route/action and service owner first; trace controller, search model, ActiveRecord,
   view, authorization and neighboring tests before editing.
2. Use existing scoped queries and service helpers. Preserve city/module filters in lists,
   exports and detail pages; a broad query can disclose cross-service or cross-franchise data.
3. Escape display content with Yii helpers and validate/authorize every mutation server-side.
   UI hiding is not authorization. Keep mutating actions POST-only and CSRF protected.
4. Keep booking state changes in the dispatch/business service so logs, offers and
   notifications stay aligned. Do not make a completed ride editable for convenience.
5. For money, identify gross, tax, platform fee, net, payment state and ledger posting
   separately. Avoid duplicate charging on retries; preserve immutable audit history.
6. For one-to-many contacts or photos, fetch and display all authorized records. Count
   required distinct photo slots rather than rows.
7. Test the actual role and boundary case. Prefer a local fixture; never mutate production
   customer, driver, wallet or booking data to validate a screen.
8. Run PHP lint and the smallest focused test. After code changes run from `app/`:

```powershell
php yii docs/annotate --apply
php yii docs/reference
php yii docs/check
```

New classes in `libs/platform` require `php yii service/pack --lib=platform`. Database
changes must follow `app/database/README.md` and the migration workflow above.

## 7. Dashboard/browser validation checklist

- Use the exact host and the intended shared-browser tab; browser profiles/cookies are not
  shared automatically.
- Confirm signed-out deep-link redirect and successful return, wrong-password feedback,
  session persistence after reload and POST logout.
- Confirm list filters, pagination, service/city scope, record detail, closed state and role
  restrictions. Do not force-click hidden or off-viewport controls to report success.
- Confirm the booking reference, vehicle, contacts, payment state, gross/net figures, history
  and photo links against the same isolated database record.
- Do not confuse a successful login with authorization to every dashboard page.
- Report blockers and pre-existing missing history explicitly; do not silently backfill
  evidence for a test.

For actual operator procedures and the verified local booking example, see
[DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md). For owner approval, reports and business-safe database
governance, see [DASHBOARD_OWNER_MANUAL.md](DASHBOARD_OWNER_MANUAL.md).
