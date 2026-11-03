# Testing

## 1. Approach

Testing has three layers:

| Layer | What | Status |
|---|---|---|
| Automated end-to-end | `php tests/run_tests.php` — 58 tests that drive the real application over HTTP and check the database | Executed — results below |
| Visual / responsive | Headless Chromium screenshots of key pages at 1366 px (desktop) and 390 px (phone) | Executed for the pages listed in section 4 |
| Manual on XAMPP + MySQL | Checklist in section 5, for the target environment | **Not Run** — must be done on your laptop |

Status values: **Pass**, **Fail**, **Blocked**, **Not Run**. A result is only recorded after the test was actually executed.

### How the automated tests work

1. `tests/support/make_sqlite_db.php` converts `database/schema.sql` and loads `database/seed.sql` into a fresh SQLite file (foreign keys and CHECK constraints enforced).
2. The application is started with PHP's built-in web server pointed at that file.
3. `tests/support/HttpClient.php` acts like a browser: cookies/sessions, CSRF tokens, form posts, multipart uploads.
4. Each test checks the HTTP response **and** the database.

**Limitation:** SQLite is not MySQL. The suite proves the PHP logic, routing, access control and the SQL both engines share. It does **not** prove MySQL-only behaviour: `SELECT … FOR UPDATE` row locking under real concurrency, `ON UPDATE CURRENT_TIMESTAMP`, MySQL strict-mode quirks, or Apache `.htaccess` rules. Section 5 covers those manually.

## 2. Automated test cases and results

The table below is copied from `tests/results/latest.md`, which the test runner writes itself.

- Run: 2026-10-26 11:58:55 (Asia/Kuala_Lumpur)
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

### What each group covers

| Group | Covers |
|---|---|
| TC-AUTH | Registration, validation, duplicate e-mail, role tampering, wrong password, rate limiting, session regeneration, logout, guest redirects |
| TC-CAT | Database search, category/price/stock filters, sorting, hidden pending products, pagination with filters |
| TC-SEC | SQL injection, reflected XSS, security headers, CSRF, open redirect, files outside `public/` |
| TC-CART | Add/update/remove, server-side totals ignoring a tampered price, stock and approval checks, JSON add, sellers buying their own product |
| TC-CHK | Multi-shop checkout, stock deduction, duplicate submission, stock re-check at checkout, validation |
| TC-ORD | Customer order privacy, cancellation, single stock restore |
| TC-SEL | Full workflow with cash record, invalid transitions, cross-shop access, reject with reason, image upload (valid / disguised PHP / oversized), invalid prices, customer blocked from seller area, report figures and CSV |
| TC-PAY | Simulated payment: blocked confirmation before payment, failure path, success path, no double payment, other customers blocked |
| TC-ADM | Admin-only access, seller approval, product moderation, suspension, self-lockout, promotion with password, category deletion rule, audited override with refund flag, temporary password, shop suspension, dashboard figures |
| TC-REV / TC-NOT | Review eligibility, duplicates, rating range, escaping, moderation; notification ownership |
| TC-INT | No negative stock, subtotals equal item sums, line totals correct, no foreign-key violations |

## 3. Bugs found during testing

| # | Found by | Problem | Cause | Fix | Re-tested |
|---|---|---|---|---|---|
| 1 | TC-SEC-06 | Any URL containing a dot (e.g. `/.env.example`) showed the homepage instead of 404. No file was leaked, but routing was wrong. | `base_path()` trusted `$_SERVER['SCRIPT_NAME']`, which PHP's built-in server sets to the requested path for such URLs | Only use `SCRIPT_NAME` when it ends in `/index.php` (`app/Helpers/functions.php`) | Pass |
| 2 | Screenshot review | Header **Daftar** button text was invisible | `.header-actions > a` colour overrode the primary button's white text | More specific rule for `.btn--primary` in the header | Visually confirmed |
| 3 | Screenshot review | Mobile menu button showed on desktop | `.btn` (declared later) overrode the media query that hides `.nav-toggle` | `.btn.nav-toggle` in the media query | Visually confirmed |
| 4 | Screenshot review | Phone header crowded; brand and buttons wrapped at 390 px | Too many items for the width | Hide the college line and the Daftar button below 480 px | Visually confirmed |
| 5 | Screenshot review | Edit-product price showed `3` instead of `3.00`; price box taller than stock box | DECIMAL value printed without formatting; grid fields stretched to row height | `number_format` in the form; `.field { align-content: start }` | Visually confirmed |
| 6 | Screenshot review | Second column in dashboard grids pushed down 16 px | `.panel + .panel` margin applied inside grids | Reset margin for grid children | Visually confirmed |
| 7 | Test report | Results file labelled UTC time as Malaysia time | Test runner did not set its timezone | `date_default_timezone_set('Asia/Kuala_Lumpur')` in the runner | Confirmed in `latest.md` |

