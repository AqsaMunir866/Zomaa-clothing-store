#!/bin/sh
set -e

cd /var/www

# Surface misconfiguration in the deploy log instead of a bare 500 at runtime.
if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] ERROR: APP_KEY is not set - every web page will return 500." >&2
    echo "[entrypoint] Generate one with: php artisan key:generate --show" >&2
elif [ "${#APP_KEY}" -ne 51 ]; then
    echo "[entrypoint] WARNING: APP_KEY has ${#APP_KEY} characters; a valid key is 'base64:' plus 44 characters." >&2
fi
echo "[entrypoint] Database target: ${DB_USERNAME:-<unset>}@${DB_HOST:-<unset>}:${DB_PORT:-3306}/${DB_DATABASE:-<unset>}"

# Render injects PORT; nginx cannot read env vars directly, so render the template.
PORT="${PORT:-8080}"
sed "s/__PORT__/${PORT}/g" /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link || true
php artisan package:discover --ansi || true

# Schema must exist before the app starts.
php artisan migrate --force

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

# Optional first-deploy seeding (set RUN_SEED=true once, then false).
if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force || true
    php artisan db:seed --class=ClothingStoreSeeder --force || true
    php artisan db:seed --class=BannerAndSettingsSeeder --force || true
fi

exec supervisord -c /etc/supervisord.conf
