# Export Queue Operations

## Scope

This document describes the queued Excel export workflow for the Kebab SK web
panels. It covers transaction history, stock logs, ingredient usage, daily
stock, operational expenses, and sales reports.

## Export Matrix

| Domain | GeneratedExport type | Direct Excel limit | Large Excel | HTML/PDF above limit |
| --- | --- | ---: | --- | --- |
| Transaction history | `transaction_history` | 100 rows | Queued | Rejected |
| Stock log | `stock_log` | 100 rows | Queued | Rejected |
| Ingredient usage | `usage_report` | 100 rows | Queued | Rejected |
| Daily stock | `daily_stock_report` | 100 rows | Queued | Rejected |
| Operational expense | `expense_report` | 250 rows | Queued | Rejected |
| Sales report | `sales_report` | 250 rows | Queued | Rejected |

The exact boundary is intentional: `T-1` and `T` use the direct export path;
`T+1` creates a queued `GeneratedExport` record.

## Lifecycle

```text
PENDING
  -> PROCESSING (atomic claim by one worker)
  -> COMPLETED  (private file exists)
  -> FAILED     (safe error message, no file path)

FAILED
  -> PENDING    (atomic manual retry)
```

`GeneratedExportLifecycle` performs the atomic status transitions. A first
attempt can only claim `PENDING`. A queue retry can reclaim its own previously
failed export, while a second first-attempt job cannot claim an export that is
already `PROCESSING` or `COMPLETED`.

Each export job has two attempts, a 60 second timeout, and a 60 second backoff.
It is dispatched after the database transaction commits to the dedicated
`exports` database queue. The worker timeout is 75 seconds and the configured
database queue `retry_after` is 120 seconds, so the job timeout remains below
both values. A job that fails removes its expected private file and marks the
record as `FAILED`.

## Data Snapshot And Authorization

At queue time, every export stores:

- requester ID;
- selected branch ID;
- export type and format;
- normalized date and filter values;
- original filename;
- expiry time.

Jobs rebuild their query from this stored snapshot. They never read branch
selection from the web session. Queued query builders apply the saved
`branch_id` before the data is streamed.

Generated files are written to the non-public `local` disk under:

```text
exports/{requested_by}/{generated_export_id}/{filename}
```

The generated export policy allows only its requester to view, download, or
retry it. Download additionally requires `COMPLETED`, an unexpired record, and
an existing file.

## Retention And Cleanup

Generated files expire after seven days. Run Laravel scheduler continuously:

```bash
php artisan schedule:work
```

The scheduled `exports:cleanup` command runs daily at 03:35. It deletes only
expired terminal records (`COMPLETED` and `FAILED`) and their private files.
It intentionally does not delete `PENDING` or `PROCESSING` records, so an
active worker cannot lose its lifecycle record during a cleanup race.

For a worker using the database queue, keep the timeout relation documented in
the worker runbook:

```bash
php artisan queue:work database --queue=exports --tries=2 --timeout=75 --sleep=3 --memory=192 --max-jobs=100 --max-time=3600
```

Inspect failed queue work separately with Laravel's failed-job tooling. A hard
process termination can still leave a `PROCESSING` record; use
`exports:detect-stale` to inspect it and mark it failed only with an explicit
export ID after confirming no job remains queued. Cleanup deliberately
preserves `PENDING` and `PROCESSING` records instead of creating an
orphan-file race. See [Queue Worker Operations](QUEUE_WORKER_OPERATIONS.md)
for deployment, worker restart, diagnosis, and recovery procedures.

## Workbook Implementation

Large Excel exports use `FromQuery` and a chunk size of 250 rows. Direct
exports remain available only below their documented limits and may use views
to preserve the existing report layout. HTML and PDF are not used for large
datasets.

## Local Benchmark Baseline

Measured on 14 July 2026 in the PHPUnit SQLite testing environment. The timing
starts immediately before the queued transaction workbook is generated; setup
and synthetic data creation are excluded.

| Rows | Duration | Peak memory delta | File size |
| ---: | ---: | ---: | ---: |
| 100 | 138.47 ms | 26.00 MB | 8.66 KB |
| 250 | 96.97 ms | 20.00 MB | 12.35 KB |
| 1,000 | 313.19 ms | 20.00 MB | 30.28 KB |

These values are a local baseline, not a production SLA. Re-run the dedicated
benchmark after changing PhpSpreadsheet, Laravel Excel, export mapping, or
database infrastructure:

```bash
php artisan test tests/Benchmark/QueuedTransactionExportBenchmarkTest.php --debug
```

## Verification Coverage

Feature tests cover:

- direct/queued threshold boundaries for all six domains;
- queued branch snapshot isolation;
- requester-only generated export access;
- workbook readability, headings, and branch-scoped rows;
- duplicate worker claim prevention;
- atomic manual retry;
- failed-export cleanup and retry;
- expired-file cleanup and active-export cleanup safety.
