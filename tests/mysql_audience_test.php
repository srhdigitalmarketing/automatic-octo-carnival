<?php
require __DIR__ . '/../app/Libraries/MysqlAudience.php';
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
function cache() { static $cache; return $cache ?? ($cache = new class {
    public $data = [];
    public function get($key) { return $this->data[$key] ?? null; }
    public function save($key, $value, $ttl) { $this->data[$key] = $value; }
}); }
function db_connect() { static $db; return $db ?? ($db = new class {
    public $calls = [];
    public function query($sql, $args) {
        $this->calls[] = [$sql, $args];
        if (strpos($sql, 'INSERT') === 0) { return true; }
        if (strpos($sql, 'GROUP BY visit_date') !== false) {
            $rows = [['visit_date' => date('Y-m-d'), 'total' => 2], ['visit_date' => date('Y-m-d', strtotime('-1 day')), 'total' => 1]];
        } elseif (strpos($sql, 'GROUP BY platform') !== false) {
            $rows = [['platform' => 'desktop', 'total' => 1], ['platform' => 'mobile', 'total' => 1]];
        } else {
            check(strpos($sql, 'COUNT(DISTINCT visitor_key)') !== false, 'Unique total SQL');
            $rows = [['total' => 2]];
        }
        return new class($rows) {
            private $rows;
            public function __construct($rows) { $this->rows = $rows; }
            public function getResultArray() { return $this->rows; }
            public function getRowArray() { return $this->rows[0]; }
        };
    }
}); }
$service = new App\Libraries\MysqlAudience();
$service->record('test-browser-123456', 'iPhone Mobile');
$insert = db_connect()->calls[0];
check(strpos($insert[0], 'ON DUPLICATE KEY UPDATE') !== false, 'Daily deduplication');
check($insert[1][2] === 'mobile', 'Device category');
$data = $service->audience();
check(count($data['dates']) === 30 && count($data['daily']) === 30, 'Thirty chart days');
check($data['total'] === 2 && array_sum($data['daily']) === 3, 'Total uses distinct query rather than daily sum');
check($data['platforms']['mobile'] === 1 && $data['platforms']['tablet'] === 0, 'All device categories present');
$count = count(db_connect()->calls); $service->audience();
check(count(db_connect()->calls) === $count, 'Dashboard cache avoids repeat SQL');
echo "PASS: audience data contract, SQL deduplication, devices and cache.\n";
