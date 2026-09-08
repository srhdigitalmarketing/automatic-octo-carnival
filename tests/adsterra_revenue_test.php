<?php
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$class = new ReflectionClass(App\Libraries\AdRevenueToday::class);
$instance = $class->newInstanceWithoutConstructor();
$method = $class->getMethod('adsterraMetrics'); $method->setAccessible(true);
$payload=['items'=>[['date'=>'2018-10-04','revenue'=>1.154,'impression'=>3014],['date'=>'2018-10-05','revenue'=>1.379]],'itemCount'=>2];
$result=$method->invoke($instance,$payload,'2018-10-04');
if ($result['revenue'] !== 1.154 || $result['impressions'] !== 0) throw new RuntimeException('Wrong daily revenue');
$payload['items'][]=['date'=>'2018-10-04','revenue'=>'2.100'];
if ($method->invoke($instance,$payload,'2018-10-04')['revenue'] !== 3.254) throw new RuntimeException('Revenue summation failed');
if ($method->invoke($instance,['items'=>[]],'2018-10-04')['revenue'] !== 0.0) throw new RuntimeException('Empty stats failed');
try {$method->invoke($instance,['items'=>[['revenue'=>'bad']]],'2018-10-04');throw new LogicException('Invalid revenue accepted');} catch (RuntimeException $expected) {}
echo "PASS: Adsterra items, daily date filter, revenue-only sum, empty and invalid response\n";
