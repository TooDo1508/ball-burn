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

## Docker on WSL 2

For the Vue Starter Kit admin login, WSL setup and verification commands, see
[Admin authentication](docs/admin-starter-kit.md).

Docker Engine and Docker Compose run directly inside Ubuntu on WSL 2; Docker Desktop is not required. The `.docker/` directory contains the PHP-FPM image and PHP settings, Nginx website configuration, and MySQL settings. The root `compose.yaml` connects those services and persists the database in a named volume.

Install Docker Engine and the Compose plugin in Ubuntu using Docker's official [Ubuntu installation guide](https://docs.docker.com/engine/install/ubuntu/). Enable systemd and start Docker:

```bash
sudo usermod -aG docker "$USER"
sudo systemctl enable --now docker
```

If you just added your user to the Docker group, restart WSL from Windows with `wsl --shutdown`, then open Ubuntu again. Verify `docker info` and `docker compose version`.

Start the project from the Ubuntu terminal:

```bash
cd /mnt/g/Openai-project/ballburn
docker compose up -d --build
docker compose exec app php artisan migrate
```

Open [http://localhost:8000](http://localhost:8000). The `frontend` service runs Vite at port 5173. Stop the containers with `docker compose down`; keep the database when stopping. To also delete all database data, use `docker compose down -v`.

### PHP debugging with VS Code

The local `app` and `queue-worker` images install Xdebug 3.5.3. In VS Code, install the recommended **PHP Debug** extension, then open **Run and Debug** and start **Listen for Xdebug (Docker)**. The launch configuration maps the container's `/var/www/html` directory to the opened workspace.

Rebuild the PHP services after changing the Dockerfile or Xdebug settings:

```bash
docker compose up -d --build app queue-worker
```

Set a breakpoint and add `?XDEBUG_TRIGGER=1` to the local page URL, for example `http://localhost:8000/?XDEBUG_TRIGGER=1`. For an Artisan command, set the trigger for that command:

```bash
docker compose exec -e XDEBUG_TRIGGER=1 app php artisan about
```

Check that the extension loaded with `docker compose exec app php --ri xdebug`. Xdebug connects from the PHP container to VS Code on `host.docker.internal:9003`; the debugger only starts for requests with a trigger.

To debug a queued job, start the one-shot worker with the trigger after stopping the regular worker, so it cannot claim the job first:

```bash
docker compose stop queue-worker
docker compose run --rm -e XDEBUG_TRIGGER=1 queue-worker php artisan queue:work database --queue=payments,default --once
docker compose start queue-worker
```

### Payment receipt OCR queue

The booking payment-proof endpoint now creates a `payment_receipts` row with the booking/payment, then queues OCR after the transaction commits. The worker runs Tesseract with Vietnamese and English data and saves the OCR and mapping result; a match never marks the payment as paid. After pulling this change, rebuild and migrate, then restart the worker:

```bash
docker compose up -d --build app queue-worker
docker compose exec app php artisan migrate
docker compose restart queue-worker
```

The frontend uses Vue 3 with Vite and Vue Router. Add pages, reusable components, composables, and assets under `src/`; the app entry and shared styles are `src/app.js` and `src/assets/styles/global.css`. Vite serves frontend assets from the `frontend` container.

For a fresh checkout, copy `.env.example` to `.env`, then run `docker compose up -d --build`, `docker compose exec app composer install`, `docker compose exec app php artisan key:generate`, and `docker compose exec app php artisan migrate`.

The existing `database/migrations/pickleball_booking_schema.sql` is a standalone schema for an empty database; it is not automatically imported by Compose. Use Laravel migrations for the default app setup. For better file performance, consider keeping active development copies in the WSL filesystem (for example, `~/projects/ballburn`) instead of under `/mnt`.

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
