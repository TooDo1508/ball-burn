# AGENTS.md — BALLBURN PickleClub & Bistro

This file describes the implemented application. Check routes, migrations and tests before changing behavior; historical plans and SQL exports are reference material, not the current runtime contract.

## Stack and project map

- Laravel 13, PHP 8.4 (Composer platform pin), MySQL 8.4 and Composer 2.
- Vue 3, Vite 8, Tailwind 4 and TypeScript. Use `composer.lock` and `package-lock.json`; do not upgrade dependencies as part of routine setup.
- Public home `/` is an Inertia page at `resources/js/pages/Home.vue`, styled with `resources/css/home.css`. Its content comes from `HomePageService` and `site_settings`.
- Other public pages use `resources/views/welcome.blade.php`, `src/app.js`, Vue Router and `src/pages/`. Both frontend entry points are required; they are not duplicate applications to merge casually. `src/pages/HomePage.vue` is still reachable through the public Vue Router.
- Admin and authentication use Inertia, `resources/js/pages/` and the layouts/components in `resources/js/`.
- HTTP routes: `routes/web.php`, `routes/api.php`, `routes/settings.php`; API prefix is empty in `bootstrap/app.php`, so the court endpoints are `/courts`, not `/api/courts`.
- Controllers validate/authorize and shape responses; services coordinate business rules; repository contracts and Eloquent implementations own database queries. Bindings are in `RepositoryServiceProvider`. Prefer reusable model scopes for shared query predicates.
- Payment OCR uses local Tesseract with Vietnamese and English language data, a database queue, and `storage/app/private` through the local storage disk.

## Implemented public workflows

### Court availability and guest booking

- Guests do not create customer accounts. `/booking` displays a daily, 30-minute court grid; `/booking/data` supplies availability and server-calculated prices.
- Venue timezone is `VENUE_TIMEZONE=Asia/Ho_Chi_Minh`; retain `APP_TIMEZONE=UTC`. Interpret venue input locally, convert to UTC for persistence, and display locally.
- Public booking dates are today through today + 30 days inclusive. Reject past slots, invalid 30-minute boundaries and overlapping submitted ranges. A single order can contain multiple courts/ranges, up to 64 half-hours.
- `/booking/holds` creates/synchronizes temporary `booking_holds`. An opaque 64-character token is hashed in storage. Holds expire after five minutes; synchronizing does not extend the original expiry. DELETE releases the token's holds.
- Payment instructions verify held slots and server prices without creating an order. The recipient VPBank account is configured in site contact settings; VietQR image generation is in `VietQrImageUrlService`.
- Uploading one receipt image to `/booking/payment-proof` creates the `booking_orders` record, its booking segments, one manual QR payment on the primary segment and a `payment_receipts` record. Supported images: JPG/JPEG/PNG/WebP, at most 5 MB. Receipt paths are private.
- Submission is idempotent for the same hold token, status token, payment reference, slots and customer/amount details. A conflicting reuse must fail. Remove a newly uploaded file when the transaction fails or an existing order wins the retry.
- The order retains the hold's payment deadline. Do not restart the countdown on submission.
- Secure status pages are `/booking/status/{token}` and `/booking/status/{token}/data`. Persist only the SHA-256 token hash; use `no-store, private` on status responses. `GuestBookingOrderResource` is the shared public payload for submission and status. Keep customer contacts, token hashes, proof paths and internal OCR data out of it.

### Current status and concurrency rules

