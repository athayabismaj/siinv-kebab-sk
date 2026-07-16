# Blade Asset Separation Audit

## Scope and source of truth

This audit covers the Laravel frontend presentation layer only. Android, routes,
API contracts, authorization, branch scope, business formulas, database schema,
queue, scheduler, export lifecycle, backup, and restore behavior are outside the
refactor scope.

| Item | Result |
| --- | --- |
| Local branch | `main` |
| Local HEAD | `fa15a937304962470e23cdeafd6c1d7bb02763c4` |
| `origin/main` | `fa15a937304962470e23cdeafd6c1d7bb02763c4` |
| Ahead / behind | `0 / 0` |
| Baseline tests | `188 passed`, `1086 assertions`, `0 failed` |
| Baseline frontend build | PASS, Vite `7.3.1`, Tailwind CSS `3.4.4` |
| Baseline Blade compilation | `view:clear` and `view:cache` PASS |

The initial working tree already contained uncommitted work in developer backup
and owner management controllers/views, routes, and backup tests, plus the
untracked `panel_admin_old.blade.php` and `components/modal.blade.php`. Those
changes were treated as user-owned source of truth and were not reverted.

## Tooling

- Vite continues to compile `resources/css/app.css`, `resources/js/app.js`, and
  page-specific CSS entries.
- Tailwind remains on `3.4.4`; Vite remains on `7.3.1`.
- Alpine.js and SweetAlert remain loaded through the existing CDN references.
- No dependency or lockfile was changed.
- Unpinned CDN versions remain technical debt; changing dependency delivery was
  intentionally excluded from this refactor.

## Inventory summary

The initial counts were captured before editing the presentation layer. The
remaining counts were captured after the refactor.

| Category | Initial files | Extracted or replaced | Remaining files | Classification |
| --- | ---: | ---: | ---: | --- |
| Static `<style>` blocks | 21 | 15 | 6 | Six professional export renderers require self-contained CSS |
| Inline `<script>` blocks/references | 17 | 11 | 6 | Three CDN references and three small error-page redirects |
| Native confirmation handlers | 7 | 7 | 0 | Replaced by `data-confirm` contract |
| Other native event attributes | Present | Moved to delegated modules | 1 | Error 429 reload remains a minimal standalone action |
| Alpine-bearing views | 46 | Complex state extracted where found | 44 | Simple show/hide, tabs, dropdowns, modal, and auto-dismiss remain |
| Views containing `@php` | 50 | Presentation mappings reduced | 47 | Formatting and small UI mappings only in audited normal views |
| Model queries in Blade | 1 view / 2 calls | 0 | 1 view / 2 calls | Deferred export-renderer debt; reporting is frozen |
| Raw HTML output | 7 occurrences initially | 4 static SVG outputs replaced | 3 occurrences / 2 views | Pre-existing developer views with static internal SVG paths |

Counts describe files unless an occurrence count is explicitly stated. Inline
`style` attributes still occur for CSS custom properties, Alpine `x-show`
fallbacks, email/export renderer requirements, and pre-existing developer views;
they are not static application stylesheet blocks.

## Detailed inventory and classification

| Area | Style block | Inline style | Script | Native event | Alpine | `@php` | Query | Raw HTML | Resolution |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| `layouts/app.blade.php` | moved | static removed | moved | removed | simple sidebar state | small role presentation | no | no | App shell CSS/JS modules |
| `admin/panel_admin.blade.php` | moved | dynamic bounded variable only | no | no | simple tabs | presentation only | no | no | Dashboard page CSS |
| Admin/owner/developer sidebars | no | static removed | no | no | simple responsive state | removed | no | removed | Shared component and navigation classes |
| Flash alerts | no | no | no | no | auto-dismiss remains | moved | no | removed | Class component and named icons |
| Login/OTP | moved | no | moved | removed | CDN remains | no | no | no | Auth modules and page CSS |
| Transactions and reports | moved | dynamic values only | moved | removed | simple controls remain | presentation only | no | no | Page CSS and report modules |
| Daily stock close | moved | no | moved | removed | form state only | presentation data | no | no | Explicit escaped data contract and JS module |
| Professional exports | 6 retained | renderer CSS | none | none | none | formatting | 2 calls in sales export | no | Self-contained renderer exception |
| Email views | retained as needed | email-client CSS | none | none | none | formatting | no | no | Email renderer exception |
| Error pages | no | layout-specific | 3 tiny redirects | 1 reload | none | no | no | Standalone error recovery exception |
| Developer dashboard/backups | user-owned dirty work | some retained | no new inline logic | no | complex pre-existing backup modal | presentation | no | 3 static SVG outputs | Deferred to avoid overwriting concurrent work |

