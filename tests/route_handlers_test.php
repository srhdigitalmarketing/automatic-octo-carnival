<?php
define('FCPATH',dirname(__DIR__).'/public/');require dirname(__DIR__).'/app/Config/Paths.php';$paths=new Config\Paths();require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$routes=file_get_contents(dirname(__DIR__).'/app/Config/Routes.php');
preg_match_all('~[\'"]([A-Za-z][A-Za-z0-9_/\\\\]*::[A-Za-z_][A-Za-z0-9_]*)(?:/[^\'"]*)?[\'"]~',$routes,$matches);
$handlers=array_unique($matches[1]);foreach($handlers as $handler){[$class,$method]=explode('::',$handler);$class='App\\Controllers\\'.str_replace('/','\\',$class);$reflection=new ReflectionMethod($class,$method);if(!$reflection->isPublic())throw new RuntimeException('Non-public route: '.$handler);}
echo 'PASS: '.count($handlers)." explicit route handlers exist and are public, including inherited methods.\n";
