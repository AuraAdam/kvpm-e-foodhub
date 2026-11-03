# Automated test results

- Run: 2026-10-26 12:07:00 (Asia/Kuala_Lumpur)
- Environment: PHP 8.3.6 built-in server, **SQLite** test database built from database/schema.sql + database/seed.sql
- Result: **58 of 58 passed**

> These results were produced by `php tests/run_tests.php` and were not edited by hand. They do not cover MySQL/MariaDB-specific behaviour (row locking with SELECT … FOR UPDATE, ON UPDATE timestamps) or Apache .htaccess rules; see docs/TESTING.md for the manual XAMPP checklist.

| ID | Test | Status | Observed failure |
|---|---|---|---|
| TC-AUTH-01 | Register a new customer account | Pass |  |
| TC-AUTH-02 | Duplicate e-mail is rejected | Pass |  |
| TC-AUTH-03 | Weak password and mismatched confirmation are rejected | Pass |  |
| TC-AUTH-04 | Tampered role field cannot create an administrator | Pass |  |
| TC-AUTH-05 | Wrong password shows a generic error and does not sign in | Pass |  |
| TC-AUTH-06 | Login is blocked after 5 failed attempts (rate limit) | Pass |  |
| TC-AUTH-07 | Session ID changes on login; logout requires POST | Pass |  |
| TC-AUTH-08 | Guests are redirected to login for protected pages | Pass |  |
| TC-CAT-01 | Keyword search matches product name, description or shop name | Pass |  |
| TC-CAT-02 | Category, price-range and in-stock filters change the results | Pass |  |
| TC-CAT-03 | Sort by price (low to high) orders results correctly | Pass |  |
| TC-CAT-04 | Pending/unapproved product is hidden from catalogue and product page | Pass |  |
| TC-CAT-05 | Pagination limits results to 12 per page and keeps filters | Pass |  |
| TC-SEC-01 | SQL injection attempts in search, sort and IDs are harmless | Pass |  |
| TC-SEC-02 | User input is HTML-escaped (reflected XSS in search) | Pass |  |
| TC-SEC-03 | Security headers are sent | Pass |  |
| TC-SEC-04 | POST without a valid CSRF token is refused (419) | Pass |  |
| TC-SEC-05 | Login "next" parameter cannot redirect to another site | Pass |  |
| TC-SEC-06 | Project files outside public/ are not served | Pass |  |
| TC-CART-01 | Add to cart, update quantity, remove (server-side totals) | Pass |  |
| TC-CART-02 | Cannot add more than stock, out-of-stock or unapproved items | Pass |  |
| TC-CART-03 | JSON add-to-cart returns the new cart count | Pass |  |
| TC-CART-04 | A seller cannot add their own product to the cart | Pass |  |
| TC-CHK-01 | Multi-shop checkout: 1 order, 1 part per shop, stock deducted, cart emptied | Pass |  |
| TC-CHK-02 | Re-submitting the same checkout form cannot create a second order | Pass |  |
| TC-CHK-03 | Checkout re-checks stock: nothing is ordered if stock ran out meanwhile | Pass |  |
| TC-CHK-04 | Checkout validates required fields | Pass |  |
| TC-ORD-01 | Customers see only their own orders (other orders return 404) | Pass |  |
| TC-ORD-02 | Customer cancels a pending part; stock restored exactly once | Pass |  |
| TC-ORD-03 | Customer cannot cancel another customer's order part | Pass |  |
| TC-SEL-01 | Seller moves an order through the full workflow; cash recorded on completion | Pass |  |
| TC-SEL-02 | Invalid status jumps and customer-only actions are refused | Pass |  |
| TC-SEL-03 | Seller cannot view or change another shop's orders and products | Pass |  |
| TC-SEL-04 | Rejecting needs a reason; rejection restores stock | Pass |  |
| TC-SEL-05 | Seller creates a product with a valid image (stored with a random name, pending approval) | Pass |  |
| TC-SEL-06 | Uploads reject PHP code disguised as an image, and oversized files | Pass |  |
| TC-SEL-07 | Negative price/stock and invalid category are rejected | Pass |  |
| TC-SEL-08 | Customers cannot open seller pages (403) | Pass |  |
| TC-SEL-09 | Seller reports count only completed orders as sales; CSV export works | Pass |  |
| TC-PAY-01 | Simulated online order: seller cannot confirm before payment | Pass |  |
| TC-PAY-02 | Simulated failure records a failed payment and leaves the order unpaid | Pass |  |
| TC-PAY-03 | Simulated success marks it paid; a second payment is refused; seller can confirm | Pass |  |
| TC-PAY-04 | Customer cannot pay someone else's order or cancel after confirmation | Pass |  |
| TC-ADM-01 | Non-administrators get 403 on admin pages and actions | Pass |  |
| TC-ADM-02 | Approving a seller application makes the account a seller (audited) | Pass |  |
| TC-ADM-03 | Approving a pending product makes it visible; rejecting needs a reason | Pass |  |
| TC-ADM-04 | Suspended users cannot sign in, and are signed out immediately | Pass |  |
| TC-ADM-05 | Self-lockout and last-administrator protection | Pass |  |
| TC-ADM-06 | Promoting to administrator requires your own password and is audited | Pass |  |
| TC-ADM-07 | A category in use cannot be deleted; an empty one can | Pass |  |
| TC-ADM-08 | Admin status override needs a reason, is audited, and refunds are flagged | Pass |  |
| TC-ADM-09 | Temporary password reset forces a password change at next sign-in | Pass |  |
| TC-ADM-10 | Suspending a shop hides all its products from the public | Pass |  |
| TC-ADM-11 | Admin dashboard metrics match the database | Pass |  |
| TC-REV-01 | Reviews: only for completed purchases, once per item, rating 1–5 | Pass |  |
| TC-REV-02 | Admin can hide a review (reason required); it disappears publicly | Pass |  |
| TC-NOT-01 | Notifications list and mark-as-read only touch your own notifications | Pass |  |
| TC-INT-01 | Database integrity: no negative stock, totals match items, no orphans | Pass |  |
