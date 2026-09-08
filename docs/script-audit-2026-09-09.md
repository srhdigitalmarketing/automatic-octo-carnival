# Script audit — 2026-09-09

## Repository scope

The main branches of automatic-octo-carnival, idokto and enokto shared commit
`7019114da45bb11caf3737b3ec259ffa18b91b74` when inspected. One audit of that identical
source tree applies to all three repositories.

## Changes

- Diagnose missing API tables/columns before querying providers in API administration
  and General settings. Show an actionable warning instead of a partial page or fatal
  error; block API writes while the required schema is incomplete.
- Load VOD configuration in the General controller once instead of querying from each
  view. Optional API schema problems no longer interrupt independent settings panels.
- Treat absent checkbox settings as false, avoiding a null argument to form_checkbox.
- Remove unreachable StreamHG connection/direct-link branches and the unused direct
  URL parser. Iframe timeout and delivery URL no longer query optional API settings.
- Update obsolete tests that still expected the removed StreamHG provider to be active;
  retain coverage using supported custom hostnames and a removed-provider rejection.
- Add database-update-api-schema.sql for manual phpMyAdmin import. It creates an absent
  third_party_apis table and adds missing fields without replacing existing rows or
  existing column definitions. It does not infer providers for old rows: legacy rows
  with no provider column receive the neutral `custom` default and require classification
  by the administrator. Existing incompatible column types still need separate review.

Historical migrations and legacy compatibility/test helpers were retained. Absence of
an obvious caller alone was not used to delete files that may support older installations.
The removed master/client updater was not restored.

## Verification

Environment: Windows, PHP 8.3.33, Node.js with Playwright/Edge, disposable MySQL 8.4.3.

- 440 syntax checks across application PHP, PHP test scripts and admin JavaScript: pass.
- 41 standalone PHP/JavaScript test scripts: pass.
- 3 real MySQL integration scripts: pass (isolated port 13389, synthetic data only).
- Route regression checks 58 explicit handlers, including inherited methods.
- SQL patch tested twice on a legacy fixture, preserving API values, existing column
  types and rows in movies, links and popup_ad_units; absent-table creation also tested.
- Existing UPNShare health, recovery, replacement, report handling, backup/restore,
  full backup, CDN, player, export, revenue and UI tests included in the suite.

Run standalone tests with PHP (ZIP extension enabled for backup tests) or Node as
appropriate. The three *_mysql_test.php scripts require an explicitly supplied disposable
MySQL port; upnshare_mysql_test.php expects 13389. Never point them at production.

## Limits

No production website/database was changed or queried. These checks do not establish
compatibility with every deployed PHP/MySQL version, real provider credentials, network
conditions or hosting configuration. Vendor/framework packages were not rewritten.
For legacy schema repair, export a database backup, select the correct database in
phpMyAdmin and review database-update-api-schema.sql before importing. The application
only reports schema readiness and does not run this patch automatically.
