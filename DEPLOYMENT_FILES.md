# File dan Folder Deployment

## Wajib tersedia pada paket produksi

- `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, dan `vendor/`
- `public/` beserta `index.php`, `.htaccess`, `build/`, `static/`, dan `favicon.ico`
- `.htaccess` root, `artisan`, `composer.json`, `composer.lock`, dan `.env` produksi
- Folder writable: `storage/app/public`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/views`, dan `storage/logs`

## Jangan di-upload

- `.git/`, `.github/`, dan folder editor seperti `.vscode/`, `.idea/`, `.fleet/`
- `node_modules/`, `tests/`, `phpunit.xml`, cache PHPUnit, serta skrip audit/development
- `.env` lokal, `.env.backup`, `.env.production`, atau file berisi kredensial lain
- `public/storage` dari komputer lokal karena berupa junction/symlink
- Isi runtime `bootstrap/cache/`, `storage/framework/cache/`, `storage/framework/sessions/`, `storage/framework/testing/`, `storage/framework/views/`, dan `storage/logs/`; foldernya wajib tetap ada

Lihat [INFINITYFREE_STAGING_PACKAGE.md](INFINITYFREE_STAGING_PACKAGE.md) untuk langkah staging yang aman.
