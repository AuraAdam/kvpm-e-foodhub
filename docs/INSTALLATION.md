# Installation guide (XAMPP on Windows)

This guide sets up KVPM E-FoodHub on a Windows laptop with XAMPP. It also works on macOS/Linux XAMPP with the paths adjusted.

## 1. Requirements

| Item | Version |
|---|---|
| XAMPP | 8.2.x or newer (includes Apache, PHP 8.2+, MariaDB, phpMyAdmin) |
| PHP extensions | `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (all enabled in a default XAMPP 8.2 install) |
| Browser | Current Chrome, Edge or Firefox |

Optional: Git, and a code editor such as Visual Studio Code.

## 2. Copy the project

Copy the whole `kvpm-e-foodhub` folder to:

```
C:\xampp\htdocs\kvpm-e-foodhub
```

## 3. Start the services

Open the **XAMPP Control Panel** and press **Start** for **Apache** and **MySQL**. Both should turn green.

## 4. Create the database

1. Open <http://localhost/phpmyadmin>.
2. Choose the **Import** tab.
3. Select `C:\xampp\htdocs\kvpm-e-foodhub\database\schema.sql` and press **Import**.
   It creates the database `kvpm_efoodhub`, all tables and the default settings.
4. *(Demo only)* Import `database\seed.sql` the same way. It adds demo accounts, shops, products and orders.

> `schema.sql` **drops and recreates** the tables. Never import it into a database that holds data you want to keep. See [DATABASE.md](DATABASE.md#upgrading-an-existing-database).

Command-line alternative:

```
C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root < database\seed.sql
```

## 5. Configure the environment

Copy `.env.example` to `.env` in the project root. The defaults match a fresh XAMPP:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kvpm_efoodhub
DB_USERNAME=root
DB_PASSWORD=
```

If you gave the MySQL `root` user a password, put it in `DB_PASSWORD`. Keep `APP_DEBUG=0` unless you are fixing an error.

## 6. Open the site

<http://localhost/kvpm-e-foodhub/public/>

