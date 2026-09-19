<?php
if (!isset($argv[1]) || (int)$argv[1] !== 13389) { echo "SKIP: supply isolated MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql=new mysqli('127.0.0.1','root','','',13389);
$databaseName='player_appearance_'.bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach (['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$databaseName,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value) putenv('database.default.'.$key.'='.$value);
define('FCPATH',dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php'; $paths=new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
function savePlayer($post) {
    $controller=new App\Controllers\Admin\Settings\Player();
    $request=Config\Services::request(null,false); $request->setMethod('post')->setGlobal('post',$post)->setGlobal('request',$post);
    Config\Services::validation()->reset();
    foreach (['request'=>$request,'response'=>Config\Services::response(null,false)] as $name=>$value) {
        $p=new ReflectionProperty($controller,$name); $p->setAccessible(true); $p->setValue($controller,$value);
    }
    session()->remove(['success','errors']); return $controller->update();
}
try {
    $db=db_connect(); check($db->database===$databaseName,'Disposable database only');
    $db->query('CREATE TABLE settings (name VARCHAR(255) PRIMARY KEY, value TEXT, data_type VARCHAR(20)) ENGINE=InnoDB');
    $db->table('settings')->insert(['name'=>'site_name','value'=>'Keep site configuration','data_type'=>'string']);
    foreach (['movies','links'] as $table) {
        $db->query('CREATE TABLE '.$table.' (id INT PRIMARY KEY, value VARCHAR(255))');
        $db->table($table)->insert(['id'=>1,'value'=>'preserve']);
    }
    $post=['player_button_color'=>'#d28a15','player_icon_color'=>'#ffffff','player_button_style'=>'solid','player_button_icon'=>'play','player_button_size'=>'88'];
    savePlayer($post); check((bool)session()->get('success'),'Old form still saves');
    check($db->table('settings')->where('name','player_loading_color')->countAllResults()===0,'Missing field not inserted');
    $post['player_loading_color']='#21c4b5'; savePlayer($post);
    check((bool)session()->get('success'),'Color saved');
    $row=$db->table('settings')->where('name','player_loading_color')->get()->getRowArray();
    check($row['value']==='#21c4b5' && $row['data_type']==='string','Persisted loading color');
    foreach (['','red','#fff','" onmouseover="alert(1)', ['#123456']] as $invalid) {
        savePlayer(array_replace($post,['player_loading_color'=>$invalid]));
        check((bool)session()->get('errors') && !session()->get('success'),'Invalid color rejected');
        check($db->table('settings')->where('name','player_loading_color')->get()->getRow()->value==='#21c4b5','Invalid request preserves color');
    }
    unset($post['player_loading_color']); savePlayer($post);
    check($db->table('settings')->where('name','player_loading_color')->get()->getRow()->value==='#21c4b5','Old form preserves custom loading color');
    $post['player_loading_color']='#Bb44Ee'; savePlayer($post); savePlayer($post);
    check($db->table('settings')->where('name','player_loading_color')->countAllResults()===1,'Repeated save is idempotent');
    CodeIgniter\Config\Factories::reset('config'); helper('config');
    check(get_config('player_loading_color')==='#Bb44Ee','New request reloads saved value');
    check(get_config('site_name')==='Keep site configuration','Other settings preserved');
    foreach (['movies','links'] as $table) check($db->table($table)->get()->getResultArray()===[['id'=>'1','value'=>'preserve']],'Content preserved: '.$table);
    echo "PASS: loading color persistence/reload, validation, old forms, idempotence, settings and content preservation.\n";
} finally {
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    $mysql->query("DROP DATABASE `$databaseName`"); $mysql->close();
}
