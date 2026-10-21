#!/usr/bin/env bash
# Instalasi SCM PT Karya Bangun Sejahtera di atas Laravel 11/12 baru.
# Prasyarat: PHP >= 8.2 (ext: pdo_sqlite/pdo_mysql, mbstring, xml, curl, zip), Composer.
# Pemakaian:  bash install.sh [nama-folder-proyek]   (default: scm)
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET="${1:-scm}"

[ -e "$TARGET" ] && { echo "Folder '$TARGET' sudah ada. Pilih nama lain."; exit 1; }

echo ">> Membuat proyek Laravel '$TARGET' ..."
composer create-project laravel/laravel "$TARGET" --no-interaction --prefer-dist

echo ">> Menyalin kode SCM ..."
for d in app config database lang resources routes; do
  mkdir -p "$TARGET/$d"
  cp -R "$HERE/$d/." "$TARGET/$d/"
done

cd "$TARGET"
# Hapus migrasi/seeder/factory/view bawaan yang digantikan overlay.
rm -f resources/views/welcome.blade.php

# Locale Indonesia & zona waktu WIB
sed -i "s/^APP_LOCALE=.*/APP_LOCALE=id/; s/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=id/; s/^APP_FAKER_LOCALE=.*/APP_FAKER_LOCALE=id_ID/" .env || true
sed -i "s/'timezone' => 'UTC'/'timezone' => 'Asia\/Jakarta'/" config/app.php

php artisan key:generate --force
touch database/database.sqlite
php artisan migrate:fresh --seed --force

echo
echo "Selesai. Jalankan:  cd $TARGET && php artisan serve"
echo "Login: andi.pratama / password   (Admin)"
