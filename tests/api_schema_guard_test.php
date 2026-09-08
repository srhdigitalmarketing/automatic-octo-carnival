<?php
namespace CodeIgniter {class Model {protected $db;public function __construct($db){$this->db=$db;}}}
namespace {
require __DIR__.'/../app/Models/ThirdPartyApi.php';
function check($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
$db=new class {public $exists=true,$fields=['name','status'];public function tableExists($table){return $this->exists;}public function getFieldNames($table){return $this->fields;}};
$model=new App\Models\ThirdPartyApi($db);
check(strpos($model->schemaError(),'provider')!==false,'Missing columns not diagnosed');
$db->exists=false;check(strpos($model->schemaError(),'belum tersedia')!==false,'Missing table not diagnosed');
$db->exists=true;$db->fields=['id','created_at','updated_at','name','provider','api_token','api_base_url','embed_domains','r2_account_id','r2_access_key_id','r2_secret_access_key','r2_bucket','r2_public_url','status'];check($model->schemaError()==='','Ready schema rejected');
echo "PASS: schema readiness handles missing tables/columns without running provider queries.\n";
}
