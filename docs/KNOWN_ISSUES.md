# Known Issues

| ID | Severity | Kondisi | Dampak | Tindak lanjut |
| --- | --- | --- | --- | --- |
| DEPLOY-001 | Major | Migration export dan cache scheduler masih pending pada database aplikasi lokal yang diaudit. | `deploy:check` akan menolak deployment sampai struktur tabel target lengkap. | Jalankan migration pada environment target melalui prosedur deployment terkontrol, lalu ulangi `deploy:check`. |
| QA-POSTGRES-001 | Medium | Suite PHPUnit default menggunakan SQLite, sedangkan sejumlah query produksi memakai karakteristik PostgreSQL seperti agregasi tanggal dan lock. | Perbedaan engine tidak dapat dibuktikan hanya dengan suite default. | Jalankan integration drill PostgreSQL pada database disposable sebelum release PostgreSQL. |
| QA-TEST-001 | Low | File test ekspor yang dijalankan sebagai proses PHP paralel memakai lokasi storage lokal sementara yang dapat sama. | Artefak test dapat saling terlihat sehingga assertion file gagal secara non-deterministik. | Jalankan test ekspor secara serial; perubahan ini tidak memengaruhi workflow ekspor aplikasi. |

Tidak ada isu fungsional lain yang dicatat tanpa reproduksi atau bukti test.
