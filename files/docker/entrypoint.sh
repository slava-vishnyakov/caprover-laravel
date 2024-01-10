#!/usr/bin/env bash

export NGINX_WEB_ROOT=${NGINX_WEB_ROOT:-'/app/public'}
export NGINX_PHP_FALLBACK=${NGINX_PHP_FALLBACK:-'/index.php'}
export NGINX_PHP_LOCATION=${NGINX_PHP_LOCATION:-'^/index\.php(/|$)'}
export NGINX_USER=${NGINX_USER:-'appuser'}
export NGINX_CONF=${NGINX_CONF:-'/etc/nginx/nginx.conf'}

export PHP_SOCK_FILE=${PHP_SOCK_FILE:-'/run/php.sock'}
export PHP_USER=${PHP_USER:-'appuser'}
export PHP_GROUP=${PHP_GROUP:-'appuser'}
export PHP_MODE=${PHP_MODE:-'0660'}
export PHP_FPM_CONF=${PHP_FPM_CONF:-'/etc/php/8.2/fpm/php-fpm.conf'}

export PORT=${PORT:-'80'}

envsubst '${PORT} ${NGINX_WEB_ROOT} ${NGINX_PHP_FALLBACK} ${NGINX_PHP_LOCATION} ${NGINX_USER} ${NGINX_CONF} ${PHP_SOCK_FILE} ${PHP_USER} ${PHP_GROUP} ${PHP_MODE} ${PHP_FPM_CONF}' < /tmp/nginx.conf.tpl > $NGINX_CONF
envsubst '${NGINX_WEB_ROOT} ${NGINX_PHP_FALLBACK} ${NGINX_PHP_LOCATION} ${NGINX_USER} ${NGINX_CONF} ${PHP_SOCK_FILE} ${PHP_USER} ${PHP_GROUP} ${PHP_MODE} ${PHP_FPM_CONF}' < /tmp/php-fpm.conf.tpl > $PHP_FPM_CONF

echo "Starting on port $PORT"

chown appuser:appuser -R /app/storage/

source .env

if [[ "${DOCKER_COMPOSE}" == "true" ]]; then
    export DATABASE_URL="postgres://webapp:secret@postgres:5432/webapp?sslmode=disable"
    export REDIS_URL="redis://@redis:6379/webapp?sslmode=disable"
fi

chmod +x /app/resources/docker/cron.sh
chmod +x /app/resources/docker/queue.sh

php artisan migrate --force --no-interaction # or migrate-with-lock

/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisor.conf
