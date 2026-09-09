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
function db_connect(){return $GLOBALS['histatsStore'];}
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);}
require __DIR__.'/../app/Libraries/HistatsCode.php';
require __DIR__.'/../app/Controllers/Admin/Settings/Histats.php';
$code=trim(file_get_contents(__DIR__.'/fixtures/histats-async.html'));
$GLOBALS['histatsStore']=new Store();
function saveHistats($data,$method='post'){
    $controller=new Histats();
    $controller->request=new class($data,$method){
        private $data; private $method;
        function __construct($data,$method){$this->data=$data;$this->method=$method;}
        function getMethod(){return $this->method;}
        function getPost($key){return $this->data[$key]??null;}
    };
    return $controller->update();
}
check(isset(saveHistats(['histats_enabled'=>'1'])->flash['errors']),'Empty enabled ID must fail');
check(isset(saveHistats(['histats_code'=>'<script>'])->flash['errors']),'Invalid ID must fail');
saveHistats([],'get');
check($GLOBALS['histatsStore']->transactions===0,'Invalid input/GET wrote settings');
check(isset(saveHistats(['histats_enabled'=>'1','histats_code'=>$code,'histats_scope'=>'public'])->flash['success']),'Save failed');
$rows=$GLOBALS['histatsStore']->rows;
check($rows['histats_code']['value']===$code,'Counter code was modified');
check($rows['histats_enabled']['value']==='1' && $rows['histats_enabled']['data_type']==='bool','Enabled type failed');
check($rows['histats_scope']['value']==='public','Public scope failed');
saveHistats(['histats_code'=>'','histats_scope'=>'unexpected']);
$rows=$GLOBALS['histatsStore']->rows;
check(count($rows)===4,'Update duplicated or removed settings');
check($rows['existing_setting']['value']==='preserve','Other settings changed');
check($rows['histats_enabled']['value']==='0' && $rows['histats_scope']['value']==='embed','Disabled/default scope failed');
echo "PASS: HiStats settings create/update, validation, disable and existing settings preservation.\n";
