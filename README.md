<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK Logo" width="100" />
  <h1>Kebab SK - SIINV</h1>
  <p><b>Sistem Manajemen Inventaris & Operasional Point of Sales Berbasis Cloud</b></p>
  
  [![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=flat-square&logo=alpine.js&logoColor=white)](https://alpinejs.dev/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  
  <br />
  <br />
  
  <a href="README.md"><img src="https://img.shields.io/badge/-🇮🇩%20ID-E11D48?style=for-the-badge" alt="ID" /></a>
  &nbsp;&nbsp;
  <a href="README-en.md"><img src="https://img.shields.io/badge/-🇬🇧%20ENG-1E40AF?style=for-the-badge" alt="ENG" /></a>
</div>

---

## 📖 Tentang Proyek
**SIINV (Sistem Inventory)** adalah platform manajemen terpadu yang dirancang khusus untuk operasional Kebab SK. Sistem ini menjembatani kompleksitas rantai pasok (stok bahan baku) dengan pencatatan transaksi kasir harian (*Point of Sales*). SIINV memisahkan panel manajemen berbasis web untuk pemilik dan admin, dengan API berkinerja tinggi yang disiapkan khusus untuk aplikasi kasir *mobile*.

---

## ✨ Fitur Utama

### 👑 Panel Owner (Pemilik)
- 📊 **Dashboard Finansial:** Pantau pendapatan harian, bulanan, dan tren penjualan secara *real-time*.
- 🔒 **Tutup Buku (Closing):** Validasi dan pengarsipan data transaksi per cabang menjadi riwayat permanen.
- 👥 **Manajemen SDM:** Pengaturan hak akses (RBAC), pembuatan akun pegawai, dan pengarsipan akun nonaktif.

### 💼 Panel Admin (Operasional)
- 📦 **Rantai Pasok:** Manajemen data bahan baku, rekap penyesuaian stok (*adjustments*), dan histori belanja (*restock*).
- 🧾 **Katalog Produk:** Standardisasi resep detail (BoM - *Bill of Materials*) untuk otomatisasi pemotongan stok.
- 📋 **Audit Harian:** Laporan pemakaian bahan, pencatatan pengeluaran operasional, dan transfer stok antar cabang.

### 📱 API Kasir (Mobile POS)
- 🚀 **Transaksi Cepat:** Pemrosesan *checkout* JSON yang ringan dan aman.
- 🔑 **Autentikasi Berlapis:** Sistem *Bearer Token* dengan fitur pemulihan kata sandi (*Forgot Password*) via OTP.
- 📈 **Riwayat Shift:** Kasir dapat melacak ringkasan omzet mereka sendiri di akhir masa *shift*.

---

## 🛠️ Arsitektur & Teknologi
Proyek ini dibangun di atas arsitektur web modern yang tangguh.
- **Backend:** PHP 8.2+, Laravel 11
- **Database:** PostgreSQL (Mendukung integrasi Supabase)
- **Frontend (Web):** Blade Templates, Tailwind CSS v3, Alpine.js, SweetAlert2
- **Integrasi Mobile:** RESTful API (JSON Response)

---

## 🚀 Memulai (Instalasi Lokal)

Untuk menjalankan proyek ini di mesin lokal (PC/Laptop), ikuti langkah berikut:

1. **Kloning Repositori**
   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```
2. **Instalasi Dependensi**
   ```bash
   composer install && npm install
   ```
3. **Konfigurasi Lingkungan**
   Salin berkas pengaturan (*environment*), lalu sesuaikan kredensial basis data Anda (gunakan format PostgreSQL).
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Migrasi Database & Seeder**
   *Tahap ini wajib dijalankan agar sistem memiliki hak akses dasar, akun admin pertama, dan opsi pembayaran default.*
   ```bash
   php artisan migrate --seed
   ```
5. **Jalankan Aplikasi**
   Buka dua jendela terminal untuk menjalankan modul frontend dan backend secara bersamaan.
   ```bash
   npm run dev
   php artisan serve
   ```
   Aplikasi kini dapat dibuka di peramban Anda pada tautan `http://127.0.0.1:8000`.

---

## 📚 Ringkasan Endpoint API

Seluruh rute API memiliki prefiks `/api/` dan memerlukan *header* `Accept: application/json`.
- `POST /auth/login` — Autentikasi dan pengambilan token sesi.
- `GET /menus` — Daftar produk yang aktif untuk dipesan.
- `POST /transactions` — Penyimpanan data transaksi pembelian pelanggan.
- `GET /revenue/summary` — Kalkulasi otomatis pendapatan harian kasir.

---
<br />
<div align="center">
  <sub>Hak Cipta Terpelihara. Dibangun untuk kelancaran operasional <b>Kebab SK</b> &copy; 2026.</sub>
</div>
