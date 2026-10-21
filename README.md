# Infaqin

A transparent donation platform built with Laravel 12, Blade, Tailwind CSS 4 and Alpine.js. Brand palette, typography (Poppins / Montserrat), logos, icons and campaign photos come from the Infaqin Figma asset sheet.

## Getting started

```bash
composer install
npm install
cp .env.example .env        # skip if .env already exists
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build               # or `npm run dev` while working on the UI
php artisan serve
```

Open http://127.0.0.1:8000.

### Demo accounts

| Role  | Email               | Password      |
|-------|---------------------|---------------|
| Admin | admin@infaqin.test  | `Admin#12345` |
| Donor | user@infaqin.test   | `Donor#12345` |

Local mail goes through Mailtrap SMTP: verification emails and donation receipts land in your Mailtrap inbox. Fill in the `MAIL_*` variables in `.env` with the SMTP credentials from your Mailtrap inbox (`MAIL_MAILER=smtp`). Tests use the `array` mailer and never send mail.

## Midtrans sandbox

Payments go through Midtrans Snap. A donation starts as `pending` and only changes status through the Midtrans webhook, the `donations:expire` command, or the donor cancelling it.

1. Create a [Midtrans sandbox](https://dashboard.sandbox.midtrans.com) account and copy the server key and client key from Settings → Access Keys into `.env`:

   ```dotenv
   MIDTRANS_SERVER_KEY=
   MIDTRANS_CLIENT_KEY=
   MIDTRANS_IS_PRODUCTION=false
   ```

   Then run `php artisan config:clear`.

2. Midtrans needs a public URL to send payment notifications. Expose the local server with ngrok:

   ```bash
   php artisan serve
   ngrok http 8000
   ```

3. In the sandbox dashboard, go to Settings → Payment → Notification URL and set the Payment Notification URL to:

   ```
   https://<your-ngrok-host>/payments/midtrans/notification
   ```

   The free ngrok host changes every time ngrok restarts, so update this URL each time.

4. Make a donation, choose a payment method in the Snap popup, then pay it with the [Midtrans payment simulator](https://simulator.sandbox.midtrans.com) (for example, paste the virtual account number or QRIS image). The webhook marks the donation `paid`, updates the campaign total and sends the receipt. Requests arriving through ngrok are visible at http://127.0.0.1:4040.

### Scheduler

Run the scheduler alongside the server:

```bash
php artisan schedule:work
```

It runs `donations:expire` every hour. For each pending donation past its 24-hour payment window, the command asks Midtrans for the transaction status and applies it. A transaction Midtrans does not know (for example, the donor closed Snap without choosing a method) is marked `expired`; one that is still pending at Midtrans is checked again next hour.

## Pages

| URL | Description |
|-----|-------------|
| `/` | Landing page: campaign carousel, about (vision and mission), top 3 campaigns, features with a working zakat calculator, testimonials, appeal, contact, footer. The navbar scrolls to each section. |
| `/login`, `/register` | Authentication. |
| `/home` | Hero with personal stats and kindness streak, shortcuts (profile, campaigns, history), top campaigns with a CTA box, prayer wall, news. |
| `/campaigns` | Search, category/status/urgent filters, sort, and a quick-look popover on hover. |
| `/campaigns/{slug}` | Image, target and progress bar, cash-out count with the Open Fund Ledger, donor count, story, donate and share buttons, expandable prayers, campaigner profile. |
| `/campaigns/{slug}/donate` | One-click amount presets, custom amount, email, anonymous ("Hamba Allah") switch, donate on behalf of someone. The payment method is chosen in the Midtrans Snap popup. |
| `/history` | Donation history with search, sort, and filters for status, payment method and date range. |
| `/profile` | Edit name, username, date of birth and phone. Shows the email address, sends a verification link, changes the password. |
| `/news` | News written by admins. |
| `/terms` | Terms and Conditions (17 sections, including privacy and refunds) with a table of contents that follows your scroll position. |
| `/faq` | 24 questions in 6 categories, with live search, category filters and an accordion. Questions live in `config/infaqin.php`. |
| `/admin` | Admin backend: dashboard, news CRUD, campaign CRUD, ledger withdrawals, donations list. |

Error pages for 401, 403, 404, 419, 429, 500 and 503 live in `resources/views/errors`.

## Security

- Passwords are hashed with bcrypt and must contain 8+ characters with mixed case, a number and a symbol.
- Login is rate limited per email and IP (5 attempts), the session is regenerated on login and invalidated on logout, and failed logins show a generic message.
- Every form has CSRF protection. Register, login, donate, comment and verification routes are throttled.
- `is_admin` cannot be mass assigned. Admin routes require the `admin` middleware.
- Users can only view or cancel their own donations; other users get a 404.
- Donating requires a verified email address.
- The Midtrans webhook verifies the `signature_key` of every notification and is the only route exempt from CSRF.
- All input is validated. Search terms escape `LIKE` wildcards. User text is escaped on output and tags are stripped on save.
- Uploads must be JPG, PNG or WebP images, 2 MB maximum.
- Security headers are added: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and HSTS on HTTPS.

## Before going live

- **Switch Midtrans to production.** Use the production keys, set `MIDTRANS_IS_PRODUCTION=true`, and register `https://<your-domain>/payments/midtrans/notification` as the Payment Notification URL in the production dashboard.
- **Testimonials are placeholders.** The personas in `config/infaqin.php` are fictional. Replace them with real endorsements you have permission to use.
- **Contact details are placeholders.** Update the company contact details and social links in `config/infaqin.php`.
- Configure a real mailer in `.env`. Paid donations send a receipt email.
- **Have a lawyer review `/terms`.** It is a solid draft written for Indonesian law (including UU PDP No. 27/2022), but it is not legal advice.
- Run the scheduler with a cron entry for `php artisan schedule:run` every minute (see [Scheduler](#scheduler)).

## Tests

```bash
php artisan test
```

### End-to-end tests (Playwright)

The E2E suite in `tests/e2e/` drives a real browser against a separate server on `http://127.0.0.1:8001` with its own database (`database/e2e.sqlite`). Your local `database/database.sqlite` is never touched.

One-time setup:

```bash
npm install
npx playwright install chromium
cp .env.e2e.example .env.e2e      # then fill in MIDTRANS_SERVER_KEY and MIDTRANS_CLIENT_KEY (sandbox)
```

`APP_KEY` in `.env.e2e` is generated on the first run. Keep `APP_ENV=e2e` and `DB_DATABASE=database/e2e.sqlite`: the reset script refuses to run with any other database path.

Run:

```bash
npm run test:e2e                  # builds assets, resets e2e.sqlite, starts the server, runs the suite
npx playwright test --ui          # interactive mode (run `npm run build` first)
npx playwright show-report        # HTML report of the last run
npm run e2e:reset                 # only recreate and seed e2e.sqlite
```

Playwright starts `php artisan serve --env=e2e --port=8001` itself, after `npm run e2e:reset` has recreated `database/e2e.sqlite` with `migrate --seed`. Port 8001 must be free.

- The donation tests call the Midtrans **sandbox**. They create a Snap token, check that the Snap popup appears, and cancel the donation. They never pay. Without sandbox keys in `.env.e2e`, those two tests fail with "No Snap token".
- Stop `npm run dev` before running the suite. While `public/hot` exists, pages load assets from the Vite dev server instead of `public/build`.
- Mail is written to `storage/logs/laravel.log` (`MAIL_MAILER=log`).
