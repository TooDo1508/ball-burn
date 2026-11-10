# Project audit — 2026-11-09

## Scope and method

Inventoried the tracked project: backend contracts/services/repositories/models, routes, requests/resources, jobs, migrations/seeders, both Vue entry points, UI components, build/runtime configuration and existing tests/documentation. Dependency trees, generated Wayfinder modules and compiled output are excluded from source cleanup. SQL exports and historical migrations are retained.

Used static class/component reference searches and JSCPD on application PHP, public Vue, Inertia Vue/TypeScript, routes and config. The initial scan (excluding the generated-style UI primitives) covered 307 sources / 21,092 lines and reported 13 clone pairs / 151 duplicated lines (0.72%). These are detector findings, not proof that every pair should be merged. Manual inspection additionally found repeated schedule predicates and duplicate guest-order serialization below the detector's matching threshold.

## Changes

- Centralized the five paid/unexpired-pending schedule predicates in `Booking::occupyingSchedule()`. Retained transaction boundaries, court locking, strict deadlines and half-open interval comparisons.
- Eager-loaded orders with schedule bookings to avoid a separate order lookup per row when rendering the admin schedule.
- Grouped bookings/holds/closures by court once for the public grid. Daily slot pricing now selects applicable price rules once per court instead of filtering/sorting them for every half-hour. Range pricing reuses rules within a date and recalculates them across dates.
- Replaced duplicated guest submission/status payloads with `GuestBookingOrderResource`. Removed the pass-through `BookingOrder::currentStatus()` method and three unused legacy booking/availability resources. Public field names and private-data restrictions are retained.
- Shared public blog summary serialization across initial HTML data and JSON endpoints. Retained detail-only content/SEO and API-only URL/update fields.
- Removed the unused `CourtRepository::findActiveById` contract/implementation and unused static `ApiResponse::envelope` builder after checking their references.
- Removed redundant authorization override in the payment-proof request; its base request already authorizes guests.
- Removed 14 unreferenced, empty `<template />` component files from `src/`. Kept actual public router pages, active UI and reusable nonempty primitives. Laravel auto-discovered commands/policies and Inertia glob-loaded pages were not classified as dead merely for lacking imports.
- Corrected OCR eligibility: `payments.status` no longer exists after the legacy-workflow migration, but mapping still required it to equal PENDING. Mapping now checks `paid_at` plus the authoritative order status/deadline. Removed the invented legacy status from the unit fixture and added a regression using persisted payments.
- Rewrote `AGENTS.md` around current workflows, schema, entry points, checks and known limits. Kept security, repository-layer, visual and safe database practices.

## Duplication intentionally retained

- Public Vue Router and Inertia each serve active routes. The legacy public home remains reachable via client navigation even though the server home is now Inertia; removing it requires a deliberate navigation migration.
- Framework configuration blocks and Reka/shadcn primitives have similar structures but separate contracts. Removing them solely to reduce a clone percentage would obscure configuration or component behavior.
- Contact list/detail payloads have different timestamp precision and detail fields. Admin booking and closure validation have distinct error wording. These small, endpoint-specific blocks remain explicit.
- Single and bulk admin operations retain separate transaction/locking flows; consolidating them needs a dedicated concurrency review.

## Follow-up findings

- Admin single/bulk status changes lock courts but do not recheck overlapping effective bookings before turning an expired pending order into PAID. This existing late-payment conflict is outside a behavior-preserving deduplication; add transactional conflict checks and MySQL concurrency tests before claiming that guarantee.
- Expiry is enforced by read-time predicates. No scheduled expiry/cleanup command is registered; expired rows can accumulate.
- `composer install` reports the existing lock content hash is stale relative to `composer.json`. Do not silently run `composer update` during onboarding; review a dependency refresh separately.
- Several existing booking tests still populate removed segment/payment status columns or assert the old CONFIRMED workflow. Test failures must be separated from environment failures and from regressions in this change.

## Validation results

- PHP 8.4.27, MySQL 8.4, Nginx, MailHog, database queue worker and Tesseract English/Vietnamese were installed and started. MySQL migrations and the eight-court seed completed.
- `npm ci`, generated Wayfinder actions/routes, `npm run types:check`, and `WAYFINDER_SKIP_GENERATION=true npm run build` passed. Pint formatted changed PHP files; PHP syntax checks passed.
- Focused MySQL regression run: **12 tests passed, 59 assertions**, exit 0. It covers seven new tests plus five OCR mapping tests: strict deadline boundaries, half-open overlaps, separate court grids, tier pricing and date changes, private status payloads, persisted payment fields, and matching blog HTML/JSON payloads.
- Complete MySQL suite: **44 tests, 33 passed, 8 assertion failures, 3 errors, no skips** (226 assertions). A separate archive of the original HEAD with the same dependencies/configuration/build assets ran **37 tests, 26 passed, the same 8 failures and 3 errors** (195 assertions). Failed test identifiers match exactly; there are no additional failing tests from this cleanup.
- The existing failures include stale segment-status/CONFIRMED fixtures, tests for missing transfer/reconciliation routes, an old `welcome` home-view expectation after the Inertia migration, and authentication/admin-creation expectation mismatches. They require a separate behavior/test alignment task; the suite is not fully green.
- The default SQLite suite is blocked by `ALTER TABLE booking_orders MODIFY ...` in the historical `2026_11_07_000001` migration. This also fails on the original HEAD (23 failing, 14 passing tests). An external MySQL PHPUnit configuration uses the dedicated `ballburn_testing` database; development `laravel` data is separate. Calling PHPUnit directly avoids an Artisan duplicate-configuration warning.
- Functional HTTP requests returned 200 for health, home, about, blog, contact, booking, admin login, courts and blog JSON. Built assets were served, and availability returned eight courts with 36 half-hour slots each. A synthetic contact inquiry traveled through the database queue and worker to local MailHog.
- Cloud checkout files are private to UID 1000. Nginx initially returned permission errors; an external Compose override runs it as UID 1000 with writable temporary cache/PID paths. Repository file permissions and tracked Compose files were preserved.
- Composer archive downloads through `api.github.com` were denied. Setup successfully warmed Composer's cache from GitHub's official `codeload.github.com` final archives at the locked commits, preserving lockfile URL keys, TLS validation, ZIP integrity checks and all supplied checksums. No dependency/lockfile updates or verification bypasses were used.

