# Project structure — zomaa-clothing-app

A short map of the application, the request flows that matter, and where media
(images) live in development and on Render.

- **Storefront** — Laravel 11 + Blade, server-rendered at `/`
- **Admin** — Vue 3 + Vite SPA at `/admin`, talking to a Laravel JSON API at `/api`
- **Database** — MySQL (local Docker, or Aiven `defaultdb` in production)
- **Hosting** — one Docker image (nginx + php-fpm + supervisor) on Render

---

## 1. Top-level layout

```
zomaa-clothing-app/
├── app/                     Laravel application code
│   ├── Http/
│   │   ├── Controllers/     Storefront (Blade) controllers
│   │   │   ├── ProductController.php    home, category, product pages
│   │   │   ├── CartController.php       add/remove/update cart
│   │   │   ├── CheckoutController.php   checkout + Stripe webhook
│   │   │   ├── OrderController.php      customer order list/pay/proof upload
│   │   │   ├── ProfileController.php    customer profile
│   │   │   ├── MediaController.php      serves DB-stored uploads (/media)
│   │   │   └── Api/                     JSON API used by the Vue admin
│   │   ├── Requests/        Form validation (ProductRequest, BannerRequest, …)
│   │   └── Resources/       JSON shapes (ProductResource, CategoryResource, …)
│   ├── Models/              Eloquent models (Product, ProductImage, Category,
│   │                        Banner, Order, Payment, MediaFile, …)
│   ├── Support/
│   │   └── MediaStorage.php Single place that writes/reads/deletes uploads
│   ├── Mail/                Order notification mailables
│   └── Services/            Stripe / payment helpers
├── backend/                 Vue 3 admin SPA (own package.json + Vite build)
│   ├── src/
│   │   ├── main.js, App.vue, axios.js
│   │   ├── router/index.js  /admin routes (login, dashboard, CRUD screens)
│   │   ├── store/           Pinia store: state.js, actions.js, mutations.js
│   │   ├── views/           Dashboard, Products, Categories, Banners, Orders,
│   │   │                    Customers, Users, Settings, Reports
│   │   └── components/      Layout, tables, ImagePreview, core widgets
│   └── dist/                build output copied to public/admin in Docker
├── config/
│   ├── filesystems.php      "public" disk → storage/app/public
│   └── media.php            MEDIA_DRIVER: local | database | s3
├── database/
│   ├── migrations/          schema history (see §4)
│   └── seeders/             admin user, demo catalogue, banners, settings
├── docker/
│   └── production/          nginx.conf, supervisord.conf, entrypoint.sh
├── docker-compose/          local nginx + php config (opcache.ini, app.conf)
├── docs/                    this file + Render/Aiven runbook
├── public/                  web root (index.php, build/, admin/, img/)
├── resources/
│   ├── css/, js/            storefront Vite assets
│   └── views/               Blade templates (see §3)
├── routes/
│   ├── web.php              storefront + /media route
│   ├── api.php              admin JSON API (Sanctum + admin middleware)
│   └── auth.php             login/register/password reset
├── storage/
│   └── app/public/          files served at /storage (seed art + local uploads)
│       ├── categories/      seeded category/product artwork (committed)
│       ├── banners/         local banner uploads (ignored)
│       ├── images/          local product uploads (ignored)
│       └── payment-proofs/  local payment proofs (ignored)
├── Dockerfile               production multi-stage build (storefront + admin + PHP)
├── Dockerfile.dev           local PHP image
├── docker-compose.yml       local app/db/node/webserver stack
└── vite.config.js           storefront asset build
```

---

## 2. Entry points and request flows

### Storefront (Blade)

```
GET /                         → ProductController@index    → resources/views/product/index.blade.php
GET /category/{slug}          → ProductController@byCategory
GET /product/{slug}           → ProductController@view
GET /cart                     → CartController@index
GET /checkout                 → CheckoutController@view (auth)
GET /orders/{order}/pay       → OrderController@pay (auth)
POST /orders/{order}/pay      → OrderController@submitPayment (proof upload)
```

Blade templates call Eloquent models directly. Product cards use
`Product::getImageAttribute()` → first `ProductImage` → its resolved URL.
Categories fall back to `storage/categories/<slug>.png|jpg` when the category
has no uploaded image.

### Admin SPA (Vue → JSON API)

