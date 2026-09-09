<?php
namespace App\Controllers\Admin;

class ReportedLinkTools extends \App\Controllers\BaseController
{
    public function run()
    {
        if (strtolower($this->request->getMethod()) !== 'post' || !$this->request->isAJAX()) return $this->response->setStatusCode(405)->setJSON(['message'=>'Invalid request']);
        try {
            $host=\App\Libraries\ReportedLinkTools::host($this->request->getPost('host'));
            $action=$this->request->getPost('action');
            if (!in_array($action,['start','clear','export'],true)) throw new \InvalidArgumentException('Aksi tidak valid.');
            $tools=new \App\Libraries\ReportedLinkTools();
            if ($action==='start') $result=$tools->start($host);
            else {
                $cursor=filter_var($this->request->getPost('cursor'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
                $max=filter_var($this->request->getPost('max_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
                if ($cursor===false || $max===false || $cursor>$max) throw new \InvalidArgumentException('Batas proses tidak valid.');
                $result=$tools->batch($host,$cursor,$max,$action==='clear');
            }
            return $this->response->setHeader('Cache-Control','no-store')->setJSON($result);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message'=>$e->getMessage()]);
        } catch (\Throwable $e) {
            log_message('error','Reported link tools failed: {message}',['message'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['message'=>'Proses gagal. Periksa log server; batch yang sudah selesai tetap tersimpan.']);
        }
    }
}
