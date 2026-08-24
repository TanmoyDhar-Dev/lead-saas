#!/usr/bin/env sh
# Run once on the cPanel server after uploading + creating .env
# Usage:  sh deploy/cpanel/post-deploy.sh
# Or from project root:  php artisan ... commands below

set -eu

cd "$(dirname "$0")/../.."

if [ ! -f .env ]; then
  echo "Missing .env. Copy .env.cpanel.example to .env and fill values first."
  exit 1
fi

php -v

php artisan key:generate --force --show >/tmp/leadflow_app_key.txt 2>/dev/null || true

# Only generate key if empty
if ! grep -q '^APP_KEY=base64:' .env; then
  KEY="$(php artisan key:generate --show --no-interaction)"
  # shellcheck disable=SC2016
  if command -v sed >/dev/null 2>&1; then
    sed -i.bak "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env || true
  fi
  echo "Set APP_KEY in .env to: $KEY (if not already set)"
fi

php artisan storage:link --force --no-interaction || true
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

echo ""
echo "Post-deploy complete."
echo "Add these cron jobs in cPanel → Cron Jobs (use your real PHP path and home path):"
echo "  * * * * * cd /home/USER/saas-leadflow && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1"
echo "  * * * * * cd /home/USER/saas-leadflow && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /dev/null 2>&1"