```
GET  /admin/*                 → public/admin/index.html (SPA router)
POST /api/login               → Api\AuthController@login (Sanctum token)
GET  /api/products            → Api\ProductController@index
POST /api/products            → Api\ProductController@store (multipart + files)
PUT  /api/products/{id}       → Api\ProductController@update
GET  /api/categories          → Api\CategoryController@index
POST /api/banners             → Api\BannerController@store (multipart)
GET  /api/orders              → Api\OrderController@index
```

Everything under `/api` except `/api/login` requires `auth:sanctum` + the
`admin` middleware. The SPA stores the token in `localStorage` and sends it as
`Authorization: Bearer <token>`.

### Media serving

```
/storage/<path>   → nginx serves storage/app/public/<path> (seed art, local driver)
/media/<path>     → Laravel MediaController@show → media_files table (database driver)
```

The `/media/{path}` route is registered with `where('path', '.*')` so nested
paths such as `images/ab12/CD34.png` match. Responses are cached
`public, max-age=31536000, immutable` with an ETag because upload paths are
random and never reused.

---

## 3. Storefront templates

```
resources/views/
├── layouts/app.blade.php          main storefront shell (nav, footer, Vite assets)
├── product/index.blade.php        home + category listing
├── product/view.blade.php         product detail + gallery
├── cart/index.blade.php
├── checkout/{index,placed,success,failure}.blade.php
├── order/{index,view,pay}.blade.php
├── profile/view.blade.php
├── auth/*.blade.php               login, register, password reset
├── mail/*.blade.php               order emails
└── components/                    shared Blade UI pieces
```

Storefront CSS/JS is compiled by Vite from `resources/css` and `resources/js`
into `public/build`. The admin SPA is a separate build in `backend/` copied to
`public/admin`.

---

## 4. Database tables

| Table | Purpose |
|---|---|
| `users` | Admins and customers (`is_admin` flag + Sanctum tokens) |
| `products` | Catalogue items (price, quantity, published, soft deletes) |
| `product_images` | One row per uploaded product photo: `path`, `url`, `position` |
| `product_categories` | Product ↔ category pivot |
| `categories` | Category tree (`parent_id`, `slug`, optional `image`) |
| `banners` | Homepage hero/split banners (`image_path`, `image_url`, `position`) |
| `orders`, `order_details`, `order_items` | Orders and line items |
| `payments` | Payment status, Stripe session, uploaded `payment_proof` |
| `payment_methods` | Bank transfer / Stripe options shown at checkout |
| `customers`, `customer_addresses`, `countries` | Customer profiles |
| `settings` | Store name, currency, contact details |
| `cart_items` | Persisted cart rows |
| `media_files` | Uploaded bytes stored in MySQL (`path` PK, `mime`, `size`, `contents` LONGBLOB) |
| `personal_access_tokens`, `password_resets`, `failed_jobs` | Framework tables |

`media_files` is the only table that stores binary data. Aiven's managed MySQL
requires a primary key on every table, so `path` is the primary key.

---

## 5. How uploaded images work

All uploads go through **`app/Support/MediaStorage.php`**. Controllers and
models never call `Storage::put()` for user files directly.

```php
$media = app(MediaStorage::class)->put($uploadedFile, 'images');
// ['path' => 'images/ab12.../cd34....jpg',
//  'url'  => 'https://.../media/images/ab12.../cd34....jpg',
//  'mime' => 'image/jpeg',
//  'size' => 123456]

app(MediaStorage::class)->delete($path);
```

### Drivers (`config/media.php`)

| `MEDIA_DRIVER` | Where bytes live | Served from | Use |
|---|---|---|---|
| `local` | `storage/app/public/<path>` | `/storage/<path>` | Local development |
| `database` | `media_files` table in MySQL | `/media/<path>` | **Default in production** — survives Render's ephemeral disk |
| `s3` | S3-compatible bucket (`s3` disk) | bucket URL / CDN | Optional, for larger sites |

Resolution rule: `env('MEDIA_DRIVER', env('APP_ENV') === 'production' ? 'database' : 'local')`.

### URL resolution

- `ProductImage::getUrlAttribute()` and `Category::getImageUrlAttribute()`
  resolve the URL from the stored **path**, not from a hard-coded host. Rows
  written by older versions keep their `url` column as a fallback.
- Seeded artwork uses two-segment paths (`categories/men.png`). It is never
  deleted by `MediaStorage`, because many products share it.
