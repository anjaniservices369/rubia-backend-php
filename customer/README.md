# Customer app (customer) - a service of its own

The customer (user) app as a system of its own: its own backend, database, dashboard, authentication and notifications.
Every other service asks it (the gateway) who a customer is, never reads a customer record of its own.

| Folder | What |
|---|---|
| `backend/modules/customerapp` | the app's API: `/api/user/*` login (OTP, JWT), logout, profile, emergency contacts, favourite locations, referral code, notifications, banners, service list (`UserController` + `UserAppActions`), `CustomerReport` |
| `backend/modules/customerapi` | the gateway the services call: `profile`, `notify`, `authenticate` (signed calls only) |
| `models` | models of the customer app's own tables (banners, pages, services catalogue, favourite locations, emergency contacts, customer wallet) |
| `dashboard` | its admin screens (banners, categories, pages, services, favourite locations, emergency contacts, notifications, customer wallet) |
| `database/schema.sql` | structure of its database (`rubia_customer`) |
| `release/manifest.json` | its release; `php yii service/release --service=customer [--profile=api]` |
| `web/`, `mobile/` | the customer web and mobile app projects (separate repositories; not here) |

Entry points: `app/web/customer.php` (API), `app/web/customer-dashboard.php` (dashboard). Environment: `DB_DSN_CUSTOMER`,
`DB_USER_CUSTOMER`, `DB_PASSWORD_CUSTOMER`, `CUSTOMER_API_SECRET_<SERVICE>` for every service that calls it,
`ADMIN_SECRET_CUSTOMER`, `JWT_SECRET`, `COOKIE_VALIDATION_KEY`, the push / SMS keys of the customer app.
Data: `php yii split/customer --target=rubia_customer --apply` then `php yii split/complete --target=rubia_customer --service=0`.
