#!/usr/bin/env bash
# Skrip deploy backend CMS Bumi — dijalankan di server cPanel via SSH.
# Dipanggil oleh .github/workflows/deploy.yml
set -euo pipefail

echo "=== ENV ==="
echo "PATH=$PATH"
echo "DEPLOY_PATH=${DEPLOY_PATH:-<kosong>}"
echo "HOME=$HOME"

# Pastikan PATH standar cPanel (non-interaktif SSH sering kosong)
export PATH="$HOME/bin:/usr/local/bin:/usr/bin:/bin:/opt/cpanel/composer/bin:$PATH"

echo "php      : $(command -v php || echo 'TIDAK ADA')"
php -v 2>/dev/null | head -1 || true

# Temukan composer (pakai path absolut bila tidak di PATH)
COMPOSER="$(command -v composer || true)"
if [ -z "$COMPOSER" ]; then
  for p in /opt/cpanel/composer/bin/composer /usr/local/bin/composer "$HOME/bin/composer" /usr/bin/composer; do
    if [ -x "$p" ]; then COMPOSER="$p"; break; fi
  done
fi
echo "composer : ${COMPOSER:-TIDAK ADA}"

cd "${DEPLOY_PATH:?DEPLOY_PATH belum diset}"
echo "lokasi   : $(pwd)"

echo "=== GIT PULL ==="
git pull origin main

echo "=== COMPOSER INSTALL ==="
if [ -n "$COMPOSER" ]; then
  "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
else
  echo "PERINGATAN: composer tidak ditemukan di PATH/lokasi umum — dilewati."
  echo "(jika vendor/ sudah ada, aplikasi tetap jalan)"
fi

echo "=== MIGRATE & CACHE ==="
php artisan migrate --force
php artisan route:clear && php artisan route:cache
php artisan config:clear && php artisan config:cache
php artisan storage:link 2>/dev/null || true

echo "=== DEPLOY SELESAI ==="
