# Free Hosting & Public Launch Guide

This app needs **PHP 8.2 + MySQL**, runs **two frontends** (Laravel storefront + Vue admin SPA), and stores **uploaded files** (product images, banners, logos, payment proofs). This guide takes it from the Docker dev setup to a **public, free URL**.

---

## 0. What makes this app "hostable" — and the constraints

| Requirement | Notes |
|---|---|
| PHP 8.2 with extensions | `pdo_mysql, mbstring, bcmath, gd, zip, fileinfo`. `gd` is needed for image validation. The repo `Dockerfile` already installs these. |
| MySQL 8 | Or SQLite (see §4). |
| Writable `storage/` + `bootstrap/cache/` | Caches + uploads. |
| `php artisan storage:link` | Serves `/storage/**`. |
| Node (build step only) | To compile storefront (`public/build`) and admin (`backend/dist`). |
| Persistent storage for uploads | Free hosts have **ephemeral** disks → uploads vanish on redeploy. Use object storage (Cloudflare R2) or accept it for a demo. |
| Stripe | **Not required.** Payments are manual (COD/Bank/JazzCash/EasyPaisa). Leave Stripe env empty. |

---

## 1. Free hosting options (2026)

| Option | Type | Free? | MySQL? | Persistent disk | Notes |
|---|---|---|---|---|---|
| **Render** | Docker/PaaS | Yes (sleeps when idle) | No free MySQL | No (paid) | Easiest Docker deploys, free SSL, good docs. **Recommended host.** |
| **Koyeb** | Docker/PaaS | Yes (1 nano, scale‑to‑zero) | No | No | Clean UX, Docker‑first. |
| **Railway** | PaaS | Trial credit only | Yes | Yes | Not free forever. |
| **Fly.io** | Docker | Usage‑based | Postgres | Paid volumes | Not free for new orgs. |
| **Aiven** | Managed DB | **Yes** (1 free MySQL, 1 GB) | **MySQL** | n/a | **Recommended DB.** |
| **TiDB Cloud Serverless** | Managed DB | Yes (5 GB, MySQL‑compatible) | MySQL protocol | n/a | Requires TLS. |
| **Clever Cloud** | PaaS | Free tiers (small) | MySQL add‑on | No | Small quotas. |
| **Cloudflare Pages / Netlify / Vercel** | Static | **Yes** | — | — | For the **admin SPA** only. |
| **Cloudflare R2** | Object storage | **Yes** (10 GB) | — | — | For uploads (S3‑compatible). |
| **InfinityFree / 000webhost** | Shared PHP | Yes | MySQL | Yes (small) | No SSH/Node; must upload prebuilt files. Fiddly. |
| **db4free.net / FreeSQLDatabase** | Shared MySQL | Yes | MySQL | — | Unstable; demo only. |

**Recommended free stack:** **Render** (Laravel Docker) + **Aiven MySQL** (free) + serve the **admin SPA from the same origin** (no CORS). Optionally **Cloudflare R2** for persistent uploads.

---

## 2. Recommended path — one origin, two services

Result: `https://your-app.onrender.com` serves the **storefront**, and the **admin panel at `/admin`**, backed by a free Aiven MySQL.

### 2.1 One code change: serve the admin SPA under `/admin`

**`backend/vite.config.js`**
```js
export default defineConfig({
  plugins: [vue()],
  base: '/admin/',          // ← add
  server: { /* ...keep dev settings... */ },
})
```

**`backend/src/router/index.js`**
```js
const router = createRouter({
  history: createWebHistory('/admin/'),   // ← base path
  routes
})
```

**`backend/.env.production`**
```
VITE_API_BASE_URL = https://your-app.onrender.com
```

At build time the SPA is copied to `public/admin` (see Dockerfile §2.4), so it is served from the same origin — no CORS needed and the Axios client calls `https://your-app.onrender.com/api`.

### 2.2 Make Laravel trust the platform proxy (HTTPS)

