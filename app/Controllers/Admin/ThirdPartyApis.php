<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ThirdPartyApi;
use CodeIgniter\Exceptions\PageNotFoundException;


class ThirdPartyApis extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ThirdPartyApi();
    }

    public function index()
    {
        $title = 'API & R2 Storage';

        $apis = $this->model->whereIn('provider', ['cloudflare_r2', 'upnshare'])->findAll();

        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis/new' => 'Add R2 Storage',
            'admin/third-party-apis/new?provider=upnshare' => 'Add UPNShare'
        ]);

        return view('admin/third_party_apis/list', compact('title', 'apis', 'topBtnGroup'));
    }



    public function new()
    {

        $title = 'Add R2 Storage';
        $tpAPI = new \App\Entities\ThirdPartyApi();
        $tpAPI->provider = $this->request->getGet('provider') === 'upnshare' ? 'upnshare' : 'cloudflare_r2';
        $title = $tpAPI->provider === 'upnshare' ? 'Add UPNShare' : $title;

        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to API & R2 Storage'
        ]);

        return view('admin/third_party_apis/new', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function edit()
    {
        $title = 'Edit R2 Storage';
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $title = $tpAPI->provider === 'upnshare' ? 'Edit UPNShare' : 'Edit R2 Storage';
        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to API & R2 Storage'
        ]);
        return view('admin/third_party_apis/edit', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function create(): \CodeIgniter\HTTP\RedirectResponse
    {
        $data = $this->request->getPost();
        $data['provider'] = ($data['provider'] ?? '') === 'upnshare' ? 'upnshare' : 'cloudflare_r2';
        $data = $this->providerData($data);
        $errors = $this->providerErrors($data);
        if (! empty($errors)) {
            return redirect()->back()->with('errors', $errors);
        }

        $tpAPI = new \App\Entities\ThirdPartyApi($data);

        if($this->model->insert( $tpAPI )){

            return redirect()->to(admin_url( '/third-party-apis' ))
                            ->with('success', 'API access added successfully');

        }

        return redirect()->back()
                         ->with('errors', $this->model->errors());
    }

    public function update()
    {
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $data = $this->request->getPost();
        $data['provider'] = $tpAPI->provider;
        $data = $this->providerData($data);
        foreach (['r2_access_key_id', 'r2_secret_access_key', 'api_token'] as $field) {
            if (empty($data[$field])) {
                unset($data[$field]);
            }
        }
        $errors = $this->providerErrors(array_merge($tpAPI->toRawArray(), $data));
        if (! empty($errors)) {
            return redirect()->back()->with('errors', $errors);
        }

        $tpAPI->fill($data);

        if($tpAPI->hasChanged()){
            if($this->model->save( $tpAPI )){

                return redirect()->to(admin_url( '/third-party-apis' ))
                                  ->with('success', $tpAPI->name . ' API access updated successfully');
            }else{
                return redirect()->back()
                                 ->with('errors', $this->model->errors());
            }
        }

        return redirect()->to(admin_url( '/third-party-apis' ));

    }

    public function delete()
    {
        $tpAPI = $this->getApi( $this->request->getGet('id') );

        if($this->model->delete( $tpAPI->id )){
            return redirect()->back()
                             ->with('success', $tpAPI->name . ' deleted successfully');
        }

        return redirect()->back()
                         ->with('errors', $this->model->errors());
    }


    protected function getApi($id)
    {
        $api = $this->model->where('id', $id)->whereIn('provider', ['cloudflare_r2', 'upnshare'])->first();

        if($api === null){
            throw new PageNotFoundException('Third party API not found');
        }

        return $api;
    }

    private function providerData(array $data): array
    {
        $fields = $data['provider'] === 'upnshare'
            ? ['name', 'provider', 'status', 'api_token', 'embed_domains']
            : ['name', 'provider', 'status', 'r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket', 'r2_public_url'];
        $data = array_intersect_key($data, array_flip($fields));
        if ($data['provider'] === 'upnshare') {
            $data['api_base_url'] = 'https://upnshare.com/api/v1';
            $data['api_token'] = trim((string) ($data['api_token'] ?? ''));
            $data['embed_domains'] = strtolower(trim((string) ($data['embed_domains'] ?? '')));
        }
        return $data;
    }

    private function providerErrors(array $data): array
    {
        if (($data['provider'] ?? '') !== 'upnshare') { return $this->r2Errors($data); }
        $errors = [];
        $token = trim((string) ($data['api_token'] ?? ''));
        if ($token === '' || strlen($token) > 255 || preg_match('/[\r\n]/', $token)) { $errors[] = 'A valid UPNShare API token is required (maximum 255 characters).'; }
        $domains = trim((string) ($data['embed_domains'] ?? ''));
        if ($domains === '' || strlen($domains) > 1000) { $errors[] = 'Enter the embed hostname(s), maximum 1000 characters.'; }
        foreach (preg_split('/[\s,]+/', $domains, -1, PREG_SPLIT_NO_EMPTY) as $domain) {
            if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strpos($domain, '.') === false) { $errors[] = 'Embed domains must contain hostnames only, without https:// or paths.'; break; }
        }
        return $errors;
    }

    /** @return array<int, string> */
    private function r2Errors(array $data): array
    {
        $labels = [
            'r2_account_id' => 'Cloudflare account ID',
            'r2_access_key_id' => 'R2 access key ID',
            'r2_secret_access_key' => 'R2 secret access key',
            'r2_bucket' => 'R2 bucket name',
            'r2_public_url' => 'Public bucket URL',
        ];
        $errors = [];
        foreach ($labels as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = $label . ' is required for Cloudflare R2.';
            }
        }
        if (! empty($data['r2_public_url']) && (filter_var($data['r2_public_url'], FILTER_VALIDATE_URL) === false || strpos($data['r2_public_url'], 'https://') !== 0)) {
            $errors[] = 'Public bucket URL must be a valid HTTPS URL.';
        }

        return $errors;
    }

}
