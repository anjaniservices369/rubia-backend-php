# Rubia backend update — what the mobile apps must change

Written for: the developers/sessions updating the **Customer app** and the **Partner (driver) app**.
Date of backend change: 2026-10-12. Base URL and endpoint paths are unchanged.

Three things changed on the server that the apps can see:

1. `auth_code` is now a signed JWT (was a random 32-char string).
2. Firebase Cloud Messaging is no longer used by the backend. Push is sent through a provider-neutral
   sender (OneSignal or Expo, chosen server-side).

Nothing else in the request/response shapes changed.

---

## A. Changes that apply to BOTH apps

### A1. `auth_code` format changed (must-do)

- Returned by `verify-otp` as `auth_code`, sent on every authenticated call exactly as before
  (HTTP header `auth_code`; query/post param `auth_code` is also accepted).
- New value looks like `eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5Iiw...<~209 chars total>`:
  three base64url parts separated by dots, characters `A-Z a-z 0-9 - _ .`
- **Remove any validation/storage assumption** that the code is 32 characters or alphanumeric only
  (column sizes, regexes, secure-storage key limits, "length == 32" checks).
- Treat it as an opaque string. Store and send it unchanged.
- Optional: the middle part decodes to `{sub: userId, iat, exp, jti, r: role}`. `exp` is 90 days after
  login. Do not use it for authorization decisions on the device.

### A2. Everyone must log in again (must-do)

- All previously issued `auth_code` values are rejected by the server.
- The server does **not** return HTTP 401. It returns HTTP 200 with:
  `{"status":"NOK","error": "User Not Found" | "User not found" | "Session Not Found" | "session not found" | "Auth code not found"}`
  (the error is sometimes a string, sometimes an array of strings).
- Add one central response handler: if a request that carried an `auth_code` comes back with
  `status == "NOK"` and one of those errors, then
  1. delete the stored `auth_code` and cached user,
  2. send the user to the login (phone + OTP) screen.
- The same handling covers a token that has expired (90 days) or been revoked by a newer login
  (a new login replaces the previous session for that user).

### A3. Push notifications: Firebase removed (must-do before push works again)

- The old Firebase push API was shut down by Google in 2024, and the backend no longer uses it.
  Push currently sends **nothing** until a provider is selected on the server (`PUSH_DRIVER`).
- Provider to be confirmed by the backend owner: **OneSignal** (recommended) or **Expo Push**.
  The apps must register with whichever is chosen and send that provider's id as `device_token`:

| Provider  | Value the app must send as `device_token`            |
|-----------|-------------------------------------------------------|
| OneSignal | OneSignal **subscription id** (`OneSignal.User.pushSubscription.id`) |
| Expo      | `ExponentPushToken[...]`                              |

- Remove the Firebase Messaging / FCM SDK and its token handling; add the chosen provider's SDK.
  Remove `google-services.json` / `GoogleService-Info.plist` if no other Firebase feature
  (Analytics, Crashlytics, Auth) is used.
- Where to send the token (unchanged endpoints, same field names):
  - at login: `verify-otp` fields `device_token`, `device_type` (unchanged);
  - whenever the provider id changes or becomes available after login:
    `POST .../check` with header `auth_code` and body `AuthSession[device_token]=<id>`.
- Notification content the server sends: `title`, `body`, plus data keys
  `order_id` and `type` (ride/booking events) and, for custom notifications, `url`, `img_url`.
  Keep your existing tap-handling logic for these keys; values are strings.
- In-app notification lists (`list-fcm-notifications`, `clear-notifications`, etc.) are database
  backed and unchanged.

### A4. OTP flow (no change in shape)

- `send-otp` (POST `contact_no`) → returns provider details including the session id.
- `verify-otp` (POST `contact_no`, `session_code`, `otp_code`, `device_token`, `device_type`) → returns
  `auth_code` and `details`.
- The server now calls the SMS provider over HTTPS and logs failures. If the SMS gateway is down the
  response is `status: NOK, error: "OTP failed"`. Show a retry message.

---

### A5. Image / document upload: Firebase Storage removed (must-do)

Today the apps upload files straight to Firebase Storage and then post the resulting URL
(`profile_image`, `aadhar_card_front/back`, `pan_card`, `driving_license_front/back`,
`vehicle_verification` images, RC / chassis proofs, ...). The backend only ever stored that URL string.

