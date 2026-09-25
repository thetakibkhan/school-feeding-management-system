# School Feeding Management System TODO

## Foundation

- [x] Create the Laravel 13 application skeleton.
- [x] Install Composer dependencies and verify the default test suite.
- [ ] Configure PHP 8.4, MySQL 8.4, environment variables, and Bootstrap; Laravel Fortify is installed and configured.
- [x] Add the base role enum, authentication middleware, and seeded test accounts.
- [ ] Establish the controller -> application service -> focused repository structure.
- [ ] Add CI checks for formatting, static analysis, tests, and the frontend build.

## Epic 1 Authentication and user management

- [x] Build the Fortify-backed Admin and Field Staff authentication boundary with public registration disabled.
- [x] Build Admin user management: list, create, edit, and deactivate accounts.
- [x] Test authentication, inactive-account protection, user management, role access, and unauthorized route protection.

## Epic 2 School management

- [x] Create schools and effective-dated student counts.
- [x] Build Admin school add, view, edit, partial-search, and safe-delete pages.
- [ ] Seed the supplied Anwara school data.
- [x] Test Bangla names, code uniqueness, effective-date count lookup, duplicate effective dates, partial search, and safe deletion.
- [x] Block deletion when delivery history references a school; delete unused student-count records with an unused school.

## Epic 3 Demand, calendar, and schedule

- [ ] Create food-item, ration-setting, non-working-date, and date-wise schedule models.
- [ ] Seed the September 2026 schedule: 16 Bun, 12 Boiled Egg, and 5 Banana dates.
- [ ] Build Admin schedule management with date-level item checkboxes/toggles.
- [ ] Implement shared demand calculation in the required order: holiday/off-day, unscheduled item, then official student/ration calculation.
- [ ] Test the supplied 90% basis, rounding, schedule behavior, and holiday override.

## Epic 4 Deliveries

- [ ] Create delivery and delivery-correction-history records.
- [ ] Build the Field Staff mobile delivery form and My Entries list.
- [ ] Validate ownership, dates, duplicate school/date entries, quantities, schedule, holidays, and chalan uploads.
- [ ] Normalize and privately store chalan photos through Laravel Filesystem.
- [ ] Test create, correction, and invalid-input paths.

## Epic 5 Dashboard and Daily Delivery Report

- [ ] Build shared daily demand-versus-delivery reporting.
- [ ] Build the dashboard and shortfall list.
- [ ] Build print and Excel export for the Daily Delivery Report.
- [ ] Test missing-entry, holiday, unscheduled-item, shortfall, excess, and total behavior.

## Epic 6 Official reports

- [ ] Implement Forms 4, 7, 10, 12, and 13 as Blade report layouts.
- [ ] Use mPDF with Noto Sans Bengali for printable PDF exports.
- [ ] Derive Forms 12 and 13 from the delivery ledger without adding a separate inventory workflow.
- [ ] Test calculations, Bangla output, total rows, and long-report pagination.

## Submission readiness

- [ ] Complete the README with setup steps, packages, test logins, assumptions, and known limitations.
- [ ] Run tests, coverage, formatting, static analysis, dependency audit, and production build.
- [ ] Deploy to a compatible free host and configure private object storage.
- [ ] Capture required screenshots or a short recording.
