# LOTRA — Coffee Management System

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-blue)](LICENSE)

**LOTRA** adalah sistem manajemen stok bahan baku coffeeshop berbasis web yang dibangun menggunakan Laravel 12. Sistem ini dirancang untuk membantu operasional coffeeshop dalam memonitor dan mengelola persediaan bahan baku secara efisien, akurat, dan real-time.

---

## ✨ Fitur

### 👑 Manager

Manager memiliki akses penuh ke seluruh fitur sistem:

| Fitur | Keterangan |
|-------|-----------|
| **Dashboard** | Ringkasan statistik stok (aman, tipis, habis), top barang sering habis, top barang hampir habis, dan aktivitas barista |
| **Data Barista** | Kelola data barista (Tambah, Lihat, Edit, Hapus) dengan validasi nomor telepon dan role |
| **Master Bahan** | Kelola data bahan baku (Tambah, Lihat, Edit, Hapus, Toggle Aktif/Nonaktif) dengan pengelompokan kategori & kelompok dinamis |
| **Pengaturan Limit Stok** | Atur batas stok *habis* dan *tipis* per bahan baku untuk monitoring stok otomatis |
| **Riwayat Stok Masuk** | Lihat, cari, filter (tanggal, shift, barista, barang), tambah, edit, hapus, dan ekspor ke Excel |
| **Riwayat Update Stok** | Lihat, cari, filter barang, detail, edit, hapus, dan ekspor ke Excel & PDF |
| **Riwayat Token Listrik** | Lihat, filter (tanggal, shift, barista), detail, hapus, hapus massal, dan ekspor ke Excel |
| **Riwayat Daily Clean** | Lihat, filter (tanggal, shift, barista), detail foto, hapus, hapus massal, dan ekspor ke Excel |
| **Forecast Kebutuhan Bahan** | Prediksi kebutuhan bahan baku mingguan berdasarkan riwayat pemakaian (dikelompokkan per kategori & kelompok) |
| **Estimasi Pembelian** | Estimasi jumlah pembelian yang diperlukan berdasarkan forecast kebutuhan |
| **Laporan** | Laporan mingguan lengkap dengan ringkasan statistik, top barang, aktivitas barista, forecast, dan ekspor PDF |
| **Edit Profil / Akun Saya** | Ubah nama, username, dan password akun Manager |

### 👤 Barista

Barista memiliki akses terbatas untuk pencatatan operasional harian:

| Fitur | Keterangan |
|-------|-----------|
| **Login** | Masuk menggunakan akun Barista yang telah didaftarkan oleh Manager |
| **Dashboard** | Ringkasan aktivitas pribadi (update stok, daily clean, token listrik hari ini & minggu ini) |
| **Input Stok Masuk** | Catat stok bahan baku yang baru datang (minimal satu item wajib diisi) |
| **Update Stok** | Catat stok terkini untuk seluruh bahan baku (semua item wajib diisi) |
| **Input Token Listrik** | Catat pemakaian token listrik (R17, R18, Mesin) per shift |
| **Daily Clean** | Upload foto dokumentasi kebersihan harian (minimal 4 foto) |
| **Logout** | Keluar dari sistem |

---

## 🛠️ Technology Stack

