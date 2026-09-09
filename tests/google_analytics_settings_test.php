<?php
namespace App\Controllers\Admin\Settings;
class BaseSettings { public $request; }
class Response { public $flash=[]; function to($url){return $this;} function withInput(){return $this;} function with($key,$value){$this->flash[$key]=$value;return $this;} }
class Store {
    public $rows=['existing_setting'=>['value'=>'preserve']]; public $name; public $transactions=0;
    function transStart(){$this->transactions++;} function transComplete(){} function transStatus(){return true;}
    function table($table){if($table!=='settings')throw new \RuntimeException('Unexpected table');return $this;}
    function where($key,$name){$this->name=$name;return $this;}
    function countAllResults(){return isset($this->rows[$this->name])?1:0;}
    function update($data){$this->rows[$this->name]=$data;}
    function insert($data){$this->rows[$data['name']]=$data;}
}
function admin_url($path){return '/admin'.$path;}
function redirect(){return new Response();}
function db_connect(){return $GLOBALS['gaStore'];}
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);}
require __DIR__.'/../app/Controllers/Admin/Settings/GoogleAnalytics.php';
$GLOBALS['gaStore']=new Store();
function saveGa($data,$method='post'){
    $controller=new GoogleAnalytics();
    $controller->request=new class($data,$method){
        private $data; private $method;
        function __construct($data,$method){$this->data=$data;$this->method=$method;}
        function getMethod(){return $this->method;}
        function getPost($key){return $this->data[$key]??null;}
    };
    return $controller->update();
}
check(isset(saveGa(['ga4_enabled'=>'1'])->flash['errors']),'Empty enabled ID must fail');
check(isset(saveGa(['ga4_measurement_id'=>'<script>'])->flash['errors']),'Invalid ID must fail');
saveGa([],'get');
check($GLOBALS['gaStore']->transactions===0,'Invalid input/GET wrote settings');
check(isset(saveGa(['ga4_enabled'=>'1','ga4_measurement_id'=>' g-test12345 ','ga4_scope'=>'public'])->flash['success']),'Save failed');
$rows=$GLOBALS['gaStore']->rows;
check($rows['ga4_measurement_id']['value']==='G-TEST12345','ID normalization failed');
check($rows['ga4_enabled']['value']==='1' && $rows['ga4_enabled']['data_type']==='bool','Enabled type failed');
check($rows['ga4_scope']['value']==='public','Public scope failed');
saveGa(['ga4_measurement_id'=>'','ga4_scope'=>'unexpected']);
$rows=$GLOBALS['gaStore']->rows;
check(count($rows)===4,'Update duplicated or removed settings');
check($rows['existing_setting']['value']==='preserve','Other settings changed');
check($rows['ga4_enabled']['value']==='0' && $rows['ga4_scope']['value']==='embed','Disabled/default scope failed');
echo "PASS: GA4 settings create/update, validation, disable and existing settings preservation.\n";
