# Performance audit — 2026-09-09

## Findings and fixes

- Viewer link selection previously performed DNS/HTTP fallback for unchecked or stale
  hosts, waiting up to 8 seconds per host by default. It now uses stored health status
  and browser fallback without external calls. Admin checks and streams:health-check
  still perform real checks. Unchecked candidates may be tried before their cron check.
- Selecting a URL now updates only last_served_at instead of treating selection as
  successful playback. It preserves health timestamps, unresolved reports and the
  concurrent-deletion protections verified by real MySQL tests.
- Revenue and provider-result actions release the authenticated PHP session before
  external API work, so the session lock no longer blocks other admin navigation.
- Link statistics use one grouped query instead of nine. Counts, sums, nulls and
  duplicate report handling are preserved. Request-local reuse resets on init.

No page cache, database migration, CDN change or cron schedule change was introduced.
Keep the existing streams:health-check job for background checks and recovery.

## Verification

443 syntax checks, 43 standalone tests and 4 disposable MySQL tests passed (47 tests).
The query-count test proves one link-statistics query and verifies refresh after data
changes. Player tests cover cold/stale paths without DNS/HTTP, failure cooldown,
provider-state exclusions and forced checks. MySQL tests also cover race protection
and preservation of health timestamps/reports when a URL is served.

Environment: PHP 8.3.33, MySQL 8.4.3, Windows, Node.js and Playwright/Edge.
Public requests to jpn.oktostream.com, id.oktostream.com and en.oktostream.com returned
HTTP 403. No authenticated production timing, CPU/RAM, PHP-FPM queue, slow-query or
real video bandwidth measurements were available. These fixes remove unnecessary
source-code work; production improvement and other causes still require measurement.
