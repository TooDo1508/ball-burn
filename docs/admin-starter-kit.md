# Admin authentication

The admin frontend is adapted from the official `laravel/vue-starter-kit`, commit
`fd5b58fb5672cae0152dd04c16ea37d9f52b1cd5` (MIT), using Vue, Inertia 3,
TypeScript, Tailwind and shadcn-vue components. Source:
https://github.com/laravel/vue-starter-kit/tree/fd5b58fb5672cae0152dd04c16ea37d9f52b1cd5

Public pages continue to use `src/` and Vue Router. Admin pages use
`resources/js/` with a separate Blade root and CSS entry. The existing Vite
build produces both bundles. Laravel is kept at the existing locked version.
Both frontend surfaces share the design tokens in `src/assets/styles/tokens.css`.
Use Be Vietnam Pro for body text and Playfair Display for headings; ordinary
body copy, labels, navigation, and buttons use at least 14px, while compact
captions and eyebrow labels may use 12px. Responsive headings use the shared
H1/H2/H3 scale documented in `AGENTS.md`.
Backend feature code follows Controller -> Service -> Repository: controllers
validate/authorize requests and format Inertia/API responses, services hold
business workflows, and repositories own database queries and persistence.
The Docker PHP-FPM image and Composer platform target PHP 8.4; Symfony
dependencies are pinned to compatible 7.4 versions.

## Setup in WSL

```bash
cd /mnt/d/ball-burn
php -v # PHP 8.4
node -v # Node 22.12 or newer
composer install
npm ci
```

On a fresh checkout, copy `.env.example` to `.env` and run
`php artisan key:generate`. Configure the real MySQL host and credentials before
running `php artisan migrate`. The Docker hostname `mysql` only resolves inside
the Compose network; use the appropriate host when running PHP directly in WSL.
Never regenerate an existing application's encryption key.

```bash
php artisan migrate
php artisan admin:create
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Open http://localhost:8000/admin/login. `admin:create` prompts for the name,
unique email and a confirmed password without echoing the password or placing
it in shell history. No default administrator or real password is seeded.

If running PHP directly in WSL or Windows, install and select PHP 8.4 for the
CLI before using Composer or Artisan. Docker Compose builds the project's
authoritative PHP 8.4 runtime and avoids depending on a host PHP installation.

Node 22 is installed in `/opt/node22`, linked from `/usr/local/bin` in WSL.

## Routes and permissions

- `/admin/login`: email/password sign-in with rate limiting.
- `/admin`: starter dashboard; requires an `admin` or `staff` role.
- `/admin/news`: article management with search, status/category/date filters, pagination, draft/published/scheduled/archived states, preview, image upload and bulk deletion.
- `/admin/news/categories`: create, edit and delete blog categories.
- `/admin/settings/profile`: update the current user's name and email.
- `/admin/settings/security`: password confirmation, password changes and 2FA.
- `/admin/settings/appearance`: theme settings.
- `/admin/forgot-password`: reset link; configure a mail transport for delivery.

Registration, passkeys, email verification and self-service account deletion
are disabled in this initial integration. Existing users receive no role
automatically. `role` is not mass assignable. Future management routes must use
the admin route group and their own action-specific policies; the access gate
alone does not distinguish staff permissions from administrator permissions.

The dashboard is a starting screen; court and booking management are still
unimplemented. News data uses the existing `blog_posts`, `blog_categories` and
`blog_post_categories` tables. A versioned migration adds article SEO metadata
and image alt text. Use HTTPS and secure session cookies in production.
Configure mail before relying on password recovery.

## Checks

```bash
php artisan wayfinder:generate --with-form
npm run types:check
npm run build
php artisan test
vendor/bin/pint --test
```

Wayfinder regenerates route helpers during Vite builds; generated helpers are
ignored by Git. Tests use SQLite in memory, not the configured application
database. No deployment database reset is required for tests.

When using Docker Compose, the Node container has no PHP. Generate helpers in
the app container before starting Vite, and regenerate after changing routes:

```bash
docker compose exec app php artisan wayfinder:generate --with-form
docker compose up -d frontend
```

Compose sets `WAYFINDER_SKIP_GENERATION=true` only for its frontend service.
Normal WSL Vite commands continue to generate helpers automatically.
