<div align="center">
  <img src="public/favicon.svg" alt="Logo Kebab SK" width="96" />
  <h1>Kebab SK — SIINV</h1>
  <p><strong>Sistem inventaris, operasional cabang, laporan, dan backend Point of Sale terintegrasi.</strong></p>

  [![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase_ready-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)

  <br><br>

  <a href="README.md"><img src="https://img.shields.io/badge/Language-English-1E40AF?style=for-the-badge" alt="English" /></a>
  <a href="README-id.md"><img src="https://img.shields.io/badge/Bahasa-Indonesia-E11D48?style=for-the-badge" alt="Bahasa Indonesia" /></a>
</div>

---

## Gambaran Umum

SIINV merupakan pusat operasional Kebab SK. Sistem ini menghubungkan stok gudang, stok harian outlet, resep menu, transaksi kasir, pengeluaran, dan laporan manajemen dalam satu alur yang dapat diaudit.

Aplikasi menyediakan panel web berbasis peran untuk owner dan admin operasional serta REST API yang digunakan oleh aplikasi kasir Android SIPOS. Validasi harga, akses cabang, sesi stok aktif, kebutuhan resep, dan mutasi inventaris tetap ditentukan oleh server.

## Alur Operasional

1. Admin membuka sesi stok harian untuk kasir dan outlet.
2. Sisa stok dari sesi terakhir yang sudah ditutup dibawa ke sesi baru dan diverifikasi secara fisik.
3. Hanya tambahan bahan yang benar-benar diambil dari gudang yang membuat mutasi pengurangan gudang.
4. SIPOS mengirim transaksi kasir; SIINV memvalidasi sesi, harga, resep, dan ketersediaan bahan secara atomik.
5. Saat sesi ditutup, sisa fisik direkonsiliasi dengan penjualan dan pemakaian tercatat.
6. Owner memantau omzet, pengeluaran, saldo kas, mutasi stok, dan performa lintas cabang.

## Fitur Utama

### Inventaris dan resep

- Kategori bahan, bahan baku, konversi pack/pcs, stok minimum, restock, dan penyesuaian.
- Katalog menu, varian, gambar produk, harga modal dan jual, serta resep Bill of Materials.
- Ketersediaan menu berdasarkan resep dan saldo stok aktif cabang.
- Riwayat mutasi stok yang dapat difilter berdasarkan cabang dan tanggal.
- Perlindungan terhadap sisa pecahan stok dan pemotongan gudang ganda.

### Operasional stok harian

- Pembukaan, penutupan, pembukaan ulang, dan rekonsiliasi sesi harian.
- Carry-forward sisa sesi sebelumnya yang tetap dapat dikoreksi berdasarkan stok fisik.
- Pemisahan jelas antara stok terbawa dan tambahan baru dari gudang.
- Laporan pemakaian kasir dan sisa bahan lintas outlet.
- Isolasi data sesi, stok, transaksi, dan laporan berdasarkan cabang.

### Penjualan dan keuangan

- Riwayat transaksi, detail struk, kontrol pembatalan, dan dampaknya terhadap stok.
- Dashboard omzet, tren penjualan, performa menu, dan closing periode.
- Pengeluaran operasional dengan keterangan dan rekam jejak audit.
- Arus kas transparan: omzet dikurangi pengeluaran beserta saldo akhirnya.
- Ekspor laporan dalam format HTML, PDF, dan Excel.

### API kasir mobile

- Autentikasi token, profil, penggantian kata sandi, dan pemulihan melalui OTP.
- Katalog menu berpaginasi dengan status ketersediaan berbasis resep dan stok.
- Checkout dengan harga dari server dan validasi stok atomik.
- Riwayat transaksi, struk, pembatalan, omzet, dan tren pendapatan.
- Status stok harian, penutupan sesi, dan pencatatan pengeluaran operasional.

### Keandalan sistem

- Queue untuk ekspor dan pekerjaan latar belakang.
- Endpoint health/readiness, pencatatan performa, cache, dan indeks PostgreSQL.
- Proses backup dan restore yang dibatasi berdasarkan lingkungan.
- Pengujian fitur, keamanan, kontrak API, ekspor, konkurensi, dan anggaran query.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | PostgreSQL, kompatibel dengan Supabase |
| Antarmuka web | Blade, Tailwind CSS 3, Alpine.js |
| Build frontend | Vite 7, Node.js |
| API | REST/JSON dengan autentikasi token |
| Dokumen | Laravel Excel, DomPDF |
| Email | Resend atau mailer kompatibel Laravel |
| Pengujian | PHPUnit 11 |

## Persyaratan

- PHP `8.2` atau lebih baru.
- Composer `2.x`.
- Node.js `20.19+` atau `22.12+`, beserta npm.
- PostgreSQL atau proyek Supabase.
- Ekstensi PHP: `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, dan `zip`.

SQLite dapat digunakan untuk sebagian pengujian lokal, tetapi PostgreSQL disarankan untuk pengembangan yang menguji query dan konkurensi seperti lingkungan produksi.

## Instalasi

1. Kloning repositori.

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

   Linux atau macOS:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Atur koneksi database pada `.env`, lalu jalankan migrasi.

   ```bash
   php artisan migrate
   ```

5. Jika diperlukan, periksa lalu jalankan seeder pengembangan.

   ```bash
   php artisan db:seed
   ```

   Seeder dapat membuat akun pengembangan dengan kredensial bawaan. Periksa seluruh seeder sebelum digunakan dan jangan membawa kredensial bawaan ke lingkungan deployment.

6. Jalankan layanan pengembangan.

   ```bash
   composer run dev
   ```

   Perintah ini menjalankan server Laravel, queue listener, penampil log, dan Vite. Aplikasi tersedia di `http://127.0.0.1:8000` secara default.

## Build dan Pengujian

```bash
npm run build
composer test
```

Laravel Pint dapat digunakan untuk menormalkan format kode PHP:

```bash
./vendor/bin/pint
```

## Ringkasan API

Semua endpoint API menggunakan prefiks `/api` dan menghasilkan respons JSON. Endpoint terlindungi menerapkan autentikasi, pemeriksaan peran, konteks cabang, dan rate limit.

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/auth/login` | Autentikasi dan memperoleh token |
| `GET` | `/api/auth/me` | Mengambil profil pengguna aktif |
| `GET` | `/api/menus` | Mengambil katalog dan ketersediaan menu |
| `POST` | `/api/transactions` | Mengirim checkout kasir |
| `GET` | `/api/transactions` | Mengambil riwayat transaksi |
| `GET` | `/api/revenue/summary` | Mengambil ringkasan omzet |
| `GET` | `/api/daily-stock-items` | Mengambil saldo stok harian kasir |
| `POST` | `/api/daily-stock-sessions/close` | Menutup sesi stok harian |
| `POST` | `/api/cashflow/expenses` | Mencatat pengeluaran operasional |

Lihat [kontrak API Android](docs/API_CONTRACT_ANDROID.md), [ketersediaan menu](docs/api-menu-availability.md), dan [matriks kontrak mobile](docs/MOBILE_API_CONTRACT_MATRIX.md) untuk detail integrasi.

## Operasional Produksi

Sebelum deployment:

- Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
- Konfigurasikan HTTPS, secure cookie, trusted proxy, dan kredensial database dengan hak minimum.
- Jalankan `php artisan migrate --force` dan `npm run build`.
- Jalankan queue worker dan scheduler Laravel melalui process supervisor.
- Gunakan `composer run prod:optimize` setelah konfigurasi produksi selesai.
- Verifikasi prosedur backup dan restore sebelum menerima data operasional.

Jangan pernah menggunakan `migrate:fresh` pada database yang sudah berisi data operasional.

## Dokumentasi Teknis

- [Keamanan deployment](docs/DEPLOYMENT_SAFETY.md)
- [Operasional queue worker](docs/QUEUE_WORKER_OPERATIONS.md)
- [Operasional scheduler](docs/SCHEDULER_OPERATIONS.md)
- [Backup dan restore](docs/BACKUP_RESTORE_OPERATIONS.md)
- [Monitoring dan health check](docs/MONITORING_AND_HEALTH_OPERATIONS.md)
- [QA performa dan konkurensi PostgreSQL](docs/POSTGRESQL_CONCURRENCY_PERFORMANCE_QA.md)
- [Checklist keamanan deployment](docs/security-deployment-checklist.md)

## Keamanan

- Jangan commit `.env`, dump database, kredensial Supabase, API key, kredensial email, atau arsip backup.
- Ganti seluruh akun dan kata sandi hasil seeder sebelum deployment.
- Pertahankan otorisasi, perhitungan harga, isolasi cabang, dan validasi stok pada server.
- Laporkan dugaan celah keamanan secara privat kepada pemilik proyek dan jangan menyertakan kredensial atau data operasional pada laporan issue.

## Kontribusi

Gunakan commit yang terfokus, sertakan pengujian untuk perubahan perilaku, dan dokumentasikan perubahan kontrak API yang memengaruhi SIPOS. Pull request ditinjau berdasarkan prioritas proyek dan kebutuhan kompatibilitas.

## Lisensi

Repositori ini belum menyertakan lisensi khusus proyek. Hubungi pemilik proyek sebelum menggunakan ulang, memodifikasi, mendistribusikan, atau menggunakan proyek secara komersial. Dependensi pihak ketiga tetap mengikuti lisensinya masing-masing.

Hak cipta © 2026 Kebab SK. Seluruh hak dilindungi.
