<div align="center">
  <img src="public/favicon.svg" alt="Logo Kebab SK" width="96" />
  <h1>Kebab SK — SIINV</h1>
  <p><strong>Sistem inventaris, operasional cabang, dan backend Point of Sale terintegrasi.</strong></p>

  [![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase_ready-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)

  <br><br>

  <a href="README.md"><img src="https://img.shields.io/badge/Bahasa-Indonesia-E11D48?style=for-the-badge" alt="Bahasa Indonesia" /></a>
  <a href="README-en.md"><img src="https://img.shields.io/badge/Language-English-1E40AF?style=for-the-badge" alt="English" /></a>
</div>

---

## Edisi Publik

Repositori ini adalah **edisi publik** SIINV Kebab SK: sebuah snapshot stabil yang ditujukan untuk dokumentasi teknis, portofolio, evaluasi, serta demonstrasi arsitektur sistem.

Pengembangan aktif berikutnya—termasuk perbaikan lanjutan, fitur baru, konfigurasi produksi, integrasi khusus, dan modul yang bersifat internal—dapat dikelola secara privat dan tidak selalu diterbitkan kembali ke repositori ini. Edisi publik tetap berdiri sebagai aplikasi yang dapat dipasang dan diuji secara mandiri.

> Kode aplikasi kasir mobile, kredensial produksi, data operasional, dan rahasia layanan pihak ketiga tidak menjadi bagian dari distribusi publik.

## Tentang SIINV

SIINV menghubungkan stok gudang, saldo bahan harian outlet, resep menu, transaksi kasir, dan arus kas dalam satu alur yang dapat diaudit. Aplikasi menyediakan panel web berbasis peran untuk owner, admin, dan developer, serta REST API untuk aplikasi kasir mobile.

Alur utamanya:

1. Admin membuka sesi stok harian dan memverifikasi sisa sesi sebelumnya.
2. Tambahan bahan dari gudang dicatat sebagai mutasi baru tanpa memotong ulang stok carry-forward.
3. Kasir melakukan checkout; ketersediaan menu divalidasi berdasarkan resep dan stok cabang.
4. Saat sesi ditutup, pemakaian dan sisa fisik bahan direkonsiliasi.
5. Owner memantau omzet, pengeluaran, saldo kas, stok, dan laporan lintas cabang.

## Fitur Utama

### Owner

- Dashboard pendapatan, tren penjualan, arus kas, dan ringkasan lintas cabang.
- Manajemen cabang, akun pengguna, hak akses, arsip, dan pemulihan akun.
- Riwayat transaksi dan mutasi stok dengan filter cabang.
- Laporan penjualan, pemakaian bahan, pengeluaran, serta ekspor HTML, PDF, dan Excel.
- Closing periode dan analisis performa menu.

### Admin operasional

- Manajemen kategori bahan, bahan baku, satuan pack/pcs, stok minimum, restock, dan penyesuaian.
- Katalog menu, varian, gambar produk, harga modal/jual, serta resep atau Bill of Materials.
- Sesi stok harian dengan carry-forward sisa sebelumnya, tambahan gudang, tutup sesi, buka ulang, dan rekonsiliasi.
- Riwayat transaksi, laporan stok harian, pemakaian bahan, dan arus kas operasional.
- Pemisahan data berdasarkan cabang untuk mencegah stok dan transaksi tercampur.

### API kasir mobile

- Autentikasi token, profil, ganti kata sandi, dan pemulihan melalui OTP.
- Katalog menu dengan status ketersediaan berbasis resep dan stok aktif.
- Checkout dengan harga dari server, validasi stok atomik, dan isolasi cabang.
- Riwayat transaksi, detail struk, void transaksi, omzet, dan tren pendapatan.
- Status sesi, stok harian, penutupan sesi, dan pencatatan pengeluaran kasir.

### Operasional sistem

- Queue untuk ekspor dan pekerjaan latar belakang.
- Health/readiness endpoint, pencatatan performa, cache, dan indeks PostgreSQL.
- Backup dan restore database dengan pembatasan lingkungan.
- Pengujian fitur, kontrak API, keamanan, ekspor, serta query-budget checkout.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | PostgreSQL, kompatibel dengan Supabase |
| Web UI | Blade, Tailwind CSS 3, Alpine.js |
| Asset build | Vite 7, Node.js |
| API | REST/JSON, token authentication |
| Dokumen | Laravel Excel dan DomPDF |
| Email | Resend atau mailer Laravel |
| Test | PHPUnit 11 |

## Persyaratan Lokal

- PHP `8.2` atau lebih baru.
- Composer `2.x`.
- Node.js `20.19+` atau `22.12+` dan npm.
- PostgreSQL atau proyek Supabase. SQLite dapat dipakai untuk sebagian pengembangan dan test lokal.
- Ekstensi PHP: `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, dan `zip`.

## Instalasi Lokal

1. Kloning repositori dan masuk ke direktori proyek.

   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```

2. Pasang dependensi backend dan frontend.

   ```bash
   composer install
   npm ci
   ```

3. Buat konfigurasi lokal dan application key.

   Linux/macOS:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Atur koneksi PostgreSQL/Supabase pada `.env`, kemudian jalankan migrasi.

   ```bash
   php artisan migrate
   ```

5. Jika membutuhkan data contoh, tinjau seluruh seeder terlebih dahulu, lalu jalankan:

   ```bash
   php artisan db:seed
   ```

   Seeder hanya ditujukan untuk pengembangan. Seeder dapat membuat akun awal dengan kata sandi bawaan; jangan menjalankannya langsung pada produksi tanpa mengganti atau menonaktifkan kredensial tersebut.

6. Jalankan lingkungan pengembangan.

   ```bash
   composer run dev
   ```

   Perintah tersebut menjalankan server Laravel, queue listener, log viewer, dan Vite. Aplikasi web tersedia secara default di `http://127.0.0.1:8000`.

## Build dan Pengujian

```bash
npm run build
composer test
```

Untuk production, jalankan migrasi dengan `php artisan migrate --force`, bangun aset frontend, aktifkan queue worker, dan jalankan scheduler Laravel. Jangan gunakan `migrate:fresh` pada database yang berisi data operasional.

## Ringkasan API

Semua endpoint berada di bawah prefiks `/api` dan menggunakan respons JSON. Endpoint operasional dilindungi token, pembatasan peran, dan rate limit.

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/auth/login` | Login dan memperoleh token |
| `GET` | `/api/auth/me` | Profil pengguna aktif |
| `GET` | `/api/menus` | Katalog dan ketersediaan varian |
| `POST` | `/api/transactions` | Checkout kasir |
| `GET` | `/api/transactions` | Riwayat transaksi |
| `GET` | `/api/revenue/summary` | Ringkasan omzet |
| `GET` | `/api/daily-stock-items` | Saldo stok harian kasir |
| `POST` | `/api/daily-stock-sessions/close` | Tutup sesi harian |
| `POST` | `/api/cashflow/expenses` | Catat pengeluaran operasional |

Kontrak yang lebih rinci tersedia di [`docs/API_CONTRACT_ANDROID.md`](docs/API_CONTRACT_ANDROID.md) dan [`docs/api-menu-availability.md`](docs/api-menu-availability.md).

## Keamanan Publikasi

- Jangan commit `.env`, dump database, token Supabase, API key, kredensial email, atau file backup.
- Ganti seluruh akun dan kata sandi hasil seeder sebelum memakai aplikasi di luar lingkungan lokal.
- Gunakan `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, cookie aman, serta kredensial database dengan hak akses minimum pada production.
- Laporkan celah keamanan secara privat kepada pemilik proyek; jangan mempublikasikan data eksploitasi atau kredensial melalui issue publik.

## Dukungan dan Kontribusi

Repositori ini berfungsi terutama sebagai rilis publik dan referensi teknis. Permintaan fitur, roadmap, dukungan deployment, dan perubahan khusus klien tidak dijamin tersedia pada edisi publik. Pull request dapat ditinjau, tetapi penerimaan dan jadwal rilis sepenuhnya mengikuti kebijakan pemilik proyek.

## Hak Penggunaan

Repositori ini belum menyertakan berkas `LICENSE` khusus SIINV. Hubungi pemilik proyek untuk izin penggunaan ulang, modifikasi, distribusi, atau pemakaian komersial. Seluruh framework, library, dan dependensi pihak ketiga tetap mengikuti lisensinya masing-masing.

Hak cipta © 2026 Kebab SK. Seluruh hak dilindungi.
