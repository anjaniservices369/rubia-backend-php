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
| Booking radius (km) | Integrations > Driver settings. A new booking is shown to every online partner within this many km of the customer's pickup point (24.1) | 10 |
| In-city trip radius (km) | Integrations > Customer settings. An optional extra limit: an in-city trip that goes farther than this from the pickup is billed as outstation even inside the city map (24.3). 0 = off: only the city map decides | not set |
| In-city boundary buffer (km) | Integrations > Customer settings. How far outside the city map a pickup, drop or ride may be and still count as in-city. 0 = the map is exact | 0 |
| Return-to-pickup tolerance (km) | Integrations > Customer settings. How close to the pickup a ride must end to count as having come back (24.4) | 1 |
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

A night fare carries GST exactly like a day fare (changed 27 Oct 2026: before that the quote showed the GST but left it out of the
total, so a night booking was cheaper than the same day booking by the GST amount).

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


## 24. Who sees a booking, and how a trip that changes is billed (27 Oct 2026)

### 24.1 Who is offered a booking
- The moment a customer confirms a booking it is offered to **every online, active partner within the Booking radius of the pickup
  point** (setting above, 10 km if not set), nearest first. A partner's own city does not matter. Partners who are on a ride are skipped.
  All of them get one push notification, in a single call.
- Once a minute the dispatch job (the `assign-booking-to-driver` cron in DEPLOY.md) looks again, so a partner who comes online or drives
  into the radius later still gets the booking. It never offers the same partner twice.
- The partner sees the booking in **Available rides** with "km from you | minutes to reach", worked out from where the partner is now
  (straight line x 1.3 at 25 km/h, so it is an estimate). The **first partner to accept gets it**; for everyone else it disappears. A
  partner who turned a booking down, or accepted it and then cancelled, is not offered it again.
- If a customer books while no partner is online inside the radius, the booking waits; the next dispatch run reaches partners who come online.

### 24.2 Where the price comes from, and the drop can be anywhere
- The **pickup point** chooses the price: the smallest service map that contains it, and that city's rate card. Only maps of cities that are
  switched on and have an active fare card count; maps of switched-off cities (old copies, a state-wide outline) are ignored, so a locality
  is priced by its own city map and never by a bigger region around it. The customer app no longer
  blocks a drop outside the city and a drop never changes the city. A pickup in a place with no fares gets "Fares are not set up for
  this city and ride type yet".
- A one-way booking needs a drop; a round trip has none. The customer is limited to one active ride at a time.

### 24.3 In-city or outstation: in-city means inside the city's map
A city's **map is made from its pincodes** (section 12: you add the pincodes of the city for the service, and "Use this as the service area" draws the
outline from them). The outline is what the in-city check uses, so keep each city's pincodes correct: one pincode filed under the wrong city, or a
whole state filed as one city, makes the outline wrong. A trip is in-city only while it stays inside the city's outline.
- **When booking:** if the pickup or the drop (one way) is outside the map, the trip is priced as outstation and the customer is told why
  before confirming ("Your drop is outside the Hyderabad city area. This trip is booked as an outstation trip and is priced as one.").
  If you have also set an **In-city trip radius**, a drop farther than that from the pickup is outstation too, even inside the map.
  A round trip has no drop, so only its pickup is checked at booking.
- **During the ride:** the partner's phone reports its position. If it is outside the map (or beyond the radius, if set) on two reports in a
  row (about 20-40 seconds), the customer gets a notification and an entry in the in-app notifications, the partner gets a message, and the
  booking log gets a note. Once per ride.
- **At the end:** if any tracked point of the ride, or its final drop, was outside the map (or beyond the radius), an in-city ride is billed
  as outstation (one way: by the real distance; round trip: by the real hours), whatever was booked. If the city has no outstation fare the
  in-city fare is kept and the booking log says "needs review".
- A city with no drawn map cannot be checked, so nothing is sent to outstation for it. The pickup chooses the city (24.2); a pickup outside
  every active city map is priced by the city the app sent and, being outside that city's map, is an outstation trip.

### 24.4 One way or round trip - what was driven decides
- Booked **one way**, but the ride went at least 2 km out and ended within the **Return-to-pickup tolerance** of the pickup: billed as a
  **round trip**.
