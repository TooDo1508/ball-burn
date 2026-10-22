# AGENTS.md — Pickleball Court Booking Website

This file gives coding agents the project context, business requirements, architectural rules, and implementation expectations for the pickleball court-booking website.

## 1. Project overview

Build a production-minded website for a pickleball venue using Laravel and MySQL.

The website includes:
- Public landing page and About Us page
- Court listing and court details
- Court availability calendar and booking flow
- Guest checkout (customers do not need accounts)
- QR-based payment flow
- Secure guest booking-status page
- Customer email notifications
- Contact Us form and admin inquiry management
- Blog and content management
- Admin dashboard for managing courts, schedules, bookings, payments, inquiries, and content

Keep the implementation maintainable, secure, testable, and suitable for gradual deployment.

## 2. Technology assumptions

- Backend: Laravel (use the version already installed in the repository; do not upgrade without approval).
- Database: MySQL 8.0.16+.
- Frontend: inspect the existing repository and follow its established framework and conventions. Do not introduce a new frontend stack without a clear need.
- Queue: use Laravel queues for email and other background work.
- Time handling: store timestamps in UTC; display and interpret booking times in the venue's configured timezone.
- Payment provider: not yet selected. Implement a provider abstraction and do not invent provider-specific API details or credentials.
- Currency: currently defaulted to VND in the proposed schema, but make currency configurable where appropriate.

If the repository or owner specifies different choices, treat the actual repository and explicit owner decisions as authoritative.

## 3. Core booking requirements

### Booking slots and booking window
- Bookings use 30-minute increments.
- Customers may book within a rolling 30-day window.
- Validate slot boundaries and the booking window on the server. Frontend validation is only for user experience.
- Generate available slots dynamically from court operating hours, court closures, and active bookings. Do not pre-create every possible slot unless a later performance analysis justifies it.
- Support booking one or more consecutive 30-minute slots if the product owner confirms this behavior. Until confirmed, do not silently assume multi-slot bookings are disallowed.

### Five-minute pending-payment hold
- A new booking starts in `PENDING_PAYMENT`.
- The backend sets `payment_deadline_at` to the actual creation time plus exactly five minutes, after the booking and payment instructions/QR data have been successfully prepared according to the selected provider flow.
- The deadline stored in the database is authoritative. The browser countdown is display-only.
- A pending booking whose deadline has passed must not block availability, even if a scheduled expiry job has not yet run.
- Use both a scheduled expiry job and deadline-aware availability checks.
- Once a booking expires, release the slot for other customers.
- Never rely on a client-supplied countdown or client clock to determine expiry.

### Prevent overlapping reservations
MySQL does not provide a simple unique constraint for arbitrary overlapping datetime ranges. All code paths that create, confirm, cancel, or expire bookings must follow a consistent concurrency strategy.

Recommended baseline:
1. Start a database transaction.
2. Lock the relevant court row with `SELECT ... FOR UPDATE` / Laravel `lockForUpdate()`.
3. Re-check availability inside the transaction.
4. Treat overdue pending bookings as expired.
5. Check for overlapping `PENDING_PAYMENT` bookings whose deadline is still in the future and overlapping `CONFIRMED` bookings.
6. Create or transition the booking while holding the lock.
7. Commit before dispatching notifications.

Use consistent lock ordering and add concurrency tests. Do not check availability only before opening the transaction.

### Booking statuses
Use these states unless the repository already has an approved equivalent:
- `PENDING_PAYMENT`
- `CONFIRMED`
- `EXPIRED`
- `CANCELLED`
- `COMPLETED`

Validate state transitions in the application layer. Record meaningful transitions in `booking_status_history`.

## 4. Payments and webhooks

