# Lifecycle Data Bahan dan Menu

## Ringkasan

Manajemen Bahan dan Manajemen Menu memakai satu halaman daftar untuk seluruh lifecycle data. Admin memilih status melalui tab:

- `active`: data aktif dan dapat dikelola;
- `archived`: data yang dihapus secara lunak (`deleted_at` terisi);
- `all`: gabungan data aktif dan diarsipkan.

Status default adalah `active`. Filter pencarian, kategori, harga bahan, dan pagination tetap dibawa ketika admin berpindah tab.

## Soft Delete

Bahan dan Menu menggunakan Laravel `SoftDeletes`. Aksi arsip tidak menghapus relasi historis seperti resep, varian, detail transaksi, stock log, atau item stok harian. Data arsip hanya menyediakan aksi pemulihan; edit dan perubahan operasional hanya tersedia untuk data aktif.

## Ketersediaan Menu

`menus.is_active` adalah status ketersediaan penjualan dan berbeda dari lifecycle data:

- `is_active = true`: menu tersedia untuk penjualan selama tidak diarsipkan;
- `is_active = false`: menu tidak tersedia untuk penjualan tetapi masih aktif sebagai data;
- `deleted_at` terisi: menu diarsipkan dan tidak tampil di katalog kasir.

Pemulihan menu tidak mengubah `is_active`. Menu yang sebelumnya tidak tersedia akan tetap tidak tersedia setelah dipulihkan sampai admin mengubah pengaturan penjualannya.

## Route Kompatibilitas

Nama route lama tetap tersedia agar bookmark atau tautan lama tidak rusak:

- `admin.ingredients.archive` mengarahkan ke `admin.ingredients.index?record_status=archived`;
- `admin.menus.archive` mengarahkan ke `admin.menus.index?record_status=archived`.

Hanya filter yang dikenal yang diteruskan. Halaman arsip mandiri dan aset CSS khusus arsip sudah tidak digunakan.

## Cache dan Integrasi

Aksi arsip dan pemulihan tetap menjalankan invalidasi cache yang sama seperti sebelum refactor. Endpoint API, payload Android, dan struktur respons katalog tidak berubah. Katalog kasir hanya menampilkan menu yang tidak diarsipkan dan tersedia untuk penjualan.
