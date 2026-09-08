<?php
namespace App\Controllers\Admin;
use App\Libraries\CloudflareR2Storage;
use CodeIgniter\Files\File;
class BannerMigration extends \App\Controllers\BaseController
{
    public function run()
    {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(403)->setJSON(['error'=>'Invalid request']);
        $this->response->setHeader('Cache-Control','no-store');
        $lock=fopen(WRITEPATH.'cache/banner-migration.lock','c');
        if (!$lock || !flock($lock,LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); return $this->response->setStatusCode(409)->setJSON(['error'=>'Migrasi lain masih berjalan.']); }
        try {
            $storage=CloudflareR2Storage::active();
            if (!$storage) throw new \RuntimeException('Aktifkan R2 Storage terlebih dahulu.');
            $db=db_connect(); $owner=hash('sha256',session_id());
            if ($this->request->getPost('action') === 'start') {
                $files=[];
                foreach (['movies','series'] as $table) {
                    foreach ($db->table($table)->select('banner')->distinct()->where('banner !=','')->where('banner IS NOT NULL',null,false)->get()->getResultArray() as $row) {
                        if (!filter_var($row['banner'],FILTER_VALIDATE_URL)) $files[$row['banner']]=$row['banner'];
                    }
                }
                $token=bin2hex(random_bytes(24));
                if (!cache()->save('banner_migrate_'.$token,['owner'=>$owner,'files'=>array_values($files),'cursor'=>0],7200)) throw new \RuntimeException('Cache tidak tersedia.');
                return $this->response->setJSON(['token'=>$token,'total'=>count($files)]);
            }
            $token=(string)$this->request->getPost('token');
            if (!preg_match('/^[a-f0-9]{48}$/D',$token)) throw new \RuntimeException('Proses tidak valid.');
            $job=cache()->get('banner_migrate_'.$token);
            if (!$job || !hash_equals($job['owner'],$owner)) throw new \RuntimeException('Proses kedaluwarsa, mulai kembali.');
            if ($job['cursor']>=count($job['files'])) return $this->response->setJSON(['done'=>true]);
            $name=$job['files'][$job['cursor']++]; $url=null; $transaction=false;
            $result=['title'=>$name,'state'=>'skipped','message'=>'Referensi banner sudah berubah.'];
            try {
                $used=0; foreach (['movies','series'] as $table) $used+=$db->table($table)->where('banner',$name)->countAllResults();
                if ($used) {
                    $path=\App\Libraries\LocalBannerPath::resolve(FCPATH.'uploads/banners', $name);
                    $url=$storage->uploadBanner(new File($path));
                    $db->transBegin(); $transaction=true; $updated=0;
                    foreach (['movies','series'] as $table) {
                        if (!$db->table($table)->where('banner',$name)->update(['banner'=>$url])) throw new \RuntimeException('Database update failed');
                        $updated+=$db->affectedRows();
                    }
                    if (!$updated || !$db->transStatus() || !$db->transCommit()) throw new \RuntimeException('Banner reference changed');
                    $transaction=false; $url=null;
                    $result['state']='success'; $result['message']='Tersimpan di R2; '.$updated.' referensi diperbarui. File lokal dipertahankan.';
                }
            } catch (\Throwable $e) {
                if ($transaction) $db->transRollback();
                if ($url) { try { $storage->deletePublicUrl($url); } catch (\Throwable $ignored) {} }
                $result['state']='failed'; $result['message']='Gagal. Periksa file lokal, format gambar, izin baca dan koneksi R2.';
            }
            if (!cache()->save('banner_migrate_'.$token,$job,7200)) throw new \RuntimeException('Cache tidak tersedia.');
            return $this->response->setJSON($result+['done'=>false]);
        } catch (\Throwable $e) { return $this->response->setStatusCode(400)->setJSON(['error'=>'Migrasi tidak dapat dilanjutkan. Pastikan R2 dan cache aktif.']); }
        finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
