# Operasional Scheduler

Dokumen ini menjelaskan scheduler Laravel untuk SiInv Kebab SK. Scheduler memakai timezone aplikasi `Asia/Jakarta`; jadwal bisnis seperti auto-close jam 04:00 dan cleanup ekspor jam 03:35 selalu mengikuti timezone tersebut.

## Event Terjadwal

| Event | Jadwal | Guard overlap | Satu server saat multi-server aktif |
| --- | --- | ---: | ---: |
| `system-heartbeat` | tiap menit | 2 menit | Ya |
| `sales-summary-current` | tiap 15 menit | 10 menit | Ya |
| `sales-summary-rebuild` | 02:10 | 30 menit | Ya |
| `traffic-alert-check` | tiap 5 menit | 10 menit | Tidak |
| `daily-stock-integrity-audit` | 03:20 | 20 menit | Ya |
| `environment-doctor` | 03:10 | 10 menit | Tidak |
| `daily-stock-auto-close` | 04:00 | 30 menit | Ya |
| `exports-cleanup` | 03:35 | 10 menit | Ya |
| `backup-daily` | 01:00 | 180 menit | Ya |
| `backup-weekly` | Senin 02:00 | 180 menit | Ya |
| `backup-monthly` | tanggal 1, 03:00 | 180 menit | Ya |

`withoutOverlapping()` mencegah eksekusi berikutnya pada event yang sama selama lock masih aktif. `onOneServer()` adalah pemilihan satu instance server untuk event kritis. Keduanya dipakai untuk tujuan berbeda dan tidak saling menggantikan.

Ringkasan penjualan aman dijalankan ulang karena disimpan per cabang dan tanggal dengan unique constraint. Auto-close tetap memeriksa status `open` dan action penutup sesi mengunci row. Cleanup ekspor hanya menyentuh record terminal yang sudah kedaluwarsa. Lock scheduler melindungi dari duplikasi lintas proses, bukan menggantikan aturan idempotensi tersebut.

## Konfigurasi Lock

Untuk satu server, konfigurasi default dapat tetap menggunakan cache lokal. Untuk beberapa server, semua node wajib memakai cache bersama yang mendukung atomic lock. Konfigurasi yang direkomendasikan saat database PostgreSQL bersama tersedia:

```dotenv
CACHE_STORE=database
SCHEDULER_CACHE_STORE=database
SCHEDULER_MULTI_SERVER=true
```

Jalankan migration terlebih dahulu agar tabel `cache` dan `cache_locks` tersedia. Jangan mengaktifkan `SCHEDULER_MULTI_SERVER=true` dengan `file` atau `array`: keduanya tidak dibagikan antarserver. Store Redis, Memcached, dan DynamoDB hanya boleh dipakai bila infrastrukturnya memang sudah tersedia dan dipakai bersama seluruh node; fase ini tidak memasang Redis atau Horizon.

Ketika `SCHEDULER_MULTI_SERVER=false`, aplikasi tetap memakai anti-overlap lokal, tetapi event tidak mengklaim aman lintas server. Dalam kondisi itu, aktifkan cron hanya pada satu scheduler node.

## Cron Dan Model Deployment

Gunakan satu entry cron per node, dijalankan oleh user aplikasi non-root:

```cron
* * * * * cd {{APP_PATH}} && {{PHP_BINARY}} artisan schedule:run >> /dev/null 2>&1
```

Pilihan deployment:

1. **Single scheduler node**: cron hanya di satu server. Ini sederhana tetapi scheduler menjadi titik kegagalan tunggal.
2. **Cron di semua node**: aktifkan `SCHEDULER_MULTI_SERVER=true` hanya setelah seluruh node memakai database cache bersama atau store bersama lain yang tervalidasi. Event kritis kemudian memilih satu node dengan `onOneServer()`.

`schedule:work` cocok untuk pengembangan lokal. Production umumnya memakai cron `schedule:run` tiap menit agar restart proses deployment tidak menahan scheduler lama.

## Diagnosis Dan Incident Response

Pemeriksaan aman dan read-only:

```bash
php artisan schedule:list
php artisan scheduler:diagnose
php artisan system:diagnose
```

`scheduler:diagnose` melaporkan timezone, store lock, kesiapan multi-server, tabel database cache, jumlah event, dan daftar event kritis. Command tidak mencetak connection string atau secret.

`system:heartbeat` adalah event observability ringan, bukan jadwal bisnis. Ia menyimpan timestamp cache agar readiness dapat mendeteksi scheduler yang tidak berjalan. Detail health dan exit code diagnosis didokumentasikan di [MONITORING_AND_HEALTH_OPERATIONS.md](MONITORING_AND_HEALTH_OPERATIONS.md).

Jika task terlihat tidak berjalan:

1. Periksa cron/service scheduler dan output `schedule:list`.
2. Jalankan `scheduler:diagnose` untuk memastikan store bersama serta tabel cache/lock tersedia.
3. Periksa log aplikasi untuk command dan exit code terkait.
4. Tunggu hingga masa lock event berakhir bila proses sebelumnya mungkin masih berjalan atau mati paksa.
5. Jangan menjalankan command bisnis kedua kali hanya untuk melewati lock.
6. Jangan memakai `cache:clear` atau menghapus seluruh lock sebagai prosedur normal, karena tindakan itu dapat membuat task aktif berjalan ganda.

Laravel melepaskan overlap lock setelah event selesai. Jika proses mati paksa, lock akan kedaluwarsa berdasarkan durasi pada tabel event. Pemilihan `onOneServer()` Laravel memakai mutex satu jam per waktu jadwal; sinkronisasi jam antarserver tetap menjadi prasyarat operasional.

## Rilis Aman

1. Terapkan kode dan jalankan migration aplikasi.
2. Pastikan `cache` dan `cache_locks` sudah tersedia sebelum mengaktifkan mode multi-server.
3. Verifikasi `php artisan scheduler:diagnose` pada setiap node.
4. Pasang atau perbarui cron memakai user non-root.
5. Jangan mengubah timezone aplikasi tanpa audit jadwal bisnis.

Durasi backup 180 menit adalah batas konservatif awal. Ukur durasi backup produksi dan sesuaikan bila ukuran database bertumbuh, agar lock tidak terlalu cepat kedaluwarsa maupun terlalu lama menahan jadwal berikutnya.

## Batasan

- Cache `file` hanya aman untuk satu server.
- Ketersediaan Redis/Memcached/DynamoDB tidak diverifikasi oleh aplikasi.
- Tidak ada monitoring eksternal, alert scheduler, atau pemulihan lock otomatis pada fase ini.
- Backup, queue worker, object storage, dan sinkronisasi clock lintas region tetap memerlukan validasi deployment terpisah.
