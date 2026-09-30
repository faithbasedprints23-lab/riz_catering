# Riz Catering System Review

**Review date:** 26 September 2026 (updated after delivery and cancellation work)  
**Scope:** Static review of the PHP source, views, SQL dump and migration files in this repository. I did not connect to a live database or exercise the screens in a browser. Findings below describe what the checked-in code is designed to do, not a claim that deployed behavior has been runtime-verified.

## Executive assessment

This is a substantial catering reservation and administration application. The newer implementation has customer accounts, package-based booking, payment tracking, schedule checks, owner reporting, account administration, feedback, audit records and settings. Most sensitive changes have server-side validation, CSRF checks and database transactions.

The initial review found that the front controller did not invoke the native implementation, and that the shared layout suppressed rendered page content. Those code defects have now been corrected: `index.php` starts the native session/bootstrap and dispatcher, and the layout prints the rendered view and flash messages. These changes have passed PHP syntax checks, but the end-to-end system still needs validation against the configured database and in a browser.

**Owner-side completion estimate: 85% feature-complete, pending runtime verification.** This is an instructor-style estimate, not a measured metric. The owner workflow has a consistent sidebar, database-backed sales workspace, customer directory, notification inbox, profile management, and customer account controls, alongside reservation, scheduling, package, menu and feedback modules. The supported account roles are Owner (stored internally as Admin) and Customer.

## System structure and how requests are meant to work

- `index.php` is intended to be the single front controller. `.htaccess` redirects old `.html` URLs and denies direct access to `.sql` and `.txt` files.
- `Application/NativeApp.php` contains the active-looking procedural application: session/database helpers, page dispatch, action handling, role checks, validation, and rendering. Pages are selected with `index.php?page=...`; POST forms send an `action` field.
- `Application/NativeViews/` contains the pages rendered by `rc_render()`. `layout.php` supplies the common shell and navigation.
- `riz_catering.sql` supplies the base database. The README says to apply `Application/Database/scope_alignment.sql` and then `owner_account.sql` to add the application tables/columns used by the PHP code.
- `Application/Controllers`, `Application/Models`, `Application/Config/Routes.php`, and `Application/Views` form a separate older CodeIgniter-style implementation. The repository does not include the framework bootstrap/dependencies that would make these files the live application. Their schema assumptions also differ from the current native PHP schema. They should be treated as legacy/reference code until the project explicitly chooses one architecture.

### Request lifecycle functions

| Function | Purpose |
|---|---|
| `rc_boot()` | Configures safer session cookie settings, starts the session, and creates the session CSRF token. It must run before dispatch. |
| `rc_db()` | Lazily opens a PDO MySQL connection using `RIZ_DB_*` environment variables or local XAMPP defaults. PDO exceptions and real prepared statements are enabled. |
| `rc_dispatch()` | Routes GET requests to page queries and views; routes POST requests through CSRF validation into `rc_action()`. It catches unexpected errors, logs details server-side, and displays a generic error page. |
| `rc_action($action)` | Handles all state-changing form operations, including registration, booking, payment, status, menu/package, account and settings updates. |
| `rc_render($view, $data)` | Includes a native view, captures its HTML, and wraps it in `layout.php`. |
| `rc_e($value)` | Escapes output for HTML, reducing cross-site scripting risk. |
| `rc_url()` / `rc_redirect()` | Build query-string page URLs and redirect after actions. |
| `rc_flash()` / `rc_take_flash()` | Store a one-request success/error message in the session and consume it during rendering. |
| `rc_user()` / `rc_require_login()` | Read the signed-in user and re-check the account’s current status/role in the database. Disabled users are signed out. |
| `rc_require_owner()` | Restricts all owner pages and actions to the owner account. |
| `rc_csrf_field()` / `rc_verify_csrf()` | Add and validate a session token for POST forms. |
| `rc_cents()` / `rc_money()` / `rc_money_db()` | Convert decimal currency strings to integer cents for arithmetic and format them for display/storage. |
| `rc_audit()` | Writes an action and before/after values to `audit_logs`. |
| `rc_notify()` / `rc_notify_owner()` | Creates customer- or owner-facing in-app notification records. |
| `rc_customer_for()` / `rc_order_for_user()` | Resolve the customer profile and enforce that customers can only view their own reservations. |
| `rc_create_reminders()` | Creates an in-app reminder when a confirmed event is within seven days, avoiding a duplicate reminder per order. |
| `rc_valid_date()` / `rc_valid_time()` | Validate strict date/time formats before saving booking data. |

## Existing functionality

### Public and customer side

