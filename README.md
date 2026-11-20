# PHP Data Management & Export System

A classic PHP 8.1+ and MySQL administration application for student, course, instructor, enrollment, and payment records. It uses PDO, prepared statements, PHP sessions, CSRF tokens, server-side validation, and reusable module and export configuration.

## Features

- Administrator sign-in with `password_hash()` / `password_verify()`, plus CSRF-protected one-time account setup.
- Responsive dashboard with database-backed record counts.
- Create, list, update, and safely delete records in all five management modules.
- Server-side search, sorting, filters, and pagination (10, 25, 50, or 100 rows).
- Reusable filtered CSV, PDF (Dompdf), and Excel (PhpSpreadsheet) exports, plus a clean print report.
- Email any filtered report as an automatically generated PDF attachment.
- Foreign-key restrictions prevent deleting students and courses with related records.

## Requirements

- Windows with XAMPP (Apache, MySQL, PHP 8.0 or later).
- PHP extensions required by PhpSpreadsheet, including `gd`, `mbstring`, `xml`, `zip`, and `pdo_mysql`.
- Composer 2.

Enable the listed PHP extensions in `C:\xampp\php\php.ini` if necessary, then restart Apache. Confirm PDO MySQL is enabled.

## Setup on XAMPP

1. Copy the project folder into `C:\xampp\htdocs\php_data_management`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. If this is a new installation, in phpMyAdmin select **Import**, choose `database/php_data_management.sql`, and run it. The SQL creates the `php_data_management` database, five business tables, and the `admins` table. No example records are inserted. For an existing installation, run `ALTER TABLE admins MODIFY password VARCHAR(255) NOT NULL;` in phpMyAdmin instead of importing the full file.
4. Configure database connection values as process environment variables if your local MySQL setup differs from the defaults: `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. The default is the standard local XAMPP `root` account with an empty password; change it for your environment. Do not commit credentials.
5. From a terminal in the project directory, run:

   ```powershell
   composer install
   ```

6. Open `http://localhost/php_data_management/scripts/create_admin.php` and enter a username, password, and confirmation. Choose a password of at least 12 characters. The application hashes it with `password_hash()` before inserting it into `admins`. The setup page disables itself as soon as an administrator exists.
7. After creating the account, remove `scripts\create_admin.php` from the web server (or otherwise deny web access to it). If retained, it remains disabled while an administrator exists. Then open `http://localhost/php_data_management/` and sign in using the username and password you created.

### Migrating existing administrator passwords

The application stores new passwords using `password_hash()` and verifies them with `password_verify()`. If an older installation has plaintext passwords, first make sure the `admins.password` column is `VARCHAR(255)`, then run this one-time migration from a terminal in the project directory:

```powershell
C:\xampp\php\php.exe scripts\migrate_admin_passwords.php
```

The script is CLI-only, uses a transaction and prepared statements, replaces plaintext values with `password_hash()` hashes without changing admin IDs or usernames, and skips values recognized as password hashes or hash-like strings. It reports counts only; it never prints passwords or hashes. Existing administrators can continue signing in with their existing plaintext passwords after migration. Keep the script out of the web server or remove it after running.

## Configuration

`config/config.php` reads database settings from environment variables. `APP_BASE_URL` can override the URL prefix when the app is deployed under a different path. The default empty value supports the directory name shown above. Configure HTTPS at the web server when deploying publicly; session cookies are marked secure when HTTPS is detected.

Configure outbound report email on the server with `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` (`tls`, `ssl`, or `none`), `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and optionally `MAIL_FROM_NAME`. The default port is 587 and the default encryption is TLS. Leave both username and password empty only when the SMTP server permits unauthenticated delivery. Do not put SMTP credentials in frontend code or commit them to the repository. The Send Mail action includes every record matching the current search, filters, and sort, just like PDF download; it does not depend on the current table page.

For a local Gmail setup, copy `config/mail.local.php.example` to `config/mail.local.php`, then set `MAIL_PASSWORD` to a newly generated Google App Password (16 characters; remove spaces). The default Gmail username and sender address are `nexorainstitute026@gmail.com`; change both in the local file if you use another Gmail account. `config/mail.local.php` is ignored by Git and must never be shared. The application loads `config/mail.php` automatically and then applies the local override.

The application targets PHP 8.0+ and MySQL 8+ (or a compatible XAMPP MariaDB release). Install Composer dependencies after copying the project. The `vendor/` directory is intentionally excluded from source control.

## Project layout

```text
assets/       Styles and small interface interactions
auth/         Sign-in and sign-out
config/       PDO and application configuration
dashboard/    Database-backed overview
database/     SQL schema
exports/      Shared CSV, XLSX, PDF, and print endpoint
includes/     Authentication, validation, shared layout and module logic
modules/      Shared CRUD list, form, save, and delete handlers
students/     Student list entry point
courses/      Course list entry point
instructors/  Instructor list entry point
enrollments/  Enrollment list entry point
payments/     Payment list entry point
scripts/      One-time administrator setup page (remove after setup)
```

## Export behavior

Exports use the same allowlisted module definitions, search, filters, and sort order as the record list. They include every matching record rather than only the current page. PDF and XLSX require the Composer packages; CSV and print reports are generated by the application.

## Troubleshooting

- **Database connection error:** verify MySQL is running and confirm `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`.
- **Excel/PDF export reports missing dependencies:** run `composer install` and check PHP extensions required by PhpSpreadsheet.
- **Report email cannot be sent:** verify the server-side `MAIL_*` settings, SMTP connectivity, and that Composer dependencies are installed. The mail form never exposes SMTP credentials.
- **Database driver missing:** enable `pdo_mysql` in XAMPP's `php.ini` and restart Apache.
- **Administrator setup fails:** confirm the existing `admins` table has the specified `admin_id`, `username`, `password`, and `created_at` columns, that the username is unique, and that `password` is `VARCHAR(255)`.
- **Blank page:** check the Apache/PHP error log; application errors are logged rather than exposing SQL details to visitors.
- **Cannot delete a linked record:** first remove the related enrollment/payment record, then retry. Foreign-key restrictions are intentional.

## Security notes

All management pages require an authenticated administrator. Writes use POST and CSRF tokens; queries are parameterized and request-controlled SQL identifiers are never accepted. Output is escaped before rendering. Configure a strong MySQL password and HTTPS before exposing the app beyond a local development machine.