| Teknologi | Kegunaan |
|-----------|----------|
| [Laravel 12](https://laravel.com) | Framework PHP untuk pengembangan aplikasi web |
| [PHP](https://php.net) ^8.2 | Bahasa pemrograman backend |
| [MySQL](https://mysql.com) | Database relasional |
| [Bootstrap 5](https://getbootstrap.com) | Framework CSS untuk antarmuka responsif |
| [Bootstrap Icons](https://icons.getbootstrap.com) | Ikon antarmuka |
| [Blade Template](https://laravel.com/docs/blade) | Engine templating Laravel |
| [HTML5](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5) | Struktur halaman web |
| [CSS3](https://developer.mozilla.org/en-US/docs/Web/CSS) | Styling halaman web |
| [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript) | Interaktivitas frontend |
| [SweetAlert2](https://sweetalert2.github.io) | Notifikasi dan dialog interaktif |
| [Vite](https://vitejs.dev) | Build tool untuk asset frontend |
| [Sass](https://sass-lang.com) | Preprocessor CSS |
| [Composer](https://getcomposer.org) | Dependency manager PHP |
| [DomPDF](https://github.com/dompdf/dompdf) | Generate dokumen PDF |
| [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io) | Generate file Excel (.xlsx) |

---

## 🚀 Installasi

Ikuti langkah-langkah berikut untuk menjalankan project secara lokal:

### Prasyarat

- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Node.js & npm (untuk kompilasi asset)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/username/lotra.git
cd lotra

# 2. Install dependency PHP
composer install

# 3. Copy file environment
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Konfigurasi database di file .env
#    DB_DATABASE=lotra
#    DB_USERNAME=root
#    DB_PASSWORD=

# 6. Jalankan migrasi dan seeder
php artisan migrate --seed

# 7. Install dan kompilasi asset frontend
npm install
npm run build

# 8. Jalankan development server
php artisan serve
```

Akses aplikasi di `http://localhost:8000`.

> **Catatan:** Login menggunakan akun yang telah di-seed. Password default untuk Barista adalah 6 digit terakhir nomor telepon. Untuk Manager, gunakan password yang telah ditentukan saat seeding.

---

## ⚙️ Konfigurasi

Seluruh branding aplikasi terpusat pada file `config/branding.php`:

| Key | Default | Keterangan |
|-----|---------|------------|
| `app_name` | `LOTRA` | Nama aplikasi yang tampil pada judul halaman |
| `company_name` | `LOTRA` | Nama brand/perusahaan |
| `subtitle` | `Coffee Management System` | Subtitle yang tampil pada sidebar, login, dan footer |
| `logo` | `static/images/lotra_logo.png` | Path logo utama |
| `logo_white` | `static/images/lotra_logo.png` | Path logo versi putih (loader) |
| `favicon` | `favicon.ico` | Path favicon |
| `primary_color` | `#1E3AFF` | Warna utama tema |
| `secondary_color` | `#FFFFFF` | Warna sekunder tema |
| `accent_color` | `#5D79FF` | Warna aksen tema |
| `background_color` | `#F6F8FF` | Warna latar tema |
| `hover_color` | `#284BFF` | Warna hover tema |

Ubah nilai pada file tersebut untuk mengganti nama aplikasi, logo, atau warna tema tanpa menyentuh kode tampilan.

---

## 👥 User Roles

Sistem memiliki dua peran pengguna:

| Role | Hak Akses |
|------|-----------|
| **Manager** | Akses penuh — dashboard, master data, riwayat, forecast, laporan, pengaturan limit, dan kelola akun barista |
| **Barista** | Akses terbatas — login, input stok masuk, update stok, input token listrik, dan daily clean |

---

## 📂 Struktur Project

```
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controller untuk Manager, Barista, Auth, dll
│   │   ├── Middleware/         # Middleware (SessionAuth, RoleMiddleware)
│   │   └── Requests/          # Form request validation
│   ├── Models/                # Model Eloquent (Bahan, Barista, StokMasuk, dll)
│   ├── Providers/             # Service Providers
│   └── Services/              # Business logic (StockAnalytics, ExportService)
├── resources/
│   └── views/                 # Blade template views
│       ├── auth/              # Halaman login
│       ├── barista/           # Halaman khusus barista
│       ├── layouts/           # Layout utama
│       ├── manager/           # Halaman khusus manager
│       └── partials/          # Komponen parsial (sidebar, footer)
├── routes/
│   ├── web.php                # Route utama
│   └── auth.php               # Route autentikasi
├── public/                    # Asset publik (CSS, JS, images)
├── database/
│   ├── migrations/            # Migration database
│   └── seeders/               # Data awal (seeder)
├── storage/                   # File upload, cache, logs
├── config/                    # Konfigurasi aplikasi
├── composer.json              # Dependency PHP
└── package.json               # Dependency frontend
```

---

## 📸 Screenshot

> Dokumentasi visual akan segera ditambahkan.

### Halaman Manager

| Dashboard | Data Barista |
|-----------|-------------|
| *Placeholder: screenshot dashboard* | *Placeholder: screenshot data barista* |

| Master Bahan | Pengaturan Limit |
|-------------|-----------------|
| *Placeholder: screenshot master bahan* | *Placeholder: screenshot pengaturan limit* |

| Forecast | Laporan |
|----------|---------|
| *Placeholder: screenshot forecast* | *Placeholder: screenshot laporan* |

| Riwayat Stok Masuk | Riwayat Update Stok |
|--------------------|-------------------|
| *Placeholder: screenshot riwayat stok masuk* | *Placeholder: screenshot riwayat update stok* |

| Riwayat Token Listrik | Riwayat Daily Clean |
|-----------------------|--------------------|
| *Placeholder: screenshot riwayat token listrik* | *Placeholder: screenshot riwayat daily clean* |

### Halaman Barista

| Login | Dashboard Barista |
|-------|------------------|
| *Placeholder: screenshot login* | *Placeholder: screenshot dashboard barista* |

| Input Stok Masuk | Update Stok |
|-----------------|-------------|
| *Placeholder: screenshot input stok masuk* | *Placeholder: screenshot update stok* |

| Daily Clean | Token Listrik |
|-------------|---------------|
| *Placeholder: screenshot daily clean* | *Placeholder: screenshot token listrik* |

---

## 🧑‍💻 Developer

Project ini dikembangkan oleh:

**Michael De Haan**  
Universitas Teknologi Yogyakarta  
Program Studi Sains Data

---

## 📄 Lisensi

Project ini dilisensikan di bawah **MIT License**. Silakan lihat file [LICENSE](LICENSE) untuk informasi lebih lanjut.

---

## 📝 Catatan Pengembangan

Sistem **LOTRA** dikembangkan sebagai solusi manajemen operasional coffeeshop berbasis web menggunakan framework **Laravel 12**. Fokus utama pengembangan adalah:

- **Kemudahan penggunaan** — antarmuka yang intuitif dengan pembagian peran yang jelas.
- **Efisiensi operasional** — pencatatan digital yang menggantikan pencatatan manual.
- **Monitoring stok real-time** — dashboard dan notifikasi limit stok untuk pengambilan keputusan yang cepat.
- **Akurasi data** — validasi input otomatis dan riwayat pencatatan yang lengkap.
- **Portabilitas** — aplikasi web yang dapat diakses dari berbagai perangkat.

Dibangun dengan arsitektur MVC Laravel, sistem ini mengimplementasikan konsep **Single Source of Truth** melalui service `StockAnalytics` untuk seluruh perhitungan analitik, serta **export service** yang menghasilkan laporan dalam format PDF dan Excel sesuai standar pelaporan profesional.