- Never mark a booking paid based only on a browser redirect or a client request.
- Verify webhook signatures using the selected payment provider's official method.
- Verify provider identity, transaction/order identifiers, amount, currency, and payment state before accepting a payment.
- Make webhook processing idempotent. Provider retries must not create duplicate payments, confirmations, or notifications.
- Store each provider event using a unique `(provider, provider_event_id)` key when the provider supplies a stable event ID.
- Enforce appropriate uniqueness for provider transaction/order IDs.
- Process payment and booking state changes transactionally.
- Dispatch email/notification jobs only after the transaction commits.
- A late payment must not overwrite another customer's confirmed reservation. If the slot has already been reassigned, follow a documented refund or manual-reconciliation path.
- Keep provider secrets in environment/configuration, never in source control.
- Do not log secrets, full payment credentials, or unnecessary personal data.
- The exact provider, refund policy, cancellation policy, and reconciliation procedure require owner confirmation.

## 5. Guest booking-status page

Customers do not have accounts. After a booking is created, provide a secure link that lets the customer check their booking status.

Security rules:
- Do not expose sequential database IDs as authorization.
- A booking code alone must not grant access to personal booking details.
- Generate a high-entropy random token; store only its cryptographic hash in the database.
- Send the raw token only in the customer's secure URL.
- Use HTTPS in production. Avoid logging the token in application logs or analytics.
- Show only necessary information: booking code, court, date/time, amount/currency, status, and payment deadline when relevant.
- Do not expose full customer details, provider payloads, or internal notes.
- Consider token rotation/revocation and a suitable expiry policy.
- Avoid leaking tokens through third-party assets or referrer headers.

## 6. Notifications

Start with email notifications and the secure booking-status page. Customers do not need accounts or an in-app inbox.

Useful customer email events:
- Booking created and payment instructions available
- Booking confirmed after verified payment
- Booking expired
- Booking cancelled
- Refund requested/processed, if refunds are implemented

Rules:
- Use Laravel Notifications/Mail and queued jobs where practical.
- Queue notifications after the relevant database transaction commits.
- Use an idempotency key or equivalent protection to avoid duplicate logical notifications.
- Retry transient email failures and retain useful failure information without storing secrets.
- Do not email a message every minute for the countdown; display the countdown on the status page using the persisted deadline.
- Admin alerts can be added for new contact messages, payment/reconciliation failures, and webhook processing errors.

## 7. Contact Us form and admin workflow

Public form fields:
- Name (required)
- Email (required)
- Phone (optional unless the owner decides otherwise)
- Subject (optional or required according to product decision)
- Message (required)

Admin workflow:
- List and paginate submissions.
- Search by name/email/phone.
- Filter by status and date.
- View the full inquiry.
- Update status: `NEW`, `IN_PROGRESS`, `RESOLVED`, `SPAM`.
- Add internal notes and optionally assign to an admin/staff member.
- Optionally send an email alert to admins when a new message arrives.

Security:
- Validate on the server.
- Apply rate limiting and anti-spam protection.
- Escape submitted text when rendering it.
- Never render arbitrary message HTML as trusted markup.
- Collect only necessary personal information and define a retention policy.

Keep contact inquiries separate from court bookings.

## 8. Courts, hours, and closures

Court management should support:
- Name, slug, description, surface type, capacity if relevant
- Hourly price and currency
- Active/inactive state and sort order
- Multiple court images
- Weekly operating hours
- One-off closures for maintenance, holidays, or private events

Use server-side validation to prevent invalid hours and overlapping operating intervals. Validate booking ranges against operating hours and closures. Define how overnight operating periods are represented before implementing them; the initial schema assumes `opens_at < closes_at` within one day.

## 9. Admin, blog, and content management

Admin/staff accounts are separate from customers. Customers are guests.

Admin features:
- Authentication and authorization for every protected action
- Role-based permissions appropriate to admin/staff responsibilities
- Court and schedule management
- Booking and payment visibility
- Contact-message management
- Blog posts and categories
- Editable static pages such as About Us
- Site settings
- Audit trail for sensitive admin actions

Content management:
- Use unique slugs.
- Separate draft/published/archived states as appropriate.
- Only published content should be public.
- Validate image uploads by type and size; store files using Laravel's storage abstraction.
- Escape output and sanitize rich text using an appropriate allowlist if rich-text HTML is supported.

## 10. Database reference

