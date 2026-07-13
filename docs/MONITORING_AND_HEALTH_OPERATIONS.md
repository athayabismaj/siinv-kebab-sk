# Monitoring dan Health Operasional

Dokumen ini menjelaskan pemeriksaan kesiapan aplikasi SiInv Kebab SK tanpa layanan monitoring eksternal. Health check tidak menggantikan monitoring infrastruktur, tetapi menyediakan sinyal aman untuk deployment dan penanganan insiden awal.

## Tiga Jenis Pemeriksaan

| Jenis | Tujuan | Detail yang dikembalikan |
| --- | --- | --- |
| Liveness | Memastikan aplikasi PHP dapat merespons. | Tidak ada detail dependency. |
| Readiness | Menentukan apakah aplikasi layak menerima traffic bisnis. | Status saja. |
| Diagnostics | Membantu operator mendiagnosis komponen yang bermasalah. | Detail aman melalui Artisan CLI. |

## Endpoint

| Endpoint | Autentikasi | Respons | Kode HTTP |
| --- | --- | --- | --- |
| `GET /up` | Tidak perlu | Endpoint liveness bawaan Laravel. | `200` bila aplikasi merespons. |
| `GET /health/ready` | Tidak perlu | `{ "status": "healthy" | "degraded" | "unhealthy" }` | `200` untuk `healthy`/`degraded`, `503` untuk `unhealthy`. |

Kedua endpoint memakai `Cache-Control: no-store`. Endpoint publik tidak mengembalikan exception, SQLSTATE, nama koneksi database, path server, nama file ekspor, payload job, token, atau stack trace.

`degraded` tetap `200` karena aplikasi masih dapat melayani sebagian traffic. `unhealthy` memakai `503` karena dependency kritis gagal atau mode multi-server scheduler tidak aman.

## Status dan Komponen

| Check | Gagal menjadi | Metode | Threshold awal |
| --- | --- | --- | --- |
| Application | `unhealthy` bila agregat lain kritis | Respons service | Tidak ada |
| Database | `unhealthy` | `SELECT 1` ringan | Tidak ada |
| Cache | `degraded` | Probe key acak, TTL singkat, lalu cleanup | Tidak ada |
| Queue dan export | `degraded` | Tabel queue/failed jobs/export, umur antrean, stale processing | Lihat konfigurasi health |
| Private storage | `degraded` | Probe write/read/delete pada disk lokal privat | Tidak ada |
| Scheduler heartbeat | `degraded` | Timestamp cache scheduler | Stale setelah 180 detik |
| Scheduler lock | `unhealthy` hanya jika multi-server diminta tetapi lock tidak siap | `SchedulerLockConfiguration` | Tidak ada |
| Disk | `degraded`/`unhealthy` | Persentase ruang bebas jika platform mendukung | 10% / 5% |

Queue kosong bukan bukti worker sehat. Sistem hanya melaporkan gejala operasional: antrean tua, failed jobs, export `PENDING` terlalu lama, export `PROCESSING` stale, atau export selesai yang filenya hilang.

## Konfigurasi

Nilai berikut tersedia di `.env` melalui `config/health.php`:

```dotenv
HEALTH_QUEUE_OLDEST_PENDING_WARNING_SECONDS=300
HEALTH_FAILED_JOBS_WARNING=1
HEALTH_EXPORT_PENDING_WARNING_SECONDS=900
HEALTH_EXPORT_STALE_PROCESSING_SECONDS=300
HEALTH_EXPORT_FILE_CHECK_LIMIT=20
HEALTH_SCHEDULER_HEARTBEAT_STALE_SECONDS=180
HEALTH_SCHEDULER_HEARTBEAT_TTL_SECONDS=600
HEALTH_DISK_WARNING_FREE_PERCENT=10
HEALTH_DISK_CRITICAL_FREE_PERCENT=5
```

Threshold failed job adalah peringatan sederhana. Satu failure lama tidak membuktikan worker mati; operator tetap perlu melihat waktu failure, retry, dan antrean terkait melalui command diagnostics.

## Scheduler Heartbeat

Scheduler menjalankan `system:heartbeat` setiap menit. Command ini hanya menyimpan timestamp cache dengan TTL 10 menit dan tidak menyentuh data bisnis. Readiness menganggap heartbeat stale setelah 180 detik untuk memberi toleransi cron yang terlambat satu sampai dua menit.

