# customer-backend

The customer-facing endpoints of this service, moved out of `app/modules/customerapp`:

- `controllers/UserController`: the booking flow under `/api/user/*` (get-estimate, check-boundary, current-city,
  coupons-list, vehicle-types, apply-coupon, remove-coupon, assign-booking-to-driver (cron), cancel-ride,
  cancellation-charges, ride-info, add-review, rating-and-reviews, view-booking-details, pay-now,
  ride-request-active|completed|cancelled, my-referral-earings)
- `controllers/BookingController`, `WalletController`, `UserWalletController` (`/api/booking/*`, `/api/wallet/*`,
  `/api/user-wallet/*`; the customer wallet is switched off, see DEPLOY.md)
- `Module.php`: module `haltingdriver-customer`

The user app's own endpoints (login, profile, address book, notifications) stay in `app/modules/customerapp`.
In the shared process that controller extends this one; the Halting drivers entry answers `/api/user/*` with this module
only (`config/services.php`, `routes`).
