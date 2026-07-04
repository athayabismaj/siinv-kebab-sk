# Security Deployment Checklist

Gunakan checklist ini sebelum deploy atau audit production. Jangan simpan credential, dump, backup, atau log ke repository.

## Environment

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` memakai domain production yang benar.
- [ ] HTTPS aktif dan redirect HTTP ke HTTPS berjalan.
- [ ] `APP_FORCE_HTTPS=true` atau reverse proxy/server sudah memaksa HTTPS.
- [ ] Cookie/session secure aktif jika aplikasi dilayani lewat HTTPS (`SESSION_SECURE_COOKIE=true`).
- [ ] `SESSION_HTTP_ONLY=true`.
- [ ] `SESSION_SAME_SITE=lax` atau `strict` sesuai kebutuhan integrasi.
- [ ] `SESSION_DOMAIN` tidak terlalu luas dan tidak memakai wildcard domain.
- [ ] Credential database tidak pernah masuk repository, screenshot, atau dokumen publik.
- [ ] `.env` tidak berada di folder public dan tidak bisa diunduh dari web.
- [ ] `php artisan config:cache` dan `php artisan route:cache` dijalankan setelah env production final.
- [ ] `php artisan view:cache` dijalankan setelah deployment view final.

## File Sensitif

- [ ] `storage/logs` tidak bisa diakses publik.
- [ ] File backup database tidak berada di `public/`.
- [ ] File restore/upload backup berada di storage private.
- [ ] Folder `public/storage` hanya berisi file yang memang boleh publik.
- [ ] Tidak ada file `.env`, `.log`, `.sql`, `.dump`, `.backup`, `.zip`, atau arsip database di `public/`.
- [ ] Download backup hanya lewat route developer yang memakai middleware `auth` dan `role:developer`.
- [ ] Export laporan hanya bisa diakses role yang memang sah.

## Permission Server

- [ ] Permission `storage/` dan `bootstrap/cache/` cukup untuk aplikasi menulis, tetapi tidak world-writable jika tidak diperlukan.
- [ ] Owner/group file aplikasi sesuai user web server.
- [ ] Backup database dibuat di lokasi non-public.
- [ ] File backup lama punya retention policy dan tidak menumpuk tanpa batas.
- [ ] Laravel scheduler aktif jika backup otomatis dipakai.
- [ ] Queue worker aktif jika fitur queue digunakan.

## Route Dan Role

- [ ] Route developer/debug tidak bisa diakses role non-developer.
- [ ] Route backup, download backup, upload restore, dan restore database memakai middleware developer.
- [ ] Endpoint API sensitif memakai token dan role guard yang sesuai.
- [ ] Endpoint auth dan endpoint API tulis memakai rate limit.
- [ ] `php artisan route:list` diperiksa setelah deploy untuk memastikan route sensitif tidak terbuka.
- [ ] Route backup/restore developer diuji tidak dapat diakses admin, owner, kasir, atau guest.

## API Dan Browser Security

- [ ] `CORS_ALLOWED_ORIGINS` hanya berisi domain frontend/web production yang sah.
- [ ] CORS tidak memakai wildcard `*` untuk origin production.
- [ ] `CORS_SUPPORTS_CREDENTIALS=false` kecuali benar-benar diperlukan dan origin sudah spesifik.
- [ ] Header reverse proxy tidak mematikan HTTPS detection.
- [ ] Response error production tidak menampilkan stack trace, path server, command, username DB, atau credential.
- [ ] Laravel log menangkap detail error internal untuk investigasi server-side.
- [ ] Security header dasar aktif: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, dan `Permissions-Policy`.
- [ ] CSP belum kompleks kecuali sudah diuji penuh pada semua halaman.

## Exception Handling

- [ ] `APP_DEBUG=false` sudah diverifikasi langsung di production.
- [ ] Error internal web panel tampil generik dan detailnya masuk `storage/logs`.
- [ ] Controller web/admin/owner/developer tidak menampilkan `$e->getMessage()` mentah ke user.
- [ ] Tidak ada `dd()`, `dump()`, atau `var_dump()` tertinggal di controller production.
- [ ] Error validasi input tetap informatif dan tidak dicampur dengan error internal.

## Dependency Dan Repository

- [ ] `composer install --no-dev --optimize-autoloader` digunakan untuk production.
- [ ] `composer audit` dijalankan dan hasilnya didokumentasikan.
- [ ] `composer outdated --direct` dijalankan untuk melihat package direct dependency yang perlu patch/minor update.
- [ ] Update major version tidak dilakukan tanpa analisis kompatibilitas.
- [ ] `.gitignore` melindungi `.env`, log, backup, dump, restore upload, dan file credential.
- [ ] Backup, dump, log, dan file restore tidak ikut commit.
- [ ] Dependency lock file direview sebelum deploy.

## Backup/Restore PostgreSQL Staging

- [ ] Pengujian backup/restore dilakukan di staging/local PostgreSQL, bukan production.
- [ ] `pg_dump` berhasil membuat file backup valid di `storage/app/private/backups`.
- [ ] `pg_restore --list` berhasil membaca file backup valid.
- [ ] Restore file valid hanya berjalan setelah konfirmasi `RESTORE`.
- [ ] Upload `.txt`, `.sql`, `.zip`, `.backup` rusak, dan `.dump` rusak ditolak dengan pesan generik.
- [ ] Restore error tidak menampilkan path server, command, username database, atau output internal ke browser.
- [ ] Detail kegagalan restore tercatat di log server.
- [ ] File backup tidak bisa diakses langsung melalui URL publik.

## Validasi Pasca Deploy

- [ ] Login role owner, admin, kasir, dan developer diuji sesuai hak akses.
- [ ] Role non-developer tidak bisa membuka halaman backup.
- [ ] Upload restore file non-backup ditolak.
- [ ] Download backup tanpa login/role developer ditolak.
- [ ] Reset password dan change password mencabut token API lama.
- [ ] Kasir tidak bisa void transaksi kasir lain.
- [ ] Kasir tidak bisa void transaksi dengan session id salah.
- [ ] Void transaction mencatat `voided_by`, `voided_at`, dan `void_reason`.
- [ ] Full regression test atau smoke test production-safe selesai tanpa error.
