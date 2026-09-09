<?php
namespace App\Libraries;

use RuntimeException;
use ZipArchive;

/** Restores administrator-supplied backups only after a separate preview and confirmation. */
class SecureRestore
{
    private $store;
    public function __construct(SecureBackups $store) { $this->store = $store; }

    public function preview(string $id): array
    {
        $path = $this->store->path($id);
        $item = json_decode((string) file_get_contents(dirname($path).'/'.$id.'.json'), true);
        $hash = hash_file('sha256', $path);
        if (!hash_equals((string) ($item['sha256'] ?? ''), $hash)) {
            throw new RuntimeException('Checksum backup berubah. Upload ulang arsip yang benar.');
        }
        $result = ['id'=>$id, 'sha256'=>$hash, 'name'=>$item['name'], 'extension'=>$item['extension']];
        if ($item['extension'] === 'zip') {
            if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip.');
            $container=new ZipArchive();
            if ($container->open($path,ZipArchive::CHECKCONS)!==true) throw new RuntimeException('ZIP tidak valid.');
            try { $fullPackage=$container->locateName('secure-backup.json')!==false && $container->locateName('files.zip')!==false && $container->locateName('database.sql')!==false; }
            finally { $container->close(); }
            if ($fullPackage) throw new RuntimeException('Ini paket Full Backup. Download dan ekstrak di komputer pribadi, lalu upload files.zip dan database.sql secara terpisah ke Backup untuk Restore. Jangan ekstrak paket ini ke folder public.');
            $entries = $this->zipEntries($path);
            $result['count'] = count($entries);
            $result['scope'] = 'uploads';
            foreach ($entries as $entry) {
                if (strpos($entry['name'], 'public/uploads/') !== 0) $result['scope'] = 'application';
            }
            $result['target'] = $result['scope'] === 'uploads' ? 'public/uploads' : 'File aplikasi, konfigurasi dan uploads';
            $result['detail'] = count($entries).' file akan dipulihkan. File dengan nama sama ditimpa; file lain tetap disimpan. Backup pengaman dibuat lebih dahulu.';
        } else {
            $db = $this->databaseConnection();
            $result['target'] = 'Database: '.$db->database;
            $result['detail'] = 'SQL dijalankan pada database aktif dan dapat mengganti tabel serta data. Backup SQL pengaman dibuat terlebih dahulu. Gunakan hanya backup tepercaya milik website ini.';
        }
        return $result;
    }

    public function restore(string $id, string $hash): string
    {
        $plan = $this->preview($id);
        if (!hash_equals($plan['sha256'], $hash)) throw new RuntimeException('Backup berubah. Ulangi pratinjau Restore.');
        if ($plan['extension'] === 'zip') {
            return $this->restoreFiles($this->store->path($id), $plan['scope']);
        }
        $db = $this->databaseConnection();
        $this->store->database();
        $this->importSql($this->store->path($id), $db);
        return 'Restore database selesai. Backup pengaman tersedia di daftar. Muat ulang halaman; Anda mungkin perlu login kembali.';
    }

