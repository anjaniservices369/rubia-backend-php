# Rubia Services - Business Guide

For the owner. No technical knowledge needed. Everything here describes what the system does today.

---

## 1. What the system is

Four things work together:

| Part | Who uses it | What it is |
|---|---|---|
| **Customer app** | Your customers | Book a ride, pay, rate the driver. |
| **Partner app** | Your drivers (partners) | Receive ride offers, accept, drive, collect money, see earnings. |
| **Dashboard** (admin website) | You and your staff | See and control everything: bookings, drivers, customers, money, prices, settings. |
| **Backend** (this project) | Nobody directly | The engine that connects the three. It runs on a server and works all day by itself. |

You sell two kinds of service:

1. **Halting drivers service** - a customer books a driver for a number of hours (for example "SUV, 2 hours"). The booking is
   shown in the dashboard under **Bookings**.
2. **Taxi services** - bike taxi, auto taxi and car taxi, booked like a normal taxi. Shown in the dashboard under
   **Taxi / Auto / Bike Module**.

---

## 2. A driver-service booking, step by step

| Step | What happens | Customer sees | Driver sees | Booking status |
|---|---|---|---|---|
| 1 | Customer chooses car, time and place and confirms. | "Your order has been placed" | - | **Waiting for Driver** |
| 2 | The system offers the booking to the nearest online drivers of that city (about 25 at a time, nearest first). | Waiting | New ride offer + sound | Waiting for Driver |
| 3 | The first driver who taps **Accept** gets it. Everyone else loses the offer. | "Driver accepted" | The ride | **Driver Accepted** |
| 4 | Driver drives to the pickup. | Driver is on the way | Navigation | **Go for Pickup** |
| 5 | Driver arrives and asks the customer for the 4-digit **OTP** shown in the customer's app. | OTP on screen | Enters OTP | **Arrived at Location** |
| 6 | Driver takes photos of the car and starts the ride. | Ride started | Timer | **Start Ride** |
| 7 | Driver ends the ride at the drop point. The bill is calculated. | Bill | Bill | **Ride Completed** |
| 8 | Customer pays the driver (cash). Driver confirms **Collect cash**. Both rate each other. | Pays, rates | Collects, rates | Ride Completed (paid) |

**Rules the system enforces for you**

- A customer can have **only one booking in process** at a time. A second booking is refused until the first is
  completed or cancelled.
- A driver can have **only one ride at a time**. While a ride is accepted, on the way, arrived or started, the driver
  cannot accept another and receives no new offers.
- A driver whose wallet is below the **minimum wallet balance** cannot accept rides.
- The ride cannot start without the customer's OTP.
- **If nobody accepts before the pickup time, the booking is cancelled automatically** and the customer receives a
  message with the ride details: "was cancelled because no driver was available. Please book again." The booking
  then appears under *Auto Canceled Bookings*.
- Only the customer who owns a booking can cancel it, and only the driver who holds it can run its steps.

### What a cancellation costs

| Who cancels | When | Charge (set in Web Settings) |
|---|---|---|
| Customer | After a driver has accepted | **User Cancellation Charge** (now ₹200). Part goes to the driver, part is yours (**Admin Cancellation Earnings**, now ₹100). The customer pays it with the next ride. |
| Customer | Before any driver accepted | Nothing |
| Driver | After accepting | **Driver Cancellation Charge** (now ₹500), deducted from the driver's wallet. The booking goes back to other drivers. |
| Nobody (no driver found) | At pickup time | Nothing. The customer is told to re-book. |

---

## 3. Money - how a fare is split

For every completed ride the system records:

- **Total** - what the customer pays (after any coupon).
- **GST** - a percentage set in settings (now 5%). It belongs to you for tax.
- **Your commission** - a percentage of the fare before GST (now 10%; each driver can have their own percentage).
- **Driver earning** - what remains for the driver.

Example (a real test ride): total ₹420, GST ₹20, commission 10% of ₹400 = ₹40. You keep ₹60 (₹40 + ₹20 GST); the driver
keeps ₹360.

**Cash rides** (the normal case): the driver collects ₹420 from the customer. Your ₹60 is **deducted from the driver's
wallet**, so drivers must keep money in their wallet. That is why there is a minimum wallet balance.

Where to look in the dashboard: **Driver Earnings**, **Driver Wallet**, **Driver Payouts / Withdraw requests**,
**Ride Earnings**, **Driver Subscriptions**.

**The final bill is based on actual use** (distance and time of the real ride), not the estimate. A short ride can come
out cheaper than the estimate. If you would rather always charge at least the booked package, tell your developer: it
is one rule to change.

