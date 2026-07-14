# PostgreSQL Concurrency and Performance QA

## Scope

This Fase 5B verification uses a database generated at test runtime with the fixed
prefix `siinv_fase5b_test_`. The test owns and removes only the database name it
created. The configured Fase 4D PostgreSQL database is used only as the local
connection source; the application database is not used.

## Environment

| Check | Result |
| --- | --- |
| PostgreSQL client | 18.4 |
| PostgreSQL server | 18.4 |
| PHP PostgreSQL driver | `pdo_pgsql` and `pgsql` enabled |
| Disposable user | Has `CREATEDB` |
| Main database | Verified different from Fase 4D connection |
| Migration target | Disposable database only |
| Cleanup | `dropdb` only for a database created by the Fase 5B fixture |

## PostgreSQL scenarios

`tests/Integration/PostgreSqlConcurrencyTest.php` runs real PHP child processes
against PostgreSQL. It covers:

| Scenario | Expected protection |
| --- | --- |
| Duplicate transaction code | PostgreSQL unique constraint rejects duplicate |
| Transaction sequence | Unique `(branch_id, sequence_date)` rejects duplicate |
| Daily summary | Unique `(branch_id, sale_date)` preserves one row |
| Stock log type | PostgreSQL check constraint rejects invalid type |
| Two checkout requests, one remaining item | Exactly one checkout succeeds; remaining stock is never negative |
| Two transfers, one warehouse item | One transfer is applied; warehouse stock is never negative |
| Duplicate void | One restock, one refund entry, and one final `VOID` transaction |
| Parallel restock | Both increments are retained |
| Parallel close session | Inventory return occurs once |
| Parallel open session | One session exists for cashier and date |
| Parallel summary rebuild | One branch/date summary exists |
| Reversed multi-ingredient recipe | Two cashiers complete checkout without a lock conflict in the verified scenario |

## Lock audit

| Workflow | Current transaction boundary | Locked rows |
| --- | --- | --- |
| Checkout | `CheckoutTransactionAction` | Daily session, recipe ingredients, daily stock items, sequence row |
| Void | `VoidTransactionAction` | Transaction, daily session, ingredients, daily stock items |
| Transfer | `TransferToDailyStockAction` | Session, ingredients ordered by ID, daily items |
| Restock / adjustment | Inventory actions | Ingredient |
| Close session | `CloseDailyStockSessionAction` | Session, daily items, ingredients ordered by ID |
| Summary rebuild | `DailySalesSummaryService` | Database unique constraint plus update/insert recovery |

The actual PostgreSQL test did not reproduce a deadlock. Checkout still uses a
short `NOWAIT` ingredient lock, so a busy error remains an intentional bounded
failure mode instead of an unbounded retry.

## Query-count guard

The PostgreSQL suite includes a one-item checkout query-count assertion with a
budget of 35 queries. The measured baseline is 31 queries. It is a regression ceiling, not a production throughput
claim. Existing page-level N+1 regression tests remain in
`tests/Feature/Performance/HeavyPagesNPlusOneAuditTest.php`.

No `EXPLAIN`-driven index was added: no slow query was demonstrated on the
disposable fixture, and production index changes require operational evidence.

## Parallel test infrastructure

Laravel's `Storage::fake()` appends `ParallelTesting::token()` to the fake disk
root when the Laravel parallel runner is active. Therefore production export
paths are unchanged. On this repository, `php artisan test --parallel` cannot
run because `brianium/paratest` is not installed, and Fase 5B does not add
dependencies. Parallel suite validation remains a tooling limitation; the
PostgreSQL child-process tests provide the available real-process coverage.

## Residual risks

- PostgreSQL process tests use a local disposable instance, not a production-sized dataset.
- The application still has global ingredient records; concurrent cross-session checkout can receive the bounded `NOWAIT` retry message under sustained contention.
- No multi-server queue, Redis, scheduler, or production load benchmark is included in this phase.