**Existing Firebase links are dead.** A sample of 20 stored links all return HTTP 404, so the old images
cannot be migrated. Partners must re-upload documents (the app should treat a failed image load as
"missing" and prompt for re-upload).

New flow (the other endpoints and field names are unchanged; only where the file goes changes):

1. Upload the file to the backend:

   ```
   POST /api/media/upload
   Header:  auth_code: <jwt>
   Body:    multipart/form-data, field "file"
            (alternative: form field "image_base64" with raw or data-URI base64, optional "file_name")
   ```

   Success: `{"status":"OK","url":"https://<host>/api/media/view?id=<id>","id":"<id>","mime":"image/png","size":162}`
   Failure: `{"status":"NOK","error":"..."}` (not logged in, wrong type, too large, ...).

2. Send the returned `url` in the existing field of the existing endpoint, exactly where the Firebase
   URL used to go (for example `upload-profile-picture` with `profile_image=<url>`,
   `add-aadhar`, `add-pan-card`, `driving-license`, `vehicle-verification`, ...).

3. Display images by loading that `url` directly (no auth header needed; the 128-bit id in the URL is the
   access token, same model as Firebase download URLs). Responses are cacheable for one year; uploading the same\r\n   file again returns the same URL.

Rules enforced by the server:

| Rule | Value |
|---|---|
| Types | JPEG, PNG, WebP, GIF, PDF (content is sniffed, the extension/Content-Type is not trusted) |
| Max size | 10 MB per upload. The server downscales images to at most 1600 px on the longest side, applies EXIF rotation and strips metadata (including GPS), so stored files are much smaller. Still resize on the device (about 1600 px, JPEG quality 80) to save mobile data. |
| Auth | `auth_code` required for upload; `view` is public by URL |

App changes: remove the Firebase Storage SDK usage and bucket config, add the upload call above, and
use the returned `url`. Remove `profile_image`/document uploads to any other third-party bucket too.

### A6. Ride flow without Firestore (REST polling) - backend behaviour

Customer app polls `GET /api/taxi-user/ride-request-detail?rideRequestId=<id>` (header `auth_code`) about every
3 s; the partner app polls `POST /api/skipper/new-request`. The backend guarantees:

| Item | Behaviour |
|---|---|
| `details.status` | `1` waiting for a driver, `4` accepted, `5` on the way, `6` arrived, `8` ride started, `9` completed, `10` completed and paid, `3` cancelled by rider, `7` cancelled by driver **or no driver accepted in time** |
| `details.driver.id` | the driver's **user id** (it used to be the ride id; the ride id is `details.id`). Empty string until a driver accepts. Also `details.driver_id` (int, 0 when none). |
| `details.driver.latitude` / `longitude` | the driver's last position (float), `""` until the partner app has posted `update-lat-lng` |
| Timeout | a ride nobody accepts within 120 s (`params['dispatch']['offerTimeout']`, 0 = off) becomes status `7` with `reject_reason = "No driver accepted the ride"`; the rider app should show "no driver found" and clear the local ride |
| `new-request` | lists only offers whose ride is still waiting (status 1); accepted, cancelled and expired rides disappear, and a driver who rejected an offer no longer sees it |
| Accept | `accept-or-reject` with `status=4`; the first driver wins, all other offers are withdrawn automatically |
| Reject | `accept-or-reject` with `status=7`; only that driver's offer is withdrawn, the ride stays open for others |
| `change-status` | `status` 5 / 6 / 8 / 9 are written to the ride; a ride that is not assigned to the calling driver returns `NOK` ("Ride not found for this driver") |
| Push to drivers | a push (`type=newOrder`, `order_id=<ride id>`) is sent to each driver when an offer is created, once a push provider is configured. Polling still works without it. |

Partner app: post `update-lat-lng` (`latitude`, `longitude`) every 4-10 s while on a ride so the rider sees the marker.

---

## B. Customer app (endpoints under `/api/user/...`)

Checklist:

- [ ] Accept the new JWT-style `auth_code` (A1).
- [ ] Central handler: on NOK "User Not Found"/"Session Not Found" clear session and go to login (A2).
- [ ] Replace FCM with the chosen push provider; send its id as `device_token` in `verify-otp` and
      via `check` with `AuthSession[device_token]` (A3).
- [ ] Remove Firebase config files/SDK if unused elsewhere (A3).
- [ ] Customer images (profile picture etc.): upload through `/api/media/upload` instead of Firebase Storage (A5).

