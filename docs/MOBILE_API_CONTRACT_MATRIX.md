# Mobile API Contract Matrix

Dokumen ini membekukan kontrak API aktif yang digunakan aplikasi Android
SIPOS Kebab SK. Sumber kebenaran adalah `routes/api.php`, controller/request/
resource API, dan test kontrak Laravel. Android harus mengikuti kontrak ini.

## Aturan umum

- Prefix endpoint: `/api/`.
- Endpoint terproteksi memakai `Authorization: Bearer <token>` dan
  `Accept: application/json`.
- Cabang yang boleh diakses berasal dari cabang utama dan penugasan aktif user.
  Untuk kasir, cabang operasional aktual berasal dari sesi stok harian aktif
  milik kasir pada tanggal bisnis aplikasi. Android tidak boleh mengirim
  `branch_id` untuk memilih cabang lain.
- Nominal uang adalah integer JSON dan dipetakan ke Kotlin `Long`.
- ID, jumlah, page, dan count adalah integer.
- Field opsional dapat bernilai `null`; client tidak boleh mengarang nilai.
- Endpoint lama `POST /api/sessions/{id}/close` tidak tersedia.

## Endpoint aktif yang dipakai Android

| Fitur | Method | Endpoint | Auth/role | Request utama | Response utama | Status |
| --- | --- | --- | --- | --- | --- | --- |
| Login | POST | `/api/auth/login` | Publik | `username`, `password`, `device_name?` | token, expiry, user, role, `branch?` | 200 |
| Lupa sandi | POST | `/api/auth/forgot-password` | Publik | `email` | success, message | 200/422/429 |
| Verifikasi kode | POST | `/api/auth/verify-reset-code` | Publik | email dan kode | success, message | 200/422/429 |
| Reset sandi | POST | `/api/auth/reset-password` | Publik | email, kode, password | success, message | 200/422/429 |
| Profil | GET | `/api/auth/me` | Token | - | user, role, `branch?` | 200 |
| Ubah profil | PUT | `/api/auth/profile` | Token | name, username, email | user, role, `branch?` | 200/422 |
| Ubah sandi | POST | `/api/auth/change-password` | Token | sandi lama dan baru | success, message | 200/422 |
| Logout | POST | `/api/auth/logout` | Token | - | success, message | 200 |
| Menu | GET | `/api/menus` | Token | `search?`, `category_id?` | user, menu, variant | 200 |
| Metode bayar | GET | `/api/payment-methods` | Token | - | payment_methods | 200 |
| Status sesi | GET | `/api/sessions/current-status` | Token | - | active dan data sesi | 200/404 |
| Stok harian | GET | `/api/daily-stock-items` | Token | - | `session_id?`, items | 200 |
| Tutup sesi | POST | `/api/daily-stock-sessions/close` | Kasir | remaining, `notes?` | success, message | 200/409/422 |
| Checkout | POST | `/api/transactions` | Kasir | payment_method_id, paid_amount, items, `note?` | transaksi dan nominal | 201/409/422 |
| Riwayat | GET | `/api/transactions` | Token | `date?`, `page?` | paginator transaksi | 200 |
| Detail | GET | `/api/transactions/{reference}` | Token | ID atau kode | transaksi dan items | 200/404 |
| Receipt | GET | `/api/transactions/{reference}/receipt` | Token | ID atau kode | detail receipt | 200/404 |
| Void | POST | `/api/transactions/{id}/void` | Kasir | reason, current_session_id, idempotency_key | saldo laci baru | 200/409/422 |
| Pendapatan | GET | `/api/revenue/summary` | Token | `date?` | total_revenue, count, target | 200 |
| Tren | GET | `/api/revenue/trend` | Token | `date?` | daftar tanggal dan omzet | 200 |
| Pengeluaran | POST | `/api/cashflow/expenses` | Kasir | amount, source, `note?` | success, message | 200/201/422 |

## Branch context

