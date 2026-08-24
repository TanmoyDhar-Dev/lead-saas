#!/usr/bin/env sh
set -eu

ROOT_DIR="$(CDPATH= cd -- "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

DIST_DIR="$ROOT_DIR/deploy/cpanel/dist"
STAGE_DIR="$DIST_DIR/saas-leadflow"
ZIP_PATH="$DIST_DIR/saas-leadflow-cpanel.zip"

echo "==> Installing PHP dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies..."
npm ci --ignore-scripts

echo "==> Building frontend assets..."
npm run build

rm -rf "$STAGE_DIR" "$ZIP_PATH"
mkdir -p "$STAGE_DIR"

echo "==> Staging files for upload..."
rsync -a \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.cursor' \
  --exclude='.codex' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='deploy' \
  --exclude='docker' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='public/hot' \
  --exclude='public/storage' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/app/private/*' \
  --exclude='storage/app/public/*' \
  "$ROOT_DIR/" "$STAGE_DIR/"

mkdir -p \
  "$STAGE_DIR/storage/app/public" \
  "$STAGE_DIR/storage/app/private" \
  "$STAGE_DIR/storage/framework/cache/data" \
  "$STAGE_DIR/storage/framework/sessions" \
  "$STAGE_DIR/storage/framework/views" \
  "$STAGE_DIR/storage/logs" \
  "$STAGE_DIR/bootstrap/cache"

cp "$ROOT_DIR/.env.cpanel.example" "$STAGE_DIR/.env.cpanel.example"
cp "$ROOT_DIR/.htaccess" "$STAGE_DIR/.htaccess"

if [ -d "$ROOT_DIR/storage/app/templates" ]; then
  mkdir -p "$STAGE_DIR/storage/app"
  cp -R "$ROOT_DIR/storage/app/templates" "$STAGE_DIR/storage/app/"
fi

echo "==> Creating zip: $ZIP_PATH"
(
  cd "$STAGE_DIR"
  zip -rq "$ZIP_PATH" .
)

echo ""
echo "Done."
echo "Upload this file to cPanel File Manager: $ZIP_PATH"
echo "Then follow: deploy/cpanel/README.md"
