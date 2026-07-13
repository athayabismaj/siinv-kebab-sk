<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK Logo" width="120" />

  # Kebab SK Inventory & POS System (SIINV)

  **Cloud-Based Point of Sales & Supply Chain Management System**

  [![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
  [![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)

  <br />

  <a href="#english-version"><img src="https://img.shields.io/badge/🌍_Read_in-English-0F172A?style=for-the-badge" alt="English" /></a>
  &nbsp;&nbsp;
  <a href="#versi-indonesia"><img src="https://img.shields.io/badge/🇮🇩_Baca_dalam-Bahasa_Indonesia-E11D48?style=for-the-badge" alt="Indonesia" /></a>
</div>

---

<a id="english-version"></a>
## 🌍 English Version

**Kebab SK Inventory System (SIINV)** is a unified management platform specifically designed to monitor operations, supply chain (inventory), and cashier transactions for the Kebab SK franchise. It uses a modern SaaS-like architecture that separates the web-based management panel from the mobile Point of Sales (POS) interface.

### ✨ Key Features

#### 👑 Owner (Business Owner)
High-level analytical panel to monitor business performance across all branches.
*   **Analytics & Performance:** Centralized financial dashboard, Sales Reports (Daily & Monthly), and Menu Analysis (Contribution & Trends).
*   **Finance & Audit:** Branch-specific automated Closing Book records.
*   **HR Management:** Role-Based Access Control (RBAC), Cashier/Admin account creation, and employee archives.
*   **Branch Monitoring:** Read-only stock monitoring of all registered branches.

#### 💼 Admin (Operations Manager)
Technical panel to manage daily supply chains and product administration.
*   **Inventory Management:** Raw Material Catalog, Material Categories, Stock Adjustments, and Restock History.
*   **Product Management:** Main Menu Catalog, Menu Categories, and Recipe Standardization (BoM - Bill of Materials).
*   **Daily Operations:** Daily Stock Audits, Usage Reports, and Operational Expense tracking.
*   **Unified Archives:** Restore system for deactivated raw materials and menus.

#### 📱 Cashier API (Mobile App)
Fast and secure RESTful endpoints intended for the Android/Kotlin mobile POS application.
*   **Secure Authentication:** Bearer Token System, OTP Password Reset.
*   **Real-time Transactions:** Fetch Menus, Payment Methods, and Checkout processing (integrated with automatic recipe stock deduction).
*   **Shift History:** Daily revenue summary and cashier-specific transaction history.

### 📸 Screenshots
> *Add your application screenshots here (e.g., Dashboard, POS interface, Reports).*

### 🚀 Local Installation Guide

#### Prerequisites
*   PHP >= 8.2
*   Composer >= 2.0
*   Node.js >= 18.0 & NPM
*   PostgreSQL

#### Step-by-Step
1. **Clone the repository:**
   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```
2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```
3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Update your database credentials in the `.env` file (ensure DB_CONNECTION=pgsql).*
4. **Migrate & Seed:**
   *(The seeder will generate initial roles, an admin account, and payment methods).*
   ```bash
   php artisan migrate --seed
   ```
5. **Run the application:**
   Open two terminals:
   *   Terminal 1: `npm run dev` (Vite frontend compiler)
   *   Terminal 2: `php artisan serve` (Laravel backend server)
   
   Access the web app at `http://127.0.0.1:8000`.

### 📖 API Documentation (Overview)
Base URL: `/api` (Requires `Accept: application/json`)

| Module | Endpoint | Description |
| :--- | :--- | :--- |
| **Auth** | `POST /auth/login` | Obtain Bearer Token credentials. |
| **Auth** | `GET /auth/me` | Get active cashier profile. |
| **Data** | `GET /menus` | Retrieve available product catalog. |
| **Sales** | `POST /transactions` | Submit transaction data (Checkout). |
| **Sales** | `GET /revenue/summary`| Get daily revenue summary. |

### ⚖️ License
This project is proprietary and confidential. Unauthorized copying of this file, via any medium is strictly prohibited.

---
<br><br>

<a id="versi-indonesia"></a>
## 🇮🇩 Versi Indonesia

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
