<?php
// Run against a disposable MySQL instance ONLY: php tests/mysql_integration_test.php 13389
namespace CodeIgniter { class Model { protected $db; public function __construct($db) { $this->db = $db; } } }
namespace {
require __DIR__ . '/../app/Libraries/AnalyticsRetention.php';
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
function cache() { static $cache; return $cache ?? ($cache = new class {
    private $data = [];
    public function get($key) { return $this->data[$key] ?? null; }
    public function save($key, $value, $ttl) { $this->data[$key] = $value; }
}); }
function db_connect() { return $GLOBALS['adapter']; }
if (!isset($argv[1])) { echo "SKIP: supply disposable MySQL port for integration test.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli('127.0.0.1', 'root', '', '', (int)$argv[1]);
$name = 'audit_' . bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$name`"); $mysql->select_db($name);
$adapter = new class($mysql) {
    public $db;
    private $affected = 0;
    public function __construct($db) { $this->db = $db; }
    public function tableExists($table) { return $this->db->query("SHOW TABLES LIKE '" . $this->db->real_escape_string($table) . "'")->num_rows > 0; }
    public function affectedRows() { return $this->affected; }
    public function query($sql, $args = []) {
        $statement = $this->db->prepare($sql);
        if ($args) { $statement->bind_param(str_repeat('s', count($args)), ...$args); }
        $statement->execute(); $this->affected = $statement->affected_rows; $result = $statement->get_result();
        if ($result === false) { return true; }
        return new class($result) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function getResultArray() { return $this->result->fetch_all(MYSQLI_ASSOC); }
            public function getRowArray() { return $this->result->fetch_assoc(); }
        };
    }
};
try {
    $mysql->query('CREATE TABLE traffic_daily_visitors (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, visit_date DATE NOT NULL, visitor_key VARCHAR(64) NOT NULL, platform VARCHAR(12) NOT NULL, created_at DATETIME, updated_at DATETIME, UNIQUE KEY day_visitor (visit_date,visitor_key), KEY day_platform (visit_date,platform)) ENGINE=InnoDB');
    $mysql->query('CREATE TABLE traffic_daily_player_metrics (visit_date DATE PRIMARY KEY, impressions BIGINT DEFAULT 0, play_clicks BIGINT DEFAULT 0, created_at DATETIME, updated_at DATETIME) ENGINE=InnoDB');
    $cutoff = App\Libraries\AnalyticsRetention::cutoff();
    $old = date('Y-m-d', strtotime($cutoff . ' -1 day'));
    foreach ([$cutoff, $old] as $date) {
        $adapter->query('INSERT INTO traffic_daily_visitors (visit_date,visitor_key,platform) VALUES (?, ?, ?)', [$date, 'boundary-browser', 'desktop']);
        $adapter->query('INSERT INTO traffic_daily_player_metrics (visit_date,impressions) VALUES (?, ?)', [$date, 9]);
    }
    $result = (new App\Libraries\AnalyticsRetention())->prune($adapter);
    check($result['traffic_daily_visitors']['deleted'] === 1 && $result['traffic_daily_player_metrics']['deleted'] === 1, 'Only old rows removed');
    check((int)$adapter->query('SELECT COUNT(*) AS total FROM traffic_daily_visitors WHERE visit_date = ?', [$cutoff])->getRowArray()['total'] === 1, 'Boundary date retained');
    $again = (new App\Libraries\AnalyticsRetention())->prune($adapter);
    check($again['traffic_daily_visitors']['deleted'] === 0, 'Cleanup replay safe');
    echo "PASS: real MySQL legacy retention boundary and replay.\n";
} finally {
    $mysql->query("DROP DATABASE `$name`"); $mysql->close();
}

}