## CSS separation

`resources/css/app.css` is now the shared CSS entry and imports:

- `layouts/app-shell.css`
- `components/sidebar.css`
- `components/presentation.css`
- `pages/admin-dashboard.css`
- `pages/auth-login.css`

Page-specific Vite entries were added for archive, transaction detail/index,
daily stock reports/transfer, developer owner management, owner dashboard and
transaction print, restock, and stock adjustment. Selectors are scoped with
application/page namespaces and do not intentionally redesign the UI.

Dynamic chart widths use a bounded CSS custom property rather than dynamic
Tailwind class generation. Static auto-fit grids and details marker styling use
the shared presentation stylesheet.

## JavaScript separation

`resources/js/app.js` is an explicit module entry importing:

- app bootstrap;
- app shell and flash positioning;
- data-driven confirmation dialog;
- OTP and login password behavior;
- reusable password toggle and clear-zero behavior;
- generic form behaviors;
- period filter, closing cancellation, date navigation, and sales-report
  pagination;
- daily stock close-session calculation.

The previous unreferenced `resources/js/admin/usage-report.js` was superseded by
the shared period-filter module after checking both Vite inputs and Blade
references. No route, request payload, or calculation formula changed.

All modules guard missing elements. Delegated listeners use a document-level
binding only once per module load. The confirmation handler uses
`requestSubmit(button)` for submit controls, preserving native validation,
CSRF fields, method spoofing, and submitter semantics.

## Confirmation flow migration

Native `onclick="return confirm(...)"` contracts were replaced by:

- `data-confirm`;
- `data-confirm-title`;
- `data-confirm-message`;
- `data-confirm-button`;
- `data-confirm-variant`.

The external confirmation module supports links and submit buttons, prevents a
second click while a dialog is pending, uses SweetAlert when available, and
falls back to the native confirm dialog. Characterization tests verify critical
confirmation attributes and form contracts.

## Sidebar refactor

Navigation configuration now lives in presentation-only classes:

- `App\View\Navigation\AdminNavigation`
- `App\View\Navigation\OwnerNavigation`
- `App\View\Navigation\DeveloperNavigation`
- `App\View\Navigation\SidebarNavigation`

All three sidebar partials render the shared
`components/navigation/sidebar.blade.php`. Menu labels, order, route targets,
active-route behavior, dark mode, responsive open/close state, and role-backed
server authorization remain unchanged. Icons are selected through a static
whitelist in `components/icon.blade.php`; arbitrary SVG HTML is not accepted.

## Flash alert refactor

`App\View\Components\FlashAlerts` owns the presentation variants and the
`components/flash-alerts.blade.php` view owns rendering. The legacy partial is a
compatibility wrapper. Success, warning, error, and validation messages retain
their labels, colors, validation list, Alpine transition, and 6000 ms dismiss
delay. Named icons replace raw SVG output.

## Characterization tests

`tests/Feature/View/BladePresentationCharacterizationTest.php` protects:

- admin, owner, and developer sidebar selection and labels;
- sidebar route and active-state contracts;
- flash success, warning, error, and validation rendering;
- critical form action, method, CSRF, and field contracts;
- confirmation data contracts;
- dashboard rendering with minimal presentation data;
- absence of exceptions while compiling affected views.

The added suite currently contributes 5 tests and 85 focused assertions. The
full-suite assertion delta also includes assertions exercised by existing tests.

## Exceptions and deferred findings

### Style blocks

The following views retain self-contained CSS because they are professional
export renderers and may not load Vite assets:

- `exports/daily_stock_professional.blade.php`
- `exports/expense_professional.blade.php`
- `exports/sales_professional.blade.php`
- `exports/stock_logs_professional.blade.php`
- `exports/transaction_professional.blade.php`
- `exports/usage_professional.blade.php`

### Script tags

- `auth/login.blade.php`, `layouts/auth.blade.php`, and `layouts/app.blade.php`
  retain existing external Alpine/SweetAlert CDN references.
- Error 419, 500, and 503 retain tiny standalone redirect scripts because their
  layouts intentionally do not depend on the application bundle.
