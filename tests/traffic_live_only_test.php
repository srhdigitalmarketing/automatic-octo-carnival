<?php
namespace App\Controllers { class BaseController { public $response; } }
namespace {
require __DIR__.'/../app/Controllers/Traffic.php';
$controller = new App\Controllers\Traffic();
$controller->response = new class { public function setJSON($data) { return $data; } };
if ($controller->embed() !== ['ok'=>true, 'tracking'=>'disabled']) throw new RuntimeException('Old heartbeat must be inert');
echo "PASS: old heartbeat succeeds without models, request data or database writes.\n";
}
