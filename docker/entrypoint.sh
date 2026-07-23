#!/bin/sh
set -e

APP_DIR="${APP_DIR:-/var/www/html/backup}"
PHP_TEMP_DIR="${PHP_TEMP_DIR:-/tmp/php}"

echo "[entrypoint] APP_DIR=${APP_DIR}"
echo "[entrypoint] PHP_TEMP_DIR=${PHP_TEMP_DIR}"

mkdir -p "${PHP_TEMP_DIR}"
chown -R www-data:www-data "${PHP_TEMP_DIR}" || true
chmod 1777 "${PHP_TEMP_DIR}" || true

if [ -d "${APP_DIR}" ]; then
  mkdir -p \
    "${APP_DIR}/storage/framework/cache/data" \
    "${APP_DIR}/storage/framework/sessions" \
    "${APP_DIR}/storage/framework/views" \
    "${APP_DIR}/storage/framework/testing" \
    "${APP_DIR}/storage/logs" \
    "${APP_DIR}/bootstrap/cache"

  chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache" || true

  chmod -R 775 \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache" || true

  if [ -f "${APP_DIR}/artisan" ]; then
    su -s /bin/sh www-data -c "cd ${APP_DIR} && php artisan optimize:clear" || true
  fi
else
  echo "[entrypoint] WARNING: ${APP_DIR} does not exist"
fi

exec "$@"