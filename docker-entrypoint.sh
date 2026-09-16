#!/bin/sh
set -eu

# O volume do Railway é montado na inicialização, depois do build.
mkdir -p /var/www/html/data
chown www-data:www-data /var/www/html/data
chmod 700 /var/www/html/data
exec docker-php-entrypoint "$@"
