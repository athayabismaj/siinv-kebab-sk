# SIINV Kebab SK

Sistem inventory + monitoring transaksi untuk operasional Kebab SK.

Project ini terdiri dari:
- Web panel Laravel (role `admin` dan `owner`)
- API Laravel untuk aplikasi mobile kasir (Kotlin)

## Fitur Utama

### Admin
- Dashboard ringkas operasional
- Manajemen bahan, kategori bahan, stok, restok, dan adjustment
- Riwayat stok
- Manajemen menu, kategori menu, dan resep
- Monitoring transaksi kasir
- Laporan pemakaian
- Arsip bahan/menu (restore data nonaktif)

### Owner
- Dashboard monitoring bisnis
- Monitoring stok (read-only)
- Laporan penjualan (harian & bulanan)
- Riwayat transaksi
- Analisis menu (terlaris, paling sedikit terjual, kontribusi)
- Laporan pemakaian
- Manajemen user + arsip user

### API Mobile Kasir
- Auth login + profil + ganti password + logout
- Forgot password OTP flow
- List menu
- List metode pembayaran
- Checkout transaksi
- Riwayat transaksi + summary revenue

## Teknologi
- PHP 8.x
- Laravel 11
- PostgreSQL (termasuk Supabase)
- Blade + TailwindCSS + Alpine.js

## Struktur Penting
- `routes/web.php` -> route panel admin/owner
- `routes/api.php` -> endpoint mobile
- `app/Http/Controllers/Admin` -> logic admin
- `app/Http/Controllers/Owner` -> logic owner
- `app/Http/Controllers/API` -> logic API mobile
- `app/Services` -> service layer (stok/transaksi)
- `resources/views` -> UI blade

## Instalasi Lokal

1. Clone repository
```bash
git clone https://github.com/athayabismaj/siinv-kebab-sk.git
cd siinv-kebab-sk
```

2. Install dependency
```bash
composer install
npm install
```

3. Siapkan environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Atur konfigurasi database di `.env`
Contoh PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=your-host
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-password
```

5. Jalankan migrasi + seeder
```bash
php artisan migrate --seed
```

6. Jalankan aplikasi
```bash
npm run dev
php artisan serve --host=0.0.0.0 --port=8000
```

Akses web:
- Local: `http://127.0.0.1:8000`
- Satu jaringan (HP): `http://IP-PC-KAMU:8000`

## Endpoint API Utama

Base URL: `/api`

### Auth
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/verify-reset-code`
- `POST /auth/reset-password`
- `GET /auth/me` (Bearer token)
- `PUT /auth/profile` (Bearer token)
- `POST /auth/change-password` (Bearer token)
- `POST /auth/logout` (Bearer token)

### Kasir / Transaksi
- `GET /menus` (Bearer token)
- `GET /payment-methods` (Bearer token)
- `GET /transactions` (Bearer token)
- `POST /transactions` (Bearer token)
- `GET /revenue/summary` (Bearer token)
- `GET /revenue/trend` (Bearer token)

## Catatan Konfigurasi Penting

1. Pastikan tabel dari migrasi benar-benar sudah dibuat (`users`, `api_tokens`, `payment_methods`, `transactions`, dll).
2. Jika pakai PostgreSQL/Supabase, jangan lupa seed `payment_methods` (minimal `Cash`) agar checkout tidak gagal.
3. Endpoint API mengembalikan JSON dan dilindungi middleware `api.token`.
4. Route login web/API sudah diproteksi throttle untuk mengurangi spam request.

## Troubleshooting Singkat

- Error `relation "users" does not exist`:
  - database belum migrate / salah koneksi DB
- Error `relation "cache" does not exist`:
  - pastikan `CACHE_DRIVER` sesuai dan tabel cache tersedia jika memakai database cache
- Endpoint API timeout:
  - cek koneksi DB, indeks query, dan lokasi server