# wmer — back end

[![CI](https://github.com/mojerf/wmer-back-end/actions/workflows/laravel.yml/badge.svg)](https://github.com/mojerf/wmer-back-end/actions/workflows/laravel.yml)
[![codecov](https://codecov.io/gh/mojerf/wmer-back-end/graph/badge.svg)](https://codecov.io/gh/mojerf/wmer-back-end)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777bb4?logo=php&logoColor=white)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-4f5b93)](phpstan.neon)
[![Code style: Pint](https://img.shields.io/badge/code%20style-Pint-orange)](https://laravel.com/docs/pint)
[![Last commit](https://img.shields.io/github/last-commit/mojerf/wmer-back-end)](https://github.com/mojerf/wmer-back-end/commits/main)

JSON API for the personal website [wmer](https://github.com/mojerf/wmer-front-end), built with
Laravel 13 (PHP 8.4+) and Laravel Sanctum (cookie-based SPA authentication).

## Requirements

- PHP 8.4 or newer with `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `zip`, `gd`
- Composer 2
- MySQL 8.4 (or use the provided Docker Compose file)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d db          # optional: local MySQL on 127.0.0.1:3306 (needs DB_PASSWORD in .env)
php artisan migrate --seed       # creates demo content and the admin from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan storage:link
php artisan serve                # http://localhost:8000
```

## Quality checks

```bash
vendor/bin/pest                  # tests (in-memory SQLite, no database needed)
vendor/bin/pint --test           # code style
vendor/bin/phpstan analyse       # static analysis (Larastan, level 5)
```

CI runs all of them on PHP 8.4 and 8.5 for every push and pull request.

## API (prefix `/api/v1`)

Interactive OpenAPI docs: `/docs/api` (open in local development, administrators only elsewhere);
the raw spec is at `/docs/api.json` and can be used to generate a typed TypeScript client.

| Method | Path | Access |
| --- | --- | --- |
| GET | `/main-page` | public |
| GET | `/works`, `/posts`, `/products` (`?search=`, works `?tag=`, products `?sort=latest\|oldest\|price_asc\|price_desc`, `?page=`) | public |
| GET | `/works/{slug}`, `/posts/{slug}`, `/products/{slug}` | public |
| GET | `/{works\|posts\|products}/{slug}/comments` | public |
| POST | `/contacts` | public, 3/min per IP |
| POST | `/register`, `/login`, `/forgot-password`, `/reset-password` | guests, rate limited |
| GET/PATCH | `/me` · PUT `/me/password` · POST `/logout` | signed in |
| POST | `/orders` · GET `/orders/mine`, `/orders/{id}` (owner or admin) · POST `/orders/{id}/pay` (owner, pending orders) | signed in |
| GET | `/payments/callback` | the gateway's browser redirect (confirmed with the gateway) |
| POST | `/{works\|posts\|products}/{slug}/comments` (10/min) · DELETE `/comments/{id}` (author or admin) | signed in |
| POST/PUT/DELETE | `/works`, `/posts`, `/products` (by id) | administrators |
| GET | `/orders`, `/contacts`, `/admin/stats` · PATCH `/orders/{id}/status` · DELETE `/contacts/{id}` | administrators |

Order lifecycle: `pending → processing → completed`, and `cancelled` from `pending`/`processing`; `completed`
and `cancelled` are final. Download links appear only on completed orders.

### Payments

`POST /orders/{id}/pay` creates a `Payment` (amount copied from the order) and returns the gateway URL. The gateway
sends the browser to `GET /payments/callback`, which looks the payment up by its token, **verifies it with the
gateway**, and only then marks the payment paid and the order completed (idempotent, race-safe). The browser is then
redirected to the front end (`/orders/{id}?payment=success|failed`).

- `PAYMENT_GATEWAY=fake` approves instantly. Local development and tests only: it is refused in production.
- `PAYMENT_GATEWAY=zarinpal` uses Zarinpal v4. Set `ZARINPAL_MERCHANT_ID` and keep `ZARINPAL_SANDBOX=true` until you
  are ready for real money. Add another gateway by implementing `App\Payments\PaymentGateway`.

Authentication uses Sanctum's SPA flow: call `GET /api/csrf-cookie` first, then send the `X-XSRF-TOKEN`
header with credentials on state-changing requests. Errors are always JSON (`401` not signed in,
`403` not an administrator, `422` validation, `429` rate limited).

## Architecture notes

- Controllers stay thin: validation lives in Form Requests (`app/Http/Requests`), business operations
  in Actions (`app/Actions`, e.g. `CreateOrder`, which runs in a transaction and always prices from the database).
- Authorization is a gate (`manage-content`, see `AppServiceProvider`) applied to route groups in `routes/api.php`.
- `UserRole` and `OrderStatus` are enums. A user's `role` is not mass assignable.
- Download links are only returned for orders whose status is `completed`.
- Uploaded images are validated (type, size) and stored through `App\Support\ImageStorage`.
- Lazy loading is disabled outside production so N+1 queries fail fast in development and tests.

## Configuration

See `.env.example`. Notable keys: `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` (must match the
front end's domain in production), `ADMIN_EMAIL` / `ADMIN_PASSWORD` (seeding only).

## License

[MIT](LICENSE)
