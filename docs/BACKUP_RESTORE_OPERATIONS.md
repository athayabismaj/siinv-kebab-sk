# Backup and Restore Operations

Database backups are PostgreSQL custom-format archives stored only on the private `local` disk. Each published archive has an adjacent JSON manifest containing its SHA-256 checksum, size, format, and lifecycle timestamps. The manifest does not contain credentials or connection strings.

## Backup

Run `php artisan backup:database --type=manual`. The command writes to a temporary directory, rejects a failed or empty dump, creates the manifest, then publishes the complete artifact. Scheduled commands remain configured in `routes/console.php`.

Run `php artisan backup:prune` first to review retention candidates. Only run `php artisan backup:prune --delete` after reviewing the output. Retention considers only valid manifest-backed artifacts and never removes legacy backup files. Current values (daily 7, weekly 4, monthly 12) are operational defaults, not a recovery SLA.

## Restore drill

Restore is a drill only. It is blocked outside `BACKUP_RESTORE_ALLOWED_ENVIRONMENTS`, rejects the application and maintenance databases, validates the checksum, and accepts only names beginning with `FASE4D_PG_DATABASE_PREFIX`. It never runs `pg_restore --clean` against the application database.

For a local drill, configure the dedicated Fase 4D variables, set `BACKUP_DATABASE_CONNECTION=fase4d`, create a disposable source database, and restore only into generated `siinv_restore_test_*` databases. Run `php artisan backup:restore <backup-history-id>` for a trusted archived backup. The web restore button performs the same drill; uploaded archives are rejected because they do not have a trusted manifest.

## Encryption and secrets

Encryption is disabled by default. Do not enable `BACKUP_ENCRYPTION_ENABLED` until an operator-provided encryption implementation and secret-management process are in place. Keep PostgreSQL passwords and encryption keys only in environment or secret-manager configuration; never place them in commands, manifests, logs, source control, or support tickets.
