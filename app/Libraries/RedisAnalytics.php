<?php

namespace App\Libraries;

/** Aggregate analytics only: no per-visitor SQL writes. */
class RedisAnalytics
{
    private $redis;
    private $prefix;
    private $zone;
    const DEVICES = ['desktop', 'mobile', 'tablet', 'other'];

    public function __construct()
    {
        if (! filter_var(env('analytics.enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException('Redis analytics is disabled.');
        }
        if (! extension_loaded('redis')) {
            throw new \RuntimeException('PHP Redis extension is unavailable.');
        }
        $this->prefix = (string) env('analytics.prefix', 'player:analytics:');
        $this->zone = new \DateTimeZone((string) env('analytics.timezone', 'Asia/Jakarta'));
        $this->redis = new \Redis();
        $this->redis->connect((string) env('analytics.host', '127.0.0.1'), (int) env('analytics.port', 6379), 1.0);
        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, 1.0);
        $password = (string) env('analytics.password', '');
        if ($password !== '') {
            $username = (string) env('analytics.username', '');
            if (! $this->redis->auth($username === '' ? $password : [$username, $password])) {
                throw new \RuntimeException('Redis authentication failed.');
            }
        }
        if (! $this->redis->select((int) env('analytics.database', 0))) {
            throw new \RuntimeException('Redis database selection failed.');
        }
    }

    public function dates(int $days = 30): array
    {
        $today = new \DateTimeImmutable('today', $this->zone);
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = $today->modify("-{$i} days")->format('Y-m-d');
        }
        return $dates;
    }

    public static function platform(string $agent): string
    {
        if ($agent === '') { return 'other'; }
        if (preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $agent)) { return 'tablet'; }
        if (preg_match('/mobile|iphone|ipod|android/i', $agent)) { return 'mobile'; }
        return 'desktop';
    }

    private function evaluate(string $script, array $keys, array $args = [])
    {
        $result = $this->redis->eval($script, array_merge($keys, $args), count($keys));
        if ($result === false) { throw new \RuntimeException('Redis analytics operation failed.'); }
        return $result;
    }

    public function record(string $visitor, string $platform, bool $impression, bool $play): void
    {
        if (! in_array($platform, self::DEVICES, true)) { $platform = 'other'; }
        $today = new \DateTimeImmutable('today', $this->zone);
        $base = $this->prefix . $today->format('Y-m-d');
        // Expiry is anchored to the day, never extended by later heartbeats.
        $expires = $today->modify('+35 days')->getTimestamp();
        $script = <<<'LUA'
redis.call('PFADD', KEYS[1], ARGV[1])
redis.call('PFADD', KEYS[2], ARGV[1])
if ARGV[2] == '1' then redis.call('HINCRBY', KEYS[3], 'impressions', 1) end
if ARGV[3] == '1' then redis.call('HINCRBY', KEYS[3], 'play_clicks', 1) end
for i=1,3 do redis.call('EXPIREAT', KEYS[i], ARGV[4]) end
redis.call('ZADD', KEYS[4], ARGV[5], ARGV[1])
redis.call('ZREMRANGEBYSCORE', KEYS[4], '-inf', tonumber(ARGV[5])-180)
redis.call('EXPIRE', KEYS[4], 300)
return 1
LUA;
        $this->evaluate($script, [$base . ':users', $base . ':device:' . $platform, $base . ':counts', $this->prefix . 'online'],
            [hash('sha256', $visitor), $impression ? '1' : '0', $play ? '1' : '0', $expires, time()]);
    }

    public function active(): int
    {
        return (int) $this->evaluate("redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', ARGV[1]); return redis.call('ZCARD', KEYS[1])", [$this->prefix . 'online'], [time()-180]);
    }

    public function snapshot(string $date): array
    {
        $base = $this->prefix . $date;
        $keys = [$base . ':counts', $base . ':users'];
        foreach (self::DEVICES as $device) { $keys[] = $base . ':device:' . $device; }
        $values = $this->evaluate(<<<'LUA'
local out = {tonumber(redis.call('HGET', KEYS[1], 'impressions') or '0'), tonumber(redis.call('HGET', KEYS[1], 'play_clicks') or '0')}
for i=2,6 do out[#out+1] = redis.call('PFCOUNT', KEYS[i]) end
return out
LUA, $keys);
        return array_combine(['impressions', 'play_clicks', 'unique_visitors', 'desktop', 'mobile', 'tablet', 'other'], array_map('intval', $values));
    }

    public function audience(): array
    {
        $dates = $this->dates();
        $result = ['dates' => $dates, 'labels' => [], 'daily' => [], 'total' => 0, 'platforms' => [], 'tracking_ready' => true,
            'notice' => 'Estimasi pengunjung unik browser (Redis HyperLogLog).'];
        foreach ($dates as $date) {
            $result['labels'][] = (new \DateTimeImmutable($date))->format('d M');
            $result['daily'][] = $this->snapshot($date)['unique_visitors'];
        }
        // Union, not addition: returning browsers count once across the period.
        foreach (array_merge(['users'], array_map(function ($d) { return 'device:' . $d; }, self::DEVICES)) as $suffix) {
            $keys = array_map(function ($date) use ($suffix) { return $this->prefix . $date . ':' . $suffix; }, $dates);
            $count = $this->redis->pfCount($keys);
            if ($count === false) { throw new \RuntimeException('Redis unique count failed.'); }
            if ($suffix === 'users') { $result['total'] = (int) $count; }
            else { $result['platforms'][substr($suffix, 7)] = (int) $count; }
        }
        return $result;
    }

    public function sync($db): int
    {
        if (! $db->tableExists('analytics_daily')) { throw new \RuntimeException('Run php spark migrate first.'); }
        $count = 0;
        // Catch up retained days after a missed cron run, including midnight rollover.
        foreach ($this->dates(35) as $date) {
            $row = $this->snapshot($date);
            if (array_sum($row) === 0) { continue; }
            $columns = array_keys($row);
            $updates = array_map(function ($column) {
                // A replay, stale cron, or Redis reset cannot reduce persisted totals.
                return "`{$column}` = GREATEST(`{$column}`, VALUES(`{$column}`))";
            }, $columns);
            $sql = 'INSERT INTO `analytics_daily` (`visit_date`, `' . implode('`, `', $columns) . '`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE '
                . implode(', ', $updates) . ', `updated_at` = VALUES(`updated_at`)';
            if ($db->query($sql, array_merge([$date], array_values($row), [date('Y-m-d H:i:s')])) === false) {
                throw new \RuntimeException('Analytics snapshot could not be saved.');
            }
            $count++;
        }
        return $count;
    }
}
