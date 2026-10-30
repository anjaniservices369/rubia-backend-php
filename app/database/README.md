# Database changes

Versioned, ordered scripts in `migrations/` (`NNN_name.up.sql` and `NNN_name.down.sql`), applied with the
console runner. Applied scripts are recorded in the table `db_migration_log`.

```
php yii db/status          # applied / PENDING
php yii db/up --yes        # apply all pending, in order (without --yes it only lists them)
php yii db/down --yes      # revert the most recently applied script
```

## Scripts

| # | Script | What it does |
|---|---|---|
| 001 | `001_media_and_page_tables` | Creates `media` (uploaded images/documents) and `page` (CMS pages) if they do not exist. Safe to re-run. |
| 002 | `002_taxi_naming` | Brings stored names in line with the code (skipper -> taxi): see below. |
| 003 | `003_booking_number_repair` | Gives every booking that has an empty `booking_number` a proper one. New bookings get their number from the application (`BK-yymmdd-XXXXXX`). |
| 004 | `004_onesignal_settings` | Adds the OneSignal App ID / REST API key rows to Web Settings and relabels the old Firebase push key. |
| 005 | `005_admin_reminder_keystore` | Adds one dashboard reminder (Notifications list) to back up the Partner app signing keystore. Safe to run twice; `down` removes it. |
| 006 | `006_remove_firebase_setting` | Deletes the stored Firebase legacy push key (`user_notification_key`) from Web Settings. `down` restores an empty row. |
| 007 | `007_taxi_defaults` | Adds the taxi settings the code reads (`cgst`, `sgst`, `auto_dispatch_type`, `auto_dispatch_radius`, `auto_dispatch_wait_time`) and a default commission/fare row (copied from Hyderabad's) for every city with active pincodes that had none. **Review the rates.** `down` removes exactly what it added. |
| 008 | `008_pincode_area` | Creates `pincode_area`: where each pincode is on the map (looked up once on Google), used by the dashboard's "Pincode map" to build a city's service area. |
| 009 | `009_map_log` | Creates `map_log`: the history of changes made on a city's Map page (pincodes added / removed / moved, pricing copied, franchise assigned). |
| 010 | `010_service_modules` | One map per service module: `service_map` (module, city, outline), `service_map_pincode` (halting drivers service pincodes), a `module` column on `ride_earnings`, `driver_earnings`, `taxi_payout` (filled for history), the halting drivers service's existing city outlines become its maps, and auto/taxi copies of the default fares. |
| 011 | `011_map_log_module` | Adds `module` to `map_log`, so the history shows which service a change belongs to. |
| 012 | `012_wallet_per_service` | `driver_wallet.module`: every wallet entry belongs to a service (history filled from the driver's role / vehicle type), and the off-by-default switches `wallet_min_enforced_2/3/4` for the taxi services' minimum wallet balance. |
| 013 | `013_service_billing` | Billing of each service on its own: `service_billing_profile` (legal name, GSTIN, PAN, address, invoice prefix per service), `service_invoice_counter` (own number series per financial year) and `service_invoice` (one invoice per completed ride, with CGST / SGST split). |
| 014 | `014_separate_customer_side` | Customer wallet, coupons, coupon use and referral rewards become per service (`module` columns, history filled as the halting drivers service), and per-service referral reward settings. |
| 015 | `015_user_uuid` | Every account gets a UUID (`user.uuid`, unique; trigger sets it on insert; existing accounts backfilled): the identity that is the same in every service and the user app. |
| 016 | `016_razorpay_payments` | Razorpay payment support. |
| 017 | `017_cashfree_settings` | Cashfree payment settings. |
| 018 | `018_media_object_storage` | Media object-storage support. |
| 019 | `019_company_upi` | Company UPI settings. |
| 020 | `020_documents_for_life` | Driver document validity settings. |
| 021 | `021_documents_bucket_key` | Document bucket/key storage fields. |
| 022 | `022_integration_keys` | Integration-key storage. |
| 023 | `023_media_booking` | Booking linkage for media records. |
| 024 | `024_expire_ended_subscriptions` | Expiry of ended subscriptions. |
| 025 | `025_admin_audit_log` | Admin audit-log storage. |
| 026 | `026_twilio_rename` | Twilio setting rename. |
| 027 | `027_twilio_email_api` | Twilio email API settings. |
| 028 | `028_twilio_api_key` | Twilio API key setting. |
| 029 | `029_gmail_smtp` | Gmail SMTP settings. |
| 030 | `030_email_smtp` | Email SMTP settings. |
| 031 | `031_page_app_key` | App key for page records. |
| 032 | `032_booking_route_log` | Booking route-log storage. |
| 033 | `033_booking_radius_alert` | Booking radius-alert settings. |
| 034 | `034_booking_live_track` | Booking live-tracking storage. |
| 035 | `035_pincode_map` | Pincode map support. |
| 036 | `036_account_deletion_request` | Account-deletion request records. |

Migration 036 belongs on the customer service database. When the customer database is configured
with `DB_DSN_CUSTOMER`, inspect it with `php yii db/status --customer=1`. Apply only migration
036 with `php yii db/apply 036_account_deletion_request --customer=1 --yes` after the normal
backup and approval checks. This avoids applying unrelated pending platform migrations to the
separate customer database.

This index is a short orientation, not a substitute for reviewing the SQL. Always read the
exact `.up.sql` and `.down.sql` files for the intended schema before applying a change. Some
later migrations do not have a down script; `db/down` cannot reverse those changes.

### 002 in detail

| Before | After |
|---|---|
| table `skipper_payout` | `taxi_payout` |
| table `skipper_rating` | `taxi_rating` |
| `skipper_rating.skipper_id`, `reject_log.skipper_id` | `taxi_driver_id` (indexes renamed) |
| `skipper_rating.skipper_discription` | `driver_discription` |
| `user.user_role = 'skipper'` | `'taxi'` |
| `user.username = '<number>@skipper'` | `'<number>@taxi'` |
| `auth.source = 'skipper'` | `'taxi'` |
| web setting `skipper_cancellation_charge` | `taxi_cancellation_charge` |
| web setting `skipper_mim_wallet_balance` (typo) | `taxi_min_wallet_balance` |

Not changed on purpose: the image URLs stored in `driver_details`/`user` (old Firebase links that are dead anyway)
and the history text in `fcm_notification.description`.

## Deploying to the live database

The application reads these names, so the database change and the code release must go out together.

1. Back up: `mysqldump --single-transaction --routines --triggers <db> > before_taxi_rename.sql`
2. Short maintenance window (the apps are read/write against `user`, `auth`, `reject_log`, ratings).
3. Deploy the code of this release and run `php yii db/up --yes` straight after.
4. Check: `php yii db/status` shows both scripts applied; log in as a taxi driver and as a rider; place and accept a test ride.

Rollback: restore the backup, or `php yii db/down --yes` and deploy the previous code release.

Notes
- MySQL DDL is not transactional: if a script stops half way, restore the backup, fix, and run again.
- Existing logged-in taxi drivers keep working: their session is a JWT/`auth_session` row, not the role string.
- `media_table.sql` and `page_table.sql` in this folder are the original standalone versions of script 001.

## Data audit

```
php yii audit/users               # read-only report on accounts and rows that point at missing users
php yii audit/users --fix-safe    # also: set a missing role from the @driver/@taxi username suffix,
                                  #       turn the text "null"/empty e-mail into NULL
```

The report never deletes anything. Orphaned rows (rides, wallets, ratings, notifications that reference users
that no longer exist) and duplicate accounts need a business decision, so they are listed, not changed.
Accounts are removed from the admin panel by marking them deleted (status 2), which keeps those rows valid.