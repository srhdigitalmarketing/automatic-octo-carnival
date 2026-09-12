<?php
namespace App\Controllers\Admin;

use App\Libraries\ServerDotHostStreamGrab;
use App\Models\ThirdPartyApi;

class ServerDotHostGrab extends \App\Controllers\BaseController
{
    public function run()
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        if (strtolower($this->request->getMethod()) !== 'post' || !$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error'=>'Permintaan tidak valid.']);
        }
        $session = session();
        // The session ID rotates periodically; keep ownership stable across that rotation.
        $owner = $session->get('serverdothost_grab_owner');
        if (!is_string($owner) || !preg_match('/^[a-f0-9]{64}$/D', $owner)) {
            $owner = bin2hex(random_bytes(32));
            $session->set('serverdothost_grab_owner', $owner);
        }
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        $lock = @fopen(WRITEPATH.'cache/serverdothost-grab.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            return $this->response->setStatusCode(409)->setJSON(['error'=>'Auto Grab sedang berjalan atau folder cache tidak dapat ditulis. Coba kembali.']);
        }
        try {
            $action = (string)$this->request->getPost('action');
            $grab = new ServerDotHostStreamGrab();
            if ($action === 'start') {
                $api = $this->activeApi((int)$this->request->getPost('api_id'));
                $job = $grab->start($api, $owner);
                $token = bin2hex(random_bytes(24));
                $this->saveJob($token, $job);
                return $this->response->setJSON(['token'=>$token, 'total'=>$job['total']]);
            }
            if (!in_array($action, ['next','stop'], true)) throw new \InvalidArgumentException('Aksi tidak valid.');
            $token = (string)$this->request->getPost('token');
            if (!preg_match('/^[a-f0-9]{48}$/D', $token)) throw new \InvalidArgumentException('Proses tidak valid.');
            $job = cache()->get('sdh_grab_'.$token);
            if (!is_array($job) || !hash_equals($job['owner'], $owner)) {
                throw new \InvalidArgumentException('Proses kedaluwarsa. Mulai kembali.');
            }
            if ($action === 'stop') {
                cache()->delete('sdh_grab_'.$token);
                return $this->response->setJSON(['done'=>true]);
            }
            $result = $grab->step($job, $this->activeApi($job['api_id']));
            $this->saveJob($token, $job);
            return $this->response->setJSON($result);
        } catch (\InvalidArgumentException $error) {
            return $this->response->setStatusCode(400)->setJSON(['error'=>$error->getMessage()]);
        } catch (\Throwable $error) {
            // API failure stops the job, avoiding a request storm for the remaining movies.
            $http = (int)$error->getCode();
            $message = in_array($http, [401,403], true) ? 'Token API ditolak. Periksa izin videos:read.'
                : ($http === 429 ? 'Batas permintaan ServerDotHost tercapai. Coba lagi nanti.'
                : 'Proses berhenti. Periksa API ServerDotHost, koneksi, database dan izin cache; mulai kembali untuk melanjutkan tanpa duplikat.');
            return $this->response->setStatusCode(400)->setJSON(['error'=>$message]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function activeApi(int $id): object
    {
        $apis = new ThirdPartyApi();
        if ($apis->schemaError() !== '') throw new \InvalidArgumentException('Konfigurasi tabel API belum lengkap. Buka API & R2 Storage.');
        $api = $apis->where('provider', 'serverdothost')->where('status', 'active')->find($id);
        if (!$api) throw new \InvalidArgumentException('Pilih ServerDotHost yang aktif pada API & R2 Storage.');
        return $api;
    }

    private function saveJob(string $token, array $job): void
    {
        if (!cache()->save('sdh_grab_'.$token, $job, 7200)) throw new \RuntimeException('Cache proses tidak tersedia.');
    }
}
