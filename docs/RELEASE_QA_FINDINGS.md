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
