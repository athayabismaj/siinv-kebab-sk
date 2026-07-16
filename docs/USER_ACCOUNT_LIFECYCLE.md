# User Account Lifecycle

Dokumen ini menjelaskan lifecycle akun yang dikelola owner pada halaman **Daftar Pengguna**.

## Status Akun

Status akun menggunakan `SoftDeletes` pada model `User`:

- **Aktif**: `deleted_at` bernilai `null`.
- **Nonaktif**: `deleted_at` berisi waktu penonaktifan.

Tidak ada kolom status tambahan. Halaman daftar menyediakan filter `Aktif`, `Nonaktif`, dan `Semua`; filter bawaan adalah `Aktif`.

## Penonaktifan

Owner hanya dapat menonaktifkan akun dengan role yang dikelola sistem, yaitu admin dan kasir. Akun owner dan developer tidak dapat dinonaktifkan melalui endpoint ini.

Penonaktifan memerlukan konfirmasi server-side dengan nilai tepat `NONAKTIF`. Proses dijalankan dalam satu transaksi database:

1. semua token API milik pengguna dihapus;
2. akun di-soft-delete.

Jika salah satu operasi gagal, seluruh perubahan dibatalkan. Pengguna yang sudah nonaktif tidak dapat login melalui web atau API. Session web lama ditolak oleh middleware role, dan token API lama tidak dapat digunakan kembali.

## Aktivasi Kembali

Aktivasi hanya tersedia untuk akun nonaktif dan memerlukan konfirmasi server-side dengan nilai tepat `AKTIFKAN`. Proses restore tidak:

- mengganti kata sandi;
- membuat ulang token API;
- mengubah role;
- mengubah penugasan cabang.

Setelah aktif kembali, pengguna login menggunakan kredensial sebelumnya dan memperoleh token baru melalui alur login API yang normal.

## Data Historis

Soft delete tidak menghapus data transaksi, sesi stok harian, atau penugasan cabang. Relasi pembuat/pelaku pada transaksi dan sesi stok menggunakan `withTrashed()` agar nama pengguna tetap terlihat pada histori setelah akun dinonaktifkan.

## Keunikan Username dan Email

Username dan email akun nonaktif tetap tersimpan dan tetap mengikuti unique constraint database. Nilai tersebut tidak boleh dipakai oleh akun baru karena akun lama masih dapat diaktifkan kembali.

## Kompatibilitas Route Lama

Route arsip pengguna dipertahankan untuk kompatibilitas tautan lama, tetapi mengarahkan pengguna ke halaman daftar dengan filter `Nonaktif`. Tidak ada lagi halaman atau menu arsip pengguna yang terpisah.

## Verifikasi

Verifikasi implementasi pada 16 Juli 2026:

- test lifecycle dan autentikasi: 14 test, 122 assertion;
- full Laravel suite dua kali: 220 test, 1.489 assertion pada setiap run;
- PostgreSQL disposable dua kali: 11 test, 61 assertion pada setiap run;
- sisa database PostgreSQL disposable: 0;
- frontend production build: berhasil, 76 modul;
- Blade clear/cache: berhasil;
- Pint pada seluruh file PHP yang disentuh: berhasil.

## Risiko Tersisa

- Route kompatibilitas `owner.users.archive` masih dipertahankan dan baru dapat dihapus setelah seluruh bookmark atau integrasi lama tidak lagi menggunakannya.
- Pemeriksaan visual manual desktop, mobile, dan dark mode tetap perlu dilakukan pada browser dengan session owner; automated feature test sudah memastikan kedua markup responsif dirender tanpa exception.
