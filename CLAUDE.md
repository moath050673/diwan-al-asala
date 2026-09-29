# Project Instructions — Diwan Al Asala (ديوان الأصالة)

Arabic (RTL) e-commerce store for incense and related products, with an admin panel.
Laravel 12, PHP 8.2+, MySQL/MariaDB. Deployed on Laravel Cloud (Starter plan).

## ⚠️ No Node.js / No Vite / No npm
- This Laravel project **does not use Node.js**.
- It **does not use Vite** (there is no `vite.config.js`, no `@vite` directive).
- It **does not use npm** (there is no `package.json`, no `node_modules`).
- Frontend assets are **plain HTML (Blade), CSS and JavaScript**, served directly from `public/`.
- **Never execute npm commands** (`npm install`, `npm run build`, `npm run dev`, `npx ...`)
  unless the project is explicitly migrated to a Node-based asset pipeline first.
- Do not add build steps, bundlers, or Node-based tooling. Third-party JS libraries are
  self-hosted as prebuilt files (e.g. `public/js/vendor/three.min.js`, three.js r128).
- On Laravel Cloud, the default `npm run build` build command **must be removed** — it fails
  because there is no `package.json`.

## Architecture

### Backend (`app/`)
- `Http/Controllers/PageController.php` — renders storefront Blade pages (server-side
  settings, payment account details on checkout).
- `Http/Controllers/MediaController.php` — serves public uploads from the `db_public` disk at
  `/media/{path}` (rejects `..`, long-lived cache headers).
- `Http/Controllers/Api/*` — JSON API used by both storefront JS and the admin panel.
- `Http/Requests/*` — Form Requests (`StoreOrderRequest`, `SaveProductRequest`,
  `UpdateSettingsRequest`). Validation messages are Arabic (`lang/ar/validation.php`).
- `Http/Middleware/EnsureRole.php` (`role:admin,staff`), `SecurityHeaders.php`.
- `Services/OrderService.php` — order creation (row locks, stock decrement, duplicate-line
  merge), status changes (releases/re-reserves stock via `stock_released_at`), bulk delete,
  order numbers. **All order/stock logic belongs here.**
- `Services/ProductImageStorage.php` — stores/deletes product images on the configured disk.
- `Services/NewOrderNotifier.php` — Telegram + email notification after the response is sent
  (`app()->terminating`).
- `Filesystem/DatabaseAdapter.php` — custom Flysystem adapter (`database` driver) storing files
  in the `stored_files` table; registered in `AppServiceProvider`.
- `Console/Commands/TelegramConnect.php` — `php artisan telegram:connect {token}` writes
  `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID` to `.env`.
- `Models/Setting.php` — key/value store settings cached with
  `Cache::rememberForever('settings.all')`; public keys injected into `layouts.app` by a view
  composer in `AppServiceProvider`.

### Routes
- `routes/web.php` — storefront pages, `/admin/*` pages, `/media/{path}`.
- `routes/api.php` — public: products, categories, orders (guest checkout), settings, contact,
  login. `auth:sanctum` + `role:admin,staff`: products/orders/customers/payments/dashboard.
  `role:admin` only: deletes, categories, settings.
- Rate limiters (in `AppServiceProvider`): `login` 5/min per email+IP, `checkout` 10/min,
  `contact` 5/min.

### Frontend (`resources/views/`, `public/`)
- Storefront Blade: `layouts/app.blade.php`, `pages/*`. JS in `public/js/`
  (`api.js`, `app.js`, `products.js`, `cart.js`, `checkout.js`, `whatsapp.js`,
  `hero-scene.js`). CSS in `public/css/`.
- Admin Blade: `layouts/admin.blade.php`, `admin/*`. JS/CSS in `public/admin-assets/`.
  Admin authenticates with a Sanctum bearer token kept in `localStorage`.
- JS renders HTML with template strings — **always pass dynamic values through `escapeHtml`**.
- Categories and settings come from the API/server; never hardcode them in JS.

