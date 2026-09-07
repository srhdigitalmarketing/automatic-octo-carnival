<?php
require __DIR__ . '/../app/Libraries/MysqlAnalytics.php';
use App\Libraries\MysqlAnalytics;
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
check(MysqlAnalytics::cutoff(new DateTimeImmutable('2026-09-07')) === '2026-08-09', 'Keep exactly 30 calendar dates');
check(MysqlAnalytics::cutoff(new DateTimeImmutable('2024-03-01')) === '2024-02-01', 'Leap-year boundary');
check(MysqlAnalytics::platform('Android Mobile') === 'mobile', 'Mobile category');
$db = new class {
    public $queries = [];
    public function tableExists($table) { return $table !== 'analytics_daily'; }
    public function query($sql, $args) { $this->queries[] = [$sql, $args]; return true; }
    public function affectedRows() { return count($this->queries) === 1 ? 5000 : 2; }
};
check((new MysqlAnalytics())->prune($db) === 5006, 'Batch deletion totals');
check(count($db->queries) === 4, 'Full batch continues and missing table skipped');
foreach ($db->queries as [$sql, $args]) {
    check(strpos($sql, 'LIMIT 5000') !== false, 'Bounded deletes');
    check(strpos($sql, '< ?') !== false, 'Keep cutoff day inclusively');
    check(strpos($sql, 'movies') === false, 'Never remove video data');
}
check($db->queries[0][1][0] === MysqlAnalytics::cutoff(), 'Correct daily cutoff binding');
$failed = false;
$bad = new class {
    public function tableExists($table) { return true; }
    public function query($sql, $args) { return false; }
};
try { (new MysqlAnalytics())->prune($bad); } catch (RuntimeException $e) { $failed = true; }
check($failed, 'Report cleanup failure');
echo "PASS: 30-day boundary, leap year, batch continuation, cutoff binding, missing tables, failure handling.\n";
