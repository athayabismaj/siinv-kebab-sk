<div align="center">
  <img src="public/favicon.svg" alt="Kebab SK logo" width="96" />
  <h1>Kebab SK — SIINV</h1>
  <p><strong>An integrated inventory, branch operations, and Point of Sale backend.</strong></p>

  [![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
  [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase_ready-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)

  <br><br>

  <a href="README.md"><img src="https://img.shields.io/badge/Bahasa-Indonesia-E11D48?style=for-the-badge" alt="Bahasa Indonesia" /></a>
  <a href="README-en.md"><img src="https://img.shields.io/badge/Language-English-1E40AF?style=for-the-badge" alt="English" /></a>
</div>

---

## Public Edition

This repository is the **public edition** of Kebab SK SIINV: a stable snapshot intended for technical documentation, portfolio presentation, evaluation, and architecture demonstrations.

Ongoing development—including further fixes, new features, production configuration, custom integrations, and internal modules—may be maintained privately and may not be released back to this repository. The public edition remains a self-contained application that can be installed and evaluated independently.

> The mobile cashier application source, production credentials, operational data, and third-party service secrets are not part of the public distribution.

## About SIINV

SIINV connects warehouse inventory, daily outlet stock, menu recipes, cashier transactions, and cash flow in a single auditable workflow. It provides role-based web panels for owners, administrators, and developers, together with a REST API for a mobile cashier application.

The core workflow is:

1. An administrator opens a daily stock session and verifies the previous session's remaining stock.
2. Additional warehouse stock is recorded as a new movement without deducting carried-forward stock twice.
3. The cashier checks out an order; menu availability is validated against its recipe and branch stock.
4. When the session closes, ingredient usage and physical remaining quantities are reconciled.
5. The owner reviews revenue, expenses, cash balance, inventory, and cross-branch reports.

## Key Features

### Owner

- Revenue dashboard, sales trends, cash flow, and cross-branch summaries.
- Branch, user, role, archive, and account recovery management.
- Branch-filtered transaction and inventory movement history.
- Sales, ingredient usage, and expense reports with HTML, PDF, and Excel exports.
- Period closing and menu performance analytics.

### Operations administrator

- Ingredient categories, ingredients, pack/piece units, minimum stock, restocking, and adjustments.
- Menu catalog, variants, product images, cost/selling prices, and recipes or Bill of Materials.
- Daily stock sessions with previous-balance carry-forward, warehouse additions, closing, reopening, and reconciliation.
- Transaction history, daily stock reports, ingredient usage, and operational cash flow.
- Branch-level data isolation to prevent inventory and transaction leakage.

### Mobile cashier API

- Token authentication, profile management, password changes, and OTP-based recovery.
- Menu catalog with recipe- and active-stock-based availability.
- Server-authoritative pricing, atomic stock validation, and branch-isolated checkout.
- Transaction history, receipt details, transaction voiding, revenue, and revenue trends.
- Session status, daily stock, session closing, and cashier expense entry.

### System operations

- Queue-backed exports and background jobs.
- Health/readiness endpoint, performance logging, caching, and PostgreSQL indexes.
- Environment-restricted database backup and restore.
- Feature, API contract, security, export, and checkout query-budget tests.

## Technology

| Area | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | PostgreSQL, compatible with Supabase |
| Web UI | Blade, Tailwind CSS 3, Alpine.js |
| Asset build | Vite 7, Node.js |
| API | REST/JSON, token authentication |
| Documents | Laravel Excel and DomPDF |
| Email | Resend or a Laravel mailer |
| Tests | PHPUnit 11 |

## Local Requirements

- PHP `8.2` or newer.
- Composer `2.x`.
- Node.js `20.19+` or `22.12+`, with npm.
- PostgreSQL or a Supabase project. SQLite can be used for selected local development and tests.
- PHP extensions: `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, and `zip`.

## Local Installation

1. Clone the repository and enter the project directory.

   ```bash
   git clone https://github.com/athayabismaj/siinv-kebab-sk.git
   cd siinv-kebab-sk
   ```

2. Install backend and frontend dependencies.

   ```bash
   composer install
   npm ci
   ```

3. Create the local configuration and application key.

   Linux/macOS:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Configure the PostgreSQL/Supabase connection in `.env`, then run the migrations.

   ```bash
   php artisan migrate
   ```

5. If sample data is required, review every seeder first, then run:

   ```bash
   php artisan db:seed
   ```

   Seeders are intended for development only. They may create initial accounts with default passwords; never run them directly in production without replacing or disabling those credentials.

6. Start the development environment.

   ```bash
   composer run dev
   ```

   This command starts the Laravel server, queue listener, log viewer, and Vite. The web application is available at `http://127.0.0.1:8000` by default.

## Build and Tests

```bash
npm run build
composer test
```

For production, run migrations using `php artisan migrate --force`, build the frontend assets, start a queue worker, and run the Laravel scheduler. Never use `migrate:fresh` against a database containing operational data.

## API Overview

All endpoints use the `/api` prefix and return JSON. Operational endpoints are protected by tokens, role restrictions, and rate limits.

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/auth/login` | Sign in and obtain a token |
| `GET` | `/api/auth/me` | Retrieve the active user profile |
| `GET` | `/api/menus` | Retrieve catalog and variant availability |
| `POST` | `/api/transactions` | Cashier checkout |
| `GET` | `/api/transactions` | Transaction history |
| `GET` | `/api/revenue/summary` | Revenue summary |
| `GET` | `/api/daily-stock-items` | Cashier daily stock balance |
| `POST` | `/api/daily-stock-sessions/close` | Close the daily session |
| `POST` | `/api/cashflow/expenses` | Record an operational expense |

Detailed contracts are available in [`docs/API_CONTRACT_ANDROID.md`](docs/API_CONTRACT_ANDROID.md) and [`docs/api-menu-availability.md`](docs/api-menu-availability.md).

## Publication Security

- Never commit `.env`, database dumps, Supabase tokens, API keys, email credentials, or backup files.
- Replace every seeded account and password before using the application outside a local environment.
- Use `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure cookies, and least-privilege database credentials in production.
- Report security vulnerabilities privately to the project owner; do not publish exploit details or credentials through public issues.

## Support and Contributions

This repository primarily serves as a public release and technical reference. Feature requests, roadmap items, deployment support, and client-specific changes are not guaranteed for the public edition. Pull requests may be reviewed, but acceptance and release timing remain at the project owner's discretion.

## Usage Rights

This repository does not yet include a dedicated SIINV `LICENSE` file. Contact the project owner for permission to reuse, modify, distribute, or commercially use the project. All third-party frameworks, libraries, and dependencies remain subject to their respective licenses.

Copyright © 2026 Kebab SK. All rights reserved.