- Booked **round trip**, but it ended farther than twice the tolerance (at least 2 km) from the pickup: billed as **one way**.
- Anything in between is billed as booked. If the city has no fare for the other ride type the booking is kept as booked and flagged for review.
- The bill and the booking details say what happened in words, with a map of the route ("Booked as a one-way trip. The ride went 20.6 km out
  and came back to the pickup point, so it was billed as a round trip."). Both apps show it. The route and the figures are saved in the
  `booking_route_log` table.
- **The final fare can be lower or higher than the estimate** (a one-way outstation estimate of Rs 1995 became Rs 945 when the ride came
  back, because the round-trip outstation card is cheaper). Open decision: do you want a floor such as "never below the booked fare"?

### 24.5 How the distance of a ride is measured
- While a ride is in progress the partner's phone takes a reading every 20 metres and sends one at most every 10 seconds, with its accuracy,
  speed and the time of the reading, and the server adds up the distance itself. Denser reporting makes the distance more exact.
  Ignored: readings the phone says are inaccurate (worse than 60 m), repeated or older readings, standing still (moves under 15 m, or half
  the accuracy), and impossible jumps (faster than 180 km/h; the GPS error is not counted).
- Minutes with no readings (tunnel, no signal) are bridged with the straight line x 1.3 and counted as "gap" distance. At the end the biggest
  gaps are measured again along real roads, so a long drive is not guessed.
- The bill uses this distance for the extra-km charge of one-way rides. A round trip is priced by time only (its rate card has no per-km
  charge). If the server has fewer than 3 readings it falls back to the route the phone uploaded, then to the road distance pickup to drop.
- Long rides: a ride of 10 days or 15,000 km produces about 100,000 stored points (a few MB). The farthest-from-pickup figure is kept as a
  running maximum, so the radius and return checks stay exact however long the ride is.

### 24.6 Cancelling
- **The customer cancels** a booking that is still waiting: free, every offer is closed. **After a partner accepted** (accepted or on the
  way): the customer is charged the *User cancellation charge* and the partner is credited that charge minus your *Admin cancellation
  earnings* (200 - 100 = Rs 100 now). The ride leaves the
  partner's Accepted rides and shows in the partner's **Cancelled** rides as "cancelled by the customer". If the partner is on the ride
  screen at that moment the app tells them straight away.
- **The partner cancels** after accepting: the partner is charged the *Driver cancellation charge* (Rs 500 now), the booking goes back to
  Waiting for driver and is offered to the other partners (not to this one again), and the customer's card goes back to "Waiting for Driver".
  The ride shows in the partner's **Cancelled** rides as "cancelled by you".

### 24.7 What does not change the price (yet)
Car type (automatic / manual) and car model (SUV+, SUV, Sedan, Hatchback) are saved on the booking and shown to the partner, but every
combination costs the same: the rate card has no car dimension. If a model should cost more, new rate-card fields are needed.

### 24.8 Maps drawn from pincodes (separate from the hand-drawn maps)
- A city's map can be drawn from its pincodes (City maps page: add the pincodes, then **Save as the pincode map**). It is kept in its own
  table (`pincode_map`) apart from the hand-drawn map, which is **not changed**. Booking and billing use the pincode map first; a city without
  one keeps its hand-drawn map. **Remove the pincode map** on the same page goes back to the hand-drawn one.
- Pincodes are looked up on OpenStreetMap first (free, no key); Google Geocoding is the backup. A pincode that neither finds is listed as
  not found and does not widen the map; one far from the others is flagged and left out.
- The map is the smallest convex outline around the pincodes' areas, so a place whose pincode is missing from the list (for example an airport
  pincode) is outside the map until it is added.
- Maps built this way (27 Oct 2026): Hyderabad, Bengaluru, Chennai, Cochin, Vijayawada, Mumbai, Delhi, Kolkata, Pune, Nagpur, Bhopal, Goa
  (and the test cities Kanyakumari and Srinagar). A city that had no fares got a **copy of Hyderabad's fares** (Cochin, Kolkata, Mumbai, Pune,
  Bhopal, Goa, Kanyakumari, Srinagar): edit them to each city's real rates before opening those cities to customers.

