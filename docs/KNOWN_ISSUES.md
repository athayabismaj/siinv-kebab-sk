# Known Issues

| ID | Severity | Kondisi | Dampak | Tindak lanjut |
| --- | --- | --- | --- | --- |
| DEPLOY-001 | Major | Migration export dan cache scheduler masih pending pada database aplikasi lokal yang diaudit. | `deploy:check` akan menolak deployment sampai struktur tabel target lengkap. | Jalankan migration pada environment target melalui prosedur deployment terkontrol, lalu ulangi `deploy:check`. |
| QA-POSTGRES-001 | Medium | Suite PHPUnit default menggunakan SQLite, sedangkan sejumlah query produksi memakai karakteristik PostgreSQL seperti agregasi tanggal dan lock. | Perbedaan engine tidak dapat dibuktikan hanya dengan suite default. | Jalankan integration drill PostgreSQL pada database disposable sebelum release PostgreSQL. |
| QA-TEST-001 | Low | File test ekspor yang dijalankan sebagai proses PHP paralel memakai lokasi storage lokal sementara yang dapat sama. | Artefak test dapat saling terlihat sehingga assertion file gagal secara non-deterministik. | Jalankan test ekspor secara serial; perubahan ini tidak memengaruhi workflow ekspor aplikasi. |

Tidak ada isu fungsional lain yang dicatat tanpa reproduksi atau bukti test.

## Pembaruan Fase 5B

| ID | Severity | Kondisi | Dampak | Tindak lanjut |
| --- | --- | --- | --- | --- |
| QA-POSTGRES-002 | Info | Core checkout, void, stock, session, summary, dan constraint telah diuji pada PostgreSQL disposable. | Suite default masih tidak menjalankan PostgreSQL otomatis. | Jalankan `tests/Integration/PostgreSqlConcurrencyTest.php` sebelum rilis PostgreSQL. |
| QA-TEST-002 | Info | Laravel parallel runner belum tersedia tanpa `brianium/paratest`. | Regresi penuh `--parallel --processes=2` belum dapat dibuktikan di repository ini. | Tambahkan dependency hanya melalui keputusan toolchain terpisah. |

## Pembaruan Fase 5C

| ID | Severity | Kondisi | Dampak | Tindak lanjut |
| --- | --- | --- | --- | --- |
| ANDROID-001 | Medium | Checkout tidak mempunyai idempotency key client dan response dapat timeout setelah transaksi tersimpan. | Kasir dapat melihat hasil ambigu bila langsung mengulang checkout. | Periksa riwayat transaksi sebelum submit ulang; desain idempotency diputuskan pada fase terpisah. |
| ANDROID-002 | Info | Logout tanpa jaringan membersihkan session lokal, tetapi request revocation backend dapat gagal. | Token server tetap hidup sampai expiry/revocation lain meskipun device sudah logout. | Dokumentasikan risiko dan pertimbangkan revocation retry terkontrol pada fase terpisah. |
| ANDROID-003 | Info | Level 3 dan printer fisik belum dibuktikan oleh unit test. | Variasi device, jaringan nyata, process death, dan printer belum tervalidasi. | Jalankan UAT device/emulator dan printer pada Fase 5D. |
