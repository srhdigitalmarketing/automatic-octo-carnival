<?php
namespace App\Controllers { class BaseController { protected $response; } }
namespace App\Models { class ThirdPartyApi { public function schemaError() { return 'fixture'; } } }
namespace App\Libraries { class AdRevenueToday { public function synchronize() { if (!$GLOBALS['released']) throw new \RuntimeException('Revenue API started with a session lock'); return []; } } }
namespace App\Controllers\Admin {
    function session_status() { return PHP_SESSION_ACTIVE; }
    function session_write_close() { $GLOBALS['released'] = true; }
}
namespace {
require __DIR__.'/../app/Controllers/Admin/Dashboard.php';
require __DIR__.'/../app/Controllers/Admin/ThirdPartyApis.php';
foreach (['Dashboard'=>'revenue_today', 'ThirdPartyApis'=>'result'] as $name=>$method) {
    $GLOBALS['released'] = false;
    $class = 'App\\Controllers\\Admin\\'.$name;
    $controller = new $class();
    $response = new class { public function setStatusCode($code) { return $this; } public function setJSON($data) { if (!$GLOBALS['released']) throw new RuntimeException('Session lock retained'); return $data; } };
    $property = new ReflectionProperty($controller, 'response'); $property->setAccessible(true); $property->setValue($controller, $response);
    $controller->$method();
    if (!$GLOBALS['released']) throw new RuntimeException('Session lock retained in '.$method);
}
echo "PASS: authenticated read-only API actions release the session before external work.\n";
}
