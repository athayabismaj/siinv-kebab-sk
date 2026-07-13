<div align="center">
  <img src="public/favicon.svg" alt="Logo Kebab SK" width="120" />

  # Sistem Inventory Kebab SK (SIINV)

  **Sistem Manajemen Inventaris & Operasional Point of Sales Berbasis Cloud**

  [![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
  [![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
</div>

<br>

**Sistem Inventory Kebab SK (SIINV)** adalah platform manajemen terpadu yang dirancang khusus untuk memonitor operasional, rantai pasok (stok), dan pencatatan transaksi kasir Kebab SK. Sistem ini menggunakan arsitektur modern (SaaS-like) yang memisahkan panel manajemen berbasis web dengan antarmuka kasir (*Point of Sales*) berbasis aplikasi mobile.

---

## ✨ Fitur Utama

### 👑 Owner (Pemilik Bisnis)
Panel analitik tingkat tinggi untuk memantau performa bisnis di seluruh cabang.
*   **Analitik & Performa:** Dashboard finansial terpusat, Laporan Penjualan (Harian & Bulanan), dan Analisis Menu (Kontribusi & Tren).
*   **Keuangan & Audit:** Pencatatan Tutup Buku (*Closing Book*) spesifik per cabang.
*   **Manajemen SDM:** Pengelolaan Hak Akses (*Role-Based Access Control*), Pembuatan Akun Kasir/Admin, dan Arsip Karyawan.
*   **Monitoring Cabang:** Pemantauan stok *read-only* dari seluruh cabang yang terdaftar.

### 💼 Admin (Manajer Operasional)
Panel teknis untuk mengelola rantai pasok harian dan administrasi produk.
*   **Manajemen Inventaris:** Katalog Bahan Baku, Kategori Bahan, Penyesuaian Stok (*Adjustment*), dan Histori Restok.
*   **Manajemen Produk:** Katalog Menu Utama, Kategori Menu, dan Standardisasi Resep (BoM - *Bill of Materials*).
*   **Operasional Harian:** Audit Stok Harian, Laporan Pemakaian, dan Pencatatan Pengeluaran Operasional.
*   **Arsip Terpadu:** Sistem pemulihan (*restore*) untuk data bahan baku dan menu yang dinonaktifkan.

### 📱 API Kasir (Aplikasi Mobile)
Endpoint RESTful yang cepat dan aman untuk digunakan oleh aplikasi mobile kasir (Android/Kotlin).
*   **Autentikasi Aman:** Sistem Token (Bearer), Reset Password via OTP.
*   **Transaksi Real-time:** Pengambilan Menu, Metode Pembayaran, dan Pemrosesan *Checkout* (Terintegrasi dengan pengurangan stok resep).
*   **Riwayat Shift:** Ringkasan pendapatan harian dan histori transaksi spesifik milik kasir yang bertugas.

---

## 🛠️ Teknologi yang Digunakan

*   **Backend:** [PHP 8.2+](https://www.php.net/) & [Laravel 11](https://laravel.com/)
*   **Frontend (Panel):** Blade Templates, [Tailwind CSS v3](https://tailwindcss.com/) (Desain Modern), [Alpine.js](https://alpinejs.dev/) (Reaktivitas UI), [SweetAlert2](https://sweetalert2.github.io/) (Pop-up modern).
*   **Database:** [PostgreSQL](https://www.postgresql.org/) (Dioptimalkan untuk *Supabase*).
*   **Arsitektur:** REST API, Repository/Service Pattern.

---

## 🚀 Panduan Instalasi Lokal

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di lingkungan pengembangan (*localhost*).

### Prasyarat Sistem
*   PHP >= 8.2
*   Composer >= 2.0
*   Node.js >= 18.0 & NPM
*   PostgreSQL

### 1. Kloning Repositori
```bash
git clone https://github.com/athayabismaj/siinv-kebab-sk.git
cd siinv-kebab-sk
```

### 2. Instalasi Dependensi
Jalankan perintah berikut untuk menginstal dependensi PHP dan Node.js:
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (*Environment*)
Salin berkas konfigurasi contoh dan buat kunci aplikasi Laravel:
```bash
cp .env.example .env
php artisan key:generate
```

Ubah pengaturan kredensial basis data di dalam berkas `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database_anda
DB_USERNAME=user_database_anda
DB_PASSWORD=password_database_anda
```

### 4. Migrasi & Seeding Data Awal
Sistem membutuhkan *role* awal, akun administrator, dan *payment methods* agar dapat beroperasi penuh:
```bash
php artisan migrate --seed
```

### 5. Menjalankan Aplikasi
Jalankan *compiler frontend* dan server backend secara bersamaan (gunakan dua terminal terpisah):

**Terminal 1 (Frontend Vite):**
```bash
npm run dev
```

**Terminal 2 (Backend Laravel):**
```bash
php artisan serve
```
Aplikasi kini dapat diakses melalui peramban di `http://127.0.0.1:8000`.

---

## 📖 Dokumentasi API Kasir (Mobile)

*Endpoint* API berada di bawah *path* `/api/` dan membutuhkan *header* `Accept: application/json`. Untuk *endpoint* yang dilindungi, gunakan *header* `Authorization: Bearer {token}`.

| Modul | Endpoint Utama | Deskripsi |
| :--- | :--- | :--- |
| **Auth** | `POST /auth/login` | Mendapatkan kredensial *Bearer Token*. |
| **Auth** | `GET /auth/me` | Profil kasir yang sedang aktif. |
| **Data** | `GET /menus` | Mengambil katalog produk yang tersedia. |
| **Sales** | `POST /transactions` | Mengirim data transaksi (Checkout). |
| **Sales** | `GET /revenue/summary`| Mendapatkan ringkasan pendapatan harian. |

---

## 🧪 Pengujian & Audit

Proyek ini dilengkapi dengan skrip pengujian bawaan untuk memastikan integritas data (terutama logika rantai pasok dan pemotongan resep).

*   **Menjalankan Unit/Feature Test:**
    ```bash
    php artisan test
    ```
*   **Menjalankan Test Spesifik PostgreSQL:**
    ```bash
    vendor/bin/phpunit -c phpunit.pgsql.xml
    ```
*   **Audit Integritas Stok:**
    Sistem memiliki *Command Line* khusus untuk melakukan audit apakah stok fisik dan sistem sinkron:
    ```bash
    php artisan ops:daily-stock-integrity-audit --days=1
    ```

---

<div align="center">
  <p>Dibuat untuk kelancaran operasional <b>Kebab SK</b>. &copy; 2026</p>
</div>
