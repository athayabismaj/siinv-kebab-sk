# Operasional Queue Worker

Dokumen ini menjelaskan pengoperasian worker queue ekspor pada SiInv Kebab SK.
Dokumen ini tidak mengubah konfigurasi server secara otomatis.

## Konfigurasi Aplikasi

Ekspor berjalan terpisah pada koneksi dan antrean berikut:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=120
EXPORT_QUEUE_CONNECTION=database
EXPORT_QUEUE=exports
EXPORT_STALE_PROCESSING_SECONDS=300
```

Relasi waktu yang harus dipertahankan adalah:

```text
timeout job (60 detik) < timeout worker (75 detik) < retry_after (120 detik)
```

Worker dihentikan secara bertahap dengan batas tunggu 105 detik. Nilai ini
memberi waktu job yang sedang berjalan untuk selesai sebelum proses dipaksa
berhenti.

## Menjalankan Worker

Untuk pengembangan lokal, jalankan satu proses berikut pada terminal terpisah:

```bash
php artisan queue:work database --queue=exports --sleep=3 --tries=2 --timeout=75 --memory=192 --max-jobs=100 --max-time=3600
```

Job lama di antrean `default` tidak dipindahkan otomatis. Biarkan worker
sementara berikut menguras job tersebut saat transisi, lalu hentikan ketika
antrian kosong:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=2 --timeout=75 --memory=192 --max-jobs=100 --max-time=3600
```

Worker ekspor hanya memproses `exports`, sehingga job aplikasi lain tidak
menunda laporan besar.

## Produksi

Pilih salah satu template, jangan jalankan Supervisor dan systemd untuk worker
yang sama bersamaan:

- `deploy/supervisor/siinv-exports.conf.example`
- `deploy/systemd/siinv-exports.service.example`

Ganti seluruh placeholder `{{...}}` dengan path aplikasi, binary PHP, user
non-root, serta lokasi log yang benar. Template sengaja menggunakan satu
worker. Tambah concurrency hanya setelah benchmark dan pengamatan memori
produksi menunjukkan kapasitasnya aman.

Contoh operasi Supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl status siinv-exports:*
supervisorctl restart siinv-exports:*
```

Contoh operasi systemd:

```bash
systemctl daemon-reload
systemctl enable --now siinv-exports
systemctl status siinv-exports
systemctl restart siinv-exports
journalctl -u siinv-exports -f
```

## Rilis Aman

1. Verifikasi migration aplikasi telah dijalankan jika rilis memang membawa migration.
2. Terapkan kode dan dependency yang sudah tervalidasi.
3. Jalankan `php artisan queue:restart` agar worker lama selesai secara bertahap.
4. Reload Supervisor atau restart service systemd jika file template berubah.
5. Pastikan worker kembali berstatus aktif.
6. Jalankan `php artisan exports:diagnose` dan periksa log worker.

`queue:restart` memakai cache Laravel. Pada beberapa host cache `file` cukup
untuk satu server. Untuk banyak server, pilih cache bersama sebagai keputusan
deployment terpisah agar semua worker menerima sinyal restart. Redis tidak
dipasang oleh fase ini.

## Diagnosis, Job Gagal, dan Recovery

Pemeriksaan read-only:

```bash
php artisan exports:diagnose
php artisan queue:failed
php artisan exports:detect-stale
```

`exports:diagnose` hanya mencetak hitungan dan status kesehatan; payload job
dan filter ekspor tidak dicetak. `exports:detect-stale` juga read-only secara
default. Record `PROCESSING` dianggap stale setelah 300 detik dan tetap aman
karena command menandai gagal hanya jika tidak ada job `exports` yang masih
tertunda untuk record tersebut.

Jika operator sudah memeriksa bahwa proses tidak berjalan dan record yang
tercantum memang aman dipulihkan, gunakan ID secara eksplisit:

```bash
php artisan exports:detect-stale --mark-failed --id=123
```

Command tersebut tidak menjadwalkan ulang job. Pengguna dapat memakai alur
retry ekspor yang sudah ada setelah status menjadi `FAILED`.

Untuk job gagal, identifikasi dulu melalui `php artisan queue:failed`, lalu
retry hanya job yang sudah dipahami:

```bash
php artisan queue:retry <uuid-atau-id>
```

Jangan memakai `queue:flush` sebagai pemulihan umum karena tindakan itu
menghapus seluruh riwayat failed job. Hapus satu failed job hanya sesudah
penyebabnya terdokumentasi dan tidak lagi dibutuhkan untuk audit.

## Batasan

- Queue database cocok untuk beban kecil hingga menengah; throughput dan lock
  PostgreSQL perlu diuji sebelum menaikkan jumlah worker.
- Restart worker tidak menyelesaikan proses yang macet tanpa pemeriksaan stale
  record.
- Job ekspor tetap memiliki batas percobaan dua kali dan backoff 60 detik.
- Monitoring eksternal, Redis, Horizon, queue prioritas lain, dan multi-server
  orchestration berada di luar Fase 4A.
