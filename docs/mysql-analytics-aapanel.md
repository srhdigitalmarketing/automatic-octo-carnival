# MySQL analytics: aaPanel setup

Analytics now uses MySQL only. No Redis password or extension is required for analytics. Other applications using Redis are not changed. Existing database connection settings must be retained; do not replace them with example credentials.

From the project directory containing spark:

```bash
git pull origin main
/www/server/php/82/bin/php spark migrate
/www/server/php/82/bin/php spark cache:clear
/www/server/php/82/bin/php spark analytics:prune
```

If Git reports local .env changes, back up that file securely first and reconcile it with the new public defaults; retain any actual database credentials locally. Delete the analytics.enabled/host/port/database/prefix/timezone/password/username Redis settings from the server .env. They are no longer read. Never commit credentials.

In aaPanel Cron, remove the old analytics:sync job. Add Shell Script, every hour:

```bash
cd /www/wwwroot/YOUR_PROJECT && flock -n /tmp/mysql-analytics-prune.lock /www/server/php/82/bin/php spark analytics:prune
```

Replace YOUR_PROJECT with the directory containing spark. Use a distinct lock for each site. Cron is required for automatic deletion. Monitor its logs. Each invocation removes up to 500,000 expired rows per table in 5,000-row batches; a large initial backlog may need multiple invocations. No production data is deleted merely by pulling this code.

Retention is a rolling window of today plus the preceding 29 calendar days, using the existing application timezone. It does not erase all statistics at once every month. The command deletes older rows from traffic_daily_visitors, traffic_daily_player_metrics and historical analytics_daily. live_traffic entries older than ten minutes are removed; active status uses a three-minute window.

Visitors are deduplicated by browser key per day. Thirty-day totals count DISTINCT browser keys rather than adding daily totals. Devices use the first observed category per browser/day and may overlap over the whole month. Audience results are cached for five minutes using the application's existing cache driver. Default project cache uses files; analytics does not configure Redis caching.

Daily impressions/plays are updated in MySQL immediately. Existing saved analytics_daily totals remain visible until they age out. Previously stored approximate identities from Redis cannot be reconstructed as individual MySQL visitors, so historical unique counts may have gaps during the switch.

At 200,000–500,000 visitors daily, a 30-day window can still contain 6–15 million visitor-day rows. Retention limits growth but is not a guarantee of low database size or sufficient performance. Monitor MySQL CPU, I/O and query latency. InnoDB can reuse deleted space; the on-disk file need not shrink after deletion. No automatic OPTIMIZE/TRUNCATE is performed.

Validation: php tests/mysql_analytics_test.php and node tests/analytics_heartbeat_test.js. Run deployment commands on the actual server to verify its MySQL connection and migrations.