Endpoints that touch the changed behaviour:

| Endpoint (`/api/user/`) | Notes |
|---|---|
| `send-otp`, `resend-otp`, `verify-otp` | Same inputs. `verify-otp` returns the JWT `auth_code`. |
| `check` | Use to refresh the push `device_token` (`AuthSession[device_token]`) and to verify the session. |
| `logout` | Unchanged. |
| every other action | Header `auth_code` as before; handle NOK per A2. |

If this app (or an older build) uses `/api/taxi-user/...` or `/api/wallet/...`, the same three items apply.

---

## C. Partner / driver app (endpoints under `/api/driver/...`)

Checklist:

- [ ] Accept the new JWT-style `auth_code` (A1).
- [ ] Central handler: NOK "User Not Found"/"Session Not Found" → login (A2).
- [ ] Replace FCM with the chosen push provider; send its id as `device_token` (A3).
- [ ] **Remove any code that writes driver positions to Firebase Realtime Database.**
      The admin live map no longer reads Firebase (see below).
- [ ] Keep calling `update-lat-lng` regularly while the driver is online.

Live location (new behaviour of the admin panel):

- The admin "orders panel" map now reads drivers from the backend database instead of
  Firebase Realtime Database. A driver appears on the map when **all** are true:
  - `driver-status` was set to online (`status` = 1, sets `online_status`),
  - the partner app has posted a position via `update-lat-lng` (`latitude`, `longitude`),
  - user role is Driver.
- Recommended: post `update-lat-lng` every 10–30 seconds while online and during rides.
  The map refreshes every 10 seconds.

Endpoints that touch the changed behaviour:

| Endpoint (`/api/driver/`) | Notes |
|---|---|
| `send-otp`, `resend-otp`, `verify-otp` | Same inputs; returns JWT `auth_code`. |
| `check` | Refresh push `device_token` (`AuthSession[device_token]`). |
| `driver-status` | Sets online/offline; required for the live map. |
| `update-lat-lng` | Feeds the admin live map. |
| every other action | Header `auth_code` as before; handle NOK per A2. |

If this app (or older builds) uses `/api/taxi/...`, the same items apply.

---

### C2. Partner app - taxi ride flow contract (what the driver app must call)

Prefix: **`/api/taxi/...`** (`/api/skipper/...` still works as a deprecated alias, as do the old action names `skipper-status` and `update-skipper-city`; new names are `taxi-status` and `update-taxi-city`). The older `/api/driver/...` controller is the booking flow, not this one).
All calls are `POST`, form-encoded, header `auth_code: <jwt>`. Every response has `status` = `OK` | `NOK`.

| Step | Endpoint | Body | Success response |
|---|---|---|---|
| Go online/offline | `update-online-status?status=1` (`0` = offline); `status` is a **query-string** parameter | none | `online_status` |
| Report position (every 4-10 s while online / on a ride) | `update-lat-lng` | `latitude` (-90..90), `longitude` (-180..180) | `status: OK`, `detail{id,lat,lng,online_status}`; `NOK` "Invalid coordinates" when out of range |
| Poll offers (every 5 s while online) | `new-request` | none | `details[]`: `id` (ride id), `user{id,username,contact_no}`, `pickup_address/_pincode/_latitude/_longitude`, `drop_address/_pincode/_latitude/_longitude`, `total_fare`, `payment_method`, `status`, `created_on`. `NOK` + "No new request Found" / "No new rides" when empty. |
| Accept | `accept-or-reject` | `rideRequestId`, `status=4` | `details{id,name,profile_image,vehicle_number,vehicle_type}`, `ride_status=4`. `NOK` "Another driver has accepted this ride. Wait for the next ride" if it was taken. |
| Reject | `accept-or-reject` | `rideRequestId`, `status=7` | `details: "Ride Rejected Successfully"` |
| Arrived | `change-status` | `rideRequestId`, `status=6` | `ride_status=6` (rider is notified) |
| On the way | `change-status` | `rideRequestId`, `status=5` | `ride_status=5` |
| Start ride (OTP from rider) | `verify-ride-otp` | `rideRequestId`, `status=8`, `otp` | `OK`, or `NOK` "Incorrect OTP." |
| Complete | `change-status` | `rideRequestId`, `status=9`, **required** `final_lat`, `final_lng` (the fare is computed from the drop position). Without them the call returns `NOK` "Require final lat lng" and the ride is **not** completed. | `ride_status=9` + fare details |