    private function destination(string $name): string
    {
        // Reject paths that normalize differently across Windows, Unix, and ZIP implementations.
        if ($name === '' || preg_match('~[\\\\:\x00-\x1f\x7f]~', $name) || $name[0] === '/') throw new RuntimeException('ZIP berisi path tidak aman.');
        $parts = explode('/', $name);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..' || rtrim($part, ' .') !== $part || preg_match('/^(?:CON|PRN|AUX|NUL|COM[0-9]|LPT[0-9])(?:\.|$)/i', $part)) throw new RuntimeException('ZIP berisi nama file tidak aman.');
        }
        if (preg_match('~^(?:\.git|\.codex|\.agents|node_modules|writable)(?:/|$)~i', $name)) throw new RuntimeException('ZIP tidak boleh memulihkan writable atau folder internal pengembangan.');
        $root = realpath(ROOTPATH);
        if (!$root) throw new RuntimeException('Folder aplikasi tidak tersedia.');
        $target = $root;
        foreach ($parts as $part) {
            $target .= DIRECTORY_SEPARATOR.$part;
            if (is_link($target)) throw new RuntimeException('Target restore tidak boleh berupa symlink.');
            if (file_exists($target)) {
                $real = realpath($target);
                if (!$real || strpos($real, $root.DIRECTORY_SEPARATOR) !== 0) throw new RuntimeException('Target restore di luar aplikasi.');
            }
        }
        return $target;
    }

    private function zipEntries(string $path): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Aktifkan ekstensi PHP zip.');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('ZIP tidak valid.');
        try {
            if ($zip->numFiles > 200000) throw new RuntimeException('ZIP terlalu besar. Gunakan restore aaPanel.');
            $entries = []; $seen = []; $total = 0;
            for ($i=0; $i<$zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat) throw new RuntimeException('Metadata ZIP tidak dapat dibaca.');
                $name = rtrim($stat['name'], '/');
                $target = $this->destination($name);
                $zip->getExternalAttributesIndex($i, $opsys, $attributes);
                $type = ($attributes >> 16) & 0170000;
                if ($opsys === 3 && !in_array($type, [0, 0100000, 0040000], true)) throw new RuntimeException('Symlink atau file khusus di ZIP ditolak.');
                if (substr($stat['name'], -1) === '/') {
                    if (file_exists($target) && !is_dir($target)) throw new RuntimeException('Folder ZIP bertabrakan dengan file aktif.');
                    continue;
                }
                if (isset($seen[strtolower($name)])) throw new RuntimeException('ZIP berisi nama file duplikat.');
                $seen[strtolower($name)] = true;
                if (is_dir($target) || (file_exists($target) && !is_writable($target))) throw new RuntimeException('Target file tidak dapat ditimpa.');
                $parent = dirname($target);
                while (!file_exists($parent)) $parent = dirname($parent);
                if (!is_dir($parent) || !is_writable($parent)) throw new RuntimeException('Folder target tidak dapat ditulis.');
                $total += $stat['size'];
                if ($total > 2147483648) throw new RuntimeException('Ukuran ekstraksi melebihi 2 GB. Gunakan restore aaPanel.');
                $entries[] = ['name'=>$name, 'index'=>$i, 'size'=>$stat['size'], 'crc'=>$stat['crc']];
            }
            if (!$entries) throw new RuntimeException('ZIP tidak memiliki file untuk dipulihkan.');
            foreach ($entries as $entry) {
                $parent = dirname($entry['name']);
                while ($parent !== '.') {
                    if (isset($seen[strtolower($parent)])) throw new RuntimeException('Struktur file dan folder ZIP bertabrakan.');
                    $parent = dirname($parent);
                }
            }
            $free = disk_free_space(ROOTPATH);
            if ($free !== false && $free < $total*3 + 104857600) throw new RuntimeException('Ruang disk tidak cukup untuk restore dan backup pengaman.');
            return $entries;
        } finally { $zip->close(); }
    }

    private function restoreFiles(string $path, string $scope): string
    {
        $entries = $this->zipEntries($path);
        $stage = dirname($path).'/restore-'.bin2hex(random_bytes(16));
        if (!mkdir($stage, 0700)) throw new RuntimeException('Folder sementara restore gagal dibuat.');
        $zip = new ZipArchive(); $applied = []; $createdDirs = [];
        try {
            if ($zip->open($path) !== true) throw new RuntimeException('ZIP tidak dapat dibuka.');
            // Fully stage and verify the archive before any active file is overwritten.
            foreach ($entries as $i=>$entry) {
                $in = $zip->getStream($entry['name']);
                $out = fopen($stage.'/'.$i, 'xb');
                if (!$in || !$out) {
                    if (is_resource($in)) fclose($in);
                    if (is_resource($out)) fclose($out);
                    throw new RuntimeException('File ZIP tidak dapat dibaca.');
                }
                try { $bytes = stream_copy_to_stream($in, $out, $entry['size']+1); }
                finally { fclose($in); fclose($out); }
                if ($bytes !== $entry['size'] || strtolower(hash_file('crc32b', $stage.'/'.$i)) !== sprintf('%08x', $entry['crc'])) throw new RuntimeException('Isi ZIP rusak atau ukuran file tidak sesuai.');
            }
            $zip->close();
            $this->store->files($scope);
            foreach ($entries as $i=>$entry) {
                $target = $this->destination($entry['name']);
                $missing = []; $parent = dirname($target);
                while (!is_dir($parent)) { $missing[] = $parent; $parent = dirname($parent); }
                foreach (array_reverse($missing) as $dir) {
                    if (!mkdir($dir, 0755)) throw new RuntimeException('Folder restore gagal dibuat.');
                    $createdDirs[] = $dir;
                }
                $existed = is_file($target);
                if ($existed && !copy($target, $stage.'/old-'.$i)) throw new RuntimeException('Salinan rollback gagal dibuat.');
                $mode = $existed ? fileperms($target) & 0777 : ($entry['name'] === '.env' ? 0600 : 0644);
                $applied[] = ['target'=>$target, 'old'=>$existed ? $stage.'/old-'.$i : null, 'mode'=>$mode];
                if (!copy($stage.'/'.$i, $target)) throw new RuntimeException('Penulisan file restore gagal.');
                @chmod($target, $mode);
                if (function_exists('opcache_invalidate')) @opcache_invalidate($target, true);
            }
            return 'Restore selesai: '.count($entries).' file dipulihkan. Backup pengaman tersedia di daftar. Muat ulang halaman.';
        } catch (\Throwable $error) {
            $rollbackOk = true;
            foreach (array_reverse($applied) as $entry) {
                if ($entry['old'] !== null) {
                    if (!@copy($entry['old'], $entry['target'])) $rollbackOk = false;
                    @chmod($entry['target'], $entry['mode']);
                } elseif (is_file($entry['target']) && !@unlink($entry['target'])) $rollbackOk = false;
                if (function_exists('opcache_invalidate')) @opcache_invalidate($entry['target'], true);
            }
            foreach (array_reverse($createdDirs) as $dir) @rmdir($dir);
            if (!$rollbackOk) throw new RuntimeException('Restore gagal dan rollback belum lengkap. Gunakan backup pengaman melalui aaPanel.');
            throw new RuntimeException($error->getMessage().($applied ? ' Perubahan file sudah dibatalkan.' : ''));
        } finally {
            try { $zip->close(); } catch (\Throwable $ignored) {}
            // Only numbered staging files in this randomly generated private directory.
            foreach (glob($stage.'/*') ?: [] as $file) if (is_file($file) && !is_link($file)) @unlink($file);
            @rmdir($stage);
        }
    }

    /** Require credentials scoped to this database; never import with root/global privileges. */
    public static function validateGrants(array $rows, string $database): void
    {
        if (!$rows) throw new RuntimeException('Izin akun database tidak dapat diverifikasi.');
        $scope = '`'.str_replace(['\\', '`', '_', '%'], ['\\\\', '``', '\\_', '\\%'], $database).'`.';
        foreach ($rows as $row) {
            $grant = (string) reset($row);
            if (preg_match('/^GRANT USAGE ON \*\.\* TO /i', $grant) && stripos($grant, 'WITH GRANT OPTION') === false) continue;
            if (!preg_match('/^GRANT .+ ON (.+) TO /i', $grant, $match) || strpos($match[1], $scope) !== 0 || stripos($grant, 'WITH GRANT OPTION') !== false) {
                throw new RuntimeException('Restore SQL memerlukan akun MySQL khusus database website ini, tanpa hak global/root, role atau akses database lain. Atur akun tersebut di aaPanel dan konfigurasi aplikasi.');
            }
        }
    }

    private function databaseConnection()
    {
        foreach (['proc_open','proc_get_status','proc_close','proc_terminate'] as $function) if (!function_exists($function)) throw new RuntimeException('Aktifkan '.$function.' pada PHP aaPanel.');
        $this->mysqlBinary();
        $db = db_connect(); $db->initialize();
        if ($db->DBDriver !== 'MySQLi') throw new RuntimeException('Restore SQL mendukung MySQL/MariaDB.');
        self::validateGrants($db->query('SHOW GRANTS FOR CURRENT_USER')->getResultArray(), (string) $db->database);
        return $db;
    }

    private function mysqlBinary(): string
    {
        foreach (['/www/server/mysql/bin/mysql','/www/server/mysql/bin/mariadb','/usr/bin/mysql','/usr/bin/mariadb'] as $binary) if (is_file($binary) && is_executable($binary)) return $binary;
        throw new RuntimeException('mysql atau mariadb client tidak ditemukan di server aaPanel.');
    }

    private function importSql(string $path, $db): void
    {
        $prefix = dirname($path).'/import-'.bin2hex(random_bytes(16));
        $credentials = $prefix.'.cnf'; $errors = $prefix.'.err'; $process = null;
        $quote = static fn($value)=>'"'.str_replace(["\\",'"',"\n","\r"],["\\\\",'\\"','\\n','\\r'],(string)$value).'"';
        try {
            if (file_put_contents($credentials, "[client]\nuser=".$quote($db->username)."\npassword=".$quote($db->password)."\nhost=".$quote($db->hostname)."\nport=".(int)$db->port."\n") === false) throw new RuntimeException('Konfigurasi restore sementara gagal dibuat.');
            @chmod($credentials, 0600);
            $command = [$this->mysqlBinary(), '--defaults-extra-file='.$credentials, '--batch', '--binary-mode', '--local-infile=0', '--default-character-set=utf8mb4', '--database='.$db->database];
            $process = proc_open($command, [0=>['file',$path,'r'],1=>['file',$prefix.'.out','w'],2=>['file',$errors,'w']], $pipes, null, null, ['bypass_shell'=>true]);
            if (!is_resource($process)) throw new RuntimeException('Proses restore SQL gagal dimulai.');
            $deadline = time()+240;
            do {
                $state = proc_get_status($process);
                if (!$state['running']) break;
                if (time()>$deadline) { proc_terminate($process); throw new RuntimeException('Restore melewati 4 menit. Database mungkin baru terisi sebagian. Pulihkan backup pengaman melalui aaPanel.'); }
                usleep(200000);
            } while (true);
            $exit = $state['exitcode']; proc_close($process); $process = null;
            if ($exit !== 0) throw new RuntimeException('Restore SQL gagal; database mungkin berubah sebagian. Periksa kompatibilitas SQL dan izin akun, lalu pulihkan backup pengaman melalui aaPanel.');
        } finally {
            if (is_resource($process)) { proc_terminate($process); proc_close($process); }
            @unlink($credentials); @unlink($errors); @unlink($prefix.'.out');
        }
    }
}
