# PostgreSQL Backup/Restore Staging Test

Dokumen ini dipakai untuk menguji fitur backup/restore pada PostgreSQL staging atau local PostgreSQL. Jangan menjalankan restore ke database production.

## Prasyarat

- [ ] `APP_ENV` bukan `production`.
- [ ] Database yang dipakai adalah staging/local PostgreSQL.
- [ ] `PG_DUMP_PATH` mengarah ke binary `pg_dump` yang valid atau tersedia di `PATH`.
- [ ] `PG_RESTORE_PATH` mengarah ke binary `pg_restore` yang valid atau tersedia di `PATH`.
- [ ] User database staging memiliki izin backup dan restore pada database staging.
- [ ] Developer login menggunakan role `developer`.

## Skenario Uji

- [ ] Buka halaman developer backup.
- [ ] Jalankan backup manual.
- [ ] Pastikan backup tersimpan di `storage/app/private/backups`.
- [ ] Jalankan `pg_restore --list <file.backup>` terhadap file hasil backup.
- [ ] Upload file `.txt`; hasil harus ditolak.
- [ ] Upload file `.sql`; hasil harus ditolak untuk upload manual.
- [ ] Upload file `.zip`; hasil harus ditolak.
- [ ] Upload file `.backup` rusak; hasil harus ditolak dengan pesan generik.
- [ ] Upload file `.dump` rusak; hasil harus ditolak dengan pesan generik.
- [ ] Restore file valid tanpa mengetik `RESTORE`; hasil harus ditolak.
- [ ] Restore file valid dengan konfirmasi `RESTORE`; restore hanya dilakukan pada database staging/local.
- [ ] Pastikan error browser tidak menampilkan path server, command, username database, atau output internal.
- [ ] Pastikan detail teknis kegagalan tercatat di `storage/logs`.
- [ ] Pastikan file backup tidak bisa diakses langsung dari URL publik.

## Catatan Hasil

Isi bagian ini saat pengujian staging dilakukan.

- Tanggal uji awal: 2026-07-04
- Environment awal: local Codex workspace
- Database target: belum tersedia sebagai staging PostgreSQL
- Versi PostgreSQL: belum diverifikasi
- Versi `pg_dump`: belum tersedia di `PATH`
- Versi `pg_restore`: belum tersedia di `PATH`
- Hasil backup manual: belum dijalankan karena binary PostgreSQL tidak tersedia di `PATH`
- Hasil `pg_restore --list`: belum dijalankan karena binary PostgreSQL tidak tersedia di `PATH`
- Hasil restore valid: belum dijalankan, harus dilakukan di staging/local PostgreSQL, bukan production
- Hasil uji file rusak/non-backup: tercakup oleh automated test upload restore non-backup/rusak
- Catatan log: perlu diverifikasi saat staging PostgreSQL tersedia
- Kesimpulan: kode sudah menolak file rusak/non-backup dan membutuhkan konfirmasi `RESTORE`; validasi backup/restore PostgreSQL asli masih harus dijalankan pada staging dengan `pg_dump` dan `pg_restore` tersedia.
