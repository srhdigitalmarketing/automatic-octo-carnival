# Redis analytics on aaPanel (PHP 8.2)

This release moves player heartbeat, daily audience, device and player counters to a dedicated Redis instance. No visitor-level SQL rows are written. It does not change the application's general cache/session driver, delete legacy data, or install software on the server.

## Deploy

Run from your website repository directory (the directory containing `spark`):

```bash
git pull origin main
/www/server/php/82/bin/php --ri redis
/www/server/php/82/bin/php spark migrate
```

Add these keys to the existing `.env` without replacing its database and other settings:

```dotenv
analytics.enabled = true
analytics.host = 127.0.0.1
analytics.port = 6379
analytics.database = 0
analytics.prefix = 'player:analytics:'
analytics.timezone = 'Asia/Jakarta'
analytics.password = 'YOUR_REDIS_PASSWORD'
```

Use your Redis password; use an empty string only if your local Redis has no authentication. For ACL authentication also set `analytics.username`. Never commit `.env`. Give each website a unique prefix. Do not change timezone/prefix/database after tracking starts without planning a cutover. The PHP Redis extension is required for both FPM and CLI.

```bash
/www/server/php/82/bin/php spark cache:clear
/www/server/php/82/bin/php spark analytics:sync
```

Zero days synchronized before any player visit is normal. Open a player, then run sync again and check the dashboard. Audience and Devices read live Redis estimates; Daily Player Analytics reads the latest SQL snapshot. The initial 30-day window contains only visits since activation; old audience rows are not imported. Historical player impressions/plays remain visible from the legacy daily table.

## aaPanel Cron

Cron > Add Task > Shell Script > every 5 minutes. Replace `/www/wwwroot/YOUR_SITE` with the absolute repository path containing `spark`:

```bash
cd /www/wwwroot/YOUR_SITE && flock -n /tmp/player-analytics-sync.lock /www/server/php/82/bin/php spark analytics:sync
```

Use a distinct lock filename for each site. Check Cron execution logs: success prints the number of synchronized days; errors exit nonzero. Migration requires normal database schema privileges. Cron needs the same `.env` and writable permissions as the website.

## Storage and recovery

- Redis retains daily HLLs/counters for 35 days, with day-boundary expiration. Active browser entries expire after 3 minutes of inactivity and the online key expires after 5 minutes without traffic.
- MySQL `analytics_daily` stores one global row per active date (365 rows/year); raw visitor IDs are not stored there. Historical rollups are retained. Old `traffic_daily_visitors` and `live_traffic` data is left untouched; archive/cleanup is a separate operation.
- Thirty-day uniques use HLL union, never the sum of daily unique totals. Device categories can overlap. These are estimated browser identities, not exact people; blocked storage/scripts, bots and repeated page loads affect results. Daily metric dates use the configured analytics timezone; older metrics retain their original application date boundaries.
- Sync rereads all 35 retained dates, uses absolute snapshots and monotonic SQL updates, and never clears Redis counters. Repeated or overlapping syncs cannot double counts or reduce stored values.
- SQL snapshots are not a backup of HLL identity state. Redis loss can cause missing current-day events and reset rolling-unique estimates. Restore Redis persistence/backups for continuity; SQL alone cannot reconstruct unique visitors or recover unsynced events. Monotonic snapshots protect previous totals but cannot perfectly merge a reset day's counters.
- Redis failures produce a failed analytics response without a per-request MySQL fallback. The player's analytics requests are background requests; playback is independent. Failed submissions may leave gaps. This is operational analytics, not exact billing accounting.

## Redis server

Use localhost binding/authentication, a dedicated instance, AOF with `appendfsync everysec`, `maxmemory 512mb` as an initial monitored cap, and `maxmemory-policy noeviction`. Leave RAM for MySQL, PHP, OS and Redis persistence buffers. Do not expose port 6379 publicly. A Redis logical database number does not isolate memory/eviction policies from other applications on the same instance.

Monitor used memory, rejected writes, CPU, disk/AOF growth and Cron failures in aaPanel/Redis. Daily impressions alone do not describe peak load or heartbeat volume. Test under realistic load before increasing traffic. Do not run FLUSHDB/FLUSHALL against production.

## Verification

1. Same browser, repeated heartbeats: unique count stays stable and heartbeat does not add impressions.
2. A new page load adds an impression; Play adds a click.
3. Sync twice: SQL totals stay the same unless new activity arrived.
4. Verify Desktop/Mobile estimates, 30-day union and active users.
5. Verify Cron catches up after downtime, and Redis failure does not stop playback.

Local regression: `php tests/redis_analytics_test.php 16389` against a disposable localhost Redis instance. The test uses an isolated random key prefix and removes only its own keys. It exercises the production Lua scripts through a small RESP test adapter when phpredis is unavailable locally; production still requires phpredis.
