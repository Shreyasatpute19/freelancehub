# FreelanceHub – Online Freelancing and Service Marketplace

PHP + MySQL + Bootstrap 5 project (Agile-built, runs on XAMPP).

## Requirements
- XAMPP (Apache + MySQL/MariaDB + PHP 7.4 or newer; PHP 8.x recommended) on Windows, Linux or macOS
- A web browser. Internet is needed only for the Google Fonts and Bootstrap CDN files (the site still works without them, but looks plain).

## Setup (XAMPP, Windows)
1. Install XAMPP and open the **XAMPP Control Panel**. Click **Start** for **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\` (any folder name works, for example `freelance`).
3. Open http://localhost/phpmyadmin → **Import** → *Choose file* → select `database.sql` → **Go**.
   (This creates the database `freelance_db`, the 9 tables, 8 categories and the admin account.)
4. Open http://localhost/freelance (use your folder name in the address).
   The site finds its own URL path automatically. Only edit `config.php` if your MySQL user is not `root` with an empty password.

**Admin login:** admin@market.com / Admin@123 (change it from Profile → Change password).

## Troubleshooting
| Problem | Fix |
|---|---|
| "Database connection failed" | Start MySQL in XAMPP, import `database.sql`, check `DB_USER` / `DB_PASS` in `config.php` |
| Page looks unstyled / links go to a wrong page | Open the site through `http://localhost/...` (not by double-clicking a file). If it still fails, set `$manualBase = '/yourfolder';` in `config.php` |
| Apache will not start (port 80 busy) | Close Skype/IIS or change Apache port in XAMPP (Config → httpd.conf → `Listen 8080`), then use `http://localhost:8080/freelance` |
| Image upload fails | Use JPG/PNG/WebP under 2 MB; make sure the `uploads/avatars` and `uploads/portfolio` folders exist |
| Blank white page | Enable error display: in `config.php` add `ini_set('display_errors', 1);` at the top |

## Try the full flow
1. Register a **Freelancer** → complete profile → add a service → upload portfolio.
2. Log in as **Admin** → Services → Approve the service (and Users → Verify the freelancer).
3. Register a **Client** → Find Services → open service → Book.
4. Freelancer: open booking → Accept. Client: Pay (demo gateway). Chat on the booking page.
5. Freelancer: Start work → Mark completed. Client: leave rating & review.
6. Admin: Overview shows reports and statistics.

## Folder structure
```
freelance/
├── config.php            DB connection, settings, session
├── database.sql          Schema + categories + admin account
├── includes/             functions, components (UI), header, footer, booking helpers
├── assets/               css, js, images
├── uploads/              avatars + portfolio images
├── index.php  register.php  login.php  logout.php
├── services.php  service.php  freelancer.php     (search, details, public profile)
├── book.php  booking.php  my_bookings.php  chat_fetch.php
├── dashboard.php  profile.php  my_services.php  service_form.php  portfolio.php
└── admin/                index, users, services, bookings, categories
```

## Design system (Spatial UI)
- `assets/css/style.css` is organised in 9 numbered sections: tokens, scene, type, surfaces, controls, data, feedback, components, motion.
- All colors, glass levels (`--glass-1/2/3`), radii and depth shadows are CSS variables at the top. Change them once to re-theme the whole site.
- Fonts: Bricolage Grotesque (headings) and Figtree (body), loaded from Google Fonts.
- Reusable PHP components live in `includes/components.php`:
  `page_header()`, `empty_state()`, `service_card()`, `booking_stepper()`.
- The navigation dock is in `includes/header.php` and highlights the current page automatically.
- Motion respects `prefers-reduced-motion`.

## Database tables
users, freelancer_profiles, categories, services, portfolio, bookings, payments, messages, reviews

## Security features
Prepared statements (PDO), password hashing, CSRF tokens on all forms, output escaping, role-based access,
validated image uploads (type + size), PHP execution disabled in `uploads/`.

## Notes
- Payment is a **demo gateway** (records a transaction). For real payments integrate Razorpay/Stripe in `booking.php` (action `pay`).
- Freelancer services must be approved by admin before appearing in search (Service Verification).
- Future scope from the synopsis: mobile app, AI recommendations, video chat, notifications, subscriptions.

## Changes in this version (code review fixes)
- `config.php`: automatic `BASE_URL` detection, IST timezone, clearer database-error message.
- `includes/functions.php`, `includes/components.php`: removed PHP 8-only syntax (`never`, `match`) and added small polyfills so the project also runs on PHP 7.4.
- `services.php`: the rating filter is now applied in `WHERE` (more reliable than a `HAVING` on an alias).
- `booking.php`: the demo payment is wrapped in try/catch with rollback.
- `portfolio.php`, `admin/categories.php`: guarded against a missing `id` field.
- `uploads/.htaccess`: wrapped in `IfModule` blocks so it can never cause a 500 error, and now also denies script files.
- Added `.gitkeep` files so the empty `uploads/` folders are kept in Git and ZIP files.
