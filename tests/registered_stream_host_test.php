<?php
namespace Config { class Database { public static function connect(){return new class { public function tableExists($table){return true;} public function getFieldNames($table){return ['provider','status','embed_domains'];} };} } }
namespace App\Models {
 class ThirdPartyApi {
  public static $calls=0;
  public function schemaError(){return '';}
  public function whereIn($field,$values){if($field!=='provider' || $values!==['upnshare','custom_http','vod_catalog','serverdothost'])throw new \RuntimeException('Unexpected providers');return $this;}
  public function where($field,$value){if($field!=='status'||$value!=='active')throw new \RuntimeException('Inactive API accepted');return $this;}
  public function findAll(){self::$calls++;return [(object)['embed_domains'=>'ustreamplay.online, upload18.org'], (object)['embed_domains'=>'bobaplayer.com']];}
 }
}
namespace {
 require __DIR__.'/../app/Libraries/VideoHostHealth.php';require __DIR__.'/../app/Libraries/RegisteredStreamHost.php';
 foreach(['https://ustreamplay.online/#123'=>true,'https://UPLOAD18.ORG/a'=>true,'https://bobaplayer.com/embed/test'=>true,'https://bobaplayer.com.evil.test/embed/test'=>false,'https://abcdomain.org/play/a'=>false,'https://upload18.org.evil.test/a'=>false,'https://evil.test/upload18.org'=>false,'https://upload18.org@evil.test/a'=>false,''=>false,'ftp://upload18.org/a'=>false] as $url=>$expected){if(\App\Libraries\RegisteredStreamHost::matches($url)!==$expected)throw new \RuntimeException('Wrong host decision: '.$url);}
 if(\App\Models\ThirdPartyApi::$calls!==1)throw new \RuntimeException('API queried for every row');
 echo "PASS: exact registered domain matching, active supported APIs, malformed/suffix rejection, one API query per request\n";
}
