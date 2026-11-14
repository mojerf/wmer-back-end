# wmer — back end

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

| Method | Path | Access |
| --- | --- | --- |
| GET | `/main-page` | public |
| GET | `/works`, `/works/{id}`, `/posts`, `/posts/{id}`, `/products`, `/products/{id}` | public |
| POST | `/contacts` | public, rate limited (3/min per IP) |
| POST | `/register`, `/login` | guests, rate limited |
| GET | `/me` · POST `/logout` · POST `/orders` | signed in |
| POST/PUT/DELETE | `/works`, `/posts`, `/products` | administrators |
| GET | `/orders`, `/contacts` · DELETE `/contacts/{id}` | administrators |

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

Not decided yet — add a `LICENSE` file before treating the repository as open source.
