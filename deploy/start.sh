#!/bin/sh
set -eu

php artisan atm:deployment-check
php artisan config:cache
php artisan migrate --force
php artisan atm:demo-provision
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