Konfigurasi multi-server tetap mengikuti [SCHEDULER_OPERATIONS.md](SCHEDULER_OPERATIONS.md). Saat `SCHEDULER_MULTI_SERVER=true`, seluruh node harus memakai cache bersama yang mendukung atomic lock. Cache `file` atau `array` membuat readiness `unhealthy` pada mode tersebut.

## Diagnostics Operator

Jalankan command dari host aplikasi oleh operator yang memiliki akses shell:

```bash
php artisan system:diagnose
php artisan system:diagnose --json
php artisan system:diagnose --no-write-probe
php artisan exports:diagnose
php artisan scheduler:diagnose
```

`system:diagnose` memakai service health yang sama dengan readiness. Opsi `--no-write-probe` berguna untuk inspeksi non-invasif; status storage menjadi `degraded` karena write probe sengaja dilewati.

| Exit code | Arti |
| ---: | --- |
| `0` | `healthy` |
| `1` | `degraded` dan perlu perhatian operator |
| `2` | `unhealthy` dan tidak layak menerima traffic bisnis |

Output hanya berisi nama komponen, status, pesan aman, durasi, dan metadata seperti count atau umur dalam detik. Tidak ada payload queue, filename, path internal, credential, atau stack trace.

## Structured Logging dan Request ID

Setiap respons menerima header `X-Request-ID`. Header masuk dipakai hanya jika panjang dan formatnya aman; selain itu aplikasi membuat UUID baru. Nilai ini dimasukkan ke context log global dan dikembalikan pada respons agar operator dapat menghubungkan laporan client dengan log server.

Log operasi menggunakan field yang relevan saja: `request_id`, `operation`, `user_id`, `branch_id`, `generated_export_id`, `export_type`, `queue`, `scheduler_event`, `command`, `duration_ms`, dan `result`. Aplikasi tidak mencatat password, token, isi session, payload request penuh, payload job, atau SQL error mentah pada log operasional baru.

## Smoke Test Deployment

Setelah deploy dan migration normal aplikasi selesai, jalankan:

```bash
curl -fsS https://HOST/up
curl -fsS https://HOST/health/ready
php artisan system:heartbeat
php artisan system:diagnose --json
php artisan scheduler:diagnose
```

Jangan menjalankan `migrate:fresh`, `cache:clear`, atau menghapus lock scheduler sebagai prosedur diagnosis. Untuk mode multi-server, verifikasi `SCHEDULER_MULTI_SERVER=true`, store cache bersama, serta tabel `cache` dan `cache_locks` sebelum mengaktifkan cron pada semua node.

## Tanggap Insiden

1. **Readiness `unhealthy`**: jalankan `system:diagnose --json`, lalu periksa database dan scheduler lock terlebih dahulu. Jangan arahkan traffic bisnis baru ke node tersebut sampai check kritis pulih.
2. **Readiness `degraded`**: periksa failed jobs, umur antrean, stale export, heartbeat scheduler, storage, dan disk. Aplikasi dapat tetap melayani traffic, tetapi kondisi harus ditangani.
3. **Heartbeat stale**: periksa cron atau service `schedule:run`, lalu `scheduler:diagnose`. Jangan menjalankan task bisnis ulang hanya untuk melewati lock.
4. **Storage gagal**: pastikan disk privat writable dan kapasitas cukup. Probe otomatis selalu dihapus, termasuk ketika write/read gagal.
5. **Export stale atau file hilang**: jalankan `exports:diagnose`, periksa worker dan riwayat export, lalu gunakan retry workflow yang tersedia. Jangan mengubah status database secara manual.
6. **Disk rendah**: tambah kapasitas atau bersihkan artefak sesuai prosedur deployment. Jangan menghapus storage privat secara massal.

## Batasan yang Diketahui

- Belum ada Prometheus, Grafana, Sentry, alert otomatis, atau agregasi log eksternal.
- Health queue tidak dapat membuktikan worker aktif saat antrean kosong.
- Metrik disk bergantung dukungan platform dan hanya bersifat informasional bila API platform tidak tersedia.
- Scheduler, queue worker, cache bersama, sinkronisasi jam, backup, restore, object storage, dan benchmark hardware tetap perlu divalidasi pada environment production.
- Dokumentasi ini tidak menggantikan runbook provider hosting atau kebijakan backup produksi.
