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

# ---------------------------------------------------------------------------
# Jaga .env tetap sehat untuk produksi.
# - APP_KEY kosong => route yang memakai session/encryption akan 500.
# - APP_DEBUG=true => halaman error membocorkan isi .env (bahaya, server publik).
# ---------------------------------------------------------------------------
echo "=== ENV PRODUKSI (aman) ==="
if [ -f .env ]; then
  if grep -qE '^APP_KEY=base64:.+' .env; then
    echo "APP_KEY  : sudah ada"
  else
    echo "APP_KEY  : KOSONG -> membuat baru"
    php artisan key:generate --force
  fi

  if grep -qE '^APP_DEBUG=true' .env; then
    sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
    echo "APP_DEBUG: true -> false (produksi)"
  else
    echo "APP_DEBUG: aman"
  fi

  if grep -qE '^APP_ENV=local' .env; then
    sed -i 's/^APP_ENV=local/APP_ENV=production/' .env
    echo "APP_ENV  : local -> production"
  else
    echo "APP_ENV  : $(grep -E '^APP_ENV=' .env | cut -d= -f2- || echo '-')"
  fi
else
  echo "PERINGATAN: file .env TIDAK ADA — buat manual di server."
fi

echo "=== MIGRATE & CACHE ==="
php artisan migrate --force

echo "=== SEED AKUN (idempoten) ==="
php artisan db:seed --class=UserSeeder --force || echo "PERINGATAN: seeder akun gagal (dilewati)"
php artisan db:seed --class=CategorySeeder --force || echo "PERINGATAN: seeder kategori gagal (dilewati)"

php artisan route:clear && php artisan route:cache
php artisan config:clear && php artisan config:cache
php artisan view:clear
php artisan storage:link 2>/dev/null || true

echo "=== DEPLOY SELESAI ==="
