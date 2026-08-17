# Backup and Restore Operations

Database backups are PostgreSQL custom-format archives stored only on the private `local` disk. Each published archive contains only the configured application schema, so Supabase-managed schemas such as `auth`, `storage`, `realtime`, and `vault` are never copied. The adjacent JSON manifest records the application schema, SHA-256 checksum, size, format, and lifecycle timestamps. The manifest does not contain credentials or connection strings.

## Backup

Run `php artisan backup:database --type=manual`. The command writes to a temporary directory, runs `pg_dump --schema <application-schema>`, rejects a failed or empty dump, creates the manifest, then publishes the complete artifact. `BACKUP_APPLICATION_SCHEMA` may override the connection search path; otherwise the first schema in `DB_SCHEMA` is used. Use the same dedicated application schema (recommended: `laravel`) on Supabase and local PostgreSQL for portable two-way backups.

Run `php artisan backup:prune` first to review retention candidates. Only run `php artisan backup:prune --delete` after reviewing the output. Retention considers only valid manifest-backed artifacts and never removes legacy backup files. Current values (daily 7, weekly 4, monthly 12) are operational defaults, not a recovery SLA.

## Application restore

The developer web page can restore a trusted backup history item or an uploaded PostgreSQL custom archive. Uploaded archives require an explicit application-schema selection. Restore uses `pg_restore --schema <selected-schema>`, so a full Supabase archive remains safe to import: only the selected application schema is changed. Reserved Supabase and PostgreSQL system schemas are rejected.

Before changing the target schema, the web workflow creates a verified snapshot of the currently active schema and enables maintenance mode. Migrations then run against the restored schema before the original application search path is restored. For a first Supabase-to-local migration, keep the local application on `public`, upload the Supabase archive with schema `laravel`, verify it, then set `DB_SCHEMA=laravel` on both environments.

## Restore drill

The CLI drill remains blocked outside `BACKUP_RESTORE_ALLOWED_ENVIRONMENTS`, rejects the application and maintenance databases, validates trusted checksums, and accepts only disposable database names beginning with `FASE4D_PG_DATABASE_PREFIX`. Configure the dedicated Fase 4D variables and run `php artisan backup:restore <backup-history-id>` when an isolated recovery test is required.

## Encryption and secrets

Encryption is disabled by default. Do not enable `BACKUP_ENCRYPTION_ENABLED` until an operator-provided encryption implementation and secret-management process are in place. Keep PostgreSQL passwords and encryption keys only in environment or secret-manager configuration; never place them in commands, manifests, logs, source control, or support tickets.
