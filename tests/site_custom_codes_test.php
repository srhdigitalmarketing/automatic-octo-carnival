<?php
namespace App\Controllers\Admin\Settings;
class BaseSettings { public $request; public function validate($rules){return true;} }
require __DIR__.'/../app/Controllers/Admin/Settings/Site.php';
class SiteUnderTest extends Site {
    protected function save(array $data){return $data;}
    protected function updateCustomSlugs(array $slugs){}
    protected function saveLogo(){}
    protected function saveFavicon(){}
}
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);}
foreach([
 ['<script>window.headerOnly=1;</script>','<script>window.footerOnly=1;</script>'],
 ['', '<script>window.footerOnly=1;</script>'],
 ['<script>window.headerOnly=1;</script>', ''],
 ['', ''],
 [null, null],
 [null, '<script>window.footerOnly=1;</script>'],
] as [$header,$footer]){
    $controller=new SiteUnderTest();
    $controller->request=new class($header,$footer){
        private $values;
        function __construct($header,$footer){$this->values=['custom_header_codes'=>$header,'custom_footer_codes'=>$footer];}
        function getMethod(){return 'post';}
        function getPost($keys){$result=[];foreach($keys as $key)$result[$key]=$this->values[$key]??null;return $result;}
    };
    $saved=$controller->update();
    foreach (['custom_header_codes'=>$header,'custom_footer_codes'=>$footer] as $key=>$expected) {
        if ($expected===null) check(!array_key_exists($key,$saved),'Missing field must not overwrite saved code');
        else check(base64_decode($saved[$key])===$expected,'Custom code must be independently encoded once');
    }
}
$view=file_get_contents(__DIR__.'/../app/Views/admin/settings/site/index.php');
check(strpos($view,'form_x_panels/custom_codes.php')<strpos($view,'form_close()'),'Custom code controls must be inside Site form');
echo "PASS: independent header/footer save, footer-only tracking and clearing custom code.\n";
