# Otuken Backend (Laravel 11)

Production-ready Laravel 11 API + Filament admin for the News module.

## Stack

- Laravel 11 / PHP 8.3+
- MySQL 8
- Filament admin panel
- Sanctum
- spatie/laravel-permission
- spatie/laravel-medialibrary
- cviebrock/eloquent-sluggable
- spatie/laravel-translatable
- spatie/laravel-activitylog

## Local setup (without Docker)

1. `cp .env.example .env`
2. Configure MySQL in `.env`
3. `composer install`
4. `php artisan key:generate`
5. `php artisan migrate --seed`
6. `php artisan storage:link`
7. `php artisan serve`

Filament admin: `/admin`
API base: `/api/v1`

Optional AI SEO (OpenRouter):
- `OPENROUTER_API_KEY=...`
- `OPENROUTER_MODEL=meta-llama/llama-3.1-8b-instruct:free`
- `OPENROUTER_BASE_URL=https://openrouter.ai/api/v1`
- If key is missing, SEO auto-generation falls back to safe local generation.

Admin seeder (from ENV):
- `ADMIN_NAME=Admin`
- `ADMIN_EMAIL=admin@example.com`
- `ADMIN_PASSWORD=password`
- On `php artisan migrate --seed`, admin user is created/updated automatically and gets `admin` role.

## Docker setup

1. `cp .env.example .env`
2. Set:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=mysql`
   - `DB_PORT=3306`
   - `DB_DATABASE=otuken`
   - `DB_USERNAME=otuken`
   - `DB_PASSWORD=otuken`
3. Run with PHP 8.3 (default):
   - `docker compose up -d --build`
4. `docker compose exec app composer install`
5. `docker compose exec app php artisan key:generate`
6. `docker compose exec app php artisan migrate --seed`
7. `docker compose exec app php artisan storage:link`

Switch to PHP 8.4:

1. `docker compose down`
2. `PHP_FPM_VERSION=8.4 docker compose up -d --build`
3. `docker compose exec app composer install`
4. `docker compose exec app php artisan key:generate`
5. `docker compose exec app php artisan migrate --seed`
6. `docker compose exec app php artisan storage:link`

Open: `http://localhost:8081`

## Shared hosting (no Docker)

1. Upload project files to hosting account
2. Point web root to `public`
3. Ensure PHP 8.3+, MySQL, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `zip`
4. Create MySQL DB/user and set credentials in `.env`
5. Run:
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan key:generate`
   - `php artisan migrate --seed --force`
   - `php artisan storage:link`
6. Configure cron (recommended):
   - `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## News API

### Public

- `GET /api/v1/news?locale=ru&page=1`
- `GET /api/v1/news/{slug}?locale=ru`

Returns: `title`, `slug`, `excerpt`, `cover_url`, `cover_alt`, `published_at`, `date_text`, SEO fields, `content_blocks` (detail), `locale`.

### Private preview

- `GET /api/v1/preview/news/{id}?token=...`
- `GET /api/v1/preview/news/{id}` with `auth:sanctum`
- `POST /api/v1/preview/news/{id}/token` with `auth:sanctum`

Frontend preview URL pattern:
- `/news-preview/:id?token=...`

## Status workflow

- `draft` -> `scheduled` -> `published`
- `hidden` and `archived` are non-public
- Public visibility scope:
  - status is `published`
  - `publish_at` is null or <= now
  - `unpublish_at` is null or > now
  - not soft deleted

## AI-assisted SEO in admin

- In `News -> Create/Edit`, SEO tab includes `Сгенерировать SEO автоматически (AI)`.
- When enabled, empty SEO fields are auto-filled at save time.
- Manual values always have priority (AI fills only empty fields).

## AI auto-translation to English

- In `News -> Create/Edit`, main tab includes `Авто-перевод на английский (AI)`.
- When enabled, empty `EN` fields for title/excerpt/content are auto-filled from Russian content.
- Manual English text always has priority and is not overwritten.

## Roles & permissions

- `smm`: create/read/update news only
- `editor`: publish/unpublish/archive/approve/preview
- `admin`: full permissions

Seeder: `database/seeders/RolesAndPermissionsSeeder.php`

## Running tests

- `php artisan test`
- `php artisan test tests/Feature/Feature`