- `booking_orders.status` is authoritative: current application statuses are `PENDING_PAYMENT` and `PAID`. Booking segments no longer have their own status, status token or payment deadline. `payments.status` was also removed; use `paid_at` and the owning order where appropriate.
- Paid orders occupy the schedule. Pending orders occupy it only while `payment_deadline_at > now()`. The exact deadline is already expired. `Booking::occupyingSchedule()` is the common predicate used by availability and write-time conflict checks.
- Time intervals are half-open: overlap means `starts_at < requested_end AND ends_at > requested_start`. Adjacent bookings must remain allowed.
- Lock court rows inside the transaction and recheck availability before inserting holds/bookings/closures. Lock multiple courts in ascending ID order. Preserve atomicity and existing idempotency when refactoring.
- Expired holds and pending orders are ignored by deadline-aware reads. There is currently no expiry command registered in `routes/console.php`; do not describe a scheduled cleanup job as implemented.
- There is no integrated payment webhook or automatic bank confirmation. OCR matching is advisory and must never mark an order paid.
- Existing admin status changes do not recheck newly conflicting reservations when marking an old pending order paid. Treat late-payment reconciliation and that overlap check as follow-up work, not an implemented guarantee.

### Content and contact

- `/about`, `/blog`, `/blog/{slug}` and `/contact` render public pages. Blog JSON endpoints live under `/api/blog/posts`; contact information is available from `/api/site/contact-info`.
- Blog readers return only published content. Keep body sanitization via `BlogContentSanitizer`, image URL handling, pagination and article SEO/JSON-LD consistent between HTML and JSON responses.
- The contact form requires name, email, subject and message; phone is optional. It is rate limited. Submission queues an acknowledgement and an admin email after commit.
- The configured contact email is both the public venue address and the admin inquiry recipient; SMTP settings are environment configuration.

## Implemented admin workflows

- Fortify authentication is under `/admin`: login, password reset, password confirmation and two-factor authentication. Public registration is disabled. `EnsureAdminAccount` and `can:access-admin` restrict access to active admin/staff users.
- Create administrators with `php artisan admin:create`, which uses hidden password prompts. Do not hard-code passwords, create default admin credentials, or expose receipt files publicly.
- `/admin` is the dashboard shell; do not assume every displayed metric is a live reporting service.
- `/admin/courts`: court CRUD, active state, primary image, weekly operating hours, weekday/weekend price tiers, daily schedule, closures and admin-assisted paid bookings. `CourtCatalogSeeder` idempotently provides eight courts (1–3 CENTER, 4–8 REGULAR), 05:00–23:00 venue hours and the price sheet effective 2026-04-01.
- `CourtPricingService` selects active, effective rules by weekday/weekend, using the latest effective rule for an identical interval and falling back to the base hourly price. Keep integer monetary arithmetic. Batch daily slot pricing resolves applicable rules once; a range crossing dates resolves each date separately.
- `/admin/bookings`: order-oriented list/search/filter/statistics, segment detail, single and bulk status changes, bulk deletion of pending orders, private payment-proof access and in-app notifications. Status transitions record segment history; deletions create audit records and reject linked bank transactions.
- `/admin/contact-messages`: paginated/searchable/filterable inbox, detail view, status and internal notes, including quick editing. Current statuses: NEW, IN_PROGRESS, RESOLVED, SPAM. Assignment and automated retention are not implemented.
- `/admin/news`: posts, categories, image uploads, rich text editing, drafts/publishing, previews, SEO metadata and bulk deletion.
- `/admin/settings`: profile, password/security/2FA, appearance, home-page content and venue contact/payment information.
- Appearance is light/dark/system, stored in local storage and the appearance cookie. Theme setup belongs in `useAppearance.ts` and `resources/css/app.css`; admin UI should use semantic surface/text/border tokens.

## Receipt OCR lifecycle

- `ProcessPaymentReceiptJob` runs on `payments`, with the worker also consuming `default`.
- `PaymentReceiptProcessingService` claims work, reads the private image, calls Tesseract, parses text and stores mapping results. Repeated claims/results are guarded in the receipt repository; failures queue retries and eventually mark failure.
- Receipt states include QUEUED, PROCESSING, MATCHED, NEEDS_REVIEW and FAILED. MATCHED describes receipt content only.
- Mapping checks reference uniqueness, amount, currency, order/deadline eligibility, recipient account when readable, and duplicate bank transaction IDs. Never treat OCR text as proof of settled funds or log unmasked bank accounts unnecessarily.