Push to the partner app when an offer is created:
`title="New ride request"`, `body="A new ride ..."`, data `type="newOrder"`, `order_id="<ride id>"`, and the
notification is sent to the Android channel **`new_booking_alert`** (other pushes use
`other_high_importance_channel`), so a background push rings with the channel's sound. The foreground handler
should open the offers list (or poll `new-request`) when `type == "newOrder"`.

**Who receives offers (important):** the dispatcher offers a ride only to users whose `user_role` is `skipper` (the stored value of the taxi driver role),
who have a `driver_details.vehical_id` equal to the ride's vehicle type, and whose last position is within
10 km of the pickup. Accounts created through `/api/driver/verify-otp` have the role `Driver` and receive no taxi
offers. Partners must be registered through `/api/taxi/verify-otp` and complete the skipper vehicle
details, or the backend dispatcher has to be widened. Offline partners are currently not excluded.

---

## D. Server-side items still pending (backend owner)

These are not app changes but they gate the app behaviour above:

1. Choose the push provider and set its credentials (`PUSH_DRIVER`, `ONESIGNAL_APP_ID`,
   `ONESIGNAL_API_KEY` or `EXPO_ACCESS_TOKEN`). Until then no push is delivered.
2. Deploy `config/secrets.php` (or env vars) and `config/jwt-secret.php` on the live server.
3. Optional grace period: set `params['jwt']['allowLegacy'] = true` in `config/params.php` to keep
   old random auth codes working while the apps are updated, then set it back to `false`.
4. Rotate exposed keys (SendGrid, CashFree test credentials). The Firebase service-account JSON is no longer
   in the project (see section F).
5. OTP bypass for test numbers exists only when `YII_ENV=dev` (`OtpService::isBypassed`); it is inactive in
   production.


---

## E. Backend rules added on 2026-10-14 - re-test these flows (Customer and Partner sessions)

No URL and no request field changed. What changed is that the backend now **refuses things it used to accept**, so
the apps will see new NOK answers. Each row says what you will see and what the app should do.
Messages are in `error` (some older actions use `details`); do not match on the exact text, show it to the user.

### Customer app (`/api/user/*`)