- Error 429 retains a direct reload action for the same isolated reason.

### Alpine

Simple Alpine remains for responsive sidebar state, tabs, dropdowns, modal
visibility, show/hide controls, and flash auto-dismiss. This is an intentional
Blade responsibility. The developer backup modal contains more extensive
pre-existing Alpine state in a user-owned dirty file and was not rewritten.

### `@php`

Remaining blocks are presentational formatting, labels, color classes, compact
UI arrays, or renderer preparation. No database mutation, stock/revenue
formula, branch filtering, authorization, queue dispatch, or filesystem action
was introduced into Blade.

### Query/model calls

`exports/sales_professional.blade.php` still resolves a branch name with two
`Branch::find()` calls. Moving this safely requires changing the reporting/export
data preparation contract, which is frozen by the task. It is recorded as
deferred technical debt and was not guessed around.

Model constants used by `owner/exports/show.blade.php` are presentation constants,
not database queries.

### Raw HTML

Three remaining raw outputs in developer dashboard/backup views contain static
internal SVG path fragments from local presentation arrays. They are not user or
database content. These views already had concurrent user changes, so converting
them was deferred rather than risking an overwrite. All sidebar, flash, and stock
summary raw SVG output touched by this refactor now uses named icons.

### Inline style

Remaining inline styles are classified as:

- bounded dynamic CSS custom properties for chart/progress width;
- Alpine's documented `display: none` fallback;
- email/export renderer requirements;
- pre-existing developer-view styling;
- the pre-existing untracked modal component.

## Visual validation

Automated browser smoke validation was performed against
`http://127.0.0.1:8000/` after a production asset build:

| Check | Result |
| --- | --- |
| Login desktop `1440x900` | PASS, no horizontal overflow |
| Login mobile `390x844` | PASS, no horizontal overflow |
| Console warning/error log | Empty |
| Password visibility toggle | PASS (`password` to `text`) |
| CSS asset rendering | PASS |

Authenticated dashboards could not be safely exercised without using or changing
local account credentials. Manual release QA remains required for OTP, all role
dashboards, desktop/mobile sidebars, dark mode, flash variants, confirmation
actions, transaction/stock/daily-stock/expense/report pages, export, backup, and
diagnostics.

## Files changed by this refactor

### Presentation classes and components

- Added `app/View/Components/FlashAlerts.php`.
- Added four `app/View/Navigation/*` classes.
- Added shared icon, flash-alert, and navigation sidebar Blade components.
- Converted legacy sidebar and flash partials into thin wrappers.

### CSS

- Updated `resources/css/app.css`.
- Added shared layout/component CSS and eleven page-level CSS files.
- Updated affected Blade views to load page CSS through Vite.
- Updated `vite.config.js` with explicit page CSS entries.

### JavaScript

- Updated `resources/js/app.js` as the module entry.
- Added auth, layout, confirmation, form, report, and daily-stock modules.
- Removed the stale unreferenced usage-report module after replacing it with the
  shared period filter.
- Replaced native event attributes and large inline scripts in affected views.

### Views

- Refactored the app layout, admin dashboard, sidebars, flash alerts, login,
  transaction pages, stock pages, daily-stock pages, owner reports/targets, and
  related filter partials without changing endpoints or submitted field names.
- Existing unrelated dirty changes in developer/owner management and backup
  files remain present and are not claimed as part of this refactor.

### Tests and documentation

- Added `tests/Feature/View/BladePresentationCharacterizationTest.php`.
- Added this audit.

## Protection report

| Protected surface | Change |
| --- | --- |
| Endpoints and HTTP methods | None |
| Request/response payloads | None |
| API/Android contract | None |
| Formula and business logic | None |
| Authentication/authorization/policy | None |
| Branch scope | None |
| Transaction and stock workflows | None |
| Backup/restore | None by this refactor |
| Export lifecycle | None |
| Queue/scheduler | None |
| Migration/schema/constraints | None |

Target result: **NO BEHAVIORAL CHANGES**.

## Final regression

| Check | Result |
| --- | --- |
| `npm run build` | PASS, 76 modules transformed |
| `php artisan view:clear` | PASS |
| `php artisan view:cache` | PASS |
| Characterization test | `5 passed`, `85 assertions` |
| PostgreSQL disposable integration | `11 passed`, `58 assertions` |
| Full regression run 1 | `193 passed`, `1181 assertions`, `0 failed` |
| Full regression run 2 | `193 passed`, `1181 assertions`, `0 failed` |
| Scoped `git diff --check` for new frontend files | PASS |
| Full-tree `git diff --check` | 27 pre-existing trailing-whitespace findings and CRLF/LF warnings remain in the initial dirty worktree |

