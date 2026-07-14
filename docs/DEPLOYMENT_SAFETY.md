# Deployment Safety

Run `php artisan deploy:check` before deployment. It is read-only and checks the app key, database reachability, pending migrations, queue/cache/export tables, private backup storage, and PostgreSQL client availability. It does not migrate, restore, create backups, restart workers, or modify application data.

Recommended order: enable maintenance mode where appropriate, deploy code, run `php artisan deploy:check`, review `php artisan migrate:status`, apply approved migrations once, rebuild application caches, restart queue workers, run `php artisan scheduler:diagnose` and `php artisan system:diagnose`, then remove maintenance mode.

Do not use automatic database rollback as an application rollback mechanism. Preserve the artifact and manifest, restore only through the disposable drill workflow first, and follow a separately approved incident plan for any production recovery.
