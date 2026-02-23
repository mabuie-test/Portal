#!/usr/bin/env bash
set -euo pipefail
APP_DIR=/var/www/crash-portal
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader
php -r "echo 'Run migrations manually with DBA account'.PHP_EOL;"
php scripts/package.sh
