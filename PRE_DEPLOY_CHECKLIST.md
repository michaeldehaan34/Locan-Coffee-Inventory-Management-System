# Pre-Deploy Checklist

- [ ] Tidak ada perubahan business logic, UI, database, migration, route, authentication, atau authorization dalam paket deployment.
- [ ] `composer install --no-dev --optimize-autoloader` berhasil dijalankan secara lokal dan folder `vendor/` tersedia.
- [ ] `npm run build` berhasil; `public/build/manifest.json` serta asset CSS/JS ada.
- [ ] `public/static/` berisi CSS, JavaScript, logo, dan gambar latar.
- [ ] `public/favicon.ico` tersedia dan sesuai konfigurasi `branding.favicon`.
- [ ] `.env.infinityfree.example` telah disalin sebagai referensi; nilai `APP_KEY` produksi tersedia dan tidak dicommit.
- [ ] Kredensial database InfinityFree, hostname, nama database, dan user database telah disiapkan.
- [ ] Export SQL database sumber telah dibuat dan divalidasi.
- [ ] Akun target telah diverifikasi untuk versi PHP minimal 8.2 dan ekstensi yang diperlukan aplikasi.
- [ ] Kemampuan placement folder di luar `htdocs` telah dikonfirmasi sebelum memilih Opsi B.
- [ ] Rencana akses `/storage/...` telah disetujui dan akan diuji menggunakan foto baru setelah deploy; jangan gunakan symlink.
- [ ] Folder runtime `storage/` siap ditulis oleh PHP.
