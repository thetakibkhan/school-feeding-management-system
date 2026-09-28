## School Feeding Management System

Laravel 13 application for the Prottyashi school-feeding assessment.

### Local setup

Requirements: PHP and the extensions required by Laravel (including `pdo_mysql`), Composer, Node.js/npm, and MySQL.

1. Install dependencies and create your local environment file:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   npm install
   ```

2. Set the MySQL host, port, database, username, and password in `.env`. The application uses MySQL; do not switch the application database to SQLite.
3. On a new, empty assessment database only, initialize it once:

   ```bash
   php artisan migrate:fresh --seed
   ```

   Warning: `migrate:fresh` deletes every table in the configured database. Do not run it against a database with data you need to keep. Do not rerun seeders against an Admin-edited assessment database; no invented September delivery records are seeded.
4. Build frontend assets and start Laravel:

   ```bash
   npm run build
   php artisan serve
   ```

   For frontend hot reload during development, run `npm run dev` in a second terminal instead of `npm run build`.

### Assessment accounts

- Admin: `admin@example.com` / `password`
- Field Staff: `field@example.com` / `password`

For local use, the seed falls back to these credentials if account variables are blank. Before production seeding, set `ASSESSMENT_ADMIN_EMAIL`, `ASSESSMENT_ADMIN_PASSWORD`, `ASSESSMENT_FIELD_EMAIL`, and `ASSESSMENT_FIELD_PASSWORD` in the host environment. Production seeding rejects missing, shared, or default account passwords; it never overwrites an existing account password. Record assessment login credentials only in the private submission README if required. Never put database, Cloudinary, or `APP_KEY` secrets in Git, screenshots, or the submission README.

### Seed-data assumption

The supplied Anwara source files provide real EMIS codes but no official school-code values. The seed data therefore assigns deterministic codes `AN-001` through `AN-110` in the supplied School List order. An Admin can edit a school code later.

The September Daily Demand PDF has both a distribution sequence number and an actual calendar-date column. Its 21 listed date/item rows are seeded exactly, totaling 16 Bun, 12 Boiled Egg, and 5 Banana days. Dates absent from the table are not inferred as holidays or working dates.

For September 2026 report-generation demonstration, supplied demand quantities are used as assessment fixture deliveries assuming full fulfillment (delivered = demand), because actual delivery transactions were not supplied. The source-backed quantities are seeded separately for all 110 schools, totaling 317,664 Bun packets, 238,248 Egg pieces, and 99,270 Banana pieces. Real delivery records take precedence for a school when present; otherwise that school's fixture quantities are used. Fixture quantities are clearly marked as assessment/demo data and are not represented as Field Staff-entered transactions. No delivery or chalan records, chalan numbers/photos, stock values, invoice metadata, or other missing facts are created from these fixtures. Form 7 chalan counts come only from actual delivery records.

The supplied 120g Bun, 60g Boiled Egg, and 100g Banana values are per-item unit-weight specifications. Daily demand remains the effective beneficiary count for each item scheduled on an Admin-configured working date; unit weight is not a demand multiplier.

### Chalan photo storage

Delivery photos use Cloudinary authenticated storage by default. Set `CLOUDINARY_URL` in the ignored local `.env` file using the value from the Cloudinary console; never commit this credential. Laravel checks the signed-in Field Staff member owns the entry before issuing a short-lived Cloudinary download URL. To use private local storage for development instead, set `DELIVERY_PHOTOS_DISK=delivery_photos`.

### Epic 4 routes

- `/field-staff/deliveries` — Field Staff's own delivery entries
- `/field-staff/deliveries/create` — create an entry for a scheduled working date

New delivery entries and corrections require a chalan number and chalan date entered by Field Staff. The chalan date initially matches the selected delivery date and can be changed to the physical document's date. These values are never read from the photo by OCR. Existing entries with unknown chalan dates retain null until corrected; no historical date is invented.

### Official Form 4

Admin can open `/admin/reports/form-4` for preview, browser print, and direct PDF download. Each school gets one A4 portrait page. Static artwork comes from the supplied June Form 4; dates, school details, chalan references, quantities, and totals come from the selected month's stored records. The reference's printed school-code value corresponds to EMIS, so this field uses the school's EMIS code.

Missing delivery rows contribute zero to the current output and remain distinguishable in application logic from recorded zero quantities. Missing chalan references and dates stay blank/dashed. Warnings appear outside the printed page. Biscuit/milk quantities and signatures remain blank because the system has no verified records for them. For 31-day months, daily rows are slightly compressed within the same table area to keep the final day and totals on one school page.

The September PDF was rendered as 110 A4 portrait pages; representative page geometry was checked against the reference. Final human review of all school-name fits and physical print alignment remains recommended before submission.

### Official Form 7 preview

Admin can open `/admin/reports/form-7`, select a month, then use **Print / Save PDF**. The form uses page artwork derived from the supplied six-page Form 7 PDF, with monthly per-school challan counts and delivered packet/piece totals mapped into its 110 school rows. It is separate from the operational Daily Delivery Report.

The supplied example has 110 school slots, a fixed contractor name, and 120g/60g/100g item headings. The form refuses to silently omit a 111th school. The overlaid month title and explanatory notes still need final word-for-word sign-off against the source before the form is treated as an approved official output. Preview, browser printing, and direct PDF download are available.

For Form 7 reporting, an absent scheduled school/date delivery record contributes zero to the current aggregate but is not inserted as a database row and is not counted as a chalan. The preview shows how many expected records are missing and warns that some scheduled deliveries have not yet been entered. A recorded zero-quantity delivery remains distinguishable from an absent record. Print/PDF uses the currently entered records while showing the warning outside the official form layout.

### Official Form 10 preview

Admin can open `/admin/reports/form-10` for a selected month, print the official A4 page, or download its PDF. The June Form 10 is the layout reference. September quantities come from recorded delivery entries, the supplier comes from the verified September call-off, and the item unit prices (Bun 22.883, Boiled Egg 13.543, Banana 9.807) come from the supplied item-value file. Line totals and the rounded grand total are calculated from these stored facts.

The system assigns Form 10 invoice numbers sequentially, starting at `AN-00001`; there is no invoice-number input. Invoice date, contract number, and bank details are supplied as period metadata where available. Missing September fields stay blank. Missing scheduled delivery rows contribute zero to the current output until actual entries are recorded; they remain distinguishable from confirmed zero quantities and do not create database rows or count as challans. The preview warns outside the official form. The one-page PDF has been rendered and checked, while final word-for-word and print overlay approval against the reference remains open.

### Epic 1 routes

- `/login` — Fortify login
- `/dashboard` — authenticated dashboard
- `/admin/users` — Admin-only user management

### Assessment readiness checklist

1. Verify the supplied school, EMIS, principal-contact, September schedule, and item-price source data before initializing a fresh assessment database.
2. Create dedicated assessment Admin and Field Staff accounts; keep their passwords private.
3. Confirm every application page requires authentication and the correct role; confirm Field Staff can access only their own delivery entries and chalan photos.
4. Select and test a hosting provider early. Deploy Laravel and MySQL manually; configure HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, secure session cookies, and secrets in the host environment.
5. Keep Cloudinary only if authenticated chalan upload and viewing pass the smoke test.
6. Smoke-test Admin and Field Staff login, school management, delivery entry, dashboard, reports, Excel export, Bangla PDF output, printing, and chalan-photo access.
7. Finish README setup/assumptions and capture 3–5 clear screenshots. Keep the assessment deployment available for the required review period.

Deployment is intentionally provider-neutral and manual. Do not claim the project or all official forms are complete while any required form still has unfinished mapping or visual-fidelity checks. The current full test suite also requires the PHP SQLite PDO extension for its isolated SQLite tests; MySQL remains the application database.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