- **Home/menu:** Shows active packages and menu items. Menu supports text search and category filtering.
- **Registration/login/logout:** Customer registration creates both a `users` record and linked `customers` profile. Passwords use PHP password hashing/verification; sessions are regenerated at authentication changes.
- **Customer dashboard:** Lists the customer’s reservations, balances, status and in-app notifications. It supports profile edits and marking customer notifications read.
- **Reservation submission:** Requires an active package, valid guest range, future/current date, valid time range and venue. Server-side checks verify selected food items belong to the selected package. It calculates fixed or per-person package prices, option surcharges and a rounded-up 50% deposit; saves the order, item snapshots and tentative event together in a transaction.
- **Reservation details:** Shows event/payment details and selected choices. Customers can submit one rating/comment for a completed order; the database has a unique order/customer feedback constraint.
- **Customer feedback page (`page=feedback`):** Public visitors can read owner-reviewed feedback. Signed-in customers can submit a 1–5 star rating for an order they own once it is Completed and has no prior review; a comment and JPG/PNG/WebP photo up to 5 MB are optional. New reviews stay private until the owner marks them reviewed and can preview the attached photo before publishing.
- **Password change:** Requires the current password and matching new password fields (10-character minimum in the form and handler).
- **Notifications:** Reservation, payment, status, delivery, feedback and upcoming-event notices are stored in the application. Customers see an unread-count bell in the navigation and an inbox on the dashboard; no email or SMS transport is implemented.

### Owner side

- **Owner dashboard (`page=owner`):** Summary counts, booked sales, collected payments, active outstanding balances, upcoming confirmed events, recent orders, feedback count, today’s event count, unread owner notification count, and a selectable payment chart period (today/week/month/year).
- **Owner navigation:** Admin accounts get a persistent Dashboard, Orders, Operations, Feedback, Menu, Packages, Sales and Settings sidebar. Inventory is omitted because the pasted specification says it was removed from the finalized scope.
- **Orders (`page=orders`):** Search by customer/contact/package/order ID and filter by status, payment state, date and package. Shows order details, item snapshots and payment history. Owner can approve/reject pending requests, record offline payments, and cancel eligible requests.
- **Operations (`page=operations`):** Day/week/month views of event records, venue and customer data. Only confirmed events occupy the schedule. Confirmation checks payment and looks for overlapping confirmed time slots.
- **Menu administration (`page=menu-admin`):** Create/edit menu items, descriptions, categories, prices, optional image URLs and dietary flags; activate/deactivate items. Order item snapshots protect previous bookings from later menu edits.
- **Package administration (`page=packages-admin`):** Create/edit packages, pricing type, guest minimum/maximum, image URL and permitted menu choices; activate/deactivate packages. New package choices are grouped by menu category and treated as a required single choice.
- **Offerings (`page=offerings`):** Combined item/package availability and edit interface. It overlaps with the separate menu and package administration pages.
- **Payments:** The owner can record Cash, GCash, Bank Transfer or Check transactions, optionally with a reference. The handler locks the order, blocks overpayment, updates the order balance/payment state, adds a notification and audit entry, and commits the updates.
- **Reservation lifecycle:** Pending → Confirmed → Processing → Completed, with cancellation from pending/confirmed/processing and rejection from pending only. Confirmation requires the 50% deposit and a free schedule. Owner delivery updates are Preparing → On the Way → Delivered, and completion requires full payment, Delivered progress, and the scheduled event end time to have passed. Rejection is owner-only and unpaid-only.
- **Sales:** Reservation totals and payment history with event-date, status, payment method, package and search filters; printable reports and CSV export. This is an operational report, not accounting/tax reporting.
- **Customer directory:** Search customer identity/contact records, view reservation/payment totals and open their orders. Admin can update current contact details; changes are audited and do not rewrite historical reservation snapshots.
- **Feedback administration:** Rating summary/distribution, customer comments and uploaded photos; Admin can preview and publish feedback without changing the original submission.
- **Users/settings/audit:** The owner can enable or disable customer accounts, reset customer passwords, edit owner/business details and policy notes, change the owner password, and inspect recent audit records. Customer accounts cannot be promoted to owner.
- **Notifications:** Admin can review the notification history addressed to their account and mark individual or all notifications read. The navigation shows the unread count.

## Owner navigation guide

The links use the page query parameter. After the front controller is repaired and the database migrations are installed, sign in using the initial Admin account and navigate as follows:

1. **Owner overview:** `index.php?page=owner`. Start here for pending reservations, payments, events and feedback.
2. **Review reservations:** use “Review pending orders” or open `index.php?page=orders`. Search/filter, open “View details”, record a payment, then approve eligible orders.
3. **Set up offerings:** open `index.php?page=menu-admin` to create food items, then `index.php?page=packages-admin` to create packages and attach choices/surcharges. Use `index.php?page=offerings` for the combined management view.
4. **Schedule events:** open `index.php?page=operations`, choose Day, Week or Month, and select an event to open its reservation.
5. **Read feedback:** open `index.php?page=feedback-admin`; mark each new entry reviewed after reading it.
6. **Review financial activity:** open `index.php?page=sales` to filter, print or export reservation totals and payment history. The dashboard chart can be filtered by period.
7. **Find a customer:** open `index.php?page=settings`, then Customer directory, to search/edit contact details and open their orders.
8. **Administration:** use Settings to update the owner profile and business details, manage customer accounts, review notifications/audit activity, and change the password.
9. **Sign out:** use the Sign out button in the common navigation. It submits the native POST action with CSRF protection.