A proposed schema is provided separately as `pickleball_booking_schema.sql`. It includes:
- `users`
- `courts`
- `court_images`
- `court_operating_hours`
- `court_closures`
- `bookings`
- `payments`
- `payment_webhook_events`
- `refunds`
- `booking_status_history`
- `blog_categories`
- `blog_posts`
- `blog_post_categories`
- `pages`
- `site_settings`
- `contact_messages`
- `notifications`
- `audit_logs`

Important:
- Treat this SQL as a design baseline, not an instruction to bypass Laravel migrations in an existing application.
- Before applying it, inspect the repository and current database. Do not drop or overwrite existing data.
- For a Laravel application, prefer versioned migrations that reflect the schema and can be reviewed and rolled back where safe.
- Do not assume a production database is empty.
- Review foreign-key delete behavior, retention, indexing, and any provider-specific fields before deployment.
- Laravel queue tables should be generated with the appropriate Laravel queue migration command if the database queue driver is used.
- Create the initial admin through a secure, documented seeding/setup process; never hard-code a real password.

## 11. Suggested implementation sequence

1. Inspect the existing repository, Laravel version, frontend, database configuration, tests, and coding conventions.
2. Report what exists and propose a small implementation plan before making broad structural changes.
3. Implement authentication and authorization for admin/staff.
4. Add database migrations, models, factories, and seeders for core entities.
5. Implement court management, operating hours, and closures.
6. Implement availability calculation and booking creation with concurrency protection.
7. Add the guest status link and booking-status page.
8. Integrate the chosen payment provider behind a clear interface; implement webhook verification and idempotency.
9. Add expiry processing and late-payment reconciliation.
10. Add queued email notifications.
11. Implement Contact Us and admin inquiry management.
12. Implement blog, About Us, and static-page management.
13. Add audit logging, operational monitoring, and production configuration.
14. Run automated tests and document setup/deployment steps.

Do not attempt a large rewrite in one step. Work in small, reviewable changes.

## 12. Required tests

At minimum, test:
- A valid 30-minute booking can be created.
- Invalid slot boundaries and dates outside the 30-day window are rejected.
- A court closure prevents booking.
- Two concurrent requests cannot confirm overlapping reservations.
- A pending booking blocks a slot only until its payment deadline.
- Expiry releases the slot even if the scheduled expiry job is delayed.
- Valid payment confirms the booking once.
- Duplicate webhook events do not duplicate state changes or emails.
- Invalid webhook signatures and mismatched amount/currency are rejected.
- Late payment cannot steal a slot from another confirmed booking.
- Guest status tokens cannot be guessed from booking IDs/codes.
- Unauthorized users cannot access admin actions.
- Contact form validation, rate limiting, and HTML escaping work.
- Draft blog posts and pages are not publicly visible.
- Notification jobs are dispatched after commit and are safely retryable.

Use Laravel feature tests and database transactions/parallel tests where appropriate. Avoid tests that depend on a live payment provider; use provider fakes or fixtures.

## 13. Security and engineering standards

- Follow existing Laravel conventions and use Form Requests for complex validation.
- Use policies/gates or equivalent authorization checks; hiding a button is not authorization.
- Use parameterized Eloquent/query-builder operations; do not concatenate untrusted SQL.
- Protect state-changing web routes with appropriate CSRF protection.
- Apply rate limiting to booking, contact, and token-related endpoints.
- Validate uploaded files and store them safely.
- Keep secrets in environment variables and example placeholders in `.env.example`.
- Use database transactions for multi-table state changes.
- Do not send external requests or email while holding database locks.
- Log meaningful operational errors while redacting tokens, secrets, and unnecessary personal information.
- Use decimal database types for money; avoid floating-point arithmetic for currency.
- Add pagination to admin lists.
- Provide clear error states and accessible form labels.
- Do not silently change product requirements. Ask the owner when a decision materially affects behavior.

## 14. Decisions that still need owner confirmation

Do not invent answers for these:
- Which payment provider will be used?
- Is MySQL the confirmed production database?
- Can a customer book multiple consecutive 30-minute slots in one booking?
- What are the cancellation and refund policies?
- Which timezone and operating hours should the venue use?
- Is phone number optional on the contact form?
- Should the admin receive email alerts for contact submissions?
- Which frontend stack and admin UI, if any, are already chosen?
- Should customers be allowed to modify/cancel bookings through the secure status page, or only view status?