The cloud install/start helpers live outside the checkout under `/workspace/.cloud-setup`. Current-instance execution and repeated installation are separate from saving/publishing a reusable environment configuration. Configuration-tool failures must be reported rather than treating local helper files as a published environment.

## 2026-11-10 OCR follow-up

- Added a fallback for unlabeled bank receipt amounts when a number is followed by `VND` or `DONG`, as in the TPBank transfer screenshot. Label-based amount extraction remains preferred; the fallback uses the currency marker to avoid treating dates or account numbers as amounts.
- Kept optional transaction ID and recipient account checks. They do not prevent mapping when unreadable, and they support duplicate-transaction detection and recipient verification when present.
- PHP syntax validation passed for `PaymentReceiptOcrParser.php`. Automated tests were not run for this follow-up.

## 2026-11-10 admin cancellation follow-up

- Added the terminal `CANCELLED` order status through a migration. Admin cancellation requires a reason, records it in each booking segment's status history, clears pending-payment admin alerts and releases the booked schedule. Paid orders retain `paid_at`; cancellation does not issue a refund or send a customer notification.
- Added `/admin/bookings/cancellations` and its admin navigation item. Removed the homepage editor from admin navigation, settings routes, controller and page; public homepage content continues through `HomePageService`. Active and hovered admin navigation items use the orange brand tone.
- Updated the public secure booking status page to show cancelled orders and an appropriate support message. The reason remains internal.
- PHP syntax checks, Pint check, frontend type check, production build and `git diff --check` passed. `BookingCancellationTest` was added but is not runnable in this checkout's current test setup: the configured `/cloud-setup/phpunit.mysql.xml` is not mounted, and the `sail` account cannot access `ballburn_testing`. The default SQLite suite remains blocked by the historical MySQL-only `ALTER TABLE ... MODIFY` migration.

## 2026-11-10 cancellation 502 and feedback follow-up

- The reported 502 was traced to MySQL error 1265: the development `booking_orders.status` enum had not yet been migrated to include `CANCELLED`. Applied migrations `2026_11_10_000001_update_ballburn_contact_map_location` and `2026_11_10_000002_add_cancelled_status_to_booking_orders` successfully to the app database.
- Replaced browser `window.confirm` prompts throughout admin booking, news and court actions with a centered accessible Reka dialog. Configured Vue Sonner toasts at top-right. Laravel success/error messages are read from `page.props.flash` on Inertia navigation; validation errors, HTTP exceptions and network errors also produce toasts. The earlier listener watched Inertia's separate dedicated flash event, which did not carry the Laravel shared flash props.
- Frontend type check, production build and `git diff --check` passed. The cancellation feature test could not be run because the dedicated MySQL test database is unavailable to the configured test account; no user bookings were changed for testing.

## 2026-11-10 admin dashboard follow-up

- Replaced the `/admin` placeholder with a date-filtered dashboard using repository queries for current/previous order status counts, paid revenue, occupied schedule start hours and upcoming active bookings. Order counts and revenue use `created_at`; revenue only includes orders with payment recorded.
- Added booking status cards, a revenue trend chart, popular-hour bars, a status donut, an upcoming booking table and shortcuts to existing admin workflows. The screen does not invent customer metrics; no customer account workflow exists.
- PHP syntax checks and Pint passed; `php artisan route:list --path=admin` resolved the dashboard controller. Frontend type check and production build passed. A read-only 7-day service call against the app database returned the expected aggregates. The isolated MySQL PHPUnit database is unavailable in this checkout, so no dashboard feature test was run.

## 2026-11-10 daily revenue date-basis correction

- Compared venue-day totals for 2026-11-10. There were no PAID orders created that day, while two older PAID orders had `paid_at` on that day for a total of 800,000 VND. The dashboard counted status cards by order creation but graphed revenue by payment time, explaining the apparent mismatch.
- Changed dashboard revenue series and the booking management “revenue today” metric to use `booking_orders.created_at`, matching the requested per-order-creation-day report. The dashboard still requires `paid_at` for inclusion, so pending orders do not count as revenue. PHP syntax/Pint and frontend type check/build passed. The read-only live query now shows 0 PAID orders and 0 created-day revenue for 2026-11-10; older orders paid that day no longer inflate that day's revenue.

## 2026-11-10 admin feedback and user directory

- Reworked the admin toast host to listen for Inertia's completed-visit and validation/error events, and teleported the top-right Sonner container to `body` so page overflow and stacking contexts cannot clip notifications. Toasts stay visible for six seconds and include a close button.
- Updated the admin menu labels/order and added `/admin/users`, a paginated/searchable read-only directory of ADMIN/STAFF accounts. Guest booking customers remain accountless; administrator provisioning remains through `php artisan admin:create`.
- PHP syntax, Pint, the admin users route, frontend type checking, production build and `git diff --check` passed after typing the optional Inertia flash-toast shape explicitly. No database writes or test-suite runs were needed for this change.