**`bootstrap/app.php`** — add inside `->withMiddleware(...)`:
```php
$middleware->trustProxies(at: '*');
```
Without this, Laravel may generate `http://` URLs and force redirect loops behind Render's TLS proxy.

### 2.3 Production environment variables

```
APP_NAME="Maison Blanc"
APP_ENV=production
APP_KEY=base64:...            # generate with: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

LOG_CHANNEL=stderr
CACHE_STORE=file
SESSION_DRIVER=file
FILESYSTEM_DISK=local         # or "s3" if using Cloudflare R2

DB_CONNECTION=mysql
DB_HOST=<aiven-host>
DB_PORT=<aiven-port>
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=<aiven-password>
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt   # Aiven requires TLS

MAIL_MAILER=log               # or smtp for real order emails

# Optional object storage (Cloudflare R2 — S3 compatible)
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=...
# AWS_SECRET_ACCESS_KEY=...
# AWS_DEFAULT_REGION=auto
# AWS_BUCKET=maison-blanc
# AWS_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
# AWS_URL=https://cdn.your-domain.com
# AWS_USE_PATH_STYLE_ENDPOINT=true
```

Add this to **`config/database.php`** under the `mysql` connection so Aiven TLS works:
```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
]) : [],
```

### 2.4 Production `Dockerfile` (multi-stage, nginx + php-fpm)

Create **`Dockerfile.production`** in the repo root:
```dockerfile
# ---- 1. Build frontend assets ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

WORKDIR /app/backend
COPY backend/package*.json ./
RUN npm ci
COPY backend/ ./
RUN npm run build          # outputs backend/dist

# ---- 2. Install PHP deps ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# ---- 3. Runtime: php-fpm + nginx + supervisor ----
FROM php:8.2-fpm
RUN apt-get update && apt-get install -y --no-install-recommends \
      nginx supervisor git unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath gd fileinfo \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=assets /app/backend/dist ./public/admin      # admin SPA at /admin

# nginx + supervisor configs
COPY docker/production/nginx.conf /etc/nginx/sites-available/default
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
```

**`docker/production/nginx.conf`**
```nginx
server {
    listen ${PORT};
    root /var/www/public;
    index index.php index.html;
    client_max_body_size 25m;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
> Render sets `$PORT` (default 10000). Use `listen 10000;` if templating is easier.

**`docker/production/supervisord.conf`**
```ini
[supervisord]
nodaemon=true
[program:php-fpm]
command=php-fpm -F
[program:nginx]
command=nginx -g "daemon off;"
```

**`docker/production/entrypoint.sh`**
```sh
#!/bin/sh
set -e
php artisan storage:link || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec supervisord -c /etc/supervisor/conf.d/app.conf
```

### 2.5 `render.yaml` (optional Infrastructure‑as‑Code)
```yaml
services:
  - type: web
    name: maison-blanc
    runtime: docker
    dockerfilePath: ./Dockerfile.production
    plan: free
    healthCheckPath: /up
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_KEY
        sync: false        # set in dashboard
      - key: APP_URL
        sync: false
      - key: DB_CONNECTION
        value: mysql
      - key: DB_HOST
        sync: false
      - key: DB_PORT
        sync: false
      - key: DB_DATABASE
        sync: false
      - key: DB_USERNAME
        sync: false
      - key: DB_PASSWORD
        sync: false
      - key: MYSQL_ATTR_SSL_CA
        value: /etc/ssl/certs/ca-certificates.crt
      - key: LOG_CHANNEL
        value: stderr
```

### 2.6 Deploy steps

1. **Push to GitHub.**
   ```bash
   git add -A && git commit -m "Add production deployment"
   git push origin main
   ```
2. **Create the free MySQL** at **Aiven** (service type MySQL, plan `Free`, pick a nearby region). Copy host, port, user (`avnadmin`), password, database (`defaultdb`).
3. **Render → New → Web Service → connect your repo.** Choose **Docker**, set the Dockerfile to `Dockerfile.production`, plan **Free**. Add all env vars from §2.3.
4. **First deploy** runs `migrate --force`. Then seed the catalog/admin once (Render Shell or a one‑off Job):
   ```bash
   php artisan db:seed --force
   php artisan db:seed --class=ClothingStoreSeeder --force
   php artisan db:seed --class=BannerAndSettingsSeeder --force
   ```
5. Open `https://your-app.onrender.com` (storefront) and `/admin` (login `admin@example.com / admin123`).

> Render free web services **sleep after ~15 min idle** and cold‑start in ~30–60 s. Acceptable for a demo; upgrade or keep‑alive pings if not.

---

## 3. Simpler alternative — SQLite, single container (fastest demo)

If you don't want an external DB, use **SQLite** (the repo `.env.example` already defaults to it):

```
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
```
In the entrypoint:
```sh
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
```
- **Pros:** zero DB setup, one host.
- **Cons:** the free host's disk is **ephemeral**, so data resets on every redeploy/restart. Fine for a public demo; not for real orders. Move to Aiven MySQL when you need persistence.

---

## 4. Alternative — host the admin SPA separately (free static)

If you prefer the admin panel on its own free URL (Cloudflare Pages / Netlify / Vercel):

1. Set `backend/.env.production` → `VITE_API_BASE_URL = https://your-app.onrender.com` (no `/admin` base needed — keep router at `/`).
2. Build: `cd backend && npm ci && npm run build` → deploy `backend/dist`.
3. Add **`config/cors.php`** so the SPA can call the API cross‑origin:
   ```php
   return [
       'paths' => ['api/*', 'sanctum/csrf-cookie'],
       'allowed_methods' => ['*'],
       'allowed_origins' => [env('ADMIN_URL', 'https://your-admin.pages.dev')],
       'allowed_headers' => ['*'],
       'supports_credentials' => true,
   ];
   ```
   Set `ADMIN_URL` in Render env and `php artisan config:cache`.

---

## 5. Classic shared PHP hosting (InfinityFree / 000webhost)

Free PHP+MySQL but **no SSH/Node**. You must:

1. Build locally: `npm run build` (storefront) and `cd backend && npm run build` (admin); copy `backend/dist` → `public/admin`.
2. `composer install --no-dev --optimize-autoloader` locally.
3. Upload everything, with the domain's **document root pointed at `/public`**.
4. Import the schema via **phpMyAdmin** (export from local: `php artisan migrate` then dump) and create `storage:link` manually (or set `FILESYSTEM_DISK=public` and upload into `public/storage`).
5. Set `.env` and **delete** `bootstrap/cache/config.php` after editing.

> Many free shared hosts block `exec`, restrict `putenv`, and disallow Composer autoload edge cases — expect friction. Prefer Render/Koyeb for reliability.

---

## 6. Post‑deploy checklist

- [ ] `APP_KEY` set, `APP_DEBUG=false`, `APP_ENV=production`.
- [ ] HTTPS working; `APP_URL` uses `https`.
- [ ] `trustProxies(at: '*')` enabled.
- [ ] `php artisan storage:link` ran (images/proofs load).
- [ ] Catalog + admin seeded; default admin password **changed**.
- [ ] Upload persistence decided (local ephemeral vs R2).
- [ ] Checkout: select method → place order → `/orders/{id}/pay` → upload proof → admin marks **paid**.
- [ ] Admin: products/categories/banners/payment‑methods/settings CRUD all work.
- [ ] Emails: set an SMTP provider (e.g. Brevo free) if you want order emails (currently `MAIL_MAILER=log`).

---

## 7. Free‑tier caveats (be honest with expectations)

- **Cold starts / sleeping** on free web tiers.
- **Ephemeral disks** → uploaded images/proofs are lost on redeploy unless using R2/S3.
- **Small DB quotas** on free managed MySQL.
- **Free subdomains** by default; a custom domain is usually free to attach (you pay only the domain).
- The legacy **Stripe** checkout will not work in PK — the manual payment flow is the real one.
