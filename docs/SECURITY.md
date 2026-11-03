# Security checklist

These protections are implemented and most are covered by automated tests (IDs refer to [TESTING.md](TESTING.md)). This is a student project: the measures below make it reasonably safe for a campus demonstration, but **it has not had an independent security review and is not claimed to be production-ready.**

## Authentication

| Protection | How it is done | Where | Test |
|---|---|---|---|
| Passwords hashed | `password_hash(PASSWORD_DEFAULT)` (bcrypt), checked with `password_verify`; re-hashed if the algorithm changes | `AuthController`, `AccountController` | TC-AUTH-01 |
| Password rules | ≥ 8 characters with letters and digits (≥ 10 for administrators created by CLI) | `AuthController::passwordStrengthError` | TC-AUTH-03 |
| Login rate limiting | 5 failures per e-mail (20 per IP) in 15 minutes blocks further attempts | `AuthController::tooManyAttempts`, `login_attempts` | TC-AUTH-06 |
| No user enumeration | Same message for unknown e-mail and wrong password; a dummy hash is checked so timing is similar | `AuthController::login` | TC-AUTH-05 |
| Session fixation | `session_regenerate_id(true)` on login and password change | `Auth::login` | TC-AUTH-07 |
| Session cookie | `HttpOnly`, `SameSite=Lax`, `Secure` when `SESSION_SECURE_COOKIE=1`, strict mode, 2-hour idle timeout | `Session::start` | — |
| Logout | POST only, destroys session and cookie | `AuthController::logout` | TC-AUTH-07 |
| Suspension takes effect immediately | The user row is re-read every request; suspended users are signed out | `Auth::user` | TC-ADM-04 |
| No public admin registration | Admins come only from `database/create_admin.php` or promotion by another admin (password re-entry) | `routes/web.php`, `UserController::role` | TC-AUTH-04, TC-ADM-06 |
| No hard-coded credentials | Demo passwords exist only in the demo seed file; the real admin password is typed at the CLI | `database/` | — |
| Account recovery | Admin issues a one-time temporary password; the user must change it before doing anything else; it is never logged | `UserController::resetPassword`, `Middleware::auth` | TC-ADM-09 |

## Authorisation

| Protection | How | Test |
|---|---|---|
| Role checks on every protected route | Middleware listed per route in `routes/web.php` | TC-AUTH-08, TC-SEL-08, TC-ADM-01 |
| Ownership checks | Every seller query includes `seller_id = <your shop>`; every customer order query includes `customer_id = <you>`; otherwise 404 | TC-ORD-01, TC-ORD-03, TC-SEL-03, TC-PAY-04, TC-NOT-01 |
| Status changes checked server-side | `OrderService::allowedTransitions()` per role | TC-SEL-02, TC-PAY-04 |
| Self-lockout / last admin | Admins cannot suspend or demote themselves; the last active admin cannot be suspended or demoted | TC-ADM-05 |
| Mass assignment | Only named fields are copied from `$_POST`; role, status, prices and seller IDs never come from the form | TC-AUTH-04, TC-CART-01 |

## Injection and output

| Protection | How | Test |
|---|---|---|
| SQL injection | All queries use PDO prepared statements with native (non-emulated) prepares. Sort orders come from an allow-list; LIMIT/OFFSET are cast to integers; LIKE wildcards are escaped | TC-SEC-01 |
| XSS | Every dynamic value is printed with `e()` (`htmlspecialchars`, ENT_QUOTES, UTF-8), including data from the database | TC-SEC-02, TC-REV-01 |
| Content Security Policy | `script-src 'self'` — no inline scripts or inline event handlers anywhere | TC-SEC-03 |
| Other headers | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin` | TC-SEC-03 |
| CSV injection | Product names starting with `= + - @` are prefixed in the CSV export | code review |
| Open redirects | Redirect targets must be local paths (`safe_local_path`) | TC-SEC-05 |

## CSRF

Every POST is checked by the router against a per-session 64-hex token (`hash_equals`). Forms include it with `csrf_field()`; the JavaScript add-to-cart sends it in the form data. No state is changed by GET requests. — TC-SEC-04

## File uploads

| Check | Implementation | Test |
|---|---|---|
| Allowed types | JPEG, PNG, WebP detected from file contents with `finfo` | TC-SEL-06 |
| Extension matches type | `.jpg/.jpeg`, `.png`, `.webp` only, must match the detected type | TC-SEL-06 |
| Size limit | 2 MB (`UPLOAD_MAX_BYTES`) | TC-SEL-06 |
| Must be a real image | `getimagesize()` and maximum 5000 px per side | TC-SEL-06 |
| Strip embedded content | Re-encoded with GD when available | TC-SEL-05 |
| Safe filenames | 32 random hex characters + extension; original name discarded | TC-SEL-05 |
| No execution | `public/uploads/.htaccess` removes PHP handlers and denies script extensions | Manual (Apache only) |
| Deletion limited | Only files with generated names can be deleted by the app | code review |

## Business logic

| Risk | Protection | Test |
|---|---|---|
| Price tampering | Prices and totals always recalculated from the database at checkout | TC-CART-01, TC-CHK-01 |
| Overselling | Row lock + guarded `UPDATE … WHERE stock >= q` inside a transaction; `CHECK (stock >= 0)` | TC-CHK-03, TC-INT-01 |
| Double checkout | One-time checkout token in the session and a UNIQUE `checkout_token` column | TC-CHK-02 |
| Double payment | Order row locked; only pending + unpaid parts can be paid | TC-PAY-03 |
| Restoring stock twice | `stock_restored` flag | TC-ORD-02 |
| Fake reviews | Only the buyer of a completed order line, once per line | TC-REV-01 |

## Errors and logging

- `APP_DEBUG=0` (default) shows a generic message; details go to `storage/logs/app.log`.
- Database connection errors show a helpful message without credentials.
- `storage/logs/.htaccess` denies web access, and the root `.htaccess` sends every request into `public/` (TC-SEC-06).
- `audit_logs` records approvals, suspensions, role changes, overrides, password resets and settings changes — never passwords or tokens.

## Known limitations

- No two-factor authentication.
- No e-mail verification of new accounts.
- Rate limiting is per e-mail/IP in the database; behind a shared NAT many students share one IP (limit set to 20 per IP to reduce false blocks).
- Upload execution protection relies on Apache honouring `.htaccess`; on other servers configure the equivalent rule.
- The login page lists demo accounts unless `APP_ENV=production`.
- The temporary-password flow depends on the administrator verifying identity in person.
- Concurrency protection was reasoned about and tested functionally on SQLite, but not load-tested on MySQL.
