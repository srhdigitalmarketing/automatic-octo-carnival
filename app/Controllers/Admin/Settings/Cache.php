<?php
namespace App\Controllers\Admin\Settings;
class Cache extends BaseSettings
{
    public function index() { return view('admin/settings/cache',['title'=>'Cache Settings']); }
    public function update()
    {
        if (!$this->validate(['web_page_cache_duration'=>'required|integer|greater_than[59]|less_than_equal_to[86400]'])) return redirect()->to(admin_url('/settings/cache'))->with('errors',$this->validator->getErrors());
        $pages=$this->request->getPost('cache_pages');
        $pages=is_array($pages)?array_values(array_intersect(['embed','view','download'],$pages)):[];
        $db=db_connect();
        foreach (['player_bunny_cdn_enabled'=>[$this->request->getPost('player_bunny_cdn_enabled') === '1' ? '1' : '0','bool'], 'web_page_cache'=>[empty($pages)?'0':'1','bool'], 'web_page_cache_types'=>[json_encode($pages),'array'], 'web_page_cache_duration'=>[(string)$this->request->getPost('web_page_cache_duration'),'int']] as $name=>$entry) {
            if ($db->table('settings')->where('name',$name)->countAllResults()) $db->table('settings')->where('name',$name)->update(['value'=>$entry[0],'data_type'=>$entry[1]]);
            else $db->table('settings')->insert(['name'=>$name,'value'=>$entry[0],'data_type'=>$entry[1]]);
        }
        cache()->clean();
        return redirect()->to(admin_url('/settings/cache'))->with('success','Pilihan disimpan dan cache lama dibersihkan.');
    }
    public function clean()
    {
        if (strtolower($this->request->getMethod()) !== 'post') return redirect()->to(admin_url('/settings/cache'));
        cache()->clean();
        return redirect()->to(admin_url('/settings/cache'))->with('success','All cache cleared.');
    }
    public function video()
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') return $this->response->setStatusCode(403)->setJSON(['error'=>'Invalid request']);
        $id=(int)$this->request->getPost('id');
        if (!(new \App\Models\MovieModel())->find($id)) return $this->response->setStatusCode(404)->setJSON(['error'=>'Video not found']);
        \App\Libraries\SelectedPageCache::clear($id);
        return $this->response->setJSON(['ok'=>true]);
    }
}
