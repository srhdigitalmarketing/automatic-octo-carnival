<?php
namespace App\Controllers\Admin\Settings;
use App\Libraries\SecureBackups;
class Secure extends BaseSettings
{
    public function index()
    {
        if (!session()->get('secure_backup_token')) session()->set('secure_backup_token',bin2hex(random_bytes(32)));
        $this->response->setHeader('Cache-Control','no-store');
        try { $entries=(new SecureBackups())->entries(); $error=''; }
        catch (\Throwable $exception) { $entries=[];$error='Penyimpanan backup tidak tersedia. Periksa izin writable.'; }
        return view('admin/settings/secure',['title'=>'Secure','entries'=>$entries,'error'=>$error,'token'=>session()->get('secure_backup_token')]);
    }
    public function run()
    {
        $this->response->setHeader('Cache-Control','no-store');
        if (strtolower($this->request->getMethod())!=='post' || !hash_equals((string)session()->get('secure_backup_token'),(string)$this->request->getPost('token')) || !session()->get('secure_backup_token')) return $this->response->setStatusCode(403)->setJSON(['message'=>'Sesi tidak valid. Muat ulang halaman Secure.']);
        $lock=fopen(WRITEPATH.'secure-backup.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) return $this->response->setStatusCode(409)->setJSON(['message'=>'Backup lain sedang berjalan. Tunggu hingga selesai.']);
        try {
            @set_time_limit(300);
            $store=new SecureBackups();
            switch ($this->request->getPost('action')) {
                case 'files': $store->files((string)$this->request->getPost('scope'));break;
                case 'database': $store->database();break;
                case 'upload': $store->upload($this->request->getFile('backup_file'));break;
                case 'delete': $store->remove((string)$this->request->getPost('id'));break;
                default: return $this->response->setStatusCode(422)->setJSON(['message'=>'Aksi tidak dikenal.']);
            }
            return $this->response->setJSON(['message'=>'Selesai. Daftar backup diperbarui.','entries'=>$store->entries()]);
        } catch (\RuntimeException $exception) {
            return $this->response->setStatusCode(422)->setJSON(['message'=>$exception->getMessage()]);
        } catch (\Throwable $exception) {
            return $this->response->setStatusCode(500)->setJSON(['message'=>'Proses gagal. Periksa ruang disk, izin folder, dan batas waktu PHP.']);
        } finally { flock($lock,LOCK_UN);fclose($lock); }
    }
    public function download()
    {
        try { $path=(new SecureBackups())->path((string)$this->request->getGet('id')); }
        catch (\Throwable $exception) { return $this->response->setStatusCode(404)->setBody('Backup tidak ditemukan.'); }
        return $this->response->download($path,null)->setFileName('secure-backup-'.date('Ymd-His').'.'.pathinfo($path,PATHINFO_EXTENSION))->setHeader('Cache-Control','private, no-store')->setHeader('X-Content-Type-Options','nosniff');
    }
}
