<?php
declare(strict_types=1);
function get_config($name){return null;}
function form_checkbox($name,$value,bool $checked){if($checked)throw new RuntimeException('Null config must be unchecked');return '';}
$count=0;$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app/Views/admin/settings'));
foreach($iterator as $file){if(!$file->isFile()||$file->getExtension()!=='php')continue;
 preg_match_all('/<\?=\s*(form_checkbox[^\n]*get_config[^\n]*?)\s*\?>/',file_get_contents($file->getPathname()),$matches);
 foreach($matches[1] as $expression){eval($expression.';');$count++;}
}
if($count<10)throw new RuntimeException('Checkbox view coverage incomplete');
echo "PASS: $count real settings checkbox expressions accept missing configuration.\n";
