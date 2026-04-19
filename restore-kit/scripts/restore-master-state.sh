#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

SQL_FILE="restore-kit/baseline/master-baseline.sql"
MEDIA_ZIP="restore-kit/baseline/media-baseline.zip"

echo "==> Starting master restore..."

if [ ! -f "$SQL_FILE" ]; then
  echo "ERROR: Missing SQL baseline file: $SQL_FILE"
  exit 1
fi

if [ ! -f "$MEDIA_ZIP" ]; then
  echo "ERROR: Missing media baseline zip: $MEDIA_ZIP"
  exit 1
fi

if [ ! -f "./vendor/bin/sail" ]; then
  echo "ERROR: Sail not found at ./vendor/bin/sail"
  exit 1
fi

echo "==> Reading database settings from .env..."

APP_ENV_FILE=".env"

if [ ! -f "$APP_ENV_FILE" ]; then
  echo "ERROR: .env file not found."
  exit 1
fi

DB_DATABASE="$(grep '^DB_DATABASE=' "$APP_ENV_FILE" | cut -d '=' -f2- | tr -d '"')"
DB_USERNAME="$(grep '^DB_USERNAME=' "$APP_ENV_FILE" | cut -d '=' -f2- | tr -d '"')"
DB_PASSWORD="$(grep '^DB_PASSWORD=' "$APP_ENV_FILE" | cut -d '=' -f2- | tr -d '"')"

if [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ]; then
  echo "ERROR: Could not read DB_DATABASE or DB_USERNAME from .env"
  exit 1
fi

echo "==> Running migrate:fresh..."
./vendor/bin/sail artisan migrate:fresh

echo "==> Seeding master admin..."
./vendor/bin/sail artisan db:seed --class=MasterAdminSeeder

echo "==> Importing baseline SQL into database: $DB_DATABASE"
./vendor/bin/sail mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$SQL_FILE"

echo "==> Restoring media archive..."
TMP_DIR="$(mktemp -d)"
unzip -oq "$MEDIA_ZIP" -d "$TMP_DIR"

if [ -d "$TMP_DIR/storage/app/public" ]; then
  mkdir -p storage/app
  rm -rf storage/app/public
  cp -R "$TMP_DIR/storage/app/public" storage/app/public
fi

if [ -d "$TMP_DIR/public/images" ]; then
  mkdir -p public
  rm -rf public/images
  cp -R "$TMP_DIR/public/images" public/images
fi

rm -rf "$TMP_DIR"

echo "==> Re-linking storage..."
./vendor/bin/sail artisan storage:link || true

echo "==> Clearing caches..."
./vendor/bin/sail artisan optimize:clear

echo "==> Master restore complete."
