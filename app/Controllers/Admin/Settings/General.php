<?php

namespace App\Controllers\Admin\Settings;


class General extends BaseSettings
{

    public function index()
    {
        $title = 'General Settings';

        $apis=new \App\Models\ThirdPartyApi();$apiSchemaError=$apis->schemaError();
        $vodApis=$apiSchemaError===''?$apis->where('provider','vod_catalog')->where('status','active')->findAll():[];
        $serverDotHostApis=$apiSchemaError===''?$apis->where('provider','serverdothost')->where('status','active')->findAll():[];
        return view('admin/settings/general/index', compact('title','apiSchemaError','vodApis','serverDotHostApis'));
    }


    public function update()
    {
        if($this->request->getMethod() == 'post') {

            $validationRules = [
                'is_media_download_to_server' => 'required|in_list[0,1]',
            ];

            if($this->validate( $validationRules )){

                $data = $this->request->getPost([
                    'is_media_download_to_server',
                    'is_links_report',
                ]);

                $data['is_media_download_to_server'] = $data['is_media_download_to_server'] == 1;
                $data['is_links_report'] = isset($data['is_links_report']);


                //save media files
                $this->saveDefaultMediaFiles();

                return $this->save( $data )->to(admin_url('/settings/general'));

            }

            return redirect()->to(admin_url('/settings/general'))
                             ->with('errors', $this->validator->getErrors());

        }

        return redirect()->to(admin_url('/settings/general'));

    }

    protected function saveDefaultMediaFiles()
    {

        $bannerImg = $this->request->getFile('default_banner_file');

        //save banner image
        if($bannerImg !== null && $bannerImg->isValid()){
            $validationRule = [
                'default_banner_file' => [
                    'label' => 'Default banner image',
                    'rules' => 'uploaded[default_banner_file]'
                        . '|is_image[default_banner_file]'
                        . '|mime_in[default_banner_file,image/jpg,image/jpeg,image/png,image/webp]'
                        . '|max_size[default_banner_file,2048]',
                ]
            ];

            if($this->validate( $validationRule )){

                $bannerName = 'default-banner.' . \App\Libraries\SafeImageName::extension($bannerImg);
                $dir = FCPATH . 'uploads/';
                $bannerImg->move( $dir, $bannerName, true);

                $this->save( [ 'default_banner' => $bannerName ] );

            }
        }

    }



}
