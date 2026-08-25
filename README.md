# Locan — Inventory Management System

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-blue)](LICENSE)

**Locan** adalah sistem manajemen inventaris dan stok terintegrasi berbasis web yang dibangun menggunakan Laravel 12. Sistem ini dirancang untuk memfasilitasi operasional secara efisien dari hulu ke hilir, mencakup manajemen gudang (Warehouse), area Barista (Coffeeshop), dan Kitchen. Sistem ini menerapkan konsep *multi-role* dan pelacakan riwayat (history tracking) untuk menjaga akurasi data.

---

## ✨ Fitur & Role Pengguna

Sistem ini membagi akses berdasarkan 6 peran pengguna (role) yang saling berintegrasi:

### 👑 Manager / Manajemen
Memiliki akses pengawasan (monitoring) tingkat atas.
- **Dashboard Manajemen**: Monitoring performa dan metrik inventaris.
- **Data Pegawai**: Kelola data akun pegawai secara terpusat (Barista, Kitchen, dll).
- **Edit Profil**: Manajemen akun dan keamanan mandiri.

### 🏢 Admin Gudang (Warehouse)
Bertanggung jawab atas pusat kontrol stok bahan mentah.
- **Dashboard Gudang**: Ringkasan persediaan stok dan aktivitas distribusi di gudang.
- **Stok Masuk Gudang**: Pencatatan bahan/stok yang baru masuk dari *supplier*.
- **Kirim Stok**: Mencatat dan mendistribusikan pengiriman bahan ke area Coffee Shop atau Kitchen.
- **Master Bahan**: Mengelola data induk bahan baku (Tambah, Edit, Hapus, Kelompokkan, dan Toggle Aktif/Nonaktif).
- **Pengaturan Limit**: Mengatur ambang batas (*threshold*) peringatan stok tipis atau habis per bahan baku.

### ☕ Headbar
Supervisor untuk operasional area Coffee Shop.
- **Dashboard Coffee Shop**: Monitoring stok area bar/coffee shop.
- **Terima Stok**: Memproses/mengonfirmasi penerimaan stok yang dikirim oleh Admin Gudang.
- **Monitoring Barista**: Mengawasi riwayat kegiatan tim Barista, termasuk:
  - Riwayat Update Stok
  - Riwayat Daily Clean (kebersihan harian)
  - Riwayat penggunaan Token Listrik

### 🍵 Barista
Staf operasional harian di area Coffee Shop.
- **Dashboard Barista**: Informasi ringkas mengenai tugas harian.
- **Ambil Bahan Gudang**: Melakukan pencatatan pengambilan bahan mentah ke gudang.
- **Update Stok**: Memperbarui perhitungan fisik sisa stok aktual di bar pada setiap akhir shift.
- **Daily Clean**: Mengunggah laporan visual/foto kebersihan area kerja.
- **Token Listrik**: Melaporkan pemakaian token listrik mesin dan area bar.

### 🍳 Head Kitchen
Supervisor untuk operasional area Dapur/Kitchen.
- **Dashboard Kitchen**: Monitoring operasional dan indikator stok di area dapur.
- **Terima Stok & Ambil Bahan**: Mengelola arus bahan masuk dari gudang ke dapur.
- **Monitoring Kitchen**: Mengawasi laporan update stok, daily clean, dan token listrik oleh staf kitchen.
- **Pengaturan Limit Kitchen**: Menyetel batas aman/kritis stok khusus area dapur.

### 🧑‍🍳 Kitchen
Staf operasional harian di area Dapur.
- **Dashboard Kitchen (Staff)**: Informasi operasional mandiri.
- **Ambil Bahan**: Permintaan atau pencatatan pengeluaran bahan dari gudang.
- **Update Stok**: Sinkronisasi stok fisik area dapur.
- **Daily Clean & Token Listrik**: Laporan operasional harian dapur.

---

## 🛠️ Technology Stack

| Teknologi | Kegunaan |
|-----------|----------|
| [Laravel 12](https://laravel.com) | Framework PHP untuk pengembangan backend |
| [PHP](https://php.net) ^8.2 | Bahasa pemrograman utama |
| [MySQL](https://mysql.com) | Relational Database Management System |
| [Bootstrap 5](https://getbootstrap.com) | Framework CSS responsif |
| [Bootstrap Icons](https://icons.getbootstrap.com) | Ikon antarmuka web |
| [Blade Template](https://laravel.com/docs/blade) | Engine templating bawaan Laravel |
| [Vite](https://vitejs.dev) | Build tool untuk *asset* frontend modern |
| [SweetAlert2](https://sweetalert2.github.io) | Interaksi dialog & notifikasi frontend |
| [Composer](https://getcomposer.org) & [npm](https://npmjs.com) | Dependency managers |

---


## ⚙️ Konfigurasi Branding

Seluruh identitas aplikasi terpusat pada file `config/branding.php`. Anda dapat menyesuaikan tampilan tema secara dinamis:

| Key | Default | Keterangan |
|-----|---------|------------|
| `app_name` | `Locan` | Nama aplikasi yang tampil pada judul/tab halaman |
| `company_name` | `Locan` | Nama brand/perusahaan |
| `subtitle` | `Inventory Management System` | Subtitle aplikasi |
| `logo` | `static/images/logo_locan.png` | Path gambar logo utama |
| `primary_color` | `#1E3AFF` | Kode HEX warna utama tema |

Ubah nilai pada file konfigurasi tersebut untuk melakukan pelabelan ulang (rebranding) instan tanpa perlu mengubah *source code* HTML/CSS.

---

## 📂 Struktur Project (Highlight)

- **`app/Http/Controllers/`** : Berisi controller terpisah berdasarkan spesialisasi role (contoh: `GudangController`, `ManagerController`, `BaristaController`, `HeadKitchenController`).
- **`app/Models/`** : Representasi entitas database (`Bahan`, `StokMasuk`, `AmbilBahanGudang`, `GudangKirimStok`, `DailyClean`, dsb).
- **`routes/web.php`** : Definisi alur rute aplikasi yang dipisahkan secara tegas menggunakan *Middleware* otorisasi berbasis Role.
- **`resources/views/`** : Struktur template halaman (*Blade*) yang sudah dikelompokkan dalam direktori per-role.

---

## 🧑‍💻 Developer

Project ini dikembangkan sebagai solusi terpadu operasional bisnis oleh:

**Michael De Haan**  
Universitas Teknologi Yogyakarta  
Program Studi Sains Data

---

## 📄 Lisensi

Project ini dilisensikan di bawah **MIT License**. Silakan lihat file [LICENSE](LICENSE) untuk informasi lebih lanjut.
