RIZ CATERING FOOD ORDERING SYSTEM

LOCAL SETUP

1. Start Apache and MySQL from XAMPP.
2. Create a MySQL database named riz_catering, then import riz_catering.sql.
   The SQL file now includes the base tables, full application schema, four
   views, four procedures, numeric/string/business-rule functions, and triggers.
   Import it into a new, empty DB.
   Do not re-import it into an existing installation; the separate files in
   Application/Database contain staged upgrades. Apply
   Application/Database/business_offerings.sql once after the base schema to
   add serving units, packed meal tiers, buffet sets, and order type support.
   This migration seeds the menu products and business package presets from
   the catering brief while preserving existing reservations and their totals.
   The fresh-import schema includes delivery and cancellation fields. Existing
   installations that already have the full native schema should apply
   Application/Database/owner_customer_roles.sql once if they still have legacy
   staff roles, then apply Application/Database/delivery_cancellation_policy.sql
   once to add order delivery progress and cancellation/refund calculations.
3. Browse to http://localhost/riz_catering/ or /customer/ for the customer
   portal. The customer entrypoint is customer/index.php. Owner sign-in is at
   /owner/login and the separate owner entrypoint is owner/index.php. Owner
   work pages use /owner/dashboard, /owner/orders, /owner/menu, /owner/packages,
   and other /owner/... routes. Customer pages never render the owner sidebar;
   owner sessions are redirected to their workspace.
4. Configure a private RIZ_OWNER_SETUP_KEY value in the Apache/PHP environment
   before using the first-time owner setup link. This master credential is
   required once; setup closes after one owner account is created. Use a
   unique password of at least 14 characters. The owner signs in through the
   separate owner portal. Sign in, then create menu
   selections under Menu and active packages under Packages before customers
   can reserve.
5. Customers may order a la carte dishes by quantity, select a packed meal
   tier, reserve a buffet set, or choose a custom package with or without an
   account. Guest orders require contact details and receive a session-only
   confirmation. Customer accounts link future bookings to order history,
   booking notifications, and feedback eligibility after completion. Packed
   meals and buffet sets require at least 50 guests.
6. The owner records offline payments. Confirmation requires the 50% deposit, and
	completion requires the full balance. Payment gateway processing is not part
	of this application.
7. Customers can open Feedback from the site navigation to read approved reviews.
   Signed-in customers can submit one review per completed reservation, with a
   star rating and optional comment/photo. Admin approval publishes a review.

DATABASE CONFIGURATION

The defaults target the standard local XAMPP account (localhost, database
riz_catering, user root, empty password). Set RIZ_DB_HOST, RIZ_DB_NAME,
RIZ_DB_USER, RIZ_DB_PASSWORD, and RIZ_OWNER_SETUP_KEY in the PHP/Apache
environment for other installations. Set RIZ_OWNER_SETUP_KEY to a long random
value and never publish it. Do not use an empty MySQL root password in
production.

SCOPE NOTES

There is no inventory, stock, POS, or external payment-gateway module.
The owner records Cash, GCash, Bank Transfer, and Check payments as an audit
ledger. The owner updates food progress (Preparing, On the Way, Delivered),
which customers see on the reservation and through in-site notification badges. The owner must mark food Delivered before completing an order.
No email or SMS transport is used.

On cancellation, the 50% deposit is non-refundable and includes the 10%
cancellation fee; the fee is not added a second time. Any amount already paid
above the deposit is shown as refund due. The owner issues that refund manually
outside this system. This is an operational policy choice and should match the
business's published customer terms.

Feedback photo uploads accept JPG, PNG, and WebP up to 5 MB. Local XAMPP PHP is
configured for 40M upload and POST limits. Production PHP needs at least 6M
upload_max_filesize and 8M post_max_size. HTTPS, production credentials, and
scheduled database backups must be configured on the actual production host;
see deployment/README.md for the prepared account template, backup script and
host-specific steps.

SECURITY

The app uses password_hash/password_verify, session ID rotation, role checks,
a database unique-key guard that permits at most one Admin/owner, a 24-hour
owner inactivity timeout, prepared SQL statements, escaped HTML output, CSRF
tokens on POST actions, and an Apache rule blocking direct access to
Application internals and SQL/text files. Customers cannot open owner routes.
Deploy with HTTPS and a restricted database account.
