# Admin authentication

The admin frontend is adapted from the official `laravel/vue-starter-kit`, commit
`fd5b58fb5672cae0152dd04c16ea37d9f52b1cd5` (MIT), using Vue, Inertia 3,
TypeScript, Tailwind and shadcn-vue components. Source:
https://github.com/laravel/vue-starter-kit/tree/fd5b58fb5672cae0152dd04c16ea37d9f52b1cd5

Public pages continue to use `src/` and Vue Router. Admin pages use
`resources/js/` with a separate Blade root and CSS entry. The existing Vite
build produces both bundles. Laravel is kept at the existing locked version.
Composer targets PHP 8.3 via `config.platform.php`; Symfony dependencies have
been resolved to compatible 7.4 versions because the previous lock used 8.x
packages requiring PHP 8.4.

## Setup in WSL

```bash
cd /mnt/d/ball-burn
php -v # PHP 8.3
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

The current WSL PHP 8.3 installation is compiled from the official PHP source
into `/opt/php83`, with config at `/opt/php83/etc/php.ini`. `/usr/bin/php` uses
that binary through `update-alternatives`. PHP 8.1 remains installed. This
source installation needs manual PHP security updates; it is not managed by
APT. Windows XAMPP PHP is independent of WSL PHP.

Node 22 is installed in `/opt/node22`, linked from `/usr/local/bin` in WSL.

## Routes and permissions

- `/admin/login`: email/password sign-in with rate limiting.
- `/admin`: starter dashboard; requires an `admin` or `staff` role.
- `/admin/settings/profile`: update the current user's name and email.
- `/admin/settings/security`: password confirmation, password changes and 2FA.
- `/admin/settings/appearance`: theme settings.
- `/admin/forgot-password`: reset link; configure a mail transport for delivery.

Registration, passkeys, email verification and self-service account deletion
are disabled in this initial integration. Existing users receive no role
automatically. `role` is not mass assignable. Future management routes must use
the admin route group and their own action-specific policies; the access gate
alone does not distinguish staff permissions from administrator permissions.

The dashboard is a starting screen, not implemented court or booking management.
Use HTTPS and secure session cookies in production. Configure mail before
relying on password recovery.

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
