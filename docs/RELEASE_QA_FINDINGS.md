# Release QA Findings

Tanggal audit: 14 Juli 2026  
Baseline: `175 passed`, `938 assertions`

| ID | Severity | Temuan | Bukti | Status |
| --- | --- | --- | --- | --- |
| QA-001 | Major | Database aplikasi lokal masih memiliki migration queue/cache scheduler yang belum diterapkan, sehingga `deploy:check` belum lulus pada environment ini. | Preflight Fase 4D mendeteksi migration `2026_07_14_000000_create_generated_exports_table` dan `2026_07_14_010000_create_cache_tables_for_scheduler_locks` masih pending. | Open — lakukan migrasi terkontrol pada target rilis. |
| QA-002 | Info | Suite default memakai SQLite in-memory; validasi PostgreSQL tidak otomatis menjadi bagian dari `php artisan test`. | `phpunit.xml` menetapkan `DB_CONNECTION=sqlite`; drill PostgreSQL backup/restore tersedia sebagai integration test terpisah. | Open — jalankan drill disposable sebelum rilis PostgreSQL. |
| QA-003 | Low | Menjalankan beberapa file test ekspor sebagai proses PHP paralel dapat membuat folder storage lokal sementara saling bertabrakan. | Rerun serial untuk `UsageAndDailyStockExportResilienceTest` dan `ExpenseAndSalesExportResilienceTest` lulus. | Open — jalankan regresi ekspor secara serial sampai isolasi storage per proses dirancang terpisah. |

## Hasil regresi bisnis

Tidak ada kegagalan test pada baseline. Alur checkout, void, sesi stok, ringkasan
penjualan per cabang, branch authorization, export, scheduler, dan backup sudah
memiliki coverage otomatis. Test workflow tambahan pada Fase 5A memperluas bukti
integrasi transfer → checkout → void-restok → tutup sesi dan isolasi dua cabang.

## Kriteria rilis

Rilis backend dapat dinilai setelah:

1. `deploy:check` lulus pada konfigurasi target.
2. Migration pending diterapkan melalui prosedur deployment yang disetujui.
3. Suite penuh dan test browser manual pada matriks `REL-*` lulus.
4. Jika target menggunakan PostgreSQL, drill aman memakai database disposable lulus.

## Pembaruan Fase 5B - 14 Juli 2026

Baseline sebelum perubahan Fase 5B adalah `177 passed`, `963 assertions`.
Suite `tests/Integration/PostgreSqlConcurrencyTest.php` memverifikasi PostgreSQL
disposable dengan dua proses PHP untuk checkout, transfer, void, restok, sesi,
summary, constraint, dan query-count checkout.

| ID | Severity | Temuan | Bukti | Status |
| --- | --- | --- | --- | --- |
| QA-004 | Info | Suite default tetap memakai SQLite. | PostgreSQL core diuji terpisah pada database dengan prefix `siinv_fase5b_test_`. | Open - jalankan suite PostgreSQL sebelum rilis PostgreSQL. |
| QA-005 | Info | Runner `php artisan test --parallel` belum dapat dipakai. | Collision Laravel membutuhkan `brianium/paratest` 7.x, sedangkan dependency tidak dipasang. | Open - keputusan toolchain terpisah; Fase 5B tidak menambah dependency. |
| QA-006 | Low | Proses PHP mandiri tanpa Laravel ParallelTesting dapat berbagi fake storage. | `Storage::fake()` menambahkan token hanya saat runner Laravel parallel aktif. | Open - jalankan regresi ekspor serial sampai runner resmi tersedia. |

## Pembaruan Fase 5C - 15 Juli 2026

Contract test khusus SIPOS Android ditambahkan untuk auth/logout, branch nullable,
profil, menu, metode pembayaran, sesi stok, checkout, receipt, riwayat kosong,
dan unauthorized response. Audit client menemukan dua bug kontrak: branch tidak
dipertahankan setelah update profil dan logout Android tidak mencabut token
backend. Keduanya diperbaiki dengan perubahan minimal dan test reproduksi.

Endpoint lama `POST /sessions/{id}/close` terbukti tidak memiliki pemanggil UI
aktif dan dihapus dari Android. Endpoint aktif tetap
`POST /daily-stock-sessions/close`. Tidak ada endpoint, schema, formula, atau
scope cabang backend yang diubah.
