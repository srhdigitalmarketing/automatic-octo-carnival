<?php
require __DIR__ . '/../app/Libraries/AnalyticsRetention.php';
use App\Libraries\AnalyticsRetention;
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
check(AnalyticsRetention::cutoff(new DateTimeImmutable('2026-09-07')) === '2026-08-09', '30 calendar dates');
check(AnalyticsRetention::cutoff(new DateTimeImmutable('2024-03-01')) === '2024-02-01', 'Leap year');
$db = new class {
    public $queries = [];
    public function tableExists($table) { return $table !== 'analytics_daily'; }
    public function query($sql, $args) { $this->queries[] = [$sql, $args]; return true; }
    public function affectedRows() { return count($this->queries) === 1 ? 5000 : 2; }
};
$result = (new AnalyticsRetention())->prune($db);
check($result['traffic_daily_visitors']['deleted'] === 5002, 'Continue full batch');
check($result['traffic_daily_player_metrics']['deleted'] === 2, 'Process metrics');
check(!isset($result['analytics_daily']), 'Skip absent legacy table');
foreach ($db->queries as [$sql, $args]) {
    check(strpos($sql, 'WHERE `visit_date` < ? LIMIT 5000') !== false, 'Bounded and exclusive cutoff');
    check($args === [AnalyticsRetention::cutoff()], 'Cutoff is bound');
}
$full = new class {
    public $calls = 0;
    public function tableExists($table) { return $table === 'traffic_daily_visitors'; }
    public function query($sql, $args) { $this->calls++; return true; }
    public function affectedRows() { return 5000; }
};
$result = (new AnalyticsRetention())->prune($full);
check($full->calls === 100 && $result['traffic_daily_visitors']['more'], 'Cap large backlog');
$bad = new class {
    public function tableExists($table) { return true; }
    public function query($sql, $args) { return false; }
};
$failed = false;
try { (new AnalyticsRetention())->prune($bad); } catch (RuntimeException $e) { $failed = true; }
check($failed, 'Propagate deletion errors');
echo "PASS: retention boundary, leap year, batching, backlog cap, absent tables, failures.\n";