| Call | New answer | App should |
|---|---|---|
| `get-estimate?saveBooking=true` while a ride is in process | NOK "You already have a ride in progress. Please complete or cancel it before booking another." plus `active_booking_id` | Open that booking instead of the booking form. |
| `get-estimate` with a bad input | NOK with one of: "Please choose a valid car type.", "Please choose a valid number of hours." (0-168), "Service is not available in this city.", "Sorry, we do not serve this pickup location yet.", "The pickup date and time must be in the future." | Show the message; keep the form. |
| `get-estimate` with a coupon | The discount is computed by the server (the app's `coupon_discount` number is ignored). Bad/expired code: NOK "This coupon is not valid for this ride." | Show the returned `coupon_discount`/`estimate`, never its own arithmetic. |
| `cancel-ride` | Only your own booking, only while Waiting / Accepted / On the way. Otherwise NOK "This booking can no longer be cancelled". | Hide the Cancel button after arrival; handle the NOK text. |
| `verify-otp` for a blocked account | NOK "Your account has been blocked. Please contact support." | Show it; do not retry. |
| any call, session of a blocked account | `check` answers NOK "session not found"; other calls NOK "User Not Found" | Treat as logged out and go to login (same as an expired session). |
| (push) booking nobody accepted by pickup time | Booking becomes status **9** (Booking Auto Cancelled); notification title "Ride cancelled - please book again" with the ride details | Show status 9 as "Cancelled - no driver available" and offer **Book again**. |
| (push) admin changed the booking | titles "Ride cancelled", "Finding you a driver", "Driver assigned" | Refresh the booking on open. |

### Partner app (`/api/driver/*`)

| Call | New answer | App should |
|---|---|---|
| `accept-ride` lost the race | NOK "Looks like this booking is already accepted by another driver" | Remove the offer from the list. |
| `accept-ride` with a ride in progress | NOK (in `details`) "You already having ongoing ride..." | Show it; point to the ongoing ride. |
| `go-for-pickup`, `notify-arrived`, `arrived-location`, `start-ride`, `complete-ride` | NOK "booking data not found" when the booking is not this driver's **or** not in the right previous state. `start-ride` needs the OTP step (`arrived-location`) first. | Follow the order: accept, go-for-pickup, arrived-location (OTP), start-ride, complete-ride. Refresh the booking on NOK. |
| `cancel-ride` | Only the assigned driver, only before the ride starts; otherwise NOK "This booking can no longer be cancelled". The reply **no longer contains the booking object** (read it again with `view-booking-details`). | Do not read `booking` from the reply. |
| `driver-status` | `status` must be 1 (online) or 2 (offline); anything else NOK "status required" | Send only 1 or 2. |
| `update-lat-lng` | NOK "Valid latitude and longitude are required" for missing / impossible values (was a 500) | Do not send 0/null positions. |
| `collect-cash` | The customer is taken from the booking; the `user_id` field is ignored | Keep sending it; nothing to change. |
| `add-review`, `vehicle-verification` | Only for the driver's own booking (others are ignored / "booking data not found") | - |
| `verify-otp` / any call, blocked account | same as the customer table above | Same handling. |
| (push) admin changed the ride | titles "Ride cancelled", "Ride reassigned", "New ride assigned" | Refresh lists. |

### Taxi apps (`/api/taxi-user/*`, `/api/taxi/*`)

| Call | New answer | App should |
|---|---|---|
| rider `ride-request` with a missing field | NOK "<field> can't be empty" (it used to store the error text as the value) | Send all fields: `vehical_id, ride_fare, pickup_address, pickup_pincode, pickup_latitude, pickup_longitude, drop_address, drop_pincode, drop_latitude, drop_longitude, payment_method, cgst, sgst`. |
| rider `cancel-ride` | Own ride only; allowed while New / Booked / Accepted / On the way / Arrived; otherwise NOK "Invalid Ride Id" | Handle the NOK. |
| driver `cancel-ride` | Own accepted ride only; otherwise NOK "Invalid Ride Id" | Handle the NOK. |
| driver `verify-ride-otp` for a ride that is not theirs | NOK "Ride not found for this driver" (was a 500) | - |

### Quick test list for each app session
1. Book, accept, go for pickup, arrive with OTP, start, complete, collect cash, rate - the happy path still works.
2. Try to book twice - the second is refused with `active_booking_id`.
3. Leave a booking unaccepted past its pickup time (with the dispatch job running) - status 9 and the notification.
4. Cancel after acceptance (customer) and after acceptance (driver) - both complete; the driver's wallet shows the penalty.
5. Block the account in the dashboard - the app drops to the login screen on the next call.


---

## F. Firebase: what the backend no longer has (for the Partner app, which is still on Firebase)

The backend has **no Firebase code, keys or settings** any more. The Partner app is a separate project and is the
only place Firebase may still be used. Replace each Firebase use the app still has:

| App uses Firebase for | Backend replacement | Notes |
|---|---|---|
| Push notifications (FCM token, `onMessage`) | **OneSignal**: initialise with the App ID from the dashboard, send the OneSignal subscription id as `device_token` in `verify-otp` / `check` (`AuthSession[device_token]`). | A Firebase-format token (`...:APA91b...`) is ignored by the backend. Android delivery needs the Firebase service-account JSON **uploaded inside OneSignal** (Settings -> Platforms -> Google Android); the app needs no `google-services.json` for OneSignal. |
| Firebase Storage (profile photo, documents, car photos) | `POST /api/media/upload`, then use the returned `/api/media/view?id=...` URL in the existing fields | See `MEDIA_STORAGE.md`. |
| Realtime Database / Firestore (driver location, ride state) | `POST /api/driver/update-lat-lng` (or `/api/taxi/update-lat-lng`) for position; poll `bookings` / `new-request` and react to pushes for state | The dashboard map reads the same position from the backend. |
| Firebase Auth (phone login) | Backend OTP: `send-otp` -> `verify-otp` returns `auth_code` | Send `auth_code` as a header on every call. |
| Analytics / Crashlytics | Not provided by the backend | Optional in the app. |

Backend-side removals (nothing for the apps to do): the dashboard's hidden Firebase tab, the stored legacy FCM key
(`user_notification_key`, removed by database script 006), the Firebase service-account file, and the old project
copy that still contained Firebase code.

The names `fcm_notification` (table), `FcmNotification` (model) and `list-fcm-notifications` (endpoint) are kept on
purpose: they are historical names for the in-app notification list and the installed apps call that URL.


---

## G. Taxi, Auto and Bike are separate services (new addresses)

Each taxi service now has its **own address**. The old shared `/api/taxi/*` and `/api/taxi-user/*` keep working for
everyone (so installed apps are not broken); new builds of each service's apps should use their own:

| Service | Partner app address | Customer address |
|---|---|---|
| Bike taxi | `/api/bike/*` | `/api/bike-user/*` |
| Auto | `/api/auto/*` | `/api/auto-user/*` |
| Taxi (car) | `/api/car/*` | `/api/car-user/*` |

Same actions and fields as `/api/taxi/*` and `/api/taxi-user/*`. On a service's address:
- the service only sees, accepts, changes, cancels and lists **its own** rides (others answer "not found" / "Another
  driver has accepted this ride");
- a rider can only book a vehicle of that service, and a partner can only register a vehicle of that service; otherwise
  NOK "This vehicle is not part of the <service> service.";
- the vehicle list is the service's own.


---

## H. Per-service wallets, coupons and referrals

The customer wallet is switched off: pay the driver directly. `/api/user-wallet/*` and payment by wallet answer `{"status":"NOK","error":"Wallet is not available...","wallet_enabled":false}`; hide the wallet screen and the wallet payment option. Coupons and referral rewards stay separate in every service (customer referral cashback is not paid while the wallet is off).

| Call | What changes |
|---|---|
| `/api/user-wallet/add-balance`, `available-balance`, `transaction-list` | Optional `module` (1 halting drivers service, 2 taxi, 3 auto, 4 bike). Without it the **halting drivers service** wallet is used (the customer app). On `/api/bike-user/*` etc. the service is the address. |
| `get-estimate` / apply-coupon with a coupon code | A coupon only works in the service it was created for; otherwise NOK "This coupon is not valid for this ride." |
| Partner `available-balance` / wallet | The partner's wallet is the wallet of the service the partner works in. |

Each service also issues its own tax invoice with its own number series; nothing changes in the apps for that.

## I. Night charge per night (customer app, quote)

`POST /api/user/get-estimate` -> `calculateEstimate`: new fields `nightCount` (number of nights charged) and
`nightChargePerNight`. `nightCharge` is now the total (`nightCount` x `nightChargePerNight`), `isNightChargeApplicable` is true when
`nightCount` > 0. Nothing is removed or renamed; an app that shows `nightCharge` keeps working and may show "N nights x amount".

## J. Razorpay payments and driver onboarding (Partner app)

The partner app no longer keeps any Razorpay key and no longer "pays" by calling the backend with an invented order. The joining fee,
the wallet top-up and the subscription all follow the same three steps:

1. `POST /api/driver/razorpay-create-order` with `purpose` = `joining_fee` | `wallet_topup` | `subscription` (and `amount` in rupees for
   `wallet_topup` only). The server decides the price of the joining fee (dashboard setting) and of a plan; the amount sent for those two
   is ignored. Reply `details`: `key_id`, `order_id`, `amount` (paise), `currency`, `name`, `description`, `prefill`.
2. Open Razorpay checkout with exactly those values (`order_id` is required). On success the SDK returns `razorpay_order_id`,
   `razorpay_payment_id`, `razorpay_signature`.
3. Send those three to the endpoint of the purpose: `pay-joining-fee`, `add-balance` or `subscribe-now` (or `razorpay-verify`, which works for
   any purpose). The server checks the signature, fetches the payment from Razorpay and only then marks the fee paid / credits the wallet /
   starts the plan, once. `orderId` alone (the old way) is refused.

`/api/driver/create-order` still exists and now means "wallet top-up order" (`order_amount`), answering like `razorpay-create-order`.

New: `POST|GET /api/driver/onboarding-status` returns the checklist `steps` (`key`, `label`, `done`: profile, profile_photo, driving_license, aadhar, pan,
emergency_contacts, bank, vehicle_types, police_verification, joining_fee when a fee is set, approval), `joining_fee` (`amount`, `required`,
`paid`), `complete`, `enforced` and `can_go_online`. When the company switches on "Enforce onboarding", `driver-status` with `status=1`
answers NOK with a message until every step is done, and unfinished drivers get no ride offers.

The app's city is still set by `POST /api/driver/current-city` (lat, lng) when the driver opens the app; `vehicle_id` on a driver is not used
(the vehicle types of a driver are in `driver-vehicle-types-add`).

Code in the sample app: `lib/api_services/razorpay_payment.dart` (the checkout helper), used by `views/joiningfees/qr_page.dart`,
`views/wallet/components.dart` and `views/profilePage/subscription/QR_subscription.dart`. The old keys that sat in `components.dart` and in a
commented file were removed; those two test keys should be considered public and rotated in the Razorpay dashboard.

## K. Photo kind on upload (Partner and Customer apps)

`POST /api/media/upload` now takes an optional text field `kind` next to the file:

| kind | use it for | what the server does |
|---|---|---|
| `document` (default) | Aadhaar, PAN, licence, bank proof, RC, the onboarding selfie | shrinks to 1600 px, keeps it, private link |
| `profile` | the profile photo | shrinks to 800 px, keeps it |
| `ride` | the selfie, car photos and odometer photos of a ride | shrinks to 1280 px, deleted after the retention period (default 180 days) |

Nothing else changes: the reply is the same `{status, url, id, mime, size}` and the `url` goes in the same field as before. An app that sends
no `kind` keeps working (everything is a `document`). The `url` may answer with a redirect to the file storage: the image widget must follow
redirects (the Flutter `Image.network` and `http` client do by default). A deleted ride photo answers 404, so treat a failed image load as
"not available".

Sample app: `lib/api_services/media_upload.dart` (`uploadMediaFile(file, kind: ...)`), used by `Twl().uploadImage(kind: 'ride')` on the ride screens
and `kind: 'profile'` on the profile page.

## L. QR payments when checkout does not work (Partner app)

New endpoints (same `auth_code` header, same `purpose` and `amount` fields as `razorpay-create-order`):

| Endpoint | Use |
|---|---|
| `POST /api/driver/payment-options` (optional `purpose`, `amount`) | `{amount, checkout, qr, manual: {enabled, upi_id, name}}`: what works now and the amount the server will charge |
| `POST /api/driver/razorpay-create-qr` | automated QR: `{id, image_url, amount, expires_at, purpose}`; NOK when Razorpay refuses (for example the QR feature is not enabled) |
| `POST /api/driver/razorpay-qr-status` (`qr_id`) | `{status: waiting | paid | expired}`; call every 4 seconds while the QR is shown. `paid` means the server has already applied it |
| `POST /api/driver/manual-payment-submit` (`purpose`, `amount`, `utr`) | last resort: the partner paid the company UPI id; waits for the company to approve |
| `POST /api/driver/manual-payment-status` | the partner's manual payments: `review | paid | rejected` |

Flow in the app: try checkout; if it fails ask "Pay by QR instead?"; then `razorpay-create-qr` (show `image_url`, poll the status) and, if that is refused,
the company UPI QR (build `upi://pay?pa=<upi_id>&pn=<name>&am=<amount>&cu=INR&tn=<purpose>`, show it as a QR, ask for the UTR, send it, poll
`manual-payment-status`). After a QR payment the server has already applied the payment, so the app must not call `pay-joining-fee`, `add-balance` or
`subscribe-now` with proof; for `subscribe-now` (which the review screen still calls) send `applied=1` and the server accepts it when a payment of that
purpose was paid in the last 30 minutes.

Sample app: `lib/views/payments/qr_fallback_page.dart` and `PaymentFlow.pay(...)` in `lib/api_services/razorpay_payment.dart`, used by the joining fee,
wallet top-up and subscription screens.

### M. The joining fee is never fixed in the app

Do not write the amount in the app (the old "Pay Rs 500" text). Show the amount from the server: `POST /api/driver/payment-options` with `purpose=joining_fee`
returns `details.amount` (the fee set in the dashboard right now), `onboarding-status` returns `joining_fee.amount`, and `driver/check` returns
`detail.joining_fee_amount`. When the fee is 0, `detail.is_paid_joining_fee` is `true`, so the app does not show the payment screen at all. The sample app
shows the live amount on the joining fee screen (`'Pay_Joining_fees_amount'.trParams({'fee': ...})`, in English, Hindi and Telugu).

### N. Document type on upload (Partner app)

Send `doc_type` next to `kind` when uploading an identity document: `driving_license`, `aadhar` or `pan_card` (anything else is stored as `other`). The profile photo uses
`kind=profile` (no `doc_type` needed). Documents and profile photos are kept for life, each type in its own folder of a separate bucket; ride photos (`kind=ride`) are the only
files that are ever deleted. Sample app: `pickImage(context, docType: 'aadhar')` on the Aadhaar, licence and PAN screens and `uploadImage(kind: 'profile')` for the selfie.

## O. Booking radius, trip changes, live tracking and cancelling (27 Oct 2026)

Plain-language rules: BUSINESS_GUIDE.md section 24. What the apps send and receive:

### Customer app (`/api/user/*`)
| Call | Change |
|---|---|
| `get-estimate` | A one-way booking without a drop returns NOK "Please choose a drop location for a one-way trip.". The drop is never blocked: a pickup or drop outside the city's map only makes the trip outstation (`trip_type` 2, `trip_notice` explains). The reply adds `trip_type` (1 in-city, 2 outstation), `trip_type_changed` (true when the server made it outstation), `trip_notice` (text to show before the customer confirms) and `incity_radius_km` (0 = no radius; quote it in the fare notes). The pickup chooses the price city whatever `city_id` the app sends. |
| `view-booking-details`, `list-bookings` | New keys on a completed booking billed differently from the booking: `radius_converted` (true), `radius_note` (words to show), `route_points` (list of `{lat, lng}`, at most 150, draw it), `max_km_from_pickup`, `incity_radius_km`. While the ride is on: `driver_latitude`, `driver_longitude`, `driver_position_at` (status 3-6) and `live_distance_km`, `ride_elapsed_min` (status 6). |
| `add-user-emergency-contacts` | Saving again replaces the customer's two contacts (it used to answer "Emergency contact Exist" for anyone after the first ever). |
| `cancel-ride` | Unchanged request. A waiting booking is cancelled free (every offer is closed); once a driver accepted, the cancellation charge applies. |
The in-city / outstation choice is only a request: the server decides from the trip, and can change it again when the ride ends (one way <-> round
trip, in-city -> outstation). The final bill can differ from the quote; show `radius_note` when it is present.

### Partner app (`/api/driver/*`)
| Call | Change |
|---|---|
| `update-lat-lng` | Also accepts `accuracy` (metres), `speed_kmh` and `recorded_at` (milliseconds since 1970, the time the phone took the reading). While the driver has a started ride, every reading is added to the ride's distance by the server (BUSINESS_GUIDE 24.5). Send one every 10 s or less during a ride, with the three new fields. |
| `bookings` (status 1) | Each booking has `distance_to_pickup_km` and `eta_to_pickup_min` (from the driver's last position to the pickup). Offers the driver rejected or cancelled are not returned again. |
| `bookings-history` (status 11, Cancelled) | Returns rides the driver held that the customer cancelled and rides the driver cancelled; each row has `cancelled_by` (`customer` or `driver`). A ride the driver cancelled has `status` 10. |
| `complete-ride` | `lat_long_route_map` is now optional and only a fallback: the server uses its own tracked distance. If sent, send at most a few hundred points (the whole local table of a long ride is too large for one request). |
| `view-booking-details` | Same new keys as the customer's (`radius_converted`, `radius_note`, `route_points`) for a completed ride. A ride the customer cancelled has status 8: the app must stop showing it as active. |
| Push `type=newOrder` | A new booking offer. Channel `new_booking_alert` (sound `noti6x`). `order_id` is the booking id. |

End of a ride (28 Oct 2026): `add-review` (customer and driver) now answers NOK without a rating of 1-5 and a review text. `get-estimate` with
`saveBooking=true` answers NOK with `error` and `rate_booking_id` when the customer's previous paid ride is unrated; the customer app opens the
rating sheet for that id. `accept-ride` answers NOK (`details` and `error`) while the driver has a completed ride that is unpaid or unrated.
Customer `list-bookings`: the Active list (no status) also holds a completed ride until it is paid and rated; Completed (status 7) holds only
paid and rated rides. Driver `bookings` status 12 follows the same rule; driver `bookings-history` status 7 takes `start_date` / `end_date`
(the Partner app's Completed tab on Bookings sends 2000-01-01 .. today).

Partner app (27 Oct 2026): the background recorder now takes a reading every 20 m, sends the three new fields with at most one report every 10 s,
clears its local points when a ride starts and uploads at most about 300 points at completion.
