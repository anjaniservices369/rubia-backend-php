# Rubia Dashboard Owner Manual

For owners and authorized operations leads. This guide explains what to check in the
dashboard and how to govern its database safely; it is not a database-administration
runbook. Do not edit records directly in SQL to correct an operational screen.

## 1. What the dashboard controls

The admin dashboard is the browser-based control plane for service operations and
configuration. Use the service selector and city scope to distinguish Halting Drivers,
Car Taxi, Auto Taxi and Bike Taxi. A shared login does not mean that the services have
interchangeable bookings, wallets, earnings or fare settings.

Owner-facing areas include:

- **Services and dashboard:** service overview, activity and permissions.
- **Bookings:** waiting offers, accepted, pickup, arrived, started, completed and cancelled
  halting-driver bookings.
- **Customers and drivers:** profile, contacts, documents, driver status and eligibility.
- **Subscriptions and plans:** subscription period and onboarding/payment state.
- **Billing and wallets:** service-specific gross collections, fees, driver earnings and
  wallet ledger entries.
- **Maps, cities and pricing:** service areas, city settings and fare/commission configuration.
- **Integrations and Web Settings:** operational provider configuration and site settings.
- **Terms, policies and content:** pages, banners and other content available to the role.

The menu is role-sensitive. A visible menu item is not proof that the signed-in person is
authorized for every action. Admin, sub-admin, manager and franchise accounts have different
controller and city/service limits. Use named staff accounts and grant only the needed role.

## 2. Sign-in and staff safety

Open `/site/login` on the correct host. Use an active dashboard operator account. Customers
and drivers sign in to their mobile apps and must not be used as dashboard accounts.

- If you open a protected dashboard link while signed out, login should redirect back to that
  link after successful authentication.
- A default admin, sub-admin or franchise landing page is `/admin/dashboard`; a manager's
  default is `/admin/booking/index`.
- Admin and sub-admin sign-in sessions are capped at eight hours, even if Remember Me is
  selected. Sign Out when work is complete, especially on a shared device.
- Repeated password failures are throttled. Use the normal reset process; do not share
  passwords or use another employee's session.
- A signed-in but unauthorized role must be granted the correct permission by an authorized
  administrator; do not bypass the access control.

Never put passwords, reset tokens, session cookies, API keys or personal data in spreadsheets,
screenshots, tickets or public documents.

## 3. Daily operations

### Before a test or operational booking

1. Verify the dashboard host and environment. Do not confuse a local/test booking with a
   production booking.
2. Select the correct service and city; confirm the driver belongs to that service and area.
3. Check that the driver is online, the subscription covers the pickup date, required profile
   and documents are approved, and wallet balance satisfies the configured minimum.
4. Confirm the customer-visible booking reference, pickup time, vehicle/service and fare.
5. Use a user-authorized real test workflow or an approved isolated fixture. Do not generate
   a fake customer charge or turn a real driver online without approval.

### During and after a halting-driver booking

Track the booking state from waiting to accepted, go-for-pickup, OTP-verified arrival,
started and completed. A waiting booking past pickup cutoff is auto-cancelled; a started
booking is not a stale waiting offer. The pickup OTP is protected until arrival is
notification-confirmed or verified.

At completion, reconcile:

- Customer gross amount and payment state.
- GST, platform fee/commission and any cancellation-charge settlement.
- Driver net earnings and one corresponding earning row.
- Successful wallet ledger entries and resulting balance.
- Vehicle number, inspection image slots, customer/driver reviews and contact records.
- Booking history timestamps, noting that a missing log row does not by itself prove a
  ride did not occur.

Gross collection and driver net are different amounts. For example, the tested ride 1698
had gross INR 273 and driver net INR 234; do not ask the customer to pay the net amount.
Some wallet ledger descriptions have no booking ID, so amount/time/driver matching is not a
globally unique link.

## 4. Owner review of records and financial reports

- Compare records only within the same service, date range, city and currency.
- Distinguish an estimate from the final fare, a completed ride from a paid ride, and a
  cash collection from a wallet ledger entry.
