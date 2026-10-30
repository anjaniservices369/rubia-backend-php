# Rubia Dashboard Guide

For owners, operators, testers and developers. Last updated: 30 October 2026.

This guide covers the Yii2 admin dashboard and the driver-service workflow. Taxi, Auto and
Bike are separate services: their ride records, wallets, earnings and filters must not be
mixed with halting-driver bookings.

## 1. Access and sign-in

Open `/site/login` on the intended dashboard host. `/site/admin-login` is the email-validated
alternative sign-in form. Use an active dashboard operator account, not a customer or driver
mobile number/OTP account.

- A signed-out request to `/admin/*` redirects to sign-in. A normal HTML GET preserves the
  original URL so successful login can return to that booking or other protected page.
- Without a saved destination, admins, sub-admins and franchise users go to
  `/admin/dashboard`. Managers go to `/admin/booking/index`; the administrator dashboard
  itself does not allow managers.
- The login page uses its own layout and installed Bootstrap assets. It does not depend on
  storefront sliders, chat widgets or a hidden page waiting for third-party scripts to load.
- Wrong credentials show `Incorrect username or password.` Active-account checks, the
  super-admin allowlist and IP-based failure throttling remain in effect.
- Admin and sub-admin persistent login duration is capped at eight hours by the login form,
  regardless of Remember Me. Other permitted roles use the existing remember/session policy.
- Sign Out uses POST. A subsequent protected HTML request should return to sign-in.
- Separate browser profiles, the integrated browser, desktop browser and HTTP tools have
  separate cookies. Logging in to one does not authenticate another.

Never put passwords, reset tokens, session cookies or integration secrets into this guide,
issue comments, screenshots or committed test files.

### Roles are not interchangeable

The global dashboard gate recognizes admin, sub-admin, manager and franchise operators.
Each controller still decides which actions a role can use; menu visibility is not proof
that an action is authorized.

Admins/sub-admins can use booking management actions where the booking is open. Manager and
franchise routes have narrower action lists and city/service filtering. Do not promote an
account or disable a filter to work around an access error. Customer, Driver and taxi
mobile-only accounts remain excluded from dashboard access.

## 2. Daily operational checklist

1. Confirm the host/environment before making any change. Local test data is not production.
2. Check the service section and city scope of the signed-in operator.
3. Review waiting, accepted, pickup, started and completed booking lists.
4. Before offering a test ride, confirm the driver is online, the subscription covers the
   pickup date, and wallet balance meets the configured minimum and is not negative.
5. Review incomplete profiles/documents and subscriptions separately: profile approval alone
   does not establish that a driver is eligible for a ride.
6. Reconcile collections, earnings and wallet entries without changing history to make
   numbers appear to match.
7. Sign out when finished. Do not leave a test driver online unnecessarily.

## 3. Finding driver-service bookings

Under the halting-driver section, Bookings provides:

- **Available Bookings:** `/admin/booking` (waiting for driver).
- **Driver Accepted:** `/admin/booking/driver-accepted`.
- **Go For Pickup:** `/admin/booking/go-for-pickup`.
- **Arrived Location:** `/admin/booking/arrived-location`.
- **Start Ride:** `/admin/booking/start-ride`.
- **Completed Ride:** `/admin/booking/completed-ride`.
- **Cancel Ride:** `/admin/booking/cancel-ride`.
- **Auto Canceled Bookings:** `/admin/booking/auto-canceled-bookings`.
- **Cancellation Charges:** `/admin/cancel-ride-charges`.
- **Payments:** `/admin/payments`.

Filter by booking id/reference where the grid provides it; open the booking's View action.
The detail route is `/admin/booking/view?id=<id>`. A useful test filter is
`/admin/booking/completed-ride?BookingSearch%5Bid%5D=1698`.

### Status interpretation

Driver-service status values are: waiting 1, accepted 2, rejected 3, go-for-pickup 4,
OTP-verified arrival 5, started 6, completed 7, customer-cancelled 8, auto-cancelled 9,
driver-cancelled 10, admin-cancelled 11. The driver's combined accepted-list selector 12
is a list grouping, not a persisted ride state.

