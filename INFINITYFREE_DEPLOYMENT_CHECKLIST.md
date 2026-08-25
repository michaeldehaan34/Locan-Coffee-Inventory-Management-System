# InfinityFree Deployment Checklist

## Sebelum upload

- [ ] Selesaikan [INFINITYFREE_STAGING_PACKAGE.md](INFINITYFREE_STAGING_PACKAGE.md).
- [ ] Siapkan backup database dan salinan file upload bila deployment menggantikan instalasi aktif.
- [ ] Pastikan `.env` staging memakai `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS aktual, dan semua `DB_*` dari panel.

## Upload

- [ ] Upload seluruh staging package ke `htdocs/`, termasuk `.htaccess` root.
- [ ] Pastikan `vendor/`, `public/build/`, `public/static/`, dan `public/favicon.ico` ikut ter-upload.
- [ ] Pastikan folder runtime storage ada dan dapat ditulis PHP.
- [ ] Pastikan `.git/`, `.github/`, folder editor, `node_modules/`, tests, runtime cache/session/log, dan junction `public/storage` tidak ter-upload.

## Verifikasi setelah deploy

- [ ] Login, favicon, CSS, JavaScript, dan aset build termuat tanpa 404.
- [ ] Login Manager dan Barista berhasil serta dashboard tampil.
- [ ] Upload minimal empat foto Daily Clean valid (JPG/PNG/WebP, maksimal 2 MB per file).
- [ ] Submit tidak menghasilkan HTTP 500.
- [ ] Sebagai Manager, buka riwayat, preview, dan detail foto.
- [ ] Pastikan URL `/storage/...` menampilkan foto melalui mapping `.htaccess` dan bukan 404/403/500.
- [ ] Uji delete satu Daily Clean, lalu bulk delete, dan pastikan record serta file terkait hilang.
- [ ] Periksa `storage/logs/laravel.log` bila terjadi HTTP 500; pertahankan `APP_DEBUG=false`.