- Uploads use three-segment paths (`images/<random>/<random>.<ext>`); deleting
  one removes the row, the file, and its private folder.

### Product image ordering contract

The admin product form submits:

- `images[]` — new file uploads
- `image_order[]` — authoritative display order, as `existing:<id>` / `new:<uploadIndex>`
- `deleted_images[]` — rows the admin removed

`Api\ProductController::syncImages()` reconciles the existing rows against
`image_order`: kept rows get their `position` rewritten, uploads are inserted at
their requested position, and anything absent from `image_order` is deleted
(row + bytes). Positions are then renumbered `1..n`. Uploading a replacement in
edit mode marks the displaced cover for deletion in the UI, so the swap is one
Save and the old file is really gone.

---

## 6. Local development

```bash
docker compose up -d          # app, db, node, webserver, backend node
docker compose exec app php artisan migrate --seed
```

- App: <http://localhost:8000>
- Admin: <http://localhost:8000/admin>
- DB: `laravel_db`, database `laravel_vue_ecommerce`, user `root` / `root`
- Uploads use `MEDIA_DRIVER=local` → visible at `/storage/...`
- `public/storage` is a symlink created by `php artisan storage:link`

`docker-compose/php/opcache.ini` sets `opcache.validate_timestamps=0` because
`stat` on a Windows bind mount is very slow. After editing PHP, restart the app
container so OPcache reloads the code:

```bash
docker compose restart app
```

---

## 7. Production topology (Render + Aiven)

```
Browser
  │  https://<service>.onrender.com
  ▼
Render Web Service (Docker image, free plan)
  ├── nginx :$PORT ── /admin/* → public/admin (Vue SPA)
  │                 └── everything else → php-fpm → Laravel
  ├── php-fpm (www-data)
  │     ├── /storage/* → files baked into the image (seed art)
  │     └── /media/*   → MediaController → MySQL media_files
  └── ephemeral disk (recreated on every deploy)
          │
          ▼
Aiven for MySQL (free) — defaultdb
```

- Build: `Dockerfile` (multi-stage Node builds + PHP runtime).
- Start: `docker/production/entrypoint.sh` — renders nginx port, creates
  `storage` dirs, `storage:link`, runs migrations with retries, caches config,
  optionally seeds, starts supervisor.
- Uploads survive redeploys because production defaults to `MEDIA_DRIVER=database`.
- Seed artwork must be committed under `storage/app/public/categories/`; the
  image build copies it into `/var/www` and `/storage/...` serves it directly.

See `docs/RENDER_AIVEN_DEPLOY.md` for the click-by-click setup.

---

## 8. Troubleshooting images

| Symptom | Cause | Fix |
|---|---|---|
| `GET /storage/... 404` for `categories/*` | seed artwork missing from the deployed image | commit `storage/app/public/categories/*` (whitelisted in `storage/app/public/.gitignore`), redeploy |
| `GET /storage/images/<random>/... 404` | upload written to the old ephemeral disk and lost on redeploy | re-upload the image; new uploads go to `media_files` when `MEDIA_DRIVER=database` |
| `GET /media/... 404` | row missing from `media_files` (wrong driver at upload time, or DB restored) | confirm `MEDIA_DRIVER=database` in Render → Environment, then re-upload |
| Images work locally, not on Render | local uses `/storage` + local disk; production uses `/media` + MySQL | check the deploy log line `[entrypoint] Media driver: database` |
| Upload succeeds but page still shows old image | OPcache with `validate_timestamps=0` in local dev | `docker compose restart app` |
| Admin shows images, storefront does not | `APP_URL` mismatch / stale `url` column | set `APP_URL=https://<service>.onrender.com` and redeploy |

Quick checks (replace the host):

```bash
curl -I https://<service>.onrender.com/up
curl -I https://<service>.onrender.com/storage/categories/men.png
curl -I https://<service>.onrender.com/media/<path-from-admin-response>
```

---

## 9. Useful commands

```bash
docker compose up -d                          # start local stack
docker compose exec app php artisan migrate   # apply schema changes
docker compose exec app php artisan db:seed   # seed admin + demo catalogue
docker compose restart app                    # reload PHP (OPcache)
docker compose exec app sh                    # shell inside the app
```

Production is driven by git: push to `main` → Render rebuilds → entrypoint
migrates and restarts nginx/php-fpm.
