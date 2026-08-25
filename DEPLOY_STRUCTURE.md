# Struktur Deployment InfinityFree

Upload seluruh staging package ke `htdocs/`:

```text
htdocs/
  .htaccess
  app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/
  public/
    index.php
    .htaccess
    build/
    static/
    favicon.ico
  .env
  artisan
  composer.json
  composer.lock
```

`public/index.php` tetap memakai path Laravel standar `../vendor` dan `../bootstrap`; file ini tidak diubah. Root `.htaccess` meneruskan request ke `public/`. Untuk Daily Clean, aturan yang sama memetakan URL `/storage/...` ke `storage/app/public/...`, sehingga tidak perlu dan tidak boleh memakai `storage:link` atau meng-upload symlink `public/storage`.
