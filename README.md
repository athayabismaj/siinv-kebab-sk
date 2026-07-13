<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK Logo" width="120" />

  # Sistem Inventory Kebab SK (SIINV)

  **Sistem Manajemen Inventaris & Operasional Point of Sales Berbasis Cloud**

  [![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
  [![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)

  <br />

  <a href="README.md"><img src="https://img.shields.io/badge/-🇮🇩%20Bahasa%20Indonesia-E11D48?style=for-the-badge" alt="Bahasa Indonesia" /></a>
  &nbsp;&nbsp;
  <a href="README-en.md"><img src="https://img.shields.io/badge/-🌍%20English-1E40AF?style=for-the-badge" alt="English" /></a>
</div>

---

**Sistem Inventory Kebab SK (SIINV)** adalah platform manajemen terpadu yang dirancang khusus untuk memonitor operasional, rantai pasok (stok), dan pencatatan transaksi kasir Kebab SK. Sistem ini menggunakan arsitektur modern (SaaS-like) yang memisahkan panel manajemen berbasis web dengan antarmuka kasir (*Point of Sales*) berbasis aplikasi mobile.

### ✨ Fitur Utama

#### 👑 Owner (Pemilik Bisnis)
Panel analitik tingkat tinggi untuk memantau performa bisnis di seluruh cabang.
*   **Analitik & Performa:** Dashboard finansial terpusat, Laporan Penjualan (Harian & Bulanan), dan Analisis Menu (Kontribusi & Tren).
*   **Keuangan & Audit:** Pencatatan Tutup Buku (*Closing Book*) otomatis spesifik per cabang.
*   **Manajemen SDM:** Pengelolaan Hak Akses (*Role-Based Access Control*), Pembuatan Akun Kasir/Admin, dan Arsip Karyawan.
*   **Monitoring Cabang:** Pemantauan stok *read-only* dari seluruh cabang yang terdaftar.

#### 💼 Admin (Manajer Operasional)
Panel teknis untuk mengelola rantai pasok harian dan administrasi produk.
*   **Manajemen Inventaris:** Katalog Bahan Baku, Kategori Bahan, Penyesuaian Stok (*Adjustment*), dan Histori Restok.
*   **Manajemen Produk:** Katalog Menu Utama, Kategori Menu, dan Standardisasi Resep (BoM - *Bill of Materials*).
*   **Operasional Harian:** Audit Stok Harian, Laporan Pemakaian, dan Pencatatan Pengeluaran Operasional.
*   **Arsip Terpadu:** Sistem pemulihan (*restore*) untuk data bahan baku dan menu yang dinonaktifkan.

#### 📱 API Kasir (Aplikasi Mobile)
Endpoint RESTful yang cepat dan aman untuk digunakan oleh aplikasi mobile kasir (Android/Kotlin).
*   **Autentikasi Aman:** Sistem Token (Bearer), Reset Password via OTP.
*   **Transaksi Real-time:** Pengambilan Menu, Metode Pembayaran, dan Pemrosesan *Checkout* (Terintegrasi dengan pemotongan stok otomatis berdasarkan resep).
*   **Riwayat Shift:** Ringkasan pendapatan harian dan histori transaksi spesifik milik kasir yang bertugas.

### 📸 Tangkapan Layar (Screenshots)
> *Tambahkan tangkapan layar aplikasi Anda di sini (misalnya Dashboard, UI Kasir, Laporan).*

### 🚀 Panduan Instalasi Lokal

#### Prasyarat Sistem
*   PHP >= 8.2
*   Composer >= 2.0
*   Node.js >= 18.0 & NPM
*   PostgreSQL

#### Langkah-langkah
1. **Kloning Repositori:**
   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```
2. **Instalasi Dependensi:**
   ```bash
   composer install
   npm install
   ```
3. **Konfigurasi Lingkungan:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Ubah pengaturan kredensial basis data di berkas `.env` (pastikan DB_CONNECTION=pgsql).*
4. **Migrasi & Seeding Data:**
   *(Seeder akan membuat peran awal, akun administrator, dan metode pembayaran).*
   ```bash
   php artisan migrate --seed
   ```
5. **Menjalankan Aplikasi:**
   Buka dua terminal secara bersamaan:
   *   Terminal 1: `npm run dev` (Compiler frontend Vite)
   *   Terminal 2: `php artisan serve` (Server backend Laravel)
   
   Aplikasi kini dapat diakses melalui peramban di `http://127.0.0.1:8000`.

### 📖 Dokumentasi API (Ringkasan)
Base URL: `/api` (Membutuhkan `Accept: application/json`)

| Modul | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| **Auth** | `POST /auth/login` | Mendapatkan kredensial *Bearer Token*. |
| **Auth** | `GET /auth/me` | Melihat profil kasir yang sedang aktif. |
| **Data** | `GET /menus` | Mengambil katalog produk yang tersedia. |
| **Sales** | `POST /transactions` | Mengirim data transaksi (Checkout). |
| **Sales** | `GET /revenue/summary`| Mendapatkan ringkasan pendapatan harian. |

### ⚖️ Lisensi
Proyek ini bersifat eksklusif (*proprietary*) dan rahasia. Penyalinan berkas tanpa izin yang sah dilarang keras.

---

<div align="center">
  <p>Developed with ❤️ for <b>Kebab SK</b>. &copy; 2026</p>
</div>
