<?php
require __DIR__ . '/../app/Libraries/RedisAnalytics.php';
use App\Libraries\RedisAnalytics;

// Real Redis protocol adapter: test the production Lua without requiring a local PHP DLL.
class TestRedis
{
    private $socket;
    public function __construct(int $port) {
        $this->socket = stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $message, 2);
        if (! $this->socket) { throw new RuntimeException($message); }
        stream_set_timeout($this->socket, 2);
    }
    public function command(array $args) {
        $wire = '*' . count($args) . "\r\n";
        foreach ($args as $arg) { $arg = (string) $arg; $wire .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n"; }
        fwrite($this->socket, $wire);
        return $this->read();
    }
    private function read() {
        $line = fgets($this->socket);
        if ($line === false) { throw new RuntimeException('Redis response missing'); }
        $value = substr($line, 1, -2);
        switch ($line[0]) {
            case '+': return $value;
            case '-': throw new RuntimeException($value);
            case ':': return (int) $value;
            case '$':
                if ((int) $value < 0) { return null; }
                $data = ''; $length = (int) $value + 2;
                while (strlen($data) < $length) { $data .= fread($this->socket, $length - strlen($data)); }
                return substr($data, 0, -2);
            case '*':
                $items = []; for ($i=0; $i<(int) $value; $i++) { $items[] = $this->read(); } return $items;
        }
        throw new RuntimeException('Invalid RESP');
    }
    public function eval($lua, $args, $count) { return $this->command(array_merge(['EVAL', $lua, $count], $args)); }
    public function pfCount($keys) { return $this->command(array_merge(['PFCOUNT'], $keys)); }
}
function check($condition, $message) { if (! $condition) { throw new RuntimeException($message); } }
$redis = new TestRedis((int) ($argv[1] ?? 16389));
$prefix = 'analytics-test:' . bin2hex(random_bytes(8)) . ':';
$reflection = new ReflectionClass(RedisAnalytics::class);
$analytics = $reflection->newInstanceWithoutConstructor();
foreach (['redis' => $redis, 'prefix' => $prefix, 'zone' => new DateTimeZone('Asia/Jakarta')] as $name => $value) {
    $property = $reflection->getProperty($name); $property->setAccessible(true); $property->setValue($analytics, $value);
}
try {
    $dates = $analytics->dates(); $today = end($dates); $yesterday = $dates[count($dates)-2];
    check(count($dates) === 30, 'Thirty daily buckets');
    check(RedisAnalytics::platform('Mozilla iPhone Mobile') === 'mobile', 'Phone classification');
    check(RedisAnalytics::platform('Mozilla Android Tablet') === 'tablet', 'Tablet classification');
    $analytics->record('browser-a', 'desktop', true, false);
    $analytics->record('browser-a', 'desktop', false, false);
    $analytics->record('browser-a', 'desktop', false, true);
    $analytics->record('browser-b', 'mobile', true, false);
    $row = $analytics->snapshot($today);
    check($row['impressions'] === 2 && $row['play_clicks'] === 1, 'Heartbeats do not increment impressions');
    check($row['unique_visitors'] === 2 && $row['desktop'] === 1 && $row['mobile'] === 1, 'Unique/device counts');
    $redis->command(['PFADD', $prefix . $yesterday . ':users', hash('sha256', 'browser-a')]);
    $summary = $analytics->audience();
    check($summary['total'] === 2 && array_sum($summary['daily']) === 3, 'Thirty-day uniques must union, not sum');
    check($analytics->active() === 2, 'Active visitors');
    $redis->command(['ZADD', $prefix . 'online', time()-181, 'stale']);
    check($analytics->active() === 2, 'Expired visitor pruned');
    $ttl = $redis->command(['TTL', $prefix . $today . ':users']);
    check($ttl > 33*86400 && $ttl <= 35*86400, 'Bounded retention');
    $db = new class {
        public $calls = [];
        public function tableExists($name) { return $name === 'analytics_daily'; }
        public function query($sql, $values) { $this->calls[] = [$sql, $values]; return true; }
    };
    check($analytics->sync($db) === 2, 'Sync catches up previous day');
    $first = $db->calls; $db->calls = []; $analytics->sync($db);
    foreach ($db->calls as $index => [$sql, $values]) {
        check(substr_count($sql, 'GREATEST(') === 7, 'All snapshot columns monotonic');
        check(array_slice($values, 0, 8) === array_slice($first[$index][1], 0, 8), 'Replay preserves absolute totals');
    }
    check($analytics->snapshot($today) === $row, 'Sync never clears Redis');
    $redis->command(['DEL', $prefix . $today . ':counts']);
    $redis->command(['SET', $prefix . $today . ':counts', 'invalid-type']);
    $failed = false;
    try { $analytics->record('browser-a', 'desktop', true, false); } catch (Throwable $error) { $failed = true; }
    check($failed, 'Redis errors propagate instead of acknowledging lost events');
    echo "PASS: real Redis counters, heartbeat, HLL union, devices, TTL, active expiry, sync replay/catch-up, errors.\n";
} finally {
    // Isolated disposable test prefix only; never flush the database.
    $keys = $redis->command(['KEYS', $prefix . '*']);
    if ($keys) { $redis->command(array_merge(['DEL'], $keys)); }
}