---

## 4. The dashboard - what each menu is for

| Menu | Use it to |
|---|---|
| **Dashboard** | Today's picture: drivers online, rides waiting for a driver, push-notification status, booking counts. |
| **Bookings** (all, accepted, on the way, arrived, started, completed, cancelled, auto-cancelled) | Open any booking. At the top of a booking you can **cancel it, send it back to drivers, assign a specific driver, or set the ride state**. |
| **Users** | Customers. See details, wallet, **Block / Unblock**. |
| **Driver / Driver Module** | Partners: documents, verification, wallet, subscriptions, **Block / Unblock**. |
| **Taxi / Auto / Bike Module** | The same for the taxi services: ride requests, commissions, payouts. |
| **Franchise** | City franchise accounts. |
| **City / City Rate Card** | Where you serve and what each city charges (base fare, per km, per minute, night charge). |
| **Each service's dashboard** (the first entry of every module menu: Halting drivers service, Taxi, Auto, Bike) | The module's own page: today's rides, drivers online, rides waiting, this month's company share, **its maps** (cities with pincodes, service area, pricing, franchise) and **its billing**. Maps, pricing, franchises and billing of one service are never mixed with another's. |
| **Vehicle Types, Services, Plans** | The vehicles, services and subscription plans you sell. |
| **Coupons** | Discount codes. The server checks the code, dates, minimum amount and maximum discount. |
| **Manage Banners** | Banners shown in the apps. |
| **Web Settings** | The central settings (section 6). |

### Booking page buttons (admins only)

| Button | Effect |
|---|---|
| **Cancel booking** (reason required) | Closes it. The customer gets a message with your reason; the driver, if any, is told too. |
| **Send back to drivers** | Takes the driver off and offers the ride to others again. |
| **Assign a driver** | Type the driver's id or mobile number; the ride is given to them directly. Refused if the driver is blocked or already on a ride. |
| **Set ride state** | Moves a ride between accepted / on the way / arrived / started. |

Completing a ride is only done by the driver in the app, because that is what records the fare and commission.

Every change is written to the booking's **History** with the admin who did it.

### Blocking

On a booking page (and in the Users / Drivers lists) press **Block**:

- The person is **logged out of the app at once** and cannot log in again ("Your account has been blocked").
- A blocked driver goes offline and gets no offers.
- A blocked customer's un-accepted bookings are cancelled. A ride already in progress stays for you to finish or cancel.
- **Unblock** restores access immediately.

---

## 5. Things that happen automatically

| What | When | Notes |
|---|---|---|
| Offer rides to drivers | Every minute (needs the scheduled job, see section 8) | Nearest 25 online drivers of the booking's city. |
| Cancel unanswered bookings | Every minute, with the same job | Customer is notified. |
| Cancel stale taxi requests | Scheduled job `taxi/expire-stale` | Rides that nobody accepted for 24 hours. |
| OTP SMS | At every login | Sent by the SMS provider (2factor.in). |
| Push notifications | On events (new ride, accepted, arrived, completed, cancelled) | Delivered through OneSignal. Needs a registered phone. |

---

## 6. Web Settings - the numbers you control

Dashboard → **Web Settings**. Changes apply straight away.

| Setting | What it does | Now |
|---|---|---|
| GST (%) | Tax added to fares | 5 |
| Default admin commission (%) | Your share of each fare before GST (a driver can have an individual percentage) | 10 |
| Platform charges / Secured fee | Extra fixed amounts added to fares | 0 / 0 |
| User cancellation charge | Charged to a customer who cancels after a driver accepted | 200 |
| Admin cancellation earnings | Your share of that charge | 100 |
| Driver cancellation charge | Taken from a driver who cancels after accepting | 500 |
| Driver minimum wallet balance | Below this the driver cannot accept rides | 0 |
| Taxi / auto / bike minimum wallet and cancellation charges | The same for the taxi services | 100 each |
| Minimum withdraw | Smallest payout a driver can ask for | 100 |
| User waiting time (min) | How long the customer's app waits for a driver | 30 |
| Referral earnings (driver / user / rider) | Reward for inviting someone | 0 / 50 / 0 |
| **SMS API key** | The 2factor.in key used to send OTPs | secret |
| **OneSignal App ID / REST API key** | Push notifications | set |
| Payment gateway API key | Online payments | secret |
| Taxi CGST / SGST (%) | GST on taxi rides (the halting drivers service uses the single GST setting above) | 2.5 / 2.5 |
| Taxi dispatch mode | 1 = offer to one driver at a time, 2 = all nearby drivers at once, 3 = nearest group | 2 |
| Taxi dispatch radius (km) / wait time (s) | How far from the pickup a taxi driver may be, and how long to wait before trying the next | 10 / 30 |
| Booking expiry grace (minutes, optional) | Minutes after the pickup time before an unanswered booking is cancelled. Not set = 0 | - |

