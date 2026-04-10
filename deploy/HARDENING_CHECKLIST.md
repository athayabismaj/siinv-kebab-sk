# SiInv Security & Stability Checklist (UMKM)

## 1) Nginx Rate Limit
1. Copy `deploy/nginx/siinv-security.conf` to server (example: `/etc/nginx/snippets/siinv-security.conf`).
2. Include snippet in your site config:
   - `include /etc/nginx/snippets/siinv-security.conf;`
3. Test and reload:
   - `nginx -t`
   - `systemctl reload nginx`

## 2) Fail2ban for Brute Force
1. Copy filter:
   - `deploy/fail2ban/filter.d/siinv-auth.conf` -> `/etc/fail2ban/filter.d/siinv-auth.conf`
2. Copy jail:
   - `deploy/fail2ban/jail.d/siinv-auth.local` -> `/etc/fail2ban/jail.d/siinv-auth.local`
3. Adjust `logpath` to your Laravel path.
4. Restart fail2ban:
   - `systemctl restart fail2ban`
   - `fail2ban-client status siinv-auth`

## 3) Laravel Throttle
Already configured in:
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`
- `routes/api.php`

Focus:
- login/forgot/reset endpoints tightened.
- role-aware API throttle (kasir longgar, admin/owner ketat on heavy endpoints).

## 4) Queue Worker (Export/Report)
1. Run queue migrations if needed:
   - `php artisan queue:table`
   - `php artisan queue:failed-table`
   - `php artisan migrate`
2. Set `.env`:
   - `QUEUE_CONNECTION=database`
3. Install supervisor config:
   - `deploy/supervisor/laravel-worker.conf`
4. Reload supervisor:
   - `supervisorctl reread`
   - `supervisorctl update`
   - `supervisorctl status`

## 5) DB/Worker/Timeout Guard
Set in `.env` (production recommended):
- `DB_CONNECT_TIMEOUT=5`
- `DB_POOL_MAX_CONNECTIONS` (if using external pooler)
- `QUEUE_CONNECTION=database`

PHP-FPM/Nginx suggestions:
- `request_terminate_timeout` 30-60s
- `pm.max_children` according to RAM
- keep queue worker memory <= 256MB per process

## 6) Monitoring + Alert
Scheduled command already added:
- `ops:traffic-alert-check --window=5` (every 5 minutes)

Env knobs:
- `PERF_LOG_ENABLED=true`
- `PERF_LOG_SLOW_MS=350`
- `TRAFFIC_ALERT_TOTAL_REQUESTS=1200`
- `TRAFFIC_ALERT_SLOW_REQUESTS=80`
- `TRAFFIC_ALERT_STATUS_429=40`
- `TRAFFIC_ALERT_STATUS_5XX=15`
- `TRAFFIC_ALERT_SLACK_ENABLED=true` (optional)
- `LOG_STACK="daily,slack"` (optional if Slack enabled)

Manual test:
- `php artisan ops:traffic-alert-check --window=5`
