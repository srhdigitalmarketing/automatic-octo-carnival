<?php
namespace App\Libraries;

use RuntimeException;

class SecureBackupRemote
{
    private $directory;
    private const FIELDS = [
        'ftp'=>['host','port','protocol','username','password','directory'],
        'drive'=>['client_id','client_secret','refresh_token','folder_id'],
        's3'=>['endpoint','region','bucket','prefix','access_key','secret_key','session_token'],
    ];
    private const SECRETS = ['password','client_secret','refresh_token','access_key','secret_key','session_token'];

    public function __construct(?string $directory=null)
    {
        $this->directory=rtrim($directory ?? WRITEPATH.'secure-backups','/\\').DIRECTORY_SEPARATOR;
        new SecureBackups($this->directory);
    }
    private function key(): string
    {
        $path=$this->directory.'.remote-key';
        if (is_link($path)) throw new RuntimeException('Penyimpanan kunci remote tidak valid.');
        if (!is_file($path)) {
            $handle=@fopen($path,'xb');
            if (!$handle) throw new RuntimeException('Kunci enkripsi remote gagal dibuat.');
            @chmod($path,0600);
            try { if (fwrite($handle,random_bytes(32))!==32) throw new RuntimeException('Kunci enkripsi gagal disimpan.'); }
            finally { fclose($handle); }
        }
        $key=file_get_contents($path);
        if (strlen($key)!==32) throw new RuntimeException('Kunci enkripsi remote tidak valid.');
        return $key;
    }
    private function load(): array
    {
        $path=$this->directory.'.remote-settings';
        if (!is_file($path)) return [];
        if (is_link($path) || !is_file($this->directory.'.remote-key')) throw new RuntimeException('Kunci pengaturan remote hilang. Pulihkan kunci sebelum melanjutkan.');
        $bytes=file_get_contents($path);
        if (strlen($bytes)<29) throw new RuntimeException('Pengaturan remote rusak.');
        $json=openssl_decrypt(substr($bytes,28),'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16));
        $data=$json===false?null:json_decode($json,true);
        if (!is_array($data)) throw new RuntimeException('Pengaturan remote tidak dapat didekripsi.');
        return $data;
    }
    public function settings(): array
    {
        $data=$this->load();
        foreach ($data as &$config) foreach (self::SECRETS as $field) {
            if (isset($config[$field])) { $config[$field.'_saved']=$config[$field]!=='';unset($config[$field]); }
        }
        return $data;
    }
    public function save(string $provider,array $input): void
    {
        if (!isset(self::FIELDS[$provider])) throw new RuntimeException('Tujuan backup tidak valid.');
        $all=$this->load();$config=[];
        foreach (self::FIELDS[$provider] as $field) {
            if (isset($input[$field]) && !is_scalar($input[$field])) throw new RuntimeException('Nilai pengaturan tidak valid.');
            $value=(string)($input[$field]??'');
            if (in_array($field,self::SECRETS,true) && $value==='') $value=(string)($all[$provider][$field]??'');
            if ($field==='session_token' && !empty($input['clear_session_token'])) $value='';
            if (strlen($value)>8192 || preg_match('/[\x00\r\n]/',$value)) throw new RuntimeException('Nilai pengaturan tidak valid.');
            $config[$field]=in_array($field,self::SECRETS,true)?$value:trim($value);
        }
        $this->validate($provider,$config);
        $all[$provider]=$config;
        $iv=random_bytes(12);$tag='';
        $encrypted=openssl_encrypt(json_encode($all,JSON_THROW_ON_ERROR),'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,$iv,$tag);
        if ($encrypted===false) throw new RuntimeException('Enkripsi pengaturan remote gagal.');
        $temporary=$this->directory.'.remote-settings-'.bin2hex(random_bytes(8));
        $handle=fopen($temporary,'xb');
        if (!$handle) throw new RuntimeException('Pengaturan remote gagal disimpan.');
        @chmod($temporary,0600);
        try {
            $payload=$iv.$tag.$encrypted;
            if (fwrite($handle,$payload)!==strlen($payload)) throw new RuntimeException('Pengaturan remote gagal disimpan.');
            fclose($handle);$handle=null;
            if (!rename($temporary,$this->directory.'.remote-settings')) throw new RuntimeException('Pengaturan remote gagal diganti.');
        } finally { if (is_resource($handle)) fclose($handle);if(is_file($temporary))@unlink($temporary); }
    }
    private function validate(string $provider,array $config): void
    {
        $required=['ftp'=>['host','port','protocol','username','password'],'drive'=>['client_id','client_secret','refresh_token','folder_id'],'s3'=>['endpoint','region','bucket','access_key','secret_key']];
        foreach ($required[$provider] as $field) if (($config[$field]??'')==='') throw new RuntimeException('Lengkapi '.$field.' untuk '.$provider.'.');
        if ($provider==='ftp') {
            if (!preg_match('/^[a-zA-Z0-9.-]+$/D',$config['host']) || !ctype_digit($config['port']) || (int)$config['port']<1 || (int)$config['port']>65535 || !in_array($config['protocol'],['ftps','ftp'],true) || strpos($config['username'],':')!==false) throw new RuntimeException('Hostname, port atau protokol FTP tidak valid.');
            self::safePath($config['directory']);
        } elseif ($provider==='drive') {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/D',$config['folder_id'])) throw new RuntimeException('Masukkan Folder ID Google Drive, bukan URL folder.');
        } else {
            $url=parse_url($config['endpoint']);
            if (!$url || ($url['scheme']??'')!=='https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment']) || !in_array($url['path']??'', ['', '/'],true)) throw new RuntimeException('Endpoint S3 harus berupa https://hostname tanpa path, user atau query.');
            if (!preg_match('/^[a-z0-9.-]{3,63}$/D',$config['bucket']) || !preg_match('/^[a-z0-9-]+$/D',$config['region'])) throw new RuntimeException('Bucket atau region S3 tidak valid.');
            self::safePath($config['prefix']);
        }
    }
    private static function safePath(string $path): string
    {
        $path=trim($path,'/');
        if (preg_match('/[\\\\\x00-\x1f\x7f]/',$path)) throw new RuntimeException('Path tujuan tidak valid.');
        foreach (explode('/',$path) as $part) if ($part==='.' || $part==='..') throw new RuntimeException('Path tujuan tidak boleh menggunakan titik relatif.');
        return $path;
    }
    public function ready(string $provider): void
    {
        if (!isset(self::FIELDS[$provider])) throw new RuntimeException('Pilih FTP, Google Drive atau S3 sebagai tujuan backup.');
        $all=$this->load();
        if (!isset($all[$provider])) throw new RuntimeException('Simpan pengaturan '.$provider.' terlebih dahulu.');
        $this->validate($provider,$all[$provider]);
    }
    public function send(SecureBackups $store,string $id,string $provider): array
    {
        $this->ready($provider);$config=$this->load()[$provider];$path=$store->path($id);
        $metadata=dirname($path).'/'.$id.'.json';$item=json_decode(file_get_contents($metadata),true);
        if (!hash_equals((string)$item['sha256'],hash_file('sha256',$path))) throw new RuntimeException('Checksum backup berubah. Pengiriman dibatalkan.');
        if (filesize($path)>5368709120) throw new RuntimeException('Pengiriman remote dibatasi 5 GB per arsip.');
        $name=$id.'-'.preg_replace('/[^a-zA-Z0-9._-]/','_',basename($item['name']));
        if ($provider==='ftp') $remote=$this->ftp($config,$path,$name);
        elseif ($provider==='drive') $remote=$this->drive($config,$path,$name);
        else $remote=$this->s3($config,$path,$name);
        $result=['provider'=>$provider,'sent_at'=>date('c'),'name'=>$name,'reference'=>$remote];
        $item['remote_uploads'][$provider]=$result;
        $temporary=$metadata.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            $payload=json_encode($item,JSON_THROW_ON_ERROR);
            if (file_put_contents($temporary,$payload,LOCK_EX)!==strlen($payload)) throw new RuntimeException('Backup terkirim, tetapi catatan lokal gagal disimpan. Periksa tujuan sebelum mengirim ulang.');
            @chmod($temporary,0600);
            if (!rename($temporary,$metadata)) throw new RuntimeException('Backup terkirim, tetapi catatan lokal gagal diganti. Periksa tujuan sebelum mengirim ulang.');
        } finally { if(is_file($temporary))@unlink($temporary); }
        return $result;
    }

    public function listing(string $provider,string $cursor=''): array
    {
        $this->ready($provider);$c=$this->load()[$provider];$items=[];$next='';
        if(strlen($cursor)>8192 || preg_match('/[\x00-\x1f]/',$cursor))throw new RuntimeException('Halaman remote tidak valid.');
        if($provider==='ftp') {
            if($cursor!=='' && !ctype_digit($cursor))throw new RuntimeException('Halaman FTP tidak valid.');
            $folder=self::safePath($c['directory']);
            $url='ftp://'.$c['host'].':'.$c['port'].'/'.($folder!==''?implode('/',array_map('rawurlencode',explode('/',$folder))).'/':'');
            $r=$this->request($url,'GET',[],null,null,[CURLOPT_CUSTOMREQUEST=>null,CURLOPT_DIRLISTONLY=>true,CURLOPT_PROTOCOLS=>CURLPROTO_FTP|CURLPROTO_FTPS,CURLOPT_USERPWD=>$c['username'].':'.$c['password'],CURLOPT_USE_SSL=>$c['protocol']==='ftps'?CURLUSESSL_ALL:CURLUSESSL_NONE]);
            if($r['status']<200 || $r['status']>=300)throw new RuntimeException('Daftar FTP gagal dibaca. Periksa izin folder.');
            foreach(preg_split('/\r?\n/',$r['body']) as $name) {
                if($folder!=='' && strpos($name,$folder.'/')===0)$name=substr($name,strlen($folder)+1);
                if($this->archiveName($name))$items[]=['name'=>$name,'reference'=>$name,'size'=>null];
            }
            usort($items,static fn($a,$b)=>strcmp($a['name'],$b['name']));
            $offset=(int)$cursor;$next=count($items)>$offset+100?(string)($offset+100):'';$items=array_slice($items,$offset,100);
        } elseif($provider==='drive') {
            $q=['q'=>"'".$c['folder_id']."' in parents and trashed = false",'fields'=>'nextPageToken,files(id,name,size)','pageSize'=>100,'orderBy'=>'modifiedTime desc','supportsAllDrives'=>'true','includeItemsFromAllDrives'=>'true'];
            if($cursor!=='')$q['pageToken']=$cursor;
            $r=$this->request('https://www.googleapis.com/drive/v3/files?'.http_build_query($q,'','&',PHP_QUERY_RFC3986),'GET',$this->driveAuthorization($c));$data=json_decode($r['body'],true);
            if($r['status']!==200 || !is_array($data) || !isset($data['files']) || !is_array($data['files']))throw new RuntimeException('Daftar Google Drive gagal dibaca. Periksa izin folder dan OAuth.');
            foreach($data['files'] as $file)if($this->archiveName((string)($file['name']??'')) && preg_match('/^[a-zA-Z0-9_-]+$/D',(string)($file['id']??'')))$items[]=['name'=>$file['name'],'reference'=>$file['id'],'size'=>$file['size']??null];
            $next=(string)($data['nextPageToken']??'');
        } else {
            if(!function_exists('simplexml_load_string'))throw new RuntimeException('Aktifkan ekstensi SimpleXML untuk daftar S3.');
            $prefix=self::safePath($c['prefix']);$prefix=$prefix!==''?$prefix.'/':'';
            $q=['list-type'=>'2','max-keys'=>'100','prefix'=>$prefix,'delimiter'=>'/'];if($cursor!=='')$q['continuation-token']=$cursor;
            ksort($q);$query=http_build_query($q,'','&',PHP_QUERY_RFC3986);$uri='/'.rawurlencode($c['bucket']);
            $endpoint=rtrim($c['endpoint'],'/');$parts=parse_url($endpoint);$host=$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
            $r=$this->request($endpoint.$uri.'?'.$query,'GET',self::s3Headers($c,$uri,$host,hash('sha256',''),null,'GET',$query));
            if($r['status']!==200 || stripos($r['body'],'<!DOCTYPE')!==false)throw new RuntimeException('Daftar S3 gagal dibaca. Periksa izin ListBucket.');
            $old=libxml_use_internal_errors(true);
            try{$xml=simplexml_load_string($r['body'],'SimpleXMLElement',LIBXML_NONET);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
            if($xml===false || $xml->getName()!=='ListBucketResult')throw new RuntimeException('Respons daftar S3 tidak valid.');
            foreach($xml->Contents as $file){$key=(string)$file->Key;if(strpos($key,$prefix)!==0)continue;$name=substr($key,strlen($prefix));if($this->archiveName($name))$items[]=['name'=>$name,'reference'=>$name,'size'=>(string)$file->Size];}
            $next=(string)$xml->NextContinuationToken;
            if((string)$xml->IsTruncated==='true' && $next==='')throw new RuntimeException('Halaman berikutnya dari S3 tidak valid.');
        }
        return ['items'=>$items,'cursor'=>$next];
    }
    private function archiveName(string $name): bool
    {
        return $name!=='' && strlen($name)<=255 && strpos($name,chr(92))===false && !preg_match('~[/:\x00-\x1f\x7f]~',$name) && in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),['zip','sql'],true);
    }

    /** Fetches only from a configured destination. Import never executes a restore. */
    public function receive(SecureBackups $store,string $provider,string $reference): string
    {
        $this->ready($provider);$config=$this->load()[$provider];$reference=trim($reference);
        if ($reference==='' || strpos($reference,chr(92))!==false || strlen($reference)>255 || preg_match('~[\\/:\x00-\x1f\x7f]~',$reference) || $reference==='.' || $reference==='..') throw new RuntimeException('Masukkan nama arsip saja atau File ID Drive, bukan URL atau path.');
        $headers=[];$extra=[];$expectedSize=null;$md5=null;
        if ($provider==='drive') {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/D',$reference)) throw new RuntimeException('File ID Drive tidak valid.');
            $headers=$this->driveAuthorization($config);
            $url='https://www.googleapis.com/drive/v3/files/'.rawurlencode($reference);
            $meta=$this->request($url.'?fields=name,size,md5Checksum,parents,trashed&supportsAllDrives=true','GET',$headers);
            $item=json_decode($meta['body'],true);
            if ($meta['status']!==200 || !is_array($item) || !empty($item['trashed']) || !in_array($config['folder_id'],$item['parents']??[],true)) throw new RuntimeException('File Drive tidak ditemukan di folder backup yang dikonfigurasi.');
            $name=(string)($item['name']??'');$expectedSize=(string)($item['size']??'');$md5=(string)($item['md5Checksum']??'');
            if (!ctype_digit($expectedSize) || !preg_match('/^[a-f0-9]{32}$/Di',$md5)) throw new RuntimeException('Metadata ukuran/checksum Drive tidak valid.');
            $url.='?alt=media&supportsAllDrives=true';
        } else {
            $name=$reference;
            $prefix=self::safePath($config[$provider==='ftp'?'directory':'prefix']);
            $key=($prefix!==''?$prefix.'/':'').$name;
            $encoded=implode('/',array_map('rawurlencode',explode('/',$key)));
            if ($provider==='ftp') {
                $url='ftp://'.$config['host'].':'.$config['port'].'/'.$encoded;
                $extra=[CURLOPT_PROTOCOLS=>CURLPROTO_FTP|CURLPROTO_FTPS,CURLOPT_USERPWD=>$config['username'].':'.$config['password'],CURLOPT_USE_SSL=>$config['protocol']==='ftps'?CURLUSESSL_ALL:CURLUSESSL_NONE];
            } else {
                $uri='/'.rawurlencode($config['bucket']).'/'.$encoded;
                $endpoint=rtrim($config['endpoint'],'/');$parts=parse_url($endpoint);
                $host=$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
                $headers=self::s3Headers($config,$uri,$host,hash('sha256',''),null,'GET');$url=$endpoint.$uri;
            }
        }
        if (strpos($name,chr(92))!==false || strlen($name)>255 || preg_match('~[\\/:\x00-\x1f\x7f]~',$name) || !in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),['zip','sql'],true)) throw new RuntimeException('Pilih arsip ZIP atau SQL.');
        if ($expectedSize!==null && ((int)$expectedSize<1 || (int)$expectedSize>5368709120)) throw new RuntimeException('Arsip remote maksimal 5 GB.');
        $temporary=$this->directory.'.download-'.bin2hex(random_bytes(16));
        try {
            $status=$this->downloadTo($url,$temporary,$headers,$extra);
            if (($provider==='ftp' && ($status<200 || $status>=300)) || ($provider!=='ftp' && $status!==200)) throw new RuntimeException('Remote menolak download (status '.$status.'). Periksa nama/ID arsip dan izin baca.');
            clearstatcache(true,$temporary);
            if ($expectedSize!==null && (string)filesize($temporary)!==$expectedSize) throw new RuntimeException('Download belum lengkap. Coba kembali.');
            if ($md5!==null && !hash_equals(strtolower($md5),hash_file('md5',$temporary))) throw new RuntimeException('Checksum download Drive tidak cocok.');
            return $store->importRemote($temporary,$name);
        } finally { if(is_file($temporary))@unlink($temporary); }
    }
    protected function downloadTo(string $url,string $path,array $headers,array $extra): int
    {
        $stream=fopen($path,'xb');if(!$stream)throw new RuntimeException('File sementara tidak dapat dibuat.');
        @chmod($path,0600);$curl=curl_init($url);$bytes=0;
        try {
            $options=[CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_TIMEOUT=>300,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use($stream,&$bytes){$bytes+=strlen($chunk);if($bytes>5368709120)return 0;return fwrite($stream,$chunk);}];
            curl_setopt_array($curl,array_replace($options,$extra));
            if(curl_exec($curl)===false)throw new RuntimeException('Download gagal atau melebihi 5 GB (cURL '.curl_errno($curl).'). Data website belum diubah.');
            return (int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
        } finally { fclose($stream);curl_close($curl); }
    }
    private function driveAuthorization(array $config): array
    {
        $token=$this->request('https://oauth2.googleapis.com/token','POST',['Content-Type: application/x-www-form-urlencoded'],http_build_query(['client_id'=>$config['client_id'],'client_secret'=>$config['client_secret'],'refresh_token'=>$config['refresh_token'],'grant_type'=>'refresh_token']));
        $auth=json_decode($token['body'],true);
        if ($token['status']!==200 || empty($auth['access_token']) || !is_string($auth['access_token']) || preg_match('/[\r\n]/',$auth['access_token'])) throw new RuntimeException('OAuth Google Drive gagal. Periksa Client ID, Client Secret dan Refresh Token.');
        return ['Authorization: Bearer '.$auth['access_token']];
    }

    /** Streams uploads from disk; never follows redirects with credentials. */
    protected function request(string $url,string $method,array $headers=[],?string $body=null,?string $file=null,array $extra=[]): array
    {
        $curl=curl_init($url);$stream=null;$response='';$received=[];
        try {
            $options=[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_TIMEOUT=>300,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$response){if(strlen($response)+strlen($chunk)>1048576)return 0;$response.=$chunk;return strlen($chunk);},
                CURLOPT_HEADERFUNCTION=>static function($ch,$line)use(&$received){$pos=strpos($line,':');if($pos!==false)$received[strtolower(trim(substr($line,0,$pos)))]=trim(substr($line,$pos+1));return strlen($line);}];
            if ($file!==null) {
                $stream=fopen($file,'rb');if(!$stream)throw new RuntimeException('Arsip tidak dapat dibaca.');
                $options[CURLOPT_UPLOAD]=true;$options[CURLOPT_INFILE]=$stream;$options[CURLOPT_INFILESIZE]=filesize($file);
            } elseif ($body!==null) $options[CURLOPT_POSTFIELDS]=$body;
            curl_setopt_array($curl,array_replace($options,$extra));
            if (curl_exec($curl)===false) throw new RuntimeException('Transfer remote gagal (cURL '.curl_errno($curl).'). Periksa koneksi/TLS dan tujuan sebelum mengulang; salinan lokal tetap tersedia.');
            return ['status'=>(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE),'headers'=>$received,'body'=>$response];
        } finally { if(is_resource($stream))fclose($stream);curl_close($curl); }
    }
    private function ftp(array $config,string $path,string $name): string
    {
        $remote=self::safePath($config['directory']);$remote=($remote!==''?$remote.'/':'').$name;
        $url='ftp://'.$config['host'].':'.$config['port'].'/'.implode('/',array_map('rawurlencode',explode('/',$remote.'.part')));
        $result=$this->request($url,'STOR',[],null,$path,[CURLOPT_PROTOCOLS=>CURLPROTO_FTP|CURLPROTO_FTPS,CURLOPT_USERPWD=>$config['username'].':'.$config['password'],CURLOPT_USE_SSL=>$config['protocol']==='ftps'?CURLUSESSL_ALL:CURLUSESSL_NONE,CURLOPT_FTP_CREATE_MISSING_DIRS=>CURLFTP_CREATE_DIR_RETRY,CURLOPT_POSTQUOTE=>['RNFR '.$name.'.part','RNTO '.$name]]);
        if ($result['status']<200 || $result['status']>=300) throw new RuntimeException('FTP menolak upload. Periksa akun, path dan izin tulis. Salinan lokal tetap tersedia.');
        return $remote;
    }
    public static function s3Headers(array $config,string $uri,string $host,string $hash,?string $time=null,string $method='PUT',string $query=''): array
    {
        $time=$time??gmdate('Ymd\THis\Z');$date=substr($time,0,8);
        $headers=['host'=>$host,'x-amz-content-sha256'=>$hash,'x-amz-date'=>$time];
        if (!empty($config['session_token']))$headers['x-amz-security-token']=$config['session_token'];
        ksort($headers);$canonical='';foreach($headers as $key=>$value)$canonical.=$key.':'.trim($value)."\n";
        $signed=implode(';',array_keys($headers));$scope=$date.'/'.$config['region'].'/s3/aws4_request';
        $request=$method."\n".$uri."\n".$query."\n".$canonical."\n".$signed."\n".$hash;
        $toSign="AWS4-HMAC-SHA256\n".$time."\n".$scope."\n".hash('sha256',$request);
        $key=hash_hmac('sha256',$date,'AWS4'.$config['secret_key'],true);
        foreach ([$config['region'],'s3','aws4_request'] as $part)$key=hash_hmac('sha256',$part,$key,true);
        $signature=hash_hmac('sha256',$toSign,$key);
        $out=[];foreach($headers as $name=>$value)$out[]=$name.': '.$value;
        $out[]='Authorization: AWS4-HMAC-SHA256 Credential='.$config['access_key'].'/'.$scope.', SignedHeaders='.$signed.', Signature='.$signature;
        $out[]='Content-Type: application/octet-stream';$out[]='Expect:';
        return $out;
    }
    private function s3(array $config,string $path,string $name): string
    {
        $prefix=self::safePath($config['prefix']);$key=($prefix!==''?$prefix.'/':'').$name;
        $uri='/'.rawurlencode($config['bucket']).'/'.implode('/',array_map('rawurlencode',explode('/',$key)));
        $endpoint=rtrim($config['endpoint'],'/');$parts=parse_url($endpoint);$host=$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
        $headers=self::s3Headers($config,$uri,$host,hash_file('sha256',$path));
        $result=$this->request($endpoint.$uri,'PUT',$headers,null,$path);
        if ($result['status']!==200)throw new RuntimeException('S3 menolak upload (HTTP '.$result['status'].'). Periksa endpoint, region, bucket dan izin PutObject.');
        return $config['bucket'].'/'.$key;
    }
    private function drive(array $config,string $path,string $name): string
    {
        $headers=$this->driveAuthorization($config);
        $start=$this->request('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true&fields=id,size,md5Checksum','POST',array_merge($headers,['Content-Type: application/json; charset=UTF-8','X-Upload-Content-Type: application/octet-stream','X-Upload-Content-Length: '.filesize($path)]),json_encode(['name'=>$name,'parents'=>[$config['folder_id']]]));
        $session=$start['headers']['location']??'';$parts=parse_url($session);
        if ($start['status']!==200 || !$parts || ($parts['scheme']??'')!=='https' || ($parts['host']??'')!=='www.googleapis.com' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || (isset($parts['port']) && $parts['port']!==443) || strpos($parts['path']??'','/upload/drive/v3/files')!==0) throw new RuntimeException('Google Drive menolak sesi upload. Periksa Folder ID, akses folder dan kuota.');
        $upload=$this->request($session,'PUT',array_merge($headers,['Content-Type: application/octet-stream','Content-Length: '.filesize($path)]),null,$path);
        $item=json_decode($upload['body'],true);
        if (!in_array($upload['status'],[200,201],true) || empty($item['id']) || (string)($item['size']??'')!==(string)filesize($path) || !hash_equals(hash_file('md5',$path),(string)($item['md5Checksum']??''))) throw new RuntimeException('Upload Drive belum terkonfirmasi lengkap. Periksa folder tujuan sebelum mengulang. Salinan lokal tetap tersedia.');
        return (string)$item['id'];
    }
}
