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

# Never log the secret itself, only whether it arrived.
if [ -z "${DB_PASSWORD}" ]; then
    echo "[entrypoint] ERROR: DB_PASSWORD is empty - MySQL will reject the login with 'using password: NO'." >&2
else
    echo "[entrypoint] DB_PASSWORD is set (${#DB_PASSWORD} characters)."
fi

# Secret files are often mounted root-only. Root can still run migrations while
# php-fpm (www-data) cannot read the CA, so every page that touches the database
# fails at runtime. Copy it somewhere the web user can read, before config:cache.
if [ -n "${MYSQL_ATTR_SSL_CA}" ]; then
    if [ -f "${MYSQL_ATTR_SSL_CA}" ]; then
        if cp "${MYSQL_ATTR_SSL_CA}" /usr/local/share/db-ca.pem 2>/dev/null; then
            chmod 644 /usr/local/share/db-ca.pem
            export MYSQL_ATTR_SSL_CA=/usr/local/share/db-ca.pem
            echo "[entrypoint] SSL CA: copied to ${MYSQL_ATTR_SSL_CA} (readable by www-data)."
        else
            echo "[entrypoint] WARNING: cannot copy ${MYSQL_ATTR_SSL_CA}; leaving it unchanged." >&2
        fi
    else
        echo "[entrypoint] WARNING: MYSQL_ATTR_SSL_CA is set but ${MYSQL_ATTR_SSL_CA} does not exist; connecting without a CA." >&2
        unset MYSQL_ATTR_SSL_CA
    fi
else
    echo "[entrypoint] WARNING: MYSQL_ATTR_SSL_CA is not set; Aiven requires TLS, so connections may be refused." >&2
fi

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

# Schema must exist before the app starts. Managed MySQL can be briefly
# unreachable while it wakes up, so retry instead of failing the deploy.
attempt=1
while true; do
    if php artisan migrate --force; then
        break
    fi
    if [ "$attempt" -ge 8 ]; then
        echo "[entrypoint] ERROR: migrations failed after ${attempt} attempts." >&2
        echo "[entrypoint] Check DB_HOST, DB_PORT, DB_PASSWORD and that the Aiven service is powered on." >&2
        exit 1
    fi
    echo "[entrypoint] Migration attempt ${attempt} failed; retrying in 10s..." >&2
    attempt=$((attempt + 1))
    sleep 10
done

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

# Root could reach MySQL while the web user cannot (file permissions, CA paths).
# Prove the web user's connection here instead of discovering it as a 500 later.
if su -s /bin/sh www-data -c 'php artisan migrate:status --no-ansi' >/tmp/dbprobe.log 2>&1; then
    echo "[entrypoint] DB check as www-data: OK"
else
    echo "[entrypoint] ERROR: MySQL is unreachable as www-data, so every page will return 500:" >&2
    head -n 25 /tmp/dbprobe.log >&2
fi

# Optional first-deploy seeding (set RUN_SEED=true once, then false).
if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force || true
    php artisan db:seed --class=ClothingStoreSeeder --force || true
    php artisan db:seed --class=BannerAndSettingsSeeder --force || true
fi

exec supervisord -c /etc/supervisord.conf
