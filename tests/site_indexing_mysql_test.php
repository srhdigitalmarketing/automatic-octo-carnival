<?php
if(!isset($argv[1])||(int)$argv[1]!==13389){echo "SKIP: supply isolated MySQL port 13389.\n";exit(0);}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql=new mysqli('127.0.0.1','root','','',13389);
$databaseName='site_indexing_'.bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach(['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$databaseName,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value)putenv('database.default.'.$key.'='.$value);
define('FCPATH',dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php';$paths=new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
use App\Libraries\SiteIndexing;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function saveIndexing($post,$method='post') {
    $controller=new App\Controllers\Admin\Settings\Indexing();
    $request=Config\Services::request(null,false);$request->setMethod($method)->setGlobal('post',$post);
    foreach(['request'=>$request,'response'=>Config\Services::response(null,false)] as $name=>$value){$p=new ReflectionProperty($controller,$name);$p->setAccessible(true);$p->setValue($controller,$value);}
    session()->remove(['success','errors']);return $controller->update();
}
function reloadPolicy(): void {CodeIgniter\Config\Factories::reset('config');SiteIndexing::reset();}
try {
    $db=db_connect();check($db->database===$databaseName,'Only disposable database');
    $db->query('CREATE TABLE settings (name VARCHAR(255) PRIMARY KEY, value TEXT, data_type VARCHAR(20)) ENGINE=InnoDB');
    $db->table('settings')->insertBatch([
        ['name'=>'site_name','value'=>'Original website','data_type'=>'string'],
        ['name'=>'custom_header_codes','value'=>base64_encode('<script>test()</script>'),'data_type'=>'string'],
        ['name'=>'host_file_check_1','value'=>'0','data_type'=>'bool']]);
    foreach(['movies','links','third_party_apis','popup_ad_units'] as $table){$db->query('CREATE TABLE '.$table.' (id INT PRIMARY KEY, value VARCHAR(255))');$db->table($table)->insert(['id'=>1,'value'=>'preserve']);}
    $before=$db->table('settings')->orderBy('name')->get()->getResultArray();
    helper(['form','general','config','url']);
    check(SiteIndexing::noIndex(),'Missing key preserves existing No Index');
    $html=view('admin/settings/site/form_x_panels/indexing');
    check(preg_match('/value="1"\s+selected/',$html),'No Index selected initially');
    foreach([[],['site_noindex'=>'unexpected'],['site_noindex'=>['1']]] as $post){saveIndexing($post);check((bool)session()->get('errors'),'Invalid or missing setting rejected');}
    check(saveIndexing(['site_noindex'=>'0'],'get')->getStatusCode()===405,'GET cannot change setting');
    check($db->table('settings')->orderBy('name')->get()->getResultArray()===$before,'Invalid requests preserve all settings');
    saveIndexing(['site_noindex'=>'0']);check((bool)session()->get('success'),'Index save confirmed');reloadPolicy();
    check(!SiteIndexing::noIndex() && SiteIndexing::robots()===SiteIndexing::INDEX,'Index persists across requests');
    $html=view('admin/settings/site/form_x_panels/indexing');check(preg_match('/value="0"\s+selected/',$html),'Form reflects Index');
    saveIndexing(['site_noindex'=>'1']);saveIndexing(['site_noindex'=>'1']);reloadPolicy();
    check(SiteIndexing::noIndex(),'No Index can be enabled again');
    check($db->table('settings')->where('name','site_noindex')->countAllResults()===1,'Saving twice does not duplicate');
    $row=$db->table('settings')->where('name','site_noindex')->get()->getRowArray();check($row['value']==='1'&&$row['data_type']==='bool','Saved as boolean setting');
    check($db->table('settings')->where('name !=','site_noindex')->orderBy('name')->get()->getResultArray()===$before,'Other settings unchanged');
    foreach(['movies','links','third_party_apis','popup_ad_units'] as $table)check($db->table($table)->get()->getResultArray()===[['id'=>'1','value'=>'preserve']],'Preserved '.$table);
    $db->query('ALTER TABLE settings RENAME COLUMN value TO unavailable_value');$db->resetDataCache();reloadPolicy();
    // Failure is controlled and must not claim success or expose a query.
    saveIndexing(['site_noindex'=>'0']);check((bool)session()->get('errors')&&!session()->get('success'),'Failed persistence never reports success');
    echo "PASS: real settings form/default, Index and No Index save/reload, idempotence, invalid/GET rejection, unchanged content and controlled database error\n";
} finally {
    if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
    $mysql->query("DROP DATABASE `$databaseName`");$mysql->close();
}