### 24.9 The end of a ride: bill, payment, rating, Completed (28 Oct 2026)
1. The driver completes the ride. **Both** the customer and the driver see the final bill. The ride stays in **Active** (customer) and
   **Accepted rides** (driver), marked *Payment pending* / *Collect payment*.
2. The driver collects the payment in the Partner app (**Collect cash**). The booking is then marked paid.
3. **Rating and a written review are compulsory for both sides.** As soon as the payment is confirmed the customer's app opens a rating
   sheet (stars and a review) and the driver's app opens one too. Neither sheet can be skipped, dragged away or closed with Back, and the
   server refuses a rating without stars (1-5) or without text.
4. A ride moves to **Completed** for a person when the ride is paid **and that person has rated**. The customer's and the driver's
   Completed lists are separate: each moves when that person rates.
5. **Nothing new can start until the last ride is rated.** A customer whose previous paid ride is unrated cannot book (the app says so and
   opens the rating sheet for that ride). A driver whose completed ride is not yet paid and rated cannot accept another ride.
- The rating card on the ride's details page is another way to give the same rating; it has the same rules.
- Old paid rides that were never rated count too: the customer or driver is asked for those the first time they try to book or accept.

### 24.10 Outstation round trip: priced by the hour (28 Oct 2026)
- The rate card's **base fare covers the base hours of the first day only** (for example 4 hours). **Every hour after that is charged hourly** at
  the rate card's extra rate (extra per minute x 60). There is no new base fare for later days.
- The **night charge** is added for every night (22:00 to 06:00) the trip touches, plus the rate card's outstation charge, then GST.
- The customer chooses the **trip length in hours** (up to 365 days = 8,760 hours; the app shows the same length in days). At the end of the ride
  the real elapsed hours are billed the same way, so a trip that runs longer than booked simply costs more hours.
- **The driver's food and accommodation are provided by the customer** during the trip and are not part of the fare. The estimate, the fare sheet
  and the ride details say so.
- **Base hours differ per trip type**: each rate card (in-city one-way, in-city round trip, outstation one-way, outstation round trip) has its own base
  hours and base fare (for example 3 hours for outstation one-way and 4 hours for outstation round trip). The app starts an outstation trip at its own
  base hours and the fare sheet quotes them.
- Every other trip type is limited to a 12-hour booking (a driver's duty); anything longer is charged by the hour when the ride ends.
- Example (base 900 for 4 hours, 60 per extra hour, 500 per night, GST 5%): a 38-hour trip with 2 nights = 900 + 34 x 60 + 2 x 500 = 3,940, plus
  GST 197 = **4,137**.

### 24.11 Map services: Ola Maps, Google, Mapbox, OpenStreetMap
Road distances (estimates and bills) and the city-from-address lookup go to the map services in the order set in **Integrations > Maps and
routes** (default: Ola Maps, Google, Mapbox, OpenStreetMap). A service with no key, or one that refuses, is skipped for the next, so a blocked
Google key no longer stops bookings once another service has a key. **Ola Maps** needs one API key (Ola/Krutrim Maps console) with the Routing
and Places APIs switched on. The maps *shown* in the apps still use the Google map key (a separate setting).

### 24.12 Known limits
- Push notifications reach a phone only when the OneSignal app has its **Android credentials** set up in the OneSignal dashboard. Until
  then a partner hears about a new booking only while the Partner app is open (it checks every 10 seconds and plays the booking sound).
- A partner whose phone sends no positions during the ride (app killed, no signal for hours) gets the straight-line estimate for that
  stretch, flagged as gap distance on the booking.

### 24.13 Account deletion (Google Play requirement)
- **In the apps:** Profile → **Delete account** in both the customer app and the Partner app. It asks for confirmation, then deletes at once: the person is signed out everywhere and their name, email, phone number, photo, date of birth, emergency contacts, saved places and device tokens are erased. Ride, invoice and payment records stay, without the link to the person (tax and accounting rules). A person with a ride in progress must finish it first.
- **On the web:** the public page `https://<your-domain>/account-deletion` (for people who no longer have the app). The form records a request (table `account_deletion_request`, `source = web`, `status = 0`); support calls the person back on the number to confirm and processes it within 30 days (the page says so).
- **Play Console:** put the page address into *App content → Data safety → Account deletion link* for **both** apps (com.rubia.driver and the customer app), replacing the dead Google Form link, then resubmit.
