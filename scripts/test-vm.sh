#!/usr/bin/env sh
set -eu

APP_DIR=${1:-/app}

docker run --rm -v "$APP_DIR:/app" -w /app node:24-alpine sh -lc 'npm ci && npm run build && npm test'
docker run --rm -v "$APP_DIR:/app" -w /app composer:2 install --no-interaction --prefer-dist
docker run --rm -v "$APP_DIR:/app" -w /app composer:2 composer test
docker run --rm -v "$APP_DIR:/app" -w /app nextcloud:34.0-apache sh -lc \
  'find appinfo lib tests -name "*.php" -exec php -l {} \;'