The layout now uses the native `rc_url()` helper for navigation. The common dialog supports both details popups and confirmation forms.

## Repairs made and remaining risks

### Repaired: entry point and page rendering

`index.php` now requires the native file, calls `rc_boot()`, then calls `rc_dispatch()`. The common layout now outputs the captured page content and flash messages and includes the dialog required by confirmation/details buttons. PHP syntax checks pass. A live browser/database run is still required to prove startup and page queries succeed.

### Repaired: cancellation/rejection, logout and route controls

Cancel/reject actions now prompt for the required reason using the shared dialog, then submit that reason with the POST. Logout is a CSRF-protected POST form. Header links now use native generated routes, and the legacy owner dashboard URL points to the Admin owner page.

### Medium: owner notifications lack an inbox

Owner notifications appear in the inbox and the header bell shows its unread count. Customer delivery/payment/status notifications appear on the dashboard and customer bell; customers can clear them from the dashboard.

### Repaired: package surcharge controls

Package create/update forms now expose a surcharge for each menu choice; reservation estimates and totals include these charges. Verify the business meaning and customer-facing wording before launch.

### Medium: duplicate and legacy implementation surfaces invite confusion

The native PHP code, CodeIgniter-style controllers/models/routes, old views and static HTML coexist. Their route and schema expectations differ (for example, legacy models query old column names). The application README currently describes the native system, but old code still looks executable. Choose/document the supported implementation, then archive or clearly label the unused code.

### Other limitations stated by the project itself

- No payment gateway; payment entries are manually recorded offline.
- No inventory/stock, POS, external payment gateway, payroll or tax/accounting module. Owner-managed food delivery progress is implemented as Preparing / On the Way / Delivered, with customer in-site notifications and unread badges.
- No uploaded owner profile photo, configurable deposit percentage/payment-method rules, or notification preference controls. Current payment methods and the 50% deposit are enforced system rules.
- Cancellation policy is configured: the 10% fee is included within the non-refundable 50% deposit. Amounts paid above the deposit are shown as refund due and issued manually.
- A 30-day-retention PowerShell database backup script is provided. It still needs a production backup destination, restricted credentials, and a daily Task Scheduler entry on the production host.
- The feedback page, PHP syntax and local image/database paths have had basic local smoke checks. The complete authenticated submit → admin approval → public display flow still needs a browser walkthrough. No automated regression suite is currently present.

## Instructor-style opinion

The strongest part of the project is its business-rule thinking. It models the full reservation lifecycle, preserves historical menu details, uses integer-cent arithmetic for important totals, checks deposit and balance requirements, prevents confirmed schedule overlaps, records audit history, and separates owner and customer permissions. These are meaningful design choices for a school or portfolio system and go beyond a simple CRUD application.

The main remaining weakness is integration discipline. Several generations of application structure are mixed together, and the system still needs a complete database-backed/browser walkthrough. The repaired source addresses the entry point and key UI/handler mismatches, but code review alone cannot establish that every real workflow now succeeds.

Recommended order of work:

1. Run the application against a fresh database using the documented migration order and verify home, setup, login and dashboard pages.
2. Exercise booking, deposit/payment, approval/overlap, cancellation/rejection, completion, feedback and role changes in a browser.
3. Verify package surcharges from Admin setup through the customer estimate, saved total and order detail.
4. Configure the production HTTPS certificate, restricted DB credentials, owner setup secret and scheduled backups using deployment/README.md.
5. Remove/label legacy code and document the single supported implementation.
6. Add automated regression coverage and complete security/usability review before production deployment.

## How to interpret “working perfectly”

Static inspection can establish that code exists and that important validations/transactions are present. It cannot prove that the database migrations apply cleanly, that all PHP/MariaDB versions support every query, that JavaScript dialogs and responsive layouts work in target browsers, or that real user flows succeed. Therefore the safe classification is:

- **Implemented in source:** described functionality has corresponding views, routes/dispatch cases and handlers.
- **Partially complete:** interface/handler mismatches or missing operational pieces remain.
- **Runtime verified:** not yet. The bootstrap defect has been fixed and syntax-checked, but this review did not run against a live database or browser.

## Follow-up local verification — 26 September 2026

- Imported `riz_catering.sql` into an isolated temporary MariaDB schema with no SQL errors; verified all four views, seven routines, and the delivery/refund order columns. The temporary schema was removed afterward.
- PHP lint passed for all 41 PHP files. The PowerShell backup script parsed successfully.
- Local Apache returned HTTP 200 for home, menu, feedback, and owner-login pages. Deployment files and the environment template returned HTTP 403 as intended.
- The active Apache PHP SAPI reports `upload_max_filesize=40M` and `post_max_size=40M`, above the requested minimum.
- This does not replace the signed-in full browser lifecycle walkthrough or production-host setup. No production domain/certificate, production database credentials, backup credentials/destination, or scheduled-task account is configured in this workspace.