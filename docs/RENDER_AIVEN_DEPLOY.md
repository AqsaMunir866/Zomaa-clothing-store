# Deploy to Render (free) + Aiven MySQL (free)

Production setup for this repo:

| Piece | What runs |
|---|---|
| `Dockerfile` | nginx + php-fpm + supervisor, built on Render (default path, no setting needed) |
| `docker/production/` | nginx site config, supervisord, container entrypoint |
| Storefront | Laravel Blade at `/` |
| Admin SPA | Vue 3 at `/admin` (same origin, no CORS) |
| Database | Aiven for MySQL, pre-created `defaultdb` |

`Dockerfile.dev` and `docker-compose.yml` are the local development setup; they are not used by Render.

---

## 0. What you need

- The repo pushed to GitHub (`main`).
- An Aiven MySQL service (free plan) and its connection details.
- A Render account.

---

## 1. Push the production files

```bash
git add -A
git commit -m "Add production Docker setup for Render"
git push origin main
```

Files that must be in the repo:

- `Dockerfile`
- `.dockerignore`
- `docker/production/nginx.conf`
- `docker/production/supervisord.conf`
- `docker/production/entrypoint.sh`
- `storage/app/public/categories/*` (demo artwork — deliberately force-added; the rest of `storage/app/public` stays ignored)

---

## 2. Aiven MySQL details

In the Aiven console open your MySQL service:

1. **Overview** → copy **Host**, **Port** (usually `12345`-ish), **User** (`avnadmin`), **Password**.
2. **Databases** → `defaultdb` already exists, use it as `DB_DATABASE`.
3. **Overview** → download **CA certificate** (`ca.pem`). You will upload it to Render in step 4.

Database name: `defaultdb` — do not create another one.

> Aiven free MySQL powers off after long inactivity and emails you a notice. Open the console and power it back on if the site shows a database error.

---

## 3. Create the Render web service

1. <https://dashboard.render.com> → **New** → **Web Service**.
2. Connect the GitHub repo `Zomaa-clothing-store`, branch `main`.
3. **Language / Runtime**: `Docker`.
4. **Dockerfile Path**: leave the default `./Dockerfile` (production image).
5. **Region**: pick the one closest to your Aiven service.
6. **Instance Type**: `Free`.
7. **Health Check Path**: `/up`
8. Do **not** set a start command — the image entrypoint starts nginx + php-fpm.

If the service already exists, no Dockerfile setting is needed — Render builds the repo's root `Dockerfile`. Just confirm the runtime is **Docker** (it is shown at the top of the service page) and set **Settings → Health Checks** to `/up`.

---

## 4. Environment variables

Add these under **Environment** (Values are secrets; never commit them).

| Key | Value |
|---|---|
| `APP_NAME` | `Maison Blanc` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` (generate locally with `php artisan key:generate --show`) |
| `APP_URL` | `https://<your-service>.onrender.com` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Aiven host |
| `DB_PORT` | Aiven port |
| `DB_DATABASE` | `defaultdb` |
| `DB_USERNAME` | `avnadmin` |
| `DB_PASSWORD` | Aiven password |
| `MYSQL_ATTR_SSL_CA` | `/etc/secrets/ca.pem` |
| `CACHE_DRIVER` | `file` |
| `SESSION_DRIVER` | `cookie` |
| `QUEUE_CONNECTION` | `sync` |
| `FILESYSTEM_DISK` | `local` |
| `RUN_SEED` | `true` (first deploy only) |
| `ADMIN_EMAIL` | `admin@example.com` (or your own) |
| `ADMIN_PASSWORD` | a strong password |

Leave `DATABASE_URL`, Stripe and mail credentials empty.

`APP_URL` is baked into seeded banner/product image URLs, so set it **before** the first deploy with `RUN_SEED=true`.

### Secret file (TLS certificate)

**Environment → Secret Files → Add Secret File**

- Filename: `ca.pem`
- Contents: paste the Aiven CA certificate you downloaded.

Render mounts it at `/etc/secrets/ca.pem`, which matches `MYSQL_ATTR_SSL_CA`.

---

## 5. First deploy

Click **Manual Deploy → Deploy latest commit**. The entrypoint runs, in order:

1. renders the nginx `listen` port from `$PORT`
2. `php artisan storage:link`
3. `php artisan migrate --force`
4. caches config / routes / views
5. if `RUN_SEED=true`: `db:seed` (admin + countries), then `ClothingStoreSeeder`, then `BannerAndSettingsSeeder`
6. starts supervisord (nginx + php-fpm)

Watch **Logs** for the migration list ending in `DONE` and `Seeding database.` lines.

### Verify

- `https://<service>.onrender.com/up` → `200`
- `https://<service>.onrender.com/` → storefront
- `https://<service>.onrender.com/admin` → admin login
- Log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`

### Then turn seeding off

**Environment → `RUN_SEED` → `false` → Save** (triggers a redeploy).

The seeders are idempotent, but leaving `RUN_SEED=true` would re-run them on every container start.

---

## 6. Free-tier limitations

| Limit | Effect | Mitigation |
|---|---|---|
| Ephemeral disk | Product/banner uploads made after deploy are lost on redeploy/restart | Demo only; use S3/R2 (`FILESYSTEM_DISK=s3`) for real persistence |
| Sleeps after 15 min idle | First request takes ~30–60 s | Acceptable for a demo |
| 512 MB RAM / 0.1 CPU | Slow image processing | Keep uploads small |
| Aiven 1 GB storage, ~76 connections | Enough for a demo | — |
| Aiven powers off when idle | Site errors until you power it on | Check the Aiven console |

Only the demo artwork in `storage/app/public/categories/` is committed; uploads inside other folders stay untracked and are not part of the image.

---

## 7. Troubleshooting

**`SQLSTATE[HY000] [2002]` / connection refused**
Check `DB_HOST`, `DB_PORT`, `DB_DATABASE=defaultdb`, `DB_USERNAME=avnadmin`. Aiven host/port are not the standard `3306`.

**`SSL connection error` / `cafile`**
Secret file must be named exactly `ca.pem` and `MYSQL_ATTR_SSL_CA` must be `/etc/secrets/ca.pem`.

**500 after deploy**
Set `APP_DEBUG=true` temporarily to see the error, then set it back to `false`. Also check that `APP_KEY` is set.

**`No application encryption key has been specified`**
`APP_KEY` missing or the value was pasted without the `base64:` prefix.

**Admin page loads but API calls 401**
Log out and back in; tokens are per-environment.

**Images missing**
Seeded artwork is served from `storage/app/public/categories/`. Uploaded images are lost on redeploy (see §6).

---

## 8. Redeploying

Push to `main`; Render rebuilds automatically. Migrations run on every start, so new migrations are applied without manual steps. Keep `RUN_SEED=false`.
