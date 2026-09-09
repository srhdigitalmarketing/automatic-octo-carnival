<?php
namespace App\Libraries;
use RuntimeException;
use ZipArchive;

class SecureBackups
{
    private $directory;
    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim($directory ?? WRITEPATH . 'secure-backups', '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true)) throw new RuntimeException('Folder backup tidak dapat dibuat. Periksa izin writable.');
        if (is_link(rtrim($this->directory, '/\\'))) throw new RuntimeException('Folder backup tidak boleh berupa symlink.');
        $public=realpath(FCPATH); $storage=realpath($this->directory);
        if ($public && $storage && ($storage===$public || strpos($storage,$public.DIRECTORY_SEPARATOR)===0)) throw new RuntimeException('Folder backup harus berada di luar public.');
        @chmod($this->directory,0700);
        file_put_contents($this->directory.'.htaccess', "Require all denied\nDeny from all\n");
        file_put_contents($this->directory.'index.html', '');
    }
    public function entries(): array
    {
        $items=[];
        foreach (glob($this->directory.'*.json') ?: [] as $path) {
            $item=json_decode((string)file_get_contents($path),true);
            if (!is_array($item) || !preg_match('/^[a-f0-9]{32}$/D',(string)($item['id']??''))) continue;
            try { $file=$this->path($item['id']); } catch (RuntimeException $e) { continue; }
            $item['size']=filesize($file); $items[]=$item;
        }
        usort($items,static fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
        return $items;
    }
    public function path(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new RuntimeException('Backup tidak ditemukan.');
        $metadata=$this->directory.$id.'.json';
        $item=is_file($metadata)?json_decode((string)file_get_contents($metadata),true):null;
        if (!is_array($item) || !in_array($item['extension']??'', ['sql','zip'],true)) throw new RuntimeException('Backup tidak ditemukan.');
        $path=$this->directory.$id.'.'.$item['extension'];
        if (!is_file($path) || is_link($path)) throw new RuntimeException('Backup tidak ditemukan.');
        return $path;
    }
    public function remove(string $id): void
    {
        $path=$this->path($id);
        if (!unlink($path)) throw new RuntimeException('Backup tidak dapat dihapus.');
        unlink($this->directory.$id.'.json');
    }
    private function register(string $id,string $extension,string $name,string $kind): void
    {
        $path=$this->directory.$id.'.'.$extension; @chmod($path,0600);
        $data=['id'=>$id,'extension'=>$extension,'name'=>$name,'kind'=>$kind,'created_at'=>date('c'),'sha256'=>hash_file('sha256',$path)];
        if (file_put_contents($this->directory.$id.'.json',json_encode($data),LOCK_EX)===false) { @unlink($path); throw new RuntimeException('Metadata backup gagal disimpan.'); }
        @chmod($this->directory.$id.'.json',0600);
    }
    public function upload($file): void
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) throw new RuntimeException('Upload gagal. Periksa batas upload PHP dan aaPanel.');
        $extension=strtolower(pathinfo($file->getClientName(),PATHINFO_EXTENSION));
        if (!in_array($extension,['zip','sql'],true) || $file->getSize()<1 || $file->getSize()>536870912) throw new RuntimeException('Gunakan file ZIP atau SQL, maksimal 512 MB.');
        if ($extension==='zip') {
            if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip.');
            $zip=new ZipArchive();
            if ($zip->open($file->getTempName(),ZipArchive::CHECKCONS)!==true) throw new RuntimeException('Arsip ZIP tidak valid.');
            $zip->close();
        }
        $id=bin2hex(random_bytes(16));
        $file->move($this->directory,$id.'.'.$extension);
        $this->register($id,$extension,basename(str_replace('\\','/',$file->getClientName())),'uploaded');
    }
    public function importRemote(string $temporary,string $name): string
    {
        if (is_link($temporary) || !is_file($temporary) || realpath(dirname($temporary))!==realpath($this->directory) || strpos(basename($temporary),'.download-')!==0) throw new RuntimeException('File download tidak valid.');
        $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if (strpos($name,chr(92))!==false || !in_array($extension,['zip','sql'],true) || preg_match('~[\\/:\x00-\x1f\x7f]~',$name) || filesize($temporary)<1 || filesize($temporary)>5368709120) throw new RuntimeException('Gunakan arsip ZIP atau SQL, maksimal 5 GB.');
        if ($extension==='zip') {
            if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip.');
            $zip=new ZipArchive();
            if ($zip->open($temporary,ZipArchive::CHECKCONS)!==true) throw new RuntimeException('Download bukan arsip ZIP yang valid.');
            $zip->close();
        }
        $id=bin2hex(random_bytes(16));
        if (!rename($temporary,$this->directory.$id.'.'.$extension)) throw new RuntimeException('Download gagal disimpan.');
        $this->register($id,$extension,$name,'remote');
        return $id;
    }
    public function files(string $scope): void
    {
        if ($scope==='full') { $this->full(); return; }
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip di aaPanel.');
        if (!in_array($scope,['uploads','application'],true)) throw new RuntimeException('Pilihan file tidak valid.');
        $root=realpath($scope==='uploads'?FCPATH.'uploads':ROOTPATH);
        if (!$root) throw new RuntimeException('Folder sumber tidak ditemukan.');
        $id=bin2hex(random_bytes(16));$path=$this->directory.$id.'.zip';$zip=new ZipArchive();
        if ($zip->open($path,ZipArchive::CREATE|ZipArchive::EXCL)!==true) throw new RuntimeException('Arsip tidak dapat dibuat.');
        try {
            $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS));
            $count=0;
            foreach ($iterator as $file) {
                if ($file->isLink() || !$file->isFile()) continue;
                $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
                if ($scope==='application' && preg_match('~^(?:\.git|\.codex|\.agents|node_modules|writable)(?:/|$)~',$relative)) continue;
                $real=$file->getRealPath();
                if (!$real || strpos($real,$root.DIRECTORY_SEPARATOR)!==0) continue;
                if (++$count>200000) throw new RuntimeException('Lebih dari 200.000 file. Gunakan backup aaPanel untuk arsip besar.');
                $name=$scope==='uploads'?'public/uploads/'.$relative:$relative;
                if (!$zip->addFile($real,$name)) throw new RuntimeException('Salah satu file tidak dapat dibaca.');
            }
            if ($count===0) $zip->addEmptyDir($scope==='uploads'?'public/uploads':'application');
            if (!$zip->close()) throw new RuntimeException('Penyimpanan ZIP gagal. Periksa ruang disk.');
            $this->register($id,'zip',$scope.'-'.date('Ymd-His').'.zip',$scope);
        } catch (\Throwable $error) { try { $zip->close(); } catch (\Throwable $ignored) {} @unlink($path); throw $error; }
    }
    public function full(): void
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip di aaPanel.');
        $id=bin2hex(random_bytes(16));
        $work=$this->directory.'full-work-'.$id;
        $path=$this->directory.$id.'.zip';
        $temporary=new self($work);
        $zip=new ZipArchive();
        try {
            // Private staging keeps intermediate archives out of the normal backup list.
            $temporary->database();
            $temporary->files('application');
            $items=$temporary->entries();
            $parts=[];
            foreach ($items as $item) $parts[$item['kind']]=$item;
            if (!isset($parts['database'],$parts['application'])) throw new RuntimeException('Full Backup belum lengkap.');
            if ($zip->open($path,ZipArchive::CREATE|ZipArchive::EXCL)!==true) throw new RuntimeException('Arsip Full Backup tidak dapat dibuat.');
            $manifest=['format'=>'secure-full-backup','version'=>1,'created_at'=>date('c'),'files'=>[]];
            foreach (['application'=>'files.zip','database'=>'database.sql'] as $kind=>$name) {
                $source=$temporary->path($parts[$kind]['id']);
                if (!$zip->addFile($source,$name)) throw new RuntimeException('Komponen Full Backup gagal ditambahkan.');
                // The inner file archive is already compressed.
                if ($kind==='application') $zip->setCompressionName($name,ZipArchive::CM_STORE);
                $manifest['files'][$name]=['sha256'=>hash_file('sha256',$source),'size'=>filesize($source)];
            }
            $readme="FULL BACKUP - FILES DAN DATABASE\n\n".
                "files.zip: file aplikasi, konfigurasi .env dan public/uploads.\n".
                "database.sql: database MySQL/MariaDB website.\n".
                "Tidak termasuk writable, .git, .codex, .agents, node_modules, symlink dan objek R2.\n\n".
                "RESTORE: ekstrak paket ini di komputer pribadi. Upload files.zip dan database.sql secara terpisah ke Settings > Secure, lalu gunakan Restore untuk masing-masing komponen.\n".
                "Pastikan konfigurasi .env dan nama database sesuai server tujuan. Hentikan perubahan website selama backup dan restore.\n".
                "Jangan ekstrak paket ini ke public/ karena database.sql berisi data privat. Simpan salinan di perangkat lain.\n".
                "Backup files dan SQL dibuat berurutan, bukan snapshot atomik.\n";
            if (!$zip->addFromString('secure-backup.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) || !$zip->addFromString('README.txt',$readme)) throw new RuntimeException('Metadata Full Backup gagal dibuat.');
            if (!$zip->close()) throw new RuntimeException('Penyimpanan Full Backup gagal. Periksa ruang disk.');
            $this->register($id,'zip','full-backup-'.date('Ymd-His').'.zip','full');
        } catch (\Throwable $error) {
            try { $zip->close(); } catch (\Throwable $ignored) {}
            @unlink($path);
            throw $error;
        } finally {
            // Only remove this operation's generated private staging files, never existing backups.
            foreach (glob($work.DIRECTORY_SEPARATOR.'*') ?: [] as $file) if (is_file($file) && !is_link($file)) @unlink($file);
            @unlink($work.DIRECTORY_SEPARATOR.'.htaccess');
            @rmdir($work);
        }
    }
    public function database(): void
    {
        if (!function_exists('proc_open')) throw new RuntimeException('Aktifkan proc_open pada PHP aaPanel untuk backup database.');
        $binary=null;
        foreach (['/www/server/mysql/bin/mysqldump','/www/server/mysql/bin/mariadb-dump','/usr/bin/mysqldump','/usr/bin/mariadb-dump'] as $candidate) if (is_file($candidate) && is_executable($candidate)) { $binary=$candidate; break; }
        if (!$binary) throw new RuntimeException('mysqldump tidak ditemukan. Gunakan server aaPanel dengan MySQL/MariaDB terpasang.');
        $db=db_connect();$db->initialize();
        if ($db->DBDriver!=='MySQLi') throw new RuntimeException('Backup database mendukung MySQL/MariaDB.');
        $quote=static fn($value)=>'"'.str_replace(["\\",'"',"\n","\r"],["\\\\",'\\"','\\n','\\r'],(string)$value).'"';
        $id=bin2hex(random_bytes(16));$path=$this->directory.$id.'.sql';$credentials=$this->directory.$id.'.cnf';$errors=$this->directory.$id.'.err';
        file_put_contents($credentials,"[client]\nuser=".$quote($db->username)."\npassword=".$quote($db->password)."\nhost=".$quote($db->hostname)."\nport=".(int)$db->port."\n");@chmod($credentials,0600);
        $process=null;
        try {
            $command=[$binary,'--defaults-extra-file='.$credentials,'--single-transaction','--quick','--hex-blob','--routines','--events','--triggers','--default-character-set=utf8mb4','--databases',(string)$db->database];
            $process=proc_open($command,[0=>['pipe','r'],1=>['file',$path,'w'],2=>['file',$errors,'w']],$pipes,null,null,['bypass_shell'=>true]);
            if (!is_resource($process)) throw new RuntimeException('Proses backup database gagal dimulai.');
            fclose($pipes[0]);$deadline=time()+240;
            do { $state=proc_get_status($process);if (!$state['running']) break;if(time()>$deadline){proc_terminate($process);throw new RuntimeException('Backup melewati 4 menit. Gunakan backup database aaPanel.');}usleep(200000); } while(true);
            $exit=$state['exitcode'];proc_close($process);$process=null;
            if ($exit!==0 || !is_file($path) || filesize($path)===0) throw new RuntimeException('Backup database gagal. Periksa izin SELECT, SHOW VIEW, TRIGGER, EVENT dan routine pada akun database.');
            $this->register($id,'sql','database-'.date('Ymd-His').'.sql','database');
        } catch (\Throwable $error) { if(is_resource($process)){proc_terminate($process);proc_close($process);}@unlink($path);throw $error; }
        finally { @unlink($credentials);@unlink($errors); }
    }
}
