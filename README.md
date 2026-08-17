<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK logo" width="96" />
  <h1>Kebab SK — SIINV</h1>
  <p><strong>Integrated inventory, branch operations, reporting, and Point of Sale backend.</strong></p>

  [![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase_ready-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)

  <br><br>

  <a href="README.md"><img src="https://img.shields.io/badge/Language-English-1E40AF?style=for-the-badge" alt="English" /></a>
  <a href="README-id.md"><img src="https://img.shields.io/badge/Bahasa-Indonesia-E11D48?style=for-the-badge" alt="Bahasa Indonesia" /></a>
</div>

---

## Overview

SIINV is the operational backbone of Kebab SK. It connects warehouse inventory, daily outlet stock, menu recipes, cashier transactions, expenses, and management reporting in one auditable system.

The application provides role-based web interfaces for owners and operational administrators, together with a REST API consumed by the SIPOS Android cashier application. Server-side validation remains authoritative for prices, branch access, active stock sessions, recipe requirements, and inventory mutations.

## Operational Workflow

1. An administrator opens a daily stock session for a cashier and outlet.
2. Remaining stock from the previous closed session is carried forward and physically verified.
3. Only new quantities taken from the warehouse create warehouse deduction movements.
4. SIPOS submits cashier transactions; SIINV validates the session, prices, recipes, and ingredient availability atomically.
5. At closing, physical remaining stock is reconciled against sales and recorded usage.
6. Owners review revenue, expenses, cash balance, stock movements, and cross-branch performance.

## Key Features

### Inventory and recipes

- Ingredient categories, ingredients, pack/piece conversions, minimum stock, restocking, and adjustments.
- Menu catalog, variants, product images, cost and selling prices, and Bill of Materials recipes.
- Recipe-based menu availability using the active branch stock balance.
- Auditable stock movement history with branch and date filters.
- Protection against fractional residue and duplicate warehouse deductions.

### Daily stock operations

- Daily session opening, closing, reopening, and reconciliation.
- Previous-session carry-forward with editable physical verification.
- Explicit separation between carried stock and new warehouse additions.
- Cashier usage and remaining-stock reports across outlets.
- Branch-level isolation for sessions, inventory, transactions, and reports.

### Sales and finance

- Transaction history, receipt details, cancellation controls, and inventory consequences.
- Revenue dashboards, sales trends, menu performance, and period closing.
- Operational expense records with descriptions and audit history.
- Transparent cash flow: revenue minus expenses and the resulting cash balance.
- HTML, PDF, and Excel exports for operational reports.

### Mobile cashier API

- Token authentication, profiles, password management, and OTP recovery.
- Paginated menu catalog with recipe- and stock-based availability.
- Server-authoritative checkout with atomic stock validation.
- Transaction history, receipts, cancellation, revenue, and trends.
- Daily stock status, session closing, and operational expense entry.

### Reliability and operations

- Queue-backed exports and background jobs.
- Health and readiness endpoints, performance logging, caching, and PostgreSQL indexes.
- Environment-restricted database backup and restore tooling.
- Feature, security, API contract, export, concurrency, and query-budget tests.

## Technology Stack

| Area | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | PostgreSQL, compatible with Supabase |
| Web interface | Blade, Tailwind CSS 3, Alpine.js |
| Frontend build | Vite 7, Node.js |
| API | REST/JSON with token authentication |
| Documents | Laravel Excel, DomPDF |
| Email | Resend or Laravel-compatible mailer |
| Testing | PHPUnit 11 |

## Requirements

- PHP `8.2` or newer.
- Composer `2.x`.
- Node.js `20.19+` or `22.12+`, with npm.
- PostgreSQL or a Supabase project.
- PHP extensions: `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, and `zip`.

SQLite can be used for selected local tests, but PostgreSQL is recommended for development that exercises production-equivalent queries and concurrency behavior.

## Getting Started

1. Clone the repository.

   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```

2. Install backend and frontend dependencies.

   ```bash
   composer install
   npm ci
   ```

3. Create the local environment file and application key.

   Linux or macOS:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Configure the database connection in `.env`, then migrate the schema.

   ```bash
   php artisan migrate
   ```

5. Optionally review and run the development seeders.

   ```bash
   php artisan db:seed
   ```

   Seeders may create development accounts with default credentials. Review them before use and never carry default credentials into a deployed environment.

6. Start the development services.

   ```bash
   composer run dev
   ```

   This starts the Laravel server, queue listener, log viewer, and Vite development server. The application is available at `http://127.0.0.1:8000` by default.

## Build and Test

```bash
npm run build
composer test
```

Laravel Pint can be used to normalize PHP formatting:

```bash
./vendor/bin/pint
```

## API Overview

All API endpoints use the `/api` prefix and return JSON. Protected endpoints enforce authentication, role checks, branch context, and rate limits.

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/auth/login` | Authenticate and obtain a token |
| `GET` | `/api/auth/me` | Retrieve the authenticated profile |
| `GET` | `/api/menus` | Retrieve the menu catalog and availability |
| `POST` | `/api/transactions` | Submit a cashier checkout |
| `GET` | `/api/transactions` | Retrieve transaction history |
| `GET` | `/api/revenue/summary` | Retrieve the revenue summary |
| `GET` | `/api/daily-stock-items` | Retrieve the cashier's daily stock balance |
| `POST` | `/api/daily-stock-sessions/close` | Close a daily stock session |
| `POST` | `/api/cashflow/expenses` | Record an operational expense |

See [Android API contract](docs/API_CONTRACT_ANDROID.md), [menu availability](docs/api-menu-availability.md), and [mobile contract matrix](docs/MOBILE_API_CONTRACT_MATRIX.md) for integration details.

## Production Operations

Before deployment:

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure HTTPS, secure cookies, trusted proxies, and least-privilege database credentials.
- Run `php artisan migrate --force` and `npm run build`.
- Keep the queue worker and Laravel scheduler running under a process supervisor.
- Use `composer run prod:optimize` after the production environment is configured.
- Validate backup and restore procedures before accepting operational data.

Never use `migrate:fresh` on a database containing operational records.

## Technical Documentation

- [Deployment safety](docs/DEPLOYMENT_SAFETY.md)
- [Queue worker operations](docs/QUEUE_WORKER_OPERATIONS.md)
- [Scheduler operations](docs/SCHEDULER_OPERATIONS.md)
- [Backup and restore](docs/BACKUP_RESTORE_OPERATIONS.md)
- [Monitoring and health](docs/MONITORING_AND_HEALTH_OPERATIONS.md)
- [PostgreSQL performance and concurrency QA](docs/POSTGRESQL_CONCURRENCY_PERFORMANCE_QA.md)
- [Security deployment checklist](docs/security-deployment-checklist.md)

## Security

- Never commit `.env`, database dumps, Supabase credentials, API keys, email credentials, or backup archives.
- Replace all seeded accounts and passwords before deployment.
- Keep authorization, price calculation, branch isolation, and inventory validation on the server.
- Report suspected vulnerabilities privately to the project owner and avoid including credentials or operational data in issue reports.

## Contributing

Use focused commits, include tests for behavioral changes, and document API contract changes that affect SIPOS. Pull requests are reviewed according to project priorities and compatibility requirements.

## License

This repository does not currently include a project-specific license. Contact the project owner before reuse, modification, redistribution, or commercial use. Third-party dependencies remain subject to their respective licenses.

Copyright © 2026 Kebab SK. All rights reserved.
