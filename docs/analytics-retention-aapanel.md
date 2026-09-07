# Automatic 30-day analytics retention (aaPanel / PHP 8.2)

This addition preserves the restored 9fe983f application behavior. Audience recording remains disabled as in that commit. Redis is not configured, no .env is added, and database connection settings are unchanged.

From the website project folder containing spark:

```bash
git pull origin main
/www/server/php/82/bin/php spark cache:clear
/www/server/php/82/bin/php spark analytics:prune
```

To make deletion automatic, create aaPanel Cron > Shell Script > every hour:

```bash
cd /www/wwwroot/YOUR_PROJECT && flock -n /tmp/analytics-retention.lock /www/server/php/82/bin/php spark analytics:prune
```

Replace YOUR_PROJECT with your actual project path. Use a distinct lock filename for each website. Remove obsolete analytics:sync jobs; if analytics:prune already exists, update it instead of adding a duplicate. Automatic deletion requires this Cron to be active. No deletion runs on website requests or simply by pulling the code.

The command retains today and the preceding 29 calendar dates, using the application's existing timezone. For example, on 2026-09-07 it deletes visit_date before 2026-08-09. It cleans traffic_daily_visitors and traffic_daily_player_metrics, plus analytics_daily if that table remains from an earlier deployment. Missing tables are skipped. No schema migration is needed for this addition.

Deletes run in batches of 5,000, capped at 500,000 rows per table per invocation. If the batch limit is reached, the log asks for another run; subsequent hourly runs continue the backlog. Failures exit nonzero and should be checked in aaPanel Cron logs. Old rows are permanently deleted when this command runs. Existing short-lived live_traffic cleanup remains unchanged.

InnoDB can reuse deleted space without immediately reducing the physical database file size. No TRUNCATE or OPTIMIZE is issued. Data within 30 days can still be large at high traffic.
