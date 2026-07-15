# Kontrak API SIPOS Android

Dokumen ini membekukan kontrak yang dipakai aplikasi `sipos-kebab-sk` pada Fase
5C. Sumber kebenaran adalah `routes/api.php`, controller API, contract test
Laravel di `tests/Feature/API/Android`, serta Retrofit service Android.

Semua endpoint selain login/reset password memakai header:

```text
Authorization: Bearer <token>
Accept: application/json
```

Envelope sukses umumnya `{success: boolean, message: string, data: object|array}`.
Validasi memakai HTTP 422 dan `data.errors`. Token hilang/tidak sah memakai 401.
Hak akses cabang tetap ditegakkan backend; Android tidak mengirim `branch_id`
untuk memilih cabang transaksi.

## Matriks endpoint

| Flow | HTTP | Endpoint | Request utama | Response utama | Auth | Status sukses |
| --- | --- | --- | --- | --- | --- | ---: |
| Login | POST | `/api/auth/login` | `username:string`, `password:string`, `device_name?:string` | token, expiry, user, role, `branch?` | Tidak | 200 |
| Logout | POST | `/api/auth/logout` | - | success, message | Ya | 200 |
| Profil | GET | `/api/auth/me` | - | user, role, `branch?` | Ya | 200 |
| Ubah profil | PUT | `/api/auth/profile` | name, username, email | user, role, `branch?` | Ya | 200 |
| Menu | GET | `/api/menus` | - | user, menus dan variants | Ya | 200 |
| Metode bayar | GET | `/api/payment-methods` | - | user, payment_methods | Ya | 200 |
| Status sesi | GET | `/api/sessions/current-status` | - | active dan data sesi | Ya | 200/404 |
| Stok sesi | GET | `/api/daily-stock-items` | - | `session_id?:integer`, items | Ya | 200 |
| Tutup sesi | POST | `/api/daily-stock-sessions/close` | `remaining:object`, `notes?:string` | success, message | Kasir | 200 |
| Checkout | POST | `/api/transactions` | payment_method_id, paid_amount, items, note? | transaksi dan nominal receipt | Kasir | 201 |
| Riwayat | GET | `/api/transactions` | `date?:Y-m-d`, `page?:integer` | paginator transaksi | Ya | 200 |
| Detail | GET | `/api/transactions/{reference}` | ID atau kode | transaksi dan items | Ya | 200 |
| Receipt | GET | `/api/transactions/{reference}/receipt` | ID atau kode | detail receipt | Ya | 200 |
| Void | POST | `/api/transactions/{id}/void` | reason, current_session_id, idempotency_key | new_drawer_balance | Kasir | 200 |
| Pendapatan | GET | `/api/revenue/summary` | `date?:Y-m-d` | total_revenue, total_count, target | Ya | 200 |
| Tren | GET | `/api/revenue/trend` | `date?:Y-m-d` | daftar date dan total_revenue | Ya | 200 |
| Pengeluaran | POST | `/api/cashflow/expenses` | amount, source, note? | success, message | Kasir | 200/201 |

## Tipe dan nullable

- ID, quantity, page, dan count adalah integer. Nilai uang dikirim sebagai
  integer JSON dan dipetakan menjadi Kotlin `Long`.
- `branch` dapat `null` pada profil. Jika ada, field stabilnya adalah
  `id:integer`, `name:string`, dan `code:string`.
- `note`, beberapa label, target, dan nilai pertumbuhan dapat nullable.
- Timestamp checkout adalah string ISO-8601. Detail receipt menerima string
  `Y-m-d H:i:s`; label riwayat adalah string tampilan Indonesia.
- Status riwayat adalah string tampilan backend (contoh `Sukses`/`Void`).
  Detail receipt mengirim status kanonis seperti `SUCCESS` atau `VOID`.
- Paginator riwayat berada pada `data.current_page`, `data.last_page`, dan
  `data.data`. Android tidak mengubah format ini.

## Checkout

Request Android:

```json
{
  "payment_method_id": 1,
  "paid_amount": 15000,
  "items": [{"variant_id": 401, "qty": 1}]
}
```

`note` opsional dan dihilangkan Gson ketika null. Tidak ada `branch_id`; backend
menentukan cabang dari token kasir. Response 201 memuat `transaction_id`,
`transaction_code`, `created_at`, `payment_method`, items, `total_amount`,
`paid_amount`, dan `change_amount`. Android menyimpan nominal inti sebagai Long;
detail item receipt dapat diambil dari endpoint detail/receipt.

Checkout memiliki `Mutex` dan `isSubmitting`; client tidak melakukan retry
otomatis. Jika server menyimpan transaksi tetapi response timeout, hasilnya
ambigu dan kasir harus memeriksa riwayat sebelum mencoba kembali.

## Error

| HTTP | Arti client |
| ---: | --- |
| 401 | Token hilang/kedaluwarsa; login ulang. |
| 403 | Role atau cabang tidak berwenang. |
| 404 | Resource/sesi tidak ditemukan. |
| 409 | Konflik bisnis bila endpoint menggunakannya. |
| 422 | Payload atau aturan bisnis tidak valid. |
| 429 | Terlalu banyak request. |
| 500-599 | Gangguan layanan; detail internal tidak ditampilkan. |

## Sinkronisasi fixture

Fixture aman berada di `E:/sipos-kebab-sk/app/src/test/resources/contracts`.
Setiap perubahan kontrak harus dimulai dari contract test Laravel, lalu fixture
diperbarui dari response fiktif test tersebut, dan terakhir test Android
dijalankan. Token, password, dan data production dilarang masuk fixture.