Unanswered waiting bookings expire at pickup cutoff. They should disappear from available
lists and remain in cancellation history. An accepted/started booking must not be expired
merely because its pickup time has passed.

Arrival notification is distinct from OTP verification. The customer sees the ride code
after `status=4` plus `notif_status=1`, or after verification at status 5; acceptance or
travel toward pickup alone must not reveal it.

## 4. Booking detail and safe management

Inspect:

- Booking id/reference, created time and status.
- Customer and driver identity, account state and contact linkage.
- Pickup date/time, addresses, vehicle model and entered vehicle number.
- Estimate, final fare, GST, commission and driver net earnings.
- Payment state, driver request, both reviews and vehicle-verification images.
- History entries and their timestamps.

For open bookings, authorized management controls include cancel with reason, offer again,
assign driver and set permitted ride state. These actions must use the existing management
service so offers, notifications and history remain consistent.

Completed bookings show a closed-booking notice instead of the operational management
forms. Do not test Delete, Update, Block, cancellation, reassignment, reopening or financial
mutations on retained completed evidence. Completion should occur through the driver app
so fare and commission records are created.

**Known history limitation:** booking 1698 has a persisted start timestamp but no separate
Ride Started entry in `booking_logs`. Acceptance, pickup, arrival and completion are present.
Do not infer a missing trip from a missing history row, and do not claim that the history
trail is complete. This guide does not backfill or repair that record.

## 5. Financial registry and reconciliation

- **Driver Earnings:** `/admin/driver-earnings/index`.
- **Driver Wallet:** `/admin/driver-wallet/index`.
- **Active Subscriptions:** `/admin/driver-subscription/index`.
- **Inactive Subscriptions:** `/admin/driver-subscription-inactive/index`.
- **Plans:** `/admin/plans`, where the operator is authorized.

For a cash ride, customer collection and driver net earnings are different numbers.
The completed Collect fare card uses server `price_detail.estimatePrice` plus pending
cancellation charges, matching the bill/collection screen. Driver net is the amount
remaining after applicable platform deductions, not the amount to ask the customer for.

Reconcile one earning record per ride, gross customer amount, GST/commission, net driver
amount, payment status and wallet ledger entries. Check the correct service/module.
Do not subtract a coupon twice or count a prior successful ride's debit as a duplicate.

Some driver wallet reasons contain no booking id (for example
`Admin earnings debit 26 and gst is 13`). Amount/time/driver matching was sufficient for
the isolated test, but is not a robust unique linkage for concurrent production activity.
Do not claim stronger traceability than the stored fields provide.

## 6. Profiles, photos and eligibility

Customers-profile is under `/admin/users`; a legacy detail route is
`/admin/users/view?id=<id>`. It now displays all emergency contacts, not just the first.
Drivers-profile is `/admin/users/drivers`; inspect the full onboarding profile, documents,
bank/UPI information, approval state, subscription and wallet separately.

Vehicle verification requires before and after stages, each with a selfie and four vehicle
photo slots. In stored records, stage `type` is 1 before / 2 after, and `image_type` values
1, 2, 3, 4 and 6 cover the required slots. Optional damage images are separate evidence.

Count distinct stage/slot combinations, not just rows: recapture can leave extra image
records. Check image availability and inspect content before treating images as identity
or vehicle-condition evidence. An uploaded file is not proof of a valid inspection.

## 7. Configuration boundaries

The main dashboard exposes All services, service-specific maps/operations/billing and
Dashboard Integrations according to role and deployment scope.

- Integration keys belong in Integrations, never source code or documentation.
- Map provider order comes from `maps_provider_order`: Ola, Google, Mapbox, OpenStreetMap.
- City and city-rate-card settings affect eligibility and pricing; review the service and
  city before editing.
- A zero joining fee or satisfied fee flag skips driver payment checkout; approval remains
  a separate requirement. Do not label profile approval as a successful payment.
- Terms/policies, banners, coupons and franchise menus depend on dashboard scope and role.

For pricing, integrations and multi-service business rules, consult
[BUSINESS_GUIDE.md](BUSINESS_GUIDE.md). For deployment/environment separation, use
[DEPLOY.md](DEPLOY.md).