- Treat cancelled, refunded, pending, failed and successful transactions as different
  states; reconcile only successful entries when calculating posted balances.
- Review driver wallet entries and earnings statements together, not as interchangeable
  totals. A wallet balance is the ledger result, not a manually editable note.
- Check driver approvals, subscription and wallet separately. Approval does not prove a
  subscription payment occurred; a zero joining fee can correctly skip checkout.
- Check emergency contacts as a list; customers may have more than one.
- Count distinct before/after inspection slots, not only photo rows. Extra recaptures can
  create additional records.

If a number is inconsistent, preserve the current records, record the booking/payment
references and time range, and escalate to the dashboard/backend developer. Do not cancel,
reopen, reassign, delete or manually edit a completed ride to force totals to match.

## 5. What the database represents

The deployed application may use one shared MySQL/MariaDB schema or service-specific
schemas. The configured database target is authoritative; never assume a service has its
own schema just because it has a separate menu or API entry point.

Important record groups include:

- **Platform and identity:** `user`, `auth`, `auth_session`, `web_setting`, city/service
  configuration and RBAC records.
- **Halting-driver bookings:** `booking`, `driver_requests`, `booking_logs`,
  `driver_earnings`, `driver_wallet`, `driver_subscription`, `vehicle_verification` and
  rating/contact tables.
- **Taxi/Auto/Bike rides:** `ride_request`, `taxi_driver_request`, `ride_earnings`,
  `taxi_payout`, `taxi_rating` and `driver_wallet` rows tagged by service module.
- **Payments and files:** payment logs/orders, media and service invoice records.

Rows tagged with a `module` are service-scoped and must not be mixed in reports. This
database topology and table ownership are described for developers in
[DASHBOARD_DEVELOPER_GUIDE.md](DASHBOARD_DEVELOPER_GUIDE.md) and
[ARCHITECTURE_TARGET.md](ARCHITECTURE_TARGET.md). Table names are technical identifiers,
not instructions to edit those tables directly.

## 6. Database administration: owner responsibilities

Owners approve the maintenance window, responsible operator, impact and rollback/restore
plan. A developer/DBA should execute schema changes.

1. Identify the exact environment and database owner. Confirm the configured DSN points to
   the intended target without exposing its password.
2. Arrange an encrypted, access-controlled pre-change backup and confirm a tested restore
   procedure in a non-production environment.
3. Ask the developer to review the numbered migration, data impact, rollback limitations and
   application compatibility.
4. Schedule a maintenance window when changes affect shared booking, user, payment or wallet
   records. Do not apply DDL while assuming it can be rolled back transactionally.
5. Require post-change checks for applied migration status, dashboard sign-in, service
   booking visibility, one representative financial reconciliation and app/API health.
6. Preserve audit and backup retention according to the organization's policy and applicable
   law. Do not send customer records or unencrypted database backups through email/chat.

The console's `db/status` and `db/up` commands use the configured `Yii::$app->db`; running
them against the wrong environment can change the wrong database. A pending migration is
not approval to run it. `db/up --yes` applies all pending scripts, not a selected one.
MySQL DDL is not reliably transactional. Do not use `db/down --yes` as a casual undo; some
scripts have no down file and some data changes require restore or a forward repair.

Read-only account audit is `php yii audit/users`. The optional `--fix-safe` mode writes
repairs and needs an approved target and backup. Orphaned financial, booking, rating and
notification rows require a business decision and must not be deleted to silence an audit.

## 7. Escalation and further references

- Dashboard routes, login, booking states and troubleshooting:
  [DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md).
- Developer/test/migration procedures:
  [DASHBOARD_DEVELOPER_GUIDE.md](DASHBOARD_DEVELOPER_GUIDE.md).
- Business definitions and pricing: [BUSINESS_GUIDE.md](BUSINESS_GUIDE.md).
- Deployment, environment settings and maintenance:
  [DEPLOY.md](DEPLOY.md) and [app/database/README.md](app/database/README.md).

When escalating, report environment, service, booking/payment reference, date range, expected
versus observed value and dashboard page. Exclude credentials, auth tokens, full contact
details and unredacted customer exports.
