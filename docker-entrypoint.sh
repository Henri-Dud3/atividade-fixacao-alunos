#!/bin/sh
set -eu

# O volume do Railway é montado na inicialização, depois do build.
mkdir -p /var/www/html/data
chown www-data:www-data /var/www/html/data
chmod 700 /var/www/html/data
# Mantém um único MPM também no filesystem final do contêiner.
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
apache2ctl -t
exec docker-php-entrypoint "$@"