The test count increased from 188 to 193 and no existing test was removed,
skipped, or weakened.

## Remaining risks

- Fase 5D has not been performed; the system must not be described as
  production-ready from this refactor alone.
- Physical printer behavior and device-level Android validation remain untested.
- Authenticated full-page visual QA and dark-mode comparison remain manual.
- Alpine/SweetAlert CDN versions are not fully pinned through npm.
- Email/PDF/export renderers intentionally retain self-contained styles.
- The sales professional export still contains deferred branch-name queries.
- Pre-existing developer raw SVG and complex backup Alpine remain technical debt.
- Existing CRLF/LF warnings and trailing whitespace from the initial dirty tree
  are tracked separately from new files.

## Recommended commits

No commit or push is performed by this task. Suggested grouping:

```text
refactor(frontend): separate Blade styles and scripts into Vite modules
refactor(navigation): extract shared sidebar presentation components
refactor(ui): replace inline confirmation handlers with data attributes
refactor(ui): extract flash alert variants and named icons
test(views): add Blade presentation characterization coverage
docs(frontend): document Blade asset separation audit
```

## PHP Presentation Second Pass

This pass removes repeated transaction and stock-session presentation rules from
Blade without moving business decisions, queries, authorization, or branch
resolution into the view layer.

| Surface | Before | After | Behavior changed |
| --- | --- | --- | --- |
| Admin/owner transaction index | Repeated status, payment, void-reason closures and desktop/mobile class maps | `TransactionPresenter` plus shared status/void components | No |
| Admin/owner transaction detail | Separate status/payment labels and detail color maps | One immutable `TransactionPresentation` per transaction | No for valid statuses; unknown statuses now consistently use the existing danger vocabulary |
| Owner sales report | Report-specific payment and status conditionals | Shared transaction presenter | No |
| Admin/owner dashboard session state | Duplicate open/closed/default tone arrays | `StockSessionPresenter` | No |

The presenter classes are stateless scalar mappers. They do not access models,
relationships, requests, sessions, authentication, cache, or the database. Blade
components only render values already resolved by the presenter.

PHP intentionally left local in Blade is limited to one-use view composition,
such as KPI item arrays, filter defaults, quantity totals, critical-stock flags,
and one presenter call per rendered record. These blocks do not duplicate domain
rules and extracting them would add indirection without a reusable contract.

### Second-pass protection

- Unit tests cover success, void, unknown status, payment labels, void reasons,
  detail/index CSS vocabulary, and session-state tones.
- Feature characterization covers admin/owner transaction list and detail views,
  owner sales reporting, desktop/mobile duplicate markup, and print/filter hooks.
- A static source guard prevents transaction closures, old mapper variables, and
  the unsupported `@php(...)` shorthand from returning to the migrated views.
- No endpoint, payload, response shape, controller query, eager loading, policy,
  branch scope, export lifecycle, or Android contract changed.

| Second-pass check | Result |
| --- | --- |
| Transaction presenter unit test | `4 passed`, `58 assertions` |
| Stock-session presenter unit test | `1 passed`, `12 assertions` |
| View characterization and source guard | `7 passed`, `179 assertions` |
| Blade clear/cache | PASS |
| Vite production build | PASS, 76 modules transformed |
| Full Laravel regression run 1 | `206 passed`, `1367 assertions`, `0 failed` |
| Full Laravel regression run 2 | `206 passed`, `1367 assertions`, `0 failed` |
| PostgreSQL disposable run 1 | `11 passed`, `61 assertions`, `0 failed` |
| PostgreSQL disposable run 2 | `11 passed`, `61 assertions`, `0 failed` |
| Disposable PostgreSQL databases remaining | `0` |
| Public browser smoke | PASS at `1280x720`, no horizontal overflow or console warning/error |

Authenticated visual comparison remains manual because this pass did not use or
change local account credentials. The routed characterization tests render the
admin and owner dashboards, transaction lists/details, and owner sales report.

The professional export renderer and other self-contained export templates still
contain presentation PHP. They remain deferred because their rendering contract
and memory behavior are separate from normal web Blade pages.