Backend membangun daftar cabang yang diizinkan dari `users.branch_id` sebagai
cabang utama/fallback dan penugasan cabang aktif pada `branch_user`. Untuk
operasi kasir yang memiliki sesi harian, `daily_stock_sessions.branch_id`
menjadi cabang operasional aktual. Satu resolver yang sama digunakan oleh
status sesi, stok harian, tutup sesi, katalog/ketersediaan, checkout,
pengeluaran, dan ringkasan omzet.

`branch` pada login dan profil tetap merepresentasikan cabang utama user dan
boleh `null`, atau berisi `id`, `name`, dan `code`. Field tersebut tidak berubah
menjadi cabang sesi. Android menyimpannya untuk state/tampilan, tetapi tidak
menggunakannya untuk authorization dan tidak mengirim `branch_id` pada request
operasional. Riwayat dan receipt kasir dapat membaca transaksi milik kasir pada
seluruh cabang yang masih diizinkan. Token atau sesi user lain tetap ditolak.

## Daily Stock Session

- `200` dari current-status berarti respons status dapat dibaca.
- `404` berarti tidak ada sesi aktif untuk kasir/cabang tersebut, bukan gangguan
  jaringan dan bukan status yang belum diketahui.
- Kegagalan jaringan, timeout, atau JSON rusak menghasilkan status unknown;
  checkout harus fail-closed sampai status berhasil dimuat ulang.
- Pembukaan sesi dilakukan admin melalui web. Android tidak mempunyai endpoint
  open-session.
- Status sesi dan stok harian wajib menunjuk `session_id` yang sama.
- Hanya sesi berstatus buka pada tanggal bisnis aplikasi yang digunakan; sesi
  lama yang belum tertutup tidak dipilih sebagai sesi hari ini.
- Jika data rusak menghasilkan lebih dari satu sesi valid, backend gagal aman
  dengan `409` dan tidak memilih sesi secara arbitrer.
- Stok `kg` dan `l` dikirim dalam display unit dengan desimal tetap terjaga;
  nilai dasar tetap disimpan dalam `g` dan `ml`.

## Checkout dan idempotensi

Contoh payload:

```json
{
  "payment_method_id": 1,
  "paid_amount": 15000,
  "items": [{"variant_id": 401, "qty": 1}],
  "note": null
}
```

Backend menghitung harga, subtotal, total, pemakaian stok, kembalian, dan kode
transaksi. Android tidak mengirim `branch_id` dan tidak mengganti hasil hitung
backend. Client mencegah double-submit dan tidak retry checkout secara otomatis.
Jika response timeout setelah server menyimpan transaksi, kasir harus memeriksa
riwayat sebelum mencoba kembali.

## Pagination dan format

Riwayat menggunakan paginator custom di dalam `data`: `data.data`,
`current_page`, `last_page`, `per_page`, dan `total`; URL halaman dapat nullable.
Timestamp checkout menggunakan ISO-8601. Detail/receipt juga dapat mengirim
`Y-m-d H:i:s`. Field catatan, deskripsi, branch, label, dan target tertentu
dapat nullable.

## Error envelope

| HTTP | Arti client |
| ---: | --- |
| 400 | Request tidak dapat diproses dalam bentuk yang dikirim. |
| 401 | Token tidak ada, tidak valid, atau kedaluwarsa. |
| 403 | Role/cabang tidak berwenang. |
| 404 | Resource tidak ditemukan; khusus current-status berarti tidak ada sesi aktif. |
| 409 | Konflik state bisnis; pesan backend dipertahankan bila aman. |
| 422 | Validasi request atau aturan bisnis gagal. |
| 429 | Request terlalu sering. |
| 500-599 | Gangguan server; detail internal tidak ditampilkan. |

Fixture Android berada di `app/src/test/resources/contracts` pada repository
Android. Perubahan kontrak harus dimulai dari test backend, lalu fixture Android
dan test mapping diperbarui. Jangan memasukkan token, password, atau data
production ke fixture.
