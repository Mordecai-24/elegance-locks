# Elegance Locks - Salon Management System

A salon management system built with core PHP, MySQL (PDO), PHPMailer and Tailwind CSS (CDN).
It has three roles: **customers**, **salonists** and **administrators**, all signing in through one login page.

## Features

- Customer registration with 6-digit email OTP verification (10 minute expiry, resend cooldown, attempt limit)
- One common login page; the role is read from the database and decides the redirect
- Customer dashboard: book appointments, appointment history, status tracking, cancellation, profile
- Salonist dashboard: assigned appointments, customer details, status updates, profile
- Admin dashboard: statistics, customers, salonist accounts (create, edit, activate/deactivate), appointments (assign, change status, cancel), services, profile
- Every sidebar link is its own PHP page; all pages share one header, sidebar and footer through `includes/`
- Collapsible sidebar (icons only or icons and labels, remembered in the browser) and a mobile menu

## Project structure

```
elegance_locks/
|-- index.php                 Redirects to login or the right dashboard
|-- login.php                 Common login (no role dropdown)
|-- register.php              Customer registration
|-- verify-otp.php            Email OTP verification and resend
|-- logout.php                Secure logout (POST + CSRF only)
|-- setup-admin.php           One-time creation of the first administrator
|-- config/
|   |-- config.php            App settings (base URL, OTP rules, opening hours, SETUP_KEY)
|   |-- database.php          PDO connection (XAMPP defaults)
|   |-- mail.php              <-- ENTER YOUR GMAIL ADDRESS AND APP PASSWORD HERE
|   `-- mail.example.php      Template copy of mail.php
|-- includes/                 Shared code: init, helpers, auth, OTP, mailer, layouts, navigation
|-- admin/                    dashboard, customers, salonists, salonist_form, appointments, services, profile
|-- customer/                 dashboard, book, appointments, profile
|-- salonist/                 dashboard, appointments, profile
|-- assets/                   css/custom.css, js/app.js, images/
|-- database/elegance_locks.sql
|-- vendor/                   PHPMailer
|-- composer.json
`-- .gitignore
```

## Setup on XAMPP (Windows)

1. **Extract** the ZIP so the folder is `C:\xampp\htdocs\elegance_locks`
   (open it and you should see `index.php`, `config`, `admin`... directly inside).
2. **Start Apache and MySQL** in the XAMPP Control Panel.
3. **Import the database:** open http://localhost/phpmyadmin, click **Import**, choose
   `database/elegance_locks.sql`, click **Import**. It creates the `elegance_locks` database,
   all tables and the starting services.
4. **Dependencies:** PHPMailer is already included in `vendor/`, so no extra step is needed.
   If you prefer Composer, open a terminal in the project folder and run `composer install`
   (or `composer require phpmailer/phpmailer`). Composer will replace `vendor/autoload.php`
   with its own version, which is fine.
5. **Gmail settings:** edit `config/mail.php`:
   ```php
   'username'   => 'YOUR_GMAIL_ADDRESS@gmail.com',
   'password'   => 'YOUR_GOOGLE_APP_PASSWORD',
   'from_email' => 'YOUR_GMAIL_ADDRESS@gmail.com',
   ```
   Create the App Password in your Google Account: Security > 2-Step Verification (turn it on) > App passwords.
   Paste the 16-character code. Never commit or share this file (it is already in `.gitignore`).
6. **Create the first administrator:**
   - Open `config/config.php` and change `SETUP_KEY` to a private value of your own.
   - Visit http://localhost/elegance_locks/setup-admin.php, enter that key and the admin details.
   - After one administrator exists this page locks itself permanently.
7. **Open the app:** http://localhost/elegance_locks/

If your database user is not `root` with an empty password, change it in `config/database.php`.
If you use a different folder name, change `BASE_URL` in `config/config.php`.

## Quick test walk-through

1. Log in as the administrator, open **Salonists** and add a stylist.
2. Log out. Register a customer; the 6-digit code arrives by email. Enter it on the verification page.
3. Log in as the customer and book an appointment.
4. Log in as the administrator, open **Appointments**, assign the stylist and set the status to Confirmed.
5. Log in as the salonist and update the status as the appointment progresses.

## Appointment rules

- Open daily 09:00 to 18:00, 30 minute start slots (change in `config/config.php`).
- Past dates and times that already passed today are rejected.
- A service cannot run past closing time.
- A customer cannot hold two overlapping appointments; a time is blocked when every active salonist is busy.
- An administrator cannot assign a salonist who already has an overlapping appointment.
- Salonists move appointments forward only (Pending > Confirmed > In Progress > Completed) and cannot start or complete one before its date.
- Customers can cancel Pending or Confirmed appointments at least 2 hours before the start.

## Security overview

- Passwords: `password_hash()` / `password_verify()`. OTPs: random 6 digits from `random_int()`, stored only as a hash, single use.
- PDO prepared statements everywhere; all output escaped with `htmlspecialchars`.
- CSRF token on every POST form; logout is POST-only.
- Session ID regenerated on login; idle timeout of 30 minutes; role and active status re-checked from the database on every protected page.
- Failed logins are throttled per email; OTP attempts are limited.
- `.htaccess` files block browser access to `config/`, `includes/`, `database/`, `vendor/` and `storage/`.

## Uploading to GitHub

```bash
cd C:\xampp\htdocs\elegance_locks
git init
git add .
git status        # check that config/mail.php is NOT listed
git commit -m "Initial commit: Elegance Locks salon management system"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/elegance_locks.git
git push -u origin main
```

`config/mail.php` and `vendor/` are ignored by Git. Anyone who clones the repository copies
`config/mail.example.php` to `config/mail.php` and runs `composer install`.

## Images

The login and registration pages use a remote photo set in `AUTH_IMAGE_URL` (`config/config.php`).
For an offline or permanent version, save your own photo in `assets/images/` and point `AUTH_IMAGE_URL` to it.

## Troubleshooting

- **"Database connection failed"**: MySQL is not running, or the SQL file was not imported.
- **Styles missing**: Tailwind loads from a CDN, so the computer needs internet access.
- **"Mail is not configured yet"**: fill in `config/mail.php` as shown above.
- **"The email could not be sent"**: check the Gmail address, the App Password (2-Step Verification must be on) and your connection.
- **Links go to the wrong place**: set `BASE_URL` in `config/config.php` to match your folder name.