Two first-run failures were mistakes in the **tests**, not the application, and were corrected: TC-CAT-01 expected search to match product names only (search also matches shop names, by design); TC-SEL-06's "oversized" fixture was under 2 MB, so it was rebuilt to exceed the limit.

## 4. Responsive / visual checks performed

Screenshots were taken with headless Chromium at 1366×900 and 390×900 and reviewed by eye. Fonts fell back to system fonts because Google Fonts was not reachable from the build environment.

| Page | Desktop | Phone |
|---|---|---|
| Homepage | Pass (after fixes 2–4) | Pass (after fix 4) |
| Catalogue | Pass | Pass |
| Product page | Pass | Pass |
| Checkout | Pass | Not Run |
| Customer order (slip) | Not Run | Pass |
| Seller dashboard | Pass (after fix 6) | Not Run |
| Seller order | Not Run | Not Run |
| Edit product | Pass (after fix 5) | Not Run |
| Admin dashboard | Pass | Not Run |

## 5. Manual checklist on XAMPP + MySQL (to be completed)

Run these on the laptop that will be used for the demonstration, after following [INSTALLATION.md](INSTALLATION.md). Record the **actual** result.

| ID | Feature | Steps | Expected result | Actual result | Status | Remarks |
|---|---|---|---|---|---|---|
| M-01 | Database import | Import `schema.sql`, then `seed.sql` in phpMyAdmin | Both import with no errors; 20 tables exist | | Not Run | |
| M-02 | Connection | Open `/kvpm-e-foodhub/public/` | Homepage shows categories, products and shops | | Not Run | |
| M-03 | Rewrite rules | Open `/kvpm-e-foodhub/public/products` and `/kvpm-e-foodhub/` | Catalogue loads; root URL forwards to the site | | Not Run | |
| M-04 | Wrong DB password | Set a wrong `DB_PASSWORD` in `.env`, reload | Friendly "Tidak dapat menyambung ke pangkalan data" page, no credentials shown | | Not Run | Restore `.env` after |
| M-05 | Create admin | Run `php database\create_admin.php` on a schema-only database | Admin created; can sign in | | Not Run | |
| M-06 | Customer journey | Sign in as `pelanggan@demo.test`, add items from 2 shops, check out with cash | Order created with 2 slips; stock reduced in phpMyAdmin | | Not Run | |
| M-07 | Seller journey | Sign in as the seller, move the order to *Selesai* | Customer sees each status; `payments` has a CASH row | | Not Run | |
| M-08 | Simulated payment | Check out with *Bayaran dalam talian*, simulate failure, then success | One failed and one success row in `payments`; seller can then confirm | | Not Run | |
| M-09 | Concurrent last unit | Set a product's stock to 1. In two different browsers (two customers) add it, open checkout in both, submit both quickly | Exactly one order succeeds; the other sees "baru sahaja kehabisan stok"; stock is 0, never −1 | | Not Run | Tests MySQL row locking |
| M-10 | `updated_at` | Edit a product, check `products.updated_at` in phpMyAdmin | Timestamp changes | | Not Run | |
| M-11 | Upload | Upload a real JPG product photo (< 2 MB) | Image shows on product page; file in `public/uploads/products/` with a random name | | Not Run | |
| M-12 | Upload execution blocked | Copy any `.php` file into `public/uploads/products/` and open it in the browser | 403 Forbidden or downloaded as text — never executed | | Not Run | Delete the file after |
| M-13 | Sensitive files | Open `/kvpm-e-foodhub/.env`, `/kvpm-e-foodhub/database/schema.sql`, `/kvpm-e-foodhub/storage/logs/app.log` | 404 or 403 for all | | Not Run | |
| M-14 | Admin moderation | As admin approve *Puding Roti Karamel* and the *Kek Lapis Cik Ros* shop | Product visible publicly; applicant becomes seller | | Not Run | |
| M-15 | Mobile | Open the site on a real phone on the same Wi-Fi (`http://<laptop-IP>/kvpm-e-foodhub/public/`) | Menu, cart, checkout and order slip usable; no sideways scrolling | | Not Run | |
| M-16 | Keyboard | Use only Tab/Enter/Space to search, add to cart and check out | Focus is always visible; dialog can be confirmed/cancelled with the keyboard | | Not Run | |
| M-17 | Print | On an order page press **Cetak** | Clean printout without header, footer or buttons | | Not Run | |
| M-18 | Backup/restore | Export the database in phpMyAdmin, drop it, import the export | Site works with the same data | | Not Run | |

## 6. Re-running

```
php tests/run_tests.php          # macOS/Linux
C:\xampp\php\php.exe tests\run_tests.php   # Windows (needs pdo_sqlite, curl, gd enabled in php.ini)
```

Exit code 0 means every test passed. Results are rewritten to `tests/results/latest.md` on every run — copy it into your report as evidence.
