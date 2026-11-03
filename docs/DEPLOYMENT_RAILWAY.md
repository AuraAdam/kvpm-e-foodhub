# Deploy E-FoodHub with GitHub + Railway

This application is PHP 8.2+ with MySQL/MariaDB. GitHub stores the source; Railway runs the PHP container and database. Netlify and GitHub Pages cannot execute this PHP backend.

## Before deployment

- Create a GitHub repository and push this project.
- Do not upload `.env`, real user data, database dumps, or production credentials.
- Use a new, empty production database. Import `database/schema.sql` only; never import `database/seed.sql` into production.
- Create the first administrator using `database/create_admin.php` from a secure one-off CLI session, or use your hosting provider's console. Do not expose this script through a public URL.

## GitHub

```bash
git init
git add .
git commit -m "Prepare E-FoodHub for deployment"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/kvpm-e-foodhub.git
git push -u origin main
```

The included GitHub Actions workflow checks PHP syntax and runs the automated HTTP test suite on pushes and pull requests.

## Railway deployment

1. Create a Railway project and add a MySQL service.
2. Add a new service from your GitHub repository. Railway will detect the included `Dockerfile`.
3. In the PHP service's Variables, add the following. Use the values/references shown by your Railway MySQL service; do not paste credentials into GitHub.

| Variable | Value |
|---|---|
| `APP_NAME` | `E-FoodHub` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `0` |
| `APP_TIMEZONE` | `Asia/Kuala_Lumpur` |
| `DB_DRIVER` | `mysql` |
| `DB_HOST` | MySQL private host |
| `DB_PORT` | MySQL port (usually `3306`) |
| `DB_DATABASE` | MySQL database name |
| `DB_USERNAME` | MySQL username |
| `DB_PASSWORD` | MySQL password |
| `SESSION_SECURE_COOKIE` | `1` |
| `UPLOAD_MAX_BYTES` | `2097152` |

4. Deploy the service and generate a public domain in Railway's networking settings. The included Dockerfile serves only the `public/` directory and enables Apache rewrite rules.
5. Import `database/schema.sql` into the production MySQL database using a secure database client. The schema creates the application's tables and default settings. Do not import demo seed data.
6. Create the first administrator using a secure one-off command/console in the app container: `php database/create_admin.php`. Follow its prompts. If your provider does not offer an interactive one-off command, run the script locally against the production database through a trusted secure connection, then remove any temporary local credential files.
7. Sign in, create a seller account/application, approve it as admin, and test a small end-to-end order using the simulated payment option.

### Persistent uploads

The app stores uploaded product/shop images under `public/uploads/`. Container filesystems can be replaced during redeploys. Attach a persistent Railway volume mounted at `/var/www/html/public/uploads` if uploaded images must survive redeploys. Ensure the directory is writable by `www-data`. This mount starts empty; that is fine for a clean production installation.

### Health check

Railway can use `/health.php` as its health-check path. It returns only `ok` and does not expose database credentials or application configuration.

## Multi-campus setup

Public customer registration is enabled by design. New accounts receive the customer role only. A customer may apply to open a shop and must enter the full name of the Kolej Vokasional campus where the shop operates. Approved sellers can update this under Profil Kedai. An administrator must approve the application before products become visible. For an existing database, apply `database/migrations/2026_11_03_add_campus_name_to_seller_profiles.sql` once before deploying this version.

## Production checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=0`.
- [ ] HTTPS is enabled and `SESSION_SECURE_COOKIE=1`.
- [ ] Production database is empty/new and schema imported; no demo seed data.
- [ ] A unique, strong database password is used.
- [ ] First administrator password is unique and at least 10 characters.
- [ ] Public uploads have persistent storage and are writable.
- [ ] Demo accounts are not present; login page no longer advertises demo credentials.
- [ ] Test customer registration, seller approval, product creation, cart, checkout, order status changes, cancellation and reviews.
- [ ] Confirm backup and restore procedures for MySQL and uploaded images.
- [ ] Replace simulated payment with a compliant payment gateway before accepting real online payments.

## Important limitations

- Online payment is simulated; the application does not charge cards or transfer money.
- There is no email/SMS delivery for password recovery or notifications.
- The app has not had an independent security audit or production concurrency/load test. Treat the first deployment as a controlled pilot, not a production-grade campus payment platform.
- Back up the database and uploads separately. A persistent volume is not a backup.