## 15. Agent working protocol

For each task:
1. Inspect relevant files before editing.
2. Summarize the intended changes and any assumptions.
3. Prefer the smallest coherent change that fits existing architecture.
4. Add or update tests alongside behavior changes.
5. Run the relevant tests, formatter, and static checks available in the repository.
6. Report changed files, commands run, test outcomes, and any remaining risks.
7. Never claim tests passed unless they were actually run.
8. Never run destructive database commands against a non-disposable environment without explicit approval.

## 16. Frontend visual direction — Ball Burn reference

Reference site: https://ballburn.vercel.app/#about

Use this site as the visual reference for the public-facing landing page. Its content and brand direction are centered on **BALLBURN / PickleClub & Bistro**, with an energetic, premium sports-and-hospitality feel: bold display headings, high-contrast sections, strong photography/video, compact uppercase eyebrow labels, clear booking/consultation CTAs, and generous spacing around major sections. The site uses Vietnamese copy, so preserve Vietnamese diacritics and make sure the chosen font supports them.

### Color-token rules

**Important verification note:** the reference page was readable, but its live CSS/color declarations could not be retrieved in this environment. Therefore the HEX values below are a *provisional implementation palette*, not verified exact samples from the reference website. Do not describe them as exact brand values. Before final visual sign-off, inspect the live site in the browser (DevTools → Computed styles / color picker) and replace these values with sampled values from the actual page, especially the primary CTA, page background, and headline text.

Use semantic tokens rather than scattering raw HEX values across components. The provisional palette is intentionally restrained and should be adjusted after direct visual verification:

| Token | Provisional HEX | Intended use |
|---|---|---|
| `--color-brand` | `#F36B32` | Primary brand/CTA accent; verify against the reference logo and buttons |
| `--color-brand-hover` | `#D95522` | Hover/active state for primary actions |
| `--color-ink` | `#171717` | Main dark text and dark sections |
| `--color-ink-muted` | `#666666` | Secondary text on light surfaces |
| `--color-surface` | `#FFFFFF` | Main light surface |
| `--color-surface-muted` | `#F5F4F1` | Alternate section/card background |
| `--color-border` | `#E4E1DC` | Subtle borders and dividers |
| `--color-on-brand` | `#FFFFFF` | Text/icons on the brand CTA, subject to contrast check |
| `--color-success` | `#247A4B` | Success states only; not a substitute for the brand accent |
| `--color-warning` | `#E6A323` | Pending-payment state (yellow/amber) |
| `--color-danger` | `#C83D3D` | Error/cancelled state |

Example CSS variables (keep the tokens centralized in the project's existing global stylesheet):

```css
:root {
  --color-brand: #F36B32;
  --color-brand-hover: #D95522;
  --color-ink: #171717;
  --color-ink-muted: #666666;
  --color-surface: #FFFFFF;
  --color-surface-muted: #F5F4F1;
  --color-border: #E4E1DC;
  --color-on-brand: #FFFFFF;
  --color-success: #247A4B;
  --color-warning: #E6A323;
  --color-danger: #C83D3D;
}
```

### UI consistency requirements

- Do not introduce unrelated bright colors, gradients, or a second accent color without a clear reason grounded in the reference.
- Use the brand accent consistently for the main CTA and key interactive emphasis; keep secondary actions visually quieter.
- Use dark/light section contrast, real venue/court photography, and bold typography to carry the visual identity rather than adding decorative effects everywhere.
- Keep body text readable and maintain WCAG contrast targets: at least 4.5:1 for normal text and 3:1 for large text/UI components where applicable.
- Booking-state colors are functional and must remain distinguishable: available, pending payment, confirmed/booked, expired/cancelled. Do not rely on color alone; pair each state with a label/icon.
- The admin dashboard may prioritize information density and usability, but should reuse the same semantic tokens and brand accent so it feels like part of the same product.
- Do not use the reference website's logo, photos, or other assets unless their reuse is authorized; use the reference for visual direction and color sampling.
- Once exact colors are verified, update this section and the centralized CSS tokens together. Remove the “provisional” label only after verification.