(<http://localhost/kvpm-e-foodhub/> also works: the root `.htaccess` forwards to `public/`.)

## 7. Demo accounts

Only present if you imported `seed.sql`. Password for all: **`Demo@1234`**.

| Role | E-mail | Notes |
|---|---|---|
| Administrator | `admin@demo.test` | Full admin access |
| Seller | `kantin@demo.test` | Gerai Nasi Mak Su |
| Seller | `kopi@demo.test` | Kopi Kampus |
| Seller | `pena@demo.test` | Kedai Pena Biru (stationery) |
| Seller | `snek@demo.test` | Snek Rangup Kelantan (has a product awaiting approval) |
| Customer | `pelanggan@demo.test` | Has past orders |
| Customer | `pelanggan2@demo.test` | Has a pending and a ready order |
| Customer | `pemohon@demo.test` | Has a shop application awaiting approval |

The login page shows these accounts while `APP_ENV` is not `production`.

Demo product images are illustrated placeholders (emoji on a coloured background, labelled "Gambar ilustrasi"), not photos of real products.

### Refreshing demo dates

Dashboards show the last 7/30/90 days. Demo orders are dated relative to the day `seed.sql` was generated. To regenerate with today's dates:

```
C:\xampp\php\php.exe database\seed.php
```

then re-import `schema.sql` and `seed.sql`.

## 8. Creating a real administrator

There is no web page for creating administrators — by design. For a clean installation:

1. Import **only** `schema.sql`.
2. Run:
   ```
   cd C:\xampp\htdocs\kvpm-e-foodhub
   C:\xampp\php\php.exe database\create_admin.php
   ```
3. Enter a name, e-mail and a password of at least 10 characters with letters and numbers.
4. Sign in at `/login`.

The password is typed by you and stored only as a hash. It is never written into any file.

## 9. Before using it for real (not just a demo)

- Do **not** import `seed.sql`.
- Set `APP_ENV=production` and `APP_DEBUG=0` in `.env`.
- Give the MySQL user a password and use a dedicated MySQL user with rights only on `kvpm_efoodhub`.
- Serve the site over HTTPS and set `SESSION_SECURE_COOKIE=1`.
- Point the web server's document root at `public/` if your host allows it.

## 10. Troubleshooting

| Symptom | Likely cause and fix |
|---|---|
| "Tidak dapat menyambung ke pangkalan data" | MySQL is not running in XAMPP, or `.env` has the wrong host/user/password/database. Check the database name is `kvpm_efoodhub`. |
| `SQLSTATE[HY000] [1049] Unknown database` (with `APP_DEBUG=1`) | `schema.sql` was not imported. |
| `SQLSTATE[HY000] [1045] Access denied` | Wrong `DB_USERNAME`/`DB_PASSWORD`. |
| `could not find driver` | `pdo_mysql` is disabled. In `C:\xampp\php\php.ini` remove the `;` before `extension=pdo_mysql`, restart Apache. |
| Every page except the homepage shows "Not Found" from Apache | `mod_rewrite` is off or `.htaccess` is ignored. In `C:\xampp\apache\conf\httpd.conf` make sure `LoadModule rewrite_module` is not commented and `AllowOverride All` is set for `htdocs`. Restart Apache. |
| CSS missing, page unstyled | Open the site through `http://localhost/...`, not by double-clicking a file. |
| "Sesi borang telah tamat" (419) | The form was open too long or cookies are blocked. Reload the page and submit again. |
| Image upload says "terlalu besar" | Limit is 2 MB (`UPLOAD_MAX_BYTES`). PHP's own limit is `upload_max_filesize` in `php.ini` (XAMPP default 40M). |
| Uploads fail with a permission error | On macOS/Linux, make `public/uploads/*` and `storage/logs` writable by the web server. |
| Port 80 already in use | Another program (often Skype or IIS) uses port 80. Stop it or change Apache's port in XAMPP → Config → `httpd.conf` (`Listen 8080`), then use `http://localhost:8080/...`. |
| Dashboards show no sales | The demo data is older than the selected period. Regenerate it (section 7). |
| Blank page or generic error | Look in `storage/logs/app.log`. Temporarily set `APP_DEBUG=1` to see the message in the browser. |
| Fonts look different | Plus Jakarta Sans loads from Google Fonts. Offline, the system font is used. This does not affect function. |

## 11. Running the automated tests

Requires PHP with the `pdo_sqlite`, `curl` and `gd` extensions (enable them in `php.ini` if needed):

```
C:\xampp\php\php.exe tests\run_tests.php
```

The tests use their own SQLite file in `storage/` and never touch your MySQL data.

## 12. Git and GitHub

```
cd C:\xampp\htdocs\kvpm-e-foodhub
git init
git add .
git commit -m "Initial commit: E-FoodHub"
git branch -M main
git remote add origin https://github.com/<your-username>/kvpm-e-foodhub.git
git push -u origin main
```

`.gitignore` already excludes `.env`, logs, local databases and user uploads (demo images are kept). Before every push, check `git status` and make sure no `.env` or personal data is listed.

Suggested commit style — one change per commit, present tense:

```
Add seller order rejection with reason
Fix catalogue price filter when max is empty
Document XAMPP troubleshooting
```

## 13. Hosting notes

This is a PHP + MySQL application. It needs a host that runs PHP 8.2+ and provides a MySQL/MariaDB database (most shared cPanel hosts do). **Static hosts such as GitHub Pages, Netlify or Vercel's static hosting cannot run it**, because they do not execute PHP or provide MySQL.

On a cPanel host: create the database and user in "MySQL Databases", import `schema.sql` in phpMyAdmin (without `seed.sql`), upload the project, create `.env` with the host's database details, and set the domain's document root to the `public/` folder if possible.

## Existing database upgrade (multi-campus field)

Back up the database first. For installations created before the multi-campus update, run `database/migrations/2026_11_03_add_campus_name_to_seller_profiles.sql` once in phpMyAdmin or a trusted MySQL client. New installations already include this field in `database/schema.sql`. Existing shops are assigned `Kolej Vokasional Pasir Mas` by default; approved sellers can update the campus name from Profil Kedai.
