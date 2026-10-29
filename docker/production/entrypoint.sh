#!/bin/sh
set -e

cd /var/www

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
