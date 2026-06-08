#!/usr/bin/env bash
set -euo pipefail

mkdir -p /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage
chmod -R u+rwX,g+rwX /var/www/html/storage

php /var/www/html/bin/migrate.php
php /var/www/html/bin/sync-household-users.php

chown -R www-data:www-data /var/www/html/storage
chmod -R u+rwX,g+rwX /var/www/html/storage

if [[ "${1:-}" == "serve" ]]; then
    php-fpm -D
    exec nginx -g "daemon off;"
fi

exec "$@"
