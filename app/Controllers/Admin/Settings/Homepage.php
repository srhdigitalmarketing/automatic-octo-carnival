<?php
namespace App\Controllers\Admin\Settings;
class Homepage extends BaseSettings
{
    public function index()
    {
        return view('admin/settings/homepage', ['title'=>'Index Homepage']);
    }
    public function update()
    {
        if (!$this->validate(['homepage_active'=>'required|in_list[0,1]', 'homepage_heading'=>'required|max_length[160]', 'homepage_intro'=>'required|max_length[500]'])) {
            return redirect()->to(admin_url('/settings/homepage'))->withInput()->with('errors',$this->validator->getErrors());
        }
        $db = db_connect(); $db->transBegin();
        foreach (['homepage_active'=>'bool','homepage_heading'=>'string','homepage_intro'=>'string'] as $name=>$type) {
            $value = trim((string)$this->request->getPost($name));
            if ($db->table('settings')->where('name',$name)->countAllResults()) {
                $db->table('settings')->where('name',$name)->update(['value'=>$value,'data_type'=>$type]);
            } else { $db->table('settings')->insert(['name'=>$name,'value'=>$value,'data_type'=>$type]); }
        }
        if (!$db->transStatus()) { $db->transRollback(); return redirect()->to(admin_url('/settings/homepage'))->with('errors',['Pengaturan gagal disimpan.']); }
        $db->transCommit();
        return redirect()->to(admin_url('/settings/homepage'))->with('success','Homepage settings saved.');
    }
}
