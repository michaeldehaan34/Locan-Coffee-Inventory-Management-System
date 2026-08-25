# Deployment LOTRA ke InfinityFree

Deployment ini tidak mengubah controller, model, route, migration, UI, atau alur Daily Clean.

## Struktur upload

Upload seluruh staging package ke `htdocs/`. Biarkan `public/index.php` dan `public/.htaccess` tetap pada tempatnya. File `.htaccess` root yang disertakan meneruskan request ke `public/`, sehingga domain tidak perlu mengarah langsung ke subfolder `public`.

Ikuti panduan [INFINITYFREE_STAGING_PACKAGE.md](INFINITYFREE_STAGING_PACKAGE.md) untuk membuat paket bersih.

## Konfigurasi produksi

Salin `.env.infinityfree.example` menjadi `.env` di staging package, lalu isi nilai dari akun target:

```dotenv
APP_KEY=base64:your-production-app-key
APP_URL=https://your-domain.infinityfreeapp.com
DB_HOST=your-mysql-host
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Jangan meng-upload `.env` lokal atau membagikan `APP_KEY` maupun password database.

## Database dan storage

Import SQL yang telah disetujui melalui phpMyAdmin, lalu cocokkan seluruh `DB_*` dengan panel InfinityFree. Jangan menjalankan migration produksi tanpa persetujuan terpisah.

InfinityFree tidak mendukung `storage:link`. Root `.htaccess` memetakan URL `/storage/...` ke `storage/app/public/...`, tanpa symlink dan tanpa perubahan kode. PHP tetap harus dapat menulis `storage/app/public`, `storage/framework`, dan `storage/logs`.

Setelah upload, ikuti [INFINITYFREE_DEPLOYMENT_CHECKLIST.md](INFINITYFREE_DEPLOYMENT_CHECKLIST.md), terutama uji upload, preview, detail, dan delete Daily Clean.
