# Staging Package InfinityFree

Panduan ini membuat paket upload bersih tanpa mengubah kode aplikasi. Jalankan seluruh perintah build di komputer lokal, bukan di InfinityFree.

## 1. Siapkan artefak produksi

1. Dari root proyek, jalankan `composer install --no-dev --optimize-autoloader`.
2. Jalankan `npm ci` lalu `npm run build` bila build aset perlu dibuat ulang.
3. Salin `.env.infinityfree.example` menjadi `.env` **hanya di staging package**, lalu isi `APP_KEY`, `APP_URL`, dan semua `DB_*` dengan nilai akun target. Jangan gunakan atau menyalin `.env` lokal.
4. Pastikan `vendor/`, `public/build/`, `public/static/`, dan `public/favicon.ico` ada.

## 2. Buat folder staging kosong

Buat folder baru di luar root proyek, misalnya `lotra-infinityfree-upload`. Salin hanya item berikut ke sana:

- `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `vendor/`, dan `public/`
- `storage/` tanpa isi runtime, `artisan`, `composer.json`, `composer.lock`, dan `.htaccess` root
- `.env` produksi yang dibuat dari template

Jangan menyalin `.git/`, `.github/`, `.vscode/`, `.idea/`, `.fleet/`, `node_modules/`, `tests/`, `phpunit.xml`, skrip audit/development, atau file `.env` lokal/backup.

## 3. Bersihkan runtime dari staging package

Hapus isi runtime berikut dari **copy staging saja**; jangan menghapus foldernya:

- `bootstrap/cache/` (sisakan `.gitignore` bila ada)
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/framework/testing/`
- `storage/logs/`

Pastikan folder kosong berikut tetap ada dan writable setelah upload:

```text
storage/app/public/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
```

## 4. Struktur upload dan storage

Upload seluruh staging package ke `htdocs/`, sehingga `.htaccess` root berada di `htdocs/.htaccess` dan folder `public/` tetap berada di `htdocs/public/`. Jangan memindahkan atau mengubah `public/index.php` maupun `public/.htaccess`.

Root `.htaccess` meneruskan request aplikasi ke `public/` dan memetakan URL `/storage/...` ke `storage/app/public/...`. Ini menggantikan kebutuhan `php artisan storage:link` atau symlink. Berikan akses tulis PHP untuk seluruh `storage/`, terutama lima folder di atas. Jangan meng-upload `public/storage` dari mesin lokal.

## 5. Validasi sebelum ZIP/upload

- [ ] Tidak ada `.git`, `.github`, `.vscode`, `node_modules`, atau `tests` di staging package.
- [ ] Tidak ada `laravel.log`, file session, cache, view terkompilasi, atau cache bootstrap runtime.
- [ ] `vendor/`, aset build, favicon, `.htaccess` root, dan seluruh isi `public/` ada.
- [ ] Lima folder storage wajib ada, kosong kecuali file penjaga seperti `.gitignore`.
- [ ] `.env` staging berisi nilai produksi target dan tidak berasal dari `.env` lokal.