### Storage (configurable, no code changes needed to switch)
- `config/store.php` → `disks.images` (`PRODUCT_IMAGES_DISK`) and `disks.receipts`
  (`RECEIPTS_DISK`).
- Local development: `public` (images, needs `php artisan storage:link`) and `local`
  (receipts, private).
- Laravel Cloud Starter (no object storage, ephemeral filesystem): `db_public` / `db_private`
  (files stored in MySQL). Later: set these to an S3/bucket disk name.
- Payment receipts are private — only admin/staff can read them via
  `/api/payments/{id}/receipt`. Never expose them on a public URL.
- Do not pass `visibility` when storing files (Cloudflare R2 rejects it).

## Local development (Windows / XAMPP)
- Start MySQL from XAMPP first.
- `php artisan serve --port=8100` (port 8000 may be used by another project).
- Tests: `php artisan test` (SQLite in-memory). All tests must pass before committing.

## Deployment (Laravel Cloud)
1. Push to `main` on GitHub; Laravel Cloud deploys from the repository.
2. Database: Laravel MySQL.
3. Build commands:
   ```
   composer install --no-dev --optimize-autoloader
   php artisan optimize
   ```
4. Deploy command: `php artisan migrate --force`
5. Key environment variables: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`,
   `APP_LOCALE=ar`, `APP_TIMEZONE=Asia/Aden`, `CACHE_STORE=database`,
   `SESSION_SECURE_COOKIE=true`, `PRODUCT_IMAGES_DISK=db_public`, `RECEIPTS_DISK=db_private`,
   `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, payment account variables, `ADMIN_EMAIL`,
   `ADMIN_PASSWORD`. See `.env.example`.
6. First deploy only (Commands tab):
   `php artisan db:seed --class=SettingSeeder --force`,
   `--class=CategorySeeder`, `--class=AdminUserSeeder`.
- Never commit `.env`, tokens, or passwords.

## Operations (production)
- `php artisan app:health` — health audit (DB, migrations, storage disks, cache, queue, mail,
  HTTPS, env, permissions, GD/WebP, OPcache, backups). Never prints secrets.
- `php artisan backup:run` — full DB backup incl. files stored in `stored_files`
  (`App\Services\DatabaseBackup`, gzip JSON-lines, optional AES encryption with
  `BACKUP_PASSWORD`). Scheduled daily at 03:00 Asia/Aden; optionally sent to Telegram
  (`BACKUP_TELEGRAM=true`, password required). Never use a `database`-driver disk as `BACKUP_DISK`.
- `php artisan backup:restore latest|<path>|<https-url> --force` — restores inside one
  transaction and saves a `pre-restore-*` safety backup first.
- Errors are logged with URL/IP context and, when `ERROR_ALERTS_TELEGRAM=true`, alerted on
  Telegram (`App\Services\ErrorAlerter`, throttled per error for 30 min).
- Uploaded images go through `App\Services\ImageOptimizer` (GD): max 1600px + 600px thumbnail,
  WebP when available, EXIF/GPS stripped. Without GD the original is stored.
- Tests never call real services (`Http::preventStrayRequests()`, Telegram vars blanked in
  `phpunit.xml`). To include the GD image test locally:
  `php -d extension=gd vendor/bin/phpunit`.

## General
- Always write clean and maintainable code.
- Never break existing functionality.
- Follow Laravel 12 best practices.
- Explain every important change before applying it.

## Code Style
- Use SOLID principles.
- Use Service classes when appropriate.
- Use Form Requests for validation.
- Avoid duplicated code.

## Security
- Validate all user input.
- Prevent SQL Injection.
- Prevent XSS.
- Use Laravel security features.

## Performance
- Optimize queries.
- Avoid N+1 queries.
- Cache where appropriate.

## Language
- Use English for code.
- Explain changes in Arabic.

## Before Editing
- Read the entire project.
- Understand the architecture.
- Ask before making destructive changes.