## Development and validation

Use the existing checkout. Cloud tasks are already isolated; do not create a Git worktree unless explicitly requested.

The repository-supported Docker workflow is:

```sh
cp .env.example .env # only when .env does not already exist
# Compose requires Docker Engine and Compose v2.
docker compose up -d --build mysql mailhog app nginx
docker compose exec -u www-data app composer install --no-interaction --prefer-dist
docker compose exec -u www-data app php artisan key:generate # only for a new empty APP_KEY
docker compose exec -u www-data app php artisan migrate --force
docker compose exec -u www-data app php artisan storage:link
docker compose exec -u www-data app php artisan db:seed --class=CourtCatalogSeeder --force
docker compose exec -u www-data app php artisan wayfinder:generate --with-form
npm ci
docker compose exec -u www-data app php artisan test
npm run types:check
WAYFINDER_SKIP_GENERATION=true npm run build
docker compose up -d --build queue-worker
```

- Seed demo news separately with `NewsArticlesSeeder` when needed. Avoid the general `DatabaseSeeder` for routine refreshes; it creates a demo user and is not an idempotent refresh command.
- For hot reload, use the frontend Compose service or `npm run dev -- --host 0.0.0.0` with Node 22.12+ (or a supported newer version). In the host workflow, generate Wayfinder with container PHP first and set `WAYFINDER_SKIP_GENERATION=true` for Vite when host PHP is unavailable. Type checking needs the generated route/action modules too.
- Cloud-only overrides/helpers may live outside the checkout. Preserve the inherited proxy and CA trust for containers; never disable TLS, signatures or checksums. Xdebug is optional and may be disabled by an external Compose override.
- `phpunit.xml` uses in-memory SQLite, not the development MySQL database. The current default suite is blocked by MySQL-only `ALTER TABLE ... MODIFY` in the historical `2026_11_07_000001` migration. Cloud setup generates `/workspace/.cloud-setup/phpunit.mysql.xml` and a separate disposable `ballburn_testing` database; run it with `/workspace/.cloud-setup/dc exec -T -u www-data app php vendor/bin/phpunit -c /cloud-setup/phpunit.mysql.xml`. Keep that database isolated from development data. Use PHPUnit directly for custom configs to avoid duplicate configuration options from `artisan test`. Run MySQL migrations and functional HTTP requests as well; SQLite tests do not prove MySQL locking behavior. Never run destructive database commands against user data without explicit authorization.
- Run relevant feature/unit tests, frontend types/build and Pint on changed PHP files. Report actual failures/skips; do not replace assertions with passing placeholders.
- Preserve tracked source, tests and lockfiles during environment-only setup. Code changes require a coding request. Keep secrets and machine-specific configuration out of commits.
- Do not import `database/migrations/pickleball_booking_schema.sql` or `database/exports/*.sql` over the development database; versioned migrations are authoritative.

## Engineering and visual constraints

- Use Form Requests, policies/gates, CSRF and rate limits. Keep queries in repositories, file/provider integration in services, and explicit response data in controllers/resources. Do not send external requests or emails while holding database locks.
- Use decimal storage and integer arithmetic for money. Paginate admin lists and eager-load relations needed by loops; avoid one query per booking.
- Do not remove Laravel-discovered policies, commands or Inertia pages based only on missing imports. Confirm runtime discovery and indirect entry points before deleting code. Keep migration history intact.
- Preserve Vietnamese diacritics, accessible form labels, error states and status labels alongside colors.
- Public visual reference: `https://ballburn.vercel.app/#about`; local reference images are under `docs/`. Brand colors in the old design notes were provisional, not measured from the live site. Follow existing semantic tokens rather than introducing unrelated accents.
- Payment provider automation, refunds/cancellations, guest modification/cancellation, bank reconciliation, cleanup scheduling and production concurrency guarantees need explicit design work. Do not present them as existing features.
- Keep this document aligned with implemented functionality. Record audit findings and validation limitations in `docs/project-audit.md`.