Keep keys private. The dashboard shows only the last characters of the push key after saving.

---

## 7. Other companies the system depends on

| Service | For | What breaks without it |
|---|---|---|
| **2factor.in** | Sending OTP SMS | Nobody can log in. |
| **OneSignal** (and Google's FCM for Android) | Push notifications | Drivers don't hear about new rides; customers get no updates. Rides can still be booked and accepted by opening the app. |
| **Google Maps** | Distance, time and fares, maps | Fare estimates fail. (The Directions service works today; Distance Matrix is not enabled in the Google account.) |
| **Your hosting server and database** | Everything | Everything. |
| **Payment gateways** (Cashfree / Open Money) | Online payment | Online payments only. |

Costs usually grow with usage: SMS per OTP, Google Maps per request, hosting per month.

---

## 8. Keeping it running (what to ask your developer / host)

1. **A scheduled job every minute** must call the dispatch address
   (`/api/user/assign-booking-to-driver`). Without it, no ride is ever offered to a driver and unanswered bookings are
   not cancelled. If bookings sit at "Waiting for Driver", check this first.
2. **A daily database backup**, with a restore test now and then.
3. **Uploaded documents and photos** are stored on the server (or in the database). They grow; back them up too.
4. **Updates** are applied by running a prepared release package (see `DEPLOY.md`).
5. **Secrets** (SMS key, push key, maps key, database password) live in the server's private settings file or the
   dashboard - never in the code and never in email or chat.

---

## 9. When something goes wrong

| What you see | Most likely cause | What to do |
|---|---|---|
| Nobody receives an OTP | SMS key wrong or the SMS balance is finished | Check the SMS API key in Web Settings and the 2factor.in balance. |
| Bookings stay "Waiting for Driver" | The one-minute scheduled job isn't running, or no driver is online in that city | Check the job; open the booking and **Assign a driver** by hand meanwhile. |
| Drivers say "you already have an ongoing ride" | An old ride was left open (never completed) | Open that old booking and **Cancel** it. |
| Driver says "wallet balance is low" | Wallet below the minimum | Driver recharges, or lower the minimum in settings. |
| No push messages | The OneSignal key is missing or wrong, or the phone hasn't registered | Web Settings → Notification; ask the developer to run the push check. |
| Fare estimate fails | Google Maps key problem | Ask the developer to run the geo check. |
| A customer can't book a second ride | He still has one in process | Complete or cancel the first one (you can do it from the booking page). |
| A user should not be using the app | - | **Block** them from a booking or user page. |
| Strange or abusive booking | - | Open it, **Cancel** with a reason, then block the account if needed. |

---

## 10. Words used in the system

| Word | Meaning |
|---|---|
| **Partner / driver** | A person who drives for you. In the taxi services the partner is also called a "taxi driver". |
| **Booking** | A driver-service order (hours-based). |
| **Ride request** | A taxi/auto/bike order. |
| **Offer** | A ride shown to a driver who may accept or reject it. |
| **Wallet** | Money balance of a driver (or customer) inside the system. |
| **Commission** | Your percentage of each fare. |
| **Payout** | Paying a driver the money they have earned. |
| **auth_code** | The login "key" the app holds after OTP login. |
| **Blocked** | The account cannot use the apps. |

For technical details see `DEVELOPER_GUIDE.md`. For installing a new version see `DEPLOY.md`.

---

## 11. Where the other documents are

`README.md` lists every document. `DEVELOPER_GUIDE.md` is for the person who maintains the code; `DEPLOY.md` is
the checklist for installing a new version on the server.

---

## 12. Designing a map: pincodes, pricing, franchise - per service

The platform runs **four services**, each on its own: **Halting drivers service, Taxi (car), Auto, Bike**. Every service has
its **own maps** of the cities it serves, its **own pricing**, its **own franchises** and its **own billing**. The same
pincode can belong to a different city for a different service, and changing one service never changes another.

Open the service from the dashboard menu (**<Service> -> Maps, operations and billing**), or from a city page
(**Map: Halting drivers service / Taxi / Auto / Bike**). Then **Open map** for a city:

| Step | What you do | What it changes (for this service only) |
|---|---|---|
| 1. Pincodes | Type pincodes (6 digits, commas or spaces) and tick the vehicles (taxi services); **Add to this map**. | Rides picked up in those pincodes belong to this city for this service. A pincode this service already serves from another city is **not** taken silently: move it from there first. |
| 2. Move or remove | Per pincode: **Move** to another city, or **Remove**. | From the next booking on, the pincode's rides use the other city's pricing, commission and franchise. Rides already booked are not changed. |
| 3. Pricing | A new map shows "no pricing". **Copy pricing from** another city (same service), then edit it. | The map is priced on its own. A city that already has pricing is never overwritten. |
| 4. Service area | Check the outline on the map; **Use this as the service area**. | Customers outside the outline cannot book **this service** there; other services are unaffected. |
| 5. Franchise | Choose a franchise account and **Let this franchise run this map**. | The franchise sees only this service's data of this city. One franchise account runs one service in one city; giving it a new map moves it there. |
| 6. Billing | Open **Billing** of the service. | Totals, per city and per day for a date range, with a CSV download. |

Good practice: pincodes first, then copy pricing and edit the prices, then save the service area, then assign the
franchise. The **Recent changes** list at the bottom of a map records who did what and when.

---

## 13. Wallets and billing of each service

Every service has its **own driver wallet**: money a driver holds for the Halting drivers service is separate from money held
for Bike, Auto or Taxi. Each entry is stamped with its service, the app shows the balance of the service the driver
is in, and the service's billing page (**<Service> -> Maps, operations and billing -> Billing**) shows its totals,
per city and per day, the wallets held, and a CSV statement.

- **Minimum balance to accept rides:** the Halting drivers service enforces its minimum. For Taxi, Auto and Bike the minimums
  already exist in Web Settings but were never enforced; each now has a switch **"Enforce minimum wallet balance"**.
  They are **off**, so nobody is blocked until you turn a service's switch on.
- The customer's own wallet (user wallet) stays common, because the user app is common.
- Each service's own GST registration, invoice number series and settlement reports are the next step; they need your
  accounting rules (see `ARCHITECTURE_TARGET.md`, section 8).

---

## 14. Taxi, Auto and Bike are separate services

Bike taxi, Auto and Taxi (car) are three services, not one. Each has its own address for its partner app and its
customer side, its own rides, maps, pricing, franchises, wallets and billing. A bike partner cannot see or take an
auto ride, a rider on the Auto address cannot book a bike, and nothing done in one service shows in another.
The old shared address still works so that the apps already installed do not stop; new app builds of each service use
their own address (see `MOBILE_APP_CHANGES.md`, section G).

---

## 15. Everything is separate: invoices, tax, wallets, coupons, referrals

Each service (Halting drivers service, Taxi, Auto, Bike) is a business of its own. Nothing is shared between them except the
one user app that customers install.

| What | How it is separate |
|---|---|
| **Invoices** | Every completed ride gets a tax invoice from **its own service**, in its **own number series** (`DRV/2026-27/000001`, `BIK/...`; restarts each financial year, April to March). |
| **Billing profile** | Each service has its own legal name, GSTIN, PAN, address and invoice prefix: **Service -> Billing -> Billing profile**. |
| **Tax reports** | Per service and month: invoices, taxable value, CGST, SGST, IGST, total and company share, with a CSV download: **Service -> Billing -> Tax report**. Invoices can be listed, printed and downloaded too. |
| **Driver wallets** | One wallet per driver per service, own minimum balance and own statement. |
| **Customer wallet** | **One wallet per customer, for every service**, kept by the customer app. Switched off for now (the customer pays the partner directly); when it is switched on, a ride of any service can be paid from it and the money is the same pool. Partner wallets are per service. |
| **Coupons** | Each coupon belongs to one service (a new field on the coupon form) and works only there. |
| **Referrals** | Referral rewards are set per service (Web Settings, "Driver referral reward - <service>" and "Customer referral reward - <service>") and earned in that service. |
| **Maps, pricing, franchises** | Per service (section 12). |

**Before invoices carry a real GSTIN**, fill in each service's billing profile. Rides completed before invoicing
existed have no invoice; your developer can issue them in date order with `php yii billing/backfill --service=N --apply`
(an invoice number is a tax document, so this is deliberately not automatic).

## 16. Super admin dashboard: all services

**All services** (menu, owner and sub-admins) shows every service side by side: rides today, waiting, partners online,
billed, company share, partner wallets held, invoices and tax for a chosen period, with totals on top. A service that runs
on its own database is shown with a green "own database" badge and an **Open its dashboard** button; its numbers are
asked from that service, not read from its database. Each service's own dashboard shows that service only.

## 17. Night charge

The night charge on the city's rate card is added **for every night the ride touches** (a night is 22:00 to 06:00). A ride
that is out for three days and nights pays it three times. A ride that starts at 23:00 and ends at 02:00 pays it once; a day trip
that ends before 22:00 pays nothing. The quote shows how many nights are charged and the charge per night; the final fare at
the end of the ride uses the real start and end time.

## 18. Joining fee, partner payments and onboarding

A new partner (driver) finishes these steps in the app: profile, profile photo, driving licence, Aadhaar, PAN, emergency contacts, bank details, vehicle
types, police verification, the joining fee (only when the company has set one) and, last, the approval by the company in the dashboard
(Drivers, profile verified). The joining fee, a wallet top-up and a subscription are paid with Razorpay inside the app; the amount of the
joining fee and of a plan is set by the company, never by the app. A payment counts only after Razorpay confirms it, and only once.

The company can switch on **Enforce onboarding** (Payments screen): from then on only approved drivers with the joining fee paid can go
online and receive ride offers. Switch it on after the existing drivers have been approved. The same screen lists the latest payments and
holds the Razorpay keys, which are pasted there and never shown again.

## 19. Photos and documents

Partner documents (Aadhaar, PAN, licence, bank proof) and profile photos are kept for as long as the partner is with the company. The selfie
and car photos taken at the start and end of every ride are kept for 180 days (the number can be changed in Web Settings,
"Days to keep ride photos") and then deleted automatically, so the storage cost stays flat instead of growing with every ride. If a dispute
about a ride is still open, extend the days before the clean-up runs. The photos are stored in a separate file storage (not in the database),
documents are never public (a link works for 15 minutes), and location data is removed from every photo when it is uploaded.

## 20. If the payment gateway does not work

A partner can always pay: the app first offers Razorpay checkout, then a QR code that is confirmed automatically (this needs Razorpay to switch on
"UPI QR codes" for the account: ask their support), and, if Razorpay itself is down, the company's own UPI QR. A payment made that way waits on the
dashboard (Payments, "Waiting for your approval") with the amount and the partner's bank reference (UTR). Check your bank statement for that amount and
reference and tap **Approve**: the money is credited to the partner once. Tap **Reject** if you cannot find it. Set the company UPI id on the same screen.

### Changing the joining fee

The joining fee is set on the dashboard (Payments screen, "Joining fee") and takes effect at once: the next partner who opens the payment screen sees the new
amount and is charged exactly that amount (the amount is decided by the server, never by the app). A payment that a partner already started keeps the price it
was started at. Set the fee to 0 to remove it: partners are then no longer asked to pay it and nobody is held at the payment screen.

### Partner documents are kept for life

The driving licence, Aadhaar, PAN card and profile photo of every partner are stored in their own protected storage (a separate bucket with an indefinite lock) and are
never deleted automatically, also when a partner leaves the company. When a partner renews a document the old copy is kept too. Only the selfie and car photos of rides are
deleted after the retention period. Erasing a partner's documents is possible only by a deliberate manual step by an administrator.

### Keys and passwords in the dashboard

On the settings page every key box says what it is, where to find it, and whether it is protected. Protected boxes (passwords, secret keys, tokens) are saved encrypted and can
never be read back, not even by an administrator: if a secret is lost, create a new one at the provider and type it in the box. Identifiers (key ids, user names) are private but visible.


## 21. Integrations: one page for every outside service

Under **Integrations** in the dashboard menu every outside service we use (payments, SMS, push notifications, e-mail, maps, identity checks, photo storage,
payouts) has its own page. Paste the keys there, press **Save**, then **Test connection** to see at once whether the service accepts them. Keys marked
Protected are stored locked and never shown again; to change one, paste the new value. Each service of Rubia (Halting drivers, the taxi services) has its
own dashboard, so you set up each one separately.


## 22. Drivers-profile

Open **Halting drivers > Drivers-profile** and click a partner to see and correct everything they filled in in the app: name, contact, licence, Aadhaar,
PAN, bank, emergency contacts, vehicles, police verification and photos. Press the Save button of the section you changed. What you save is what the
app shows to the partner. The ticks at the top show which onboarding steps the partner has finished.


## 23. City and franchise

You no longer choose a partner's or customer's city. Type the full address and press Save: the system finds the place on the map and the city it lies in
(Integrations > Google Maps must have its server key). The profile shows the city and a map ("Joined in"). A city franchise sees the partners of its own city
in Drivers-profile and can open their profiles to look at them, but cannot change them. In the partner list you can filter by city.
