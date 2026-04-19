#!/bin/zsh

set -e

# =========================================================
# CONFIG
# =========================================================
PROJECT_ROOT="/Users/jameelcampo/dev/your-project-folder"
DEPLOY_ROOT="$PROJECT_ROOT/deploy-package"
APP_PACKAGE="$DEPLOY_ROOT/app"
PUBLIC_PACKAGE="$DEPLOY_ROOT/public_html"

# =========================================================
# START
# =========================================================
echo "----------------------------------------"
echo "Creating fresh deploy package..."
echo "----------------------------------------"

cd "$PROJECT_ROOT"

# Clean old package
rm -rf "$DEPLOY_ROOT"
mkdir -p "$APP_PACKAGE"
mkdir -p "$PUBLIC_PACKAGE"

# =========================================================
# BUILD / PREP LOCALLY
# =========================================================
echo "Clearing Laravel caches..."
php artisan optimize:clear

echo "Installing/updating composer dependencies for production..."
composer install --no-dev --optimize-autoloader

echo "Installing frontend dependencies..."
npm install

echo "Building frontend assets..."
npm run build

# Rebuild Laravel caches for production package
echo "Caching Laravel config/routes/views/events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

# =========================================================
# COPY LARAVEL APP FILES
# =========================================================
echo "Copying Laravel application files..."

rsync -av \
  --exclude=".git" \
  --exclude=".github" \
  --exclude=".DS_Store" \
  --exclude=".env" \
  --exclude=".env.*" \
  --exclude="node_modules" \
  --exclude="tests" \
  --exclude="deploy-package" \
  --exclude="storage/logs/*" \
  --exclude="storage/framework/cache/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  --exclude="public_html" \
  --exclude="vendor/bin/.phpunit" \
  "$PROJECT_ROOT/" "$APP_PACKAGE/"

# =========================================================
# MOVE PUBLIC FILES INTO SHARED-HOSTING PUBLIC FOLDER
# =========================================================
echo "Separating public files for shared hosting..."

rm -rf "$PUBLIC_PACKAGE"
mkdir -p "$PUBLIC_PACKAGE"

rsync -av "$PROJECT_ROOT/public/" "$PUBLIC_PACKAGE/"

# Remove public folder from app package since it will live in public_html
rm -rf "$APP_PACKAGE/public"

# =========================================================
# OPTIONAL: ADD DEPLOY NOTES
# =========================================================
cat > "$DEPLOY_ROOT/DEPLOY-README.txt" <<'EOF'
UPLOAD INSTRUCTIONS

1. Upload everything inside "app/" to the Laravel app location on the server
   (usually outside public_html).

2. Upload everything inside "public_html/" to the server's public_html folder.

3. Make sure public/index.php points to the correct app location.

4. Make sure these writable folders exist and are writable:
   - storage/
   - bootstrap/cache/

5. Make sure the server .env is correct.

6. If vendor is included, do NOT run composer on server.
   If vendor is not included, composer must be run on server.
EOF

echo "----------------------------------------"
echo "Deploy package created successfully:"
echo "$DEPLOY_ROOT"
echo "----------------------------------------"



# Run this code to impliment
# chmod +x deploy-package.sh
# ./deploy-package.sh