For owner workflows and database-change approvals, see
[DASHBOARD_OWNER_MANUAL.md](DASHBOARD_OWNER_MANUAL.md). For controller, schema, migration
and developer testing details, see [DASHBOARD_DEVELOPER_GUIDE.md](DASHBOARD_DEVELOPER_GUIDE.md).

## 8. Verified local example: booking 1698

Reference **BK-261030-3RZH7U**, vehicle **E2E1698**, created 30 October 2026 at 10:53:48,
pickup 11:00, accepted 10:54:47, started 11:02:50, completed 11:06:29.

The customer used a Realme and the driver a Redmi. The apps captured/uploaded real camera
files and performed start/end, test cash confirmation and both five-star labelled reviews.
This was a zero-distance stationary workflow test, not a driven trip or actual cash exchange.

Verified records:

- Completed status 7 and paid status 1; correct customer/driver and one completed driver offer.
- Gross INR 273, GST INR 13, platform commission INR 26, driver net INR 234.
- One earning record and a single INR 39 successful debit for this isolated ride.
- An approved synthetic INR 100 credit changed the previous INR -39 wallet to INR 61 before
  booking. After the new debit, wallet was INR 22; the driver was taken offline afterward.
- Ten distinct photo slots, eleven retained camera records including an extra after/back
  capture; all eleven were retrievable and linked from the dashboard.
- Both reviews and both customer emergency contacts persisted.

Twelve ride reconciliation checks and ten additional registry checks passed against the
isolated local database. Authenticated HTTP checks passed for completed-list filtering,
booking detail, closed-management notice, earnings list/detail, wallet detail and contacts.
The shared browser rejected an incorrect password, signed in successfully, returned to the
protected booking and retained the session after reload. It displayed the vehicle number,
driver-net heading and closed-booking notice. Sign Out worked after expanding the sidebar;
opening the protected booking again redirected to the visible login form. These checks do
not certify every dashboard menu or every role through the browser.

No live gateway payment, production SMS/push delivery, new registration replay or image
content compliance was certified in this repeat cycle.

## 9. Troubleshooting and verification

**404 when signed out:** open `/site/login` on the same host/browser. Current access handling
redirects guests rather than hiding the login requirement. A signed-in, unauthorized role
can still receive a denial; verify its assigned permissions instead of bypassing them.

**Login cannot be clicked or stays hidden:** the authentication pages must use the dedicated
layout, not the public storefront's load-dependent hidden wrapper. Inspect browser errors
and verify the installed Bootstrap/jQuery asset bundle is served.

**Wrong password or throttling:** verify the operator username and active state. Use the
normal password-reset process; never grant a dashboard session from a mobile OTP.

**Login works in another window only:** authenticate/share the same browser session that is
being tested. Different host names such as localhost and 127.0.0.1 also have separate cookies.

**Missing image:** check the stored URL, local/server storage and response content type,
then the dashboard linkage. Do not replace missing evidence with fixtures without labelling it.

**Financial mismatch:** reconcile ids, service, dates, status and successful ledger entries.
Keep gross collection separate from driver net. Report missing linkage or duplicate rows.

Developer checks from the repository root:

```powershell
& 'D:\xampp\php\php.exe' app\tests\dashboard_access_test.php
```

The access regression test performs no database writes. It covers guest redirects, saved
deep links, operator roles, landing pages, mobile-role rejection and page-level restrictions.
Run booking-expiry/database tests only with an explicitly verified isolated local test DSN.

After code changes, run from `app/`:

```powershell
php yii docs/annotate --apply
php yii docs/reference
php yii docs/check
```

## 10. Dashboard agent and maintenance

Choose **Rubia Dashboard** in the VS Code agent picker. Its workspace definition is
[rubia-dashboard.agent.md](.github/agents/rubia-dashboard.agent.md).
Use it for dashboard bugs, login/role/session behavior, admin UI, browser checks, registry
reconciliation and documentation. The broader
[Rubia Backend agent](.github/agents/rubia-backend.agent.md) covers other backend/API work.

Keep this guide synchronized with [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) and
[MOBILE_APP_CHANGES.md](MOBILE_APP_CHANGES.md). Report database, HTTP, browser and device
coverage separately. Do not store credentials, database exports or personal camera files
beside these documents.
