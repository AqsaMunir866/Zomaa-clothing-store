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
- `storage/app/public/categories/*` (demo artwork — whitelisted in
  `storage/app/public/.gitignore`; the rest of `storage/app/public` stays ignored)
- `app/Support/MediaStorage.php`, `app/Http/Controllers/MediaController.php`,
  `app/Models/MediaFile.php`, `config/media.php`, and the
  `2026_11_05_000000_create_media_files_table.php` migration (durable uploads)

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
| `FILESYSTEM_DISK` | `local` (used for seed art and a local fallback) |
| `MEDIA_DRIVER` | `database` (uploads go to MySQL and are served from `/media`) |
| `RUN_SEED` | `true` (first deploy only) |
| `ADMIN_EMAIL` | `admin@example.com` (or your own) |
| `ADMIN_PASSWORD` | a strong password |

Leave `DATABASE_URL`, Stripe and mail credentials empty.

`APP_URL` is baked into seeded banner/product image URLs, so set it **before** the first deploy with `RUN_SEED=true`.

`MEDIA_DRIVER=database` is the production default even if you leave the variable
unset, because `APP_ENV=production`. Setting it explicitly makes the intent
visible and guards against a changed default.

### Secret file (TLS certificate)

**Environment → Secret Files → Add Secret File**

- Filename: `ca.pem`
- Contents: paste the Aiven CA certificate you downloaded.

Render mounts it at `/etc/secrets/ca.pem`, which matches `MYSQL_ATTR_SSL_CA`.

---

## 5. First deploy

Click **Manual Deploy → Deploy latest commit**. The entrypoint runs, in order:

1. prints the DB target and the resolved **media driver** (`database` in production)
2. renders the nginx `listen` port from `$PORT`
3. `php artisan storage:link` (and warns if `public/storage` is missing)
4. `php artisan migrate --force` (creates/updates `media_files` too)
5. caches config / routes / views
6. if `RUN_SEED=true`: `db:seed` (admin + countries), then `ClothingStoreSeeder`, then `BannerAndSettingsSeeder`
7. starts supervisord (nginx + php-fpm)

Watch **Logs** for `[entrypoint] Media driver: database`, the migration list
ending in `DONE`, and `Seeding database.` lines.

### Verify

- `https://<service>.onrender.com/up` → `200`
- `https://<service>.onrender.com/` → storefront
- `https://<service>.onrender.com/admin` → admin login
- Log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`

### Then turn seeding off

**Environment → `RUN_SEED` → `false` → Save** (triggers a redeploy).

The seeders are non-destructive: they only create rows that are missing, so
`RUN_SEED=true` no longer resets banners, logos, settings or product images.
Still keep it `false` after the first deploy; it only wastes startup time.

---

## 6. Free-tier limitations

| Limit | Effect | Mitigation |
|---|---|---|
| Ephemeral disk | Anything written under `storage/app/public` is lost on redeploy/restart | Uploads use `MEDIA_DRIVER=database` in production, so product/category/banner images and payment proofs live in MySQL and are served from `/media` |
| Sleeps after 15 min idle | First request takes ~30–60 s | Acceptable for a demo |
| 512 MB RAM / 0.1 CPU | Slow image processing | Keep uploads small |
| Aiven 1 GB storage, ~76 connections | Uploads now consume database space | Compress images before upload; keep the catalogue small |
| Aiven powers off when idle | Site errors until you power it on | Check the Aiven console |

Only the demo artwork in `storage/app/public/categories/` is committed; uploads
inside other folders stay untracked and are not part of the image.

> **Images uploaded before this change** were written to Render's ephemeral disk
> and their bytes are gone. Their database rows still point at `/storage/...`,
> so those specific images stay broken until you upload them again from the
> admin panel. After the first deploy with the media migration, new uploads are
> durable.


---

## 7. Troubleshooting

**`SQLSTATE[HY000] [2002]` / connection refused**
Check `DB_HOST`, `DB_PORT`, `DB_DATABASE=defaultdb`, `DB_USERNAME=avnadmin`. Aiven host/port are not the standard `3306`.

**`SSL connection error` / `cafile`**
Secret file must be named exactly `ca.pem` and `MYSQL_ATTR_SSL_CA` must be `/etc/secrets/ca.pem`.

**500 after deploy**
Set `APP_DEBUG=true` temporarily to see the error, then set it back to `false`. Also check that `APP_KEY` is set.

**`General error: 3750 ... sql_require_primary_key`**
Aiven (and any managed MySQL with `sql_require_primary_key=1`) rejects tables without a primary key. Add one to the migration, e.g. `$table->string('email')->primary();` for `password_resets`. Every new `Schema::create` must define `id()` or an explicit primary key.

**`No application encryption key has been specified`**
`APP_KEY` missing or the value was pasted without the `base64:` prefix.

**Admin page loads but API calls 401**
Log out and back in; tokens are per-environment.

**Images missing (`/storage/... 404`)**
Seeded artwork is served from `storage/app/public/categories/`. Make sure those
files are committed (`storage/app/public/.gitignore` whitelists the folder) and
that the deploy log says `public/storage is linked`. Uploads made before the
media-driver change were on the ephemeral disk and must be uploaded again.

**Images missing (`/media/... 404`)**
`MEDIA_DRIVER` was not `database` when the file was uploaded, or the row is not
in `media_files`. Check the deploy log for `[entrypoint] Media driver: database`,
then re-upload the image from the admin panel.

**Upload succeeds but the picture does not change**
The URL may be cached by the browser. Uploaded paths are random, so a real
replacement produces a new URL; hard-refresh the page. Locally, restart the app
container so OPcache picks up PHP edits.

**Uploaded images show for a while, then the old demo images come back after a deploy**
The seeders used to run on every deploy with `updateOrCreate()` and reset banner
images, settings and product rows to the demo values. They are now
non-destructive (`firstOrCreate()` / skipped once data exists), so re-uploading
the image once is enough. If you still see it, check the deploy log for the
seeders and confirm the image URL is `/media/...` (database driver).

**Local images work, production images do not**
Local uses `/storage` + the local disk; production uses `/media` + MySQL. That
is expected. Compare the two URLs in the browser dev tools to see which driver
served each page.

---

## 8. Redeploying

Push to `main`; Render rebuilds automatically. Migrations run on every start, so new migrations are applied without manual steps. Keep `RUN_SEED=false`.
