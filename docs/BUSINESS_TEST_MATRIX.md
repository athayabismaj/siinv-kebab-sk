# Business Test Matrix

Dokumen ini menjadi acuan regresi fitur bisnis utama. Status `Otomatis` berarti
ditutup oleh suite PHPUnit; status `Manual` harus diperiksa pada release candidate.

| ID | Area | Skenario | Jenis | Status |
| --- | --- | --- | --- | --- |
| AUTH-01 | Autentikasi | Login role admin, owner, kasir, developer diarahkan ke panel yang sesuai. | Otomatis | Covered |
| AUTH-02 | Autentikasi | Kredensial salah tidak mengungkap akun yang terdaftar. | Otomatis | Covered |
| AUTH-03 | Autentikasi | Reset kata sandi mencabut token API lama. | Otomatis | Covered |
| AUTH-04 | Autentikasi | Batas percobaan OTP dan login diterapkan. | Otomatis | Covered |
| BRANCH-01 | Cabang | Admin hanya dapat beralih ke cabang yang ditugaskan. | Otomatis | Covered |
| BRANCH-02 | Cabang | Kasir API hanya membaca transaksi cabangnya sebelum pagination. | Otomatis | Covered |
| BRANCH-03 | Cabang | Admin tidak dapat membaca detail transaksi cabang lain. | Otomatis | Covered |
| BRANCH-04 | Cabang | Owner dapat melihat transaksi lintas cabang sesuai aturan bisnis. | Otomatis | Covered |
| BRANCH-05 | Cabang | Void Cabang A tidak mengubah sesi maupun ringkasan Cabang B. | Otomatis | Covered |
| SALE-01 | Checkout | Harga server digunakan, bukan harga dari client. | Otomatis | Covered |
| SALE-02 | Checkout | Pembayaran kurang dan varian tidak tersedia ditolak tanpa transaksi parsial. | Otomatis | Covered |
| SALE-03 | Checkout | Kode transaksi berurutan per cabang per hari dan direset esok hari. | Otomatis | Covered |
| SALE-04 | Checkout | Detail struk mengirim produk, varian, jumlah, harga, subtotal, dan kode transaksi. | Otomatis | Covered |
| VOID-01 | Pembatalan | Void restok mengubah status, mengembalikan stok harian, dan membangun ulang ringkasan. | Otomatis | Covered |
| VOID-02 | Pembatalan | Void kedua dengan transaksi yang sama ditolak. | Otomatis | Covered |
| VOID-03 | Pembatalan | Void lintas cabang atau lintas sesi tidak membuat side effect. | Otomatis | Covered |
| STOCK-01 | Sesi stok | Admin membuka sesi hanya untuk kasir cabang aktif dan tanggal yang sah. | Otomatis | Covered |
| STOCK-02 | Transfer | Transfer mengurangi gudang, membuat item sesi, dan menulis stock log. | Otomatis | Covered |
| STOCK-03 | Transfer | Transfer batch menyimpan item yang cukup dan melaporkan item yang kurang. | Otomatis | Covered |
| STOCK-04 | Penyesuaian | Penyesuaian mencatat selisih stok pada cabang aktif. | Otomatis | Covered |
| STOCK-05 | Tutup sesi | Sisa stok dikembalikan, pemakaian tercatat, dan sesi tidak dapat ditutup dua kali. | Otomatis | Covered |
| STOCK-06 | Harian E2E | Transfer, checkout, void-restok, lalu tutup sesi menjaga stok dan ringkasan konsisten. | Otomatis | Covered |
| EXPENSE-01 | Pengeluaran | Kasir tidak dapat mengirim tanggal pengeluaran di luar tanggal server. | Otomatis | Covered |
| EXPENSE-02 | Pengeluaran | Data dan ekspor pengeluaran dibatasi konteks cabang. | Otomatis | Covered |
| REPORT-01 | Ringkasan | Transaksi `SUCCESS` saja yang dihitung untuk omzet dan item terjual. | Otomatis | Covered |
| REPORT-02 | Ringkasan | Daily sales summary unik pada kombinasi cabang dan tanggal. | Otomatis | Covered |
| REPORT-03 | Ringkasan | Rebuild ringkasan idempoten dan tidak mengubah cabang lain. | Otomatis | Covered |
| REPORT-04 | Dashboard | Dashboard admin memakai cabang aktif dan mengecualikan transaksi void. | Otomatis | Covered |
| REPORT-05 | Owner | Laporan owner dapat dibatasi cabang aktif atau melihat semua cabang. | Otomatis | Covered |
| EXPORT-01 | Ekspor | Ambang ekspor menentukan sinkron atau antrean tanpa mengubah hasil data. | Otomatis | Covered |
| EXPORT-02 | Ekspor | File ekspor hanya dapat diunduh oleh pengguna yang berwenang. | Otomatis | Covered |
| EXPORT-03 | Ekspor | Riwayat transaksi, stok, pemakaian, daily stock, pengeluaran, dan penjualan tetap branch-aware. | Otomatis | Covered |
| JOB-01 | Antrean | Job ekspor idempoten, kegagalan tercatat, dan export stale dapat dipulihkan. | Otomatis | Covered |
| SCHED-01 | Scheduler | Perintah penutupan sesi dan pengelolaan ekspor terjadwal serta memiliki lock database. | Otomatis | Covered |
| ERROR-01 | Konsistensi | Gagal checkout/transfer tidak mengubah stok atau membuat log parsial. | Otomatis | Covered |
| ERROR-02 | Kontrak API | Respons detail transaksi dan kesalahan validasi mempertahankan struktur yang diuji. | Otomatis | Covered |
| TIME-01 | Batas waktu | Kode transaksi berganti urutan pada hari berikutnya. | Otomatis | Covered |
| TIME-02 | Batas waktu | Sesi masa lalu dan masa depan tidak dapat dibuka secara tidak sah. | Otomatis | Covered |
| PREC-01 | Presisi | Konversi pak/pcs dan kuantitas tersisa diuji dengan toleransi desimal. | Otomatis | Covered |
| REL-01 | Release UI | Cek browser desktop/mobile: login, branch switcher, checkout mobile, export download. | Manual | Required |
| REL-02 | Release operasional | Validasi role/cabang akun seed dan metode pembayaran tunai pada environment rilis. | Manual | Required |
| REL-03 | Release data | Jalankan `deploy:check` pada database target sebelum deploy. | Manual | Required |

## Definisi transaksi valid

Transaksi penjualan yang valid untuk ringkasan dan laporan adalah transaksi dengan
`status = SUCCESS`. Transaksi `VOID` tidak menambah omzet, jumlah transaksi, atau
item terjual. Pembatalan dengan pilihan restok mengembalikan bahan ke sesi stok
harian; pembatalan waste tercatat sebagai koreksi operasional sesuai alur yang ada.

## Postgres-sensitive verification

Suite normal berjalan pada SQLite in-memory. Query agregasi tanggal, unique
ringkasan cabang/tanggal, lock transaksi, dan export besar harus dijalankan juga
pada database PostgreSQL disposable sesuai prosedur `docs/DEPLOYMENT_SAFETY.md`.
Tidak pernah gunakan database aplikasi atau produksi untuk drill tersebut.
