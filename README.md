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

Mail uses the `log` driver, so email verification links appear in `storage/logs/laravel.log`.

## Pages

| URL | Description |
|-----|-------------|
| `/` | Landing page: campaign carousel, about (vision and mission), top 3 campaigns, features with a working zakat calculator, testimonials, appeal, contact, footer. The navbar scrolls to each section. |
| `/login`, `/register` | Authentication. |
| `/home` | Hero with personal stats and kindness streak, shortcuts (profile, campaigns, history), top campaigns with a CTA box, prayer wall, news. |
| `/campaigns` | Search, category/status/urgent filters, sort, and a quick-look popover on hover. |
| `/campaigns/{slug}` | Image, target and progress bar, cash-out count with the Open Fund Ledger, donor count, story, donate and share buttons, expandable prayers, campaigner profile. |
| `/campaigns/{slug}/donate` | One-click amount presets, custom amount, email, payment method, anonymous ("Hamba Allah") switch, donate on behalf of someone. |
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
- Users can only view or confirm their own donations; other users get a 404.
- All input is validated. Search terms escape `LIKE` wildcards. User text is escaped on output and tags are stripped on save.
- Uploads must be JPG, PNG or WebP images, 2 MB maximum.
- Security headers are added: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and HSTS on HTTPS.

## Before going live

- **Payments are simulated.** The "I have paid" button stands in for a gateway callback (`DonationController@confirm`). Connect a real gateway such as Midtrans or Xendit and confirm donations from a verified webhook.
- **Testimonials are placeholders.** The personas in `config/infaqin.php` are fictional. Replace them with real endorsements you have permission to use.
- **Contact details are placeholders.** Update the company contact details and social links in `config/infaqin.php`.
- Configure a real mailer in `.env`. Paid donations send a receipt email.
- **Have a lawyer review `/terms`.** It is a solid draft written for Indonesian law (including UU PDP No. 27/2022), but it is not legal advice.
- Run the scheduler (`php artisan schedule:work`, or a cron entry for `schedule:run`). It cancels pending donations older than 24 hours every hour.

## Tests

```bash
php artisan test
```
