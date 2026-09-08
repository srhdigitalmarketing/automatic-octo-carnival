-- Run only on the application database after deploying commit 164641d or newer.
-- Export these tables first if you need to preserve their historical data.
-- This is deliberately manual, not part of php spark migrate.
-- No visitor identifiers are recorded by the current application.
DROP TABLE IF EXISTS `traffic_daily_visitors`;
DROP TABLE IF EXISTS `analytics_daily`;
