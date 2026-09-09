<?php
namespace App\Controllers {
    class BaseController { public $request; public $response; }
    function db_connect() { return new class { public function tableExists($table) { if ($table !== 'live_traffic') throw new \RuntimeException('Daily table accessed'); return true; } }; }
}
namespace App\Models { class LiveTrafficModel { public static $touches=0; public function touchEmbedVisitor($key) { self::$touches++; } } }
namespace {
require __DIR__.'/../app/Controllers/Traffic.php';
$controller = new App\Controllers\Traffic();
$controller->request = new class {
    public $valid = true;
    public function isAJAX() { return true; }
    public function getPost($key) { return ['visitor_key'=>$this->valid ? 'visitor_fixture_1234' : 'bad', 'record_impression'=>'1', 'event'=>'play'][$key] ?? null; }
};
$controller->response = new class { public $code=200; public function setStatusCode($code) { $this->code=$code; return $this; } public function setJSON($data) { return $data; } };
$result=$controller->embed();
if ($result !== ['ok'=>true] || App\Models\LiveTrafficModel::$touches !== 1) throw new RuntimeException('Legacy event must only update live traffic');
$controller->request->valid=false;
$result=$controller->embed();
if ($controller->response->code !== 422 || App\Models\LiveTrafficModel::$touches !== 1) throw new RuntimeException('Invalid heartbeat wrote data');
echo "PASS: legacy impression/play payload only touches live traffic; invalid visitor is rejected.\n";
}
