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

        $apiSchemaError=$this->model->schemaError();
        $apis = $apiSchemaError!==''?[]:$this->model->whereIn('provider', ['cloudflare_r2', 'upnshare', 'custom_http', 'vod_catalog', 'serverdothost'])->findAll();

        $topBtnGroup = create_top_btn_group([
            'admin/settings/cdn#cdn-settings' => 'Add CDN Hostname',
            'admin/third-party-apis/new' => 'Add R2 Storage',
            'admin/third-party-apis/new?provider=upnshare' => 'Add UPNShare',
            'admin/third-party-apis/new?provider=custom_http' => 'Add Custom hostname',
            'admin/third-party-apis/new?provider=vod_catalog' => 'Add VOD API',
            'admin/third-party-apis/new?provider=serverdothost' => 'Add ServerDotHost'
        ]);

        return view('admin/third_party_apis/list', compact('title', 'apis', 'topBtnGroup','apiSchemaError'));
    }



    public function fileCheck()
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['error'=>'Gunakan tombol pengaturan cek file.']);
        }
        try {
            $id = filter_var($this->request->getPost('api_id'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $value = $this->request->getPost('enabled');
            if (!$id || !in_array($value, ['0','1'], true)) throw new \InvalidArgumentException('Pilihan cek file tidak valid.');
            $api = $this->getApi($id);
            \App\Libraries\HostFileChecks::save($api, $value === '1');
            $message = 'Cek file host '.($value === '1' ? 'diaktifkan.' : 'dinonaktifkan.');
            if ($this->request->isAJAX()) return $this->response->setJSON(['enabled'=>$value === '1','message'=>$message]);
            return redirect()->to(admin_url('/third-party-apis'))->with('success', $message);
        } catch (\Throwable $error) {
            $message = $error instanceof \InvalidArgumentException ? $error->getMessage()
                : 'Pengaturan gagal disimpan. Periksa host dan izin tabel settings, lalu coba lagi.';
            if ($this->request->isAJAX()) return $this->response->setStatusCode(400)->setJSON(['error'=>$message]);
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$message]);
        }
    }

    public function result()
    {
        // Authentication has finished; external requests must not lock navigation
        // in other tabs/AJAX requests sharing the same admin session.
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

        if($error=$this->model->schemaError())return $this->response->setStatusCode(409)->setJSON(['state'=>'disconnected','label'=>'Skema API belum siap','message'=>$error]);
        $api = $this->getApi((int)$this->request->getGet('id'));
        // Credential changes invalidate cached results without storing secrets in cache keys.
        $key = 'provider_connection_' . hash('sha256', json_encode($api->toRawArray()));
        $result = cache()->get($key);
        if (!is_array($result)) {
            $result = (new \App\Libraries\ProviderConnection())->check($api);
            cache()->save($key, $result, 60);
        }
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON($result);
    }

    public function new()
    {
        if ($error = $this->model->schemaError()) {
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$error]);
        }

        $title = 'Add R2 Storage';
        $tpAPI = new \App\Entities\ThirdPartyApi();
        $tpAPI->provider = in_array($this->request->getGet('provider'), ['upnshare','custom_http','vod_catalog','serverdothost'], true) ? $this->request->getGet('provider') : 'cloudflare_r2';
        $title = $tpAPI->provider === 'upnshare' ? 'Add UPNShare' : $title;

        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to API & R2 Storage'
        ]);

        if ($tpAPI->provider === 'custom_http') { $title = 'Add Custom hostname'; }
        if ($tpAPI->provider === 'vod_catalog') { $title = 'Add VOD API'; }
        if ($tpAPI->provider === 'serverdothost') { $title = 'Add ServerDotHost'; }
        return view('admin/third_party_apis/new', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function edit()
    {
        if ($error = $this->model->schemaError()) {
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$error]);
        }
        $title = 'Edit R2 Storage';
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $title = $tpAPI->provider === 'upnshare' ? 'Edit UPNShare' : 'Edit R2 Storage';
        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to API & R2 Storage'
        ]);
        if ($tpAPI->provider === 'custom_http') { $title = 'Edit Custom hostname'; }
        if ($tpAPI->provider === 'vod_catalog') { $title = 'Edit VOD API'; }
        if ($tpAPI->provider === 'serverdothost') { $title = 'Edit ServerDotHost'; }
        return view('admin/third_party_apis/edit', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function create(): \CodeIgniter\HTTP\RedirectResponse
    {
        if ($error = $this->model->schemaError()) {
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$error]);
        }
        $data = $this->request->getPost();
        $data['provider'] = in_array($data['provider'] ?? '', ['upnshare','custom_http','vod_catalog','serverdothost'], true) ? $data['provider'] : 'cloudflare_r2';
        $data = $this->providerData($data);
        $errors = array_merge($this->providerErrors($data), $this->hostnameErrors($data));
        if (! empty($errors)) {
            return redirect()->to(admin_url('/third-party-apis/new?provider=' . rawurlencode($data['provider'])))->withInput()->with('errors', $errors);
        }

        $tpAPI = new \App\Entities\ThirdPartyApi($data);

        if($this->model->insert( $tpAPI )){

            return redirect()->to(admin_url( '/third-party-apis' ))
                            ->with('success', 'API access added successfully');

        }

        return redirect()->to(admin_url('/third-party-apis/new?provider=' . rawurlencode($data['provider'])))->withInput()
                         ->with('errors', $this->model->errors());
    }

    public function update()
    {
        if ($error = $this->model->schemaError()) {
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$error]);
        }
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $data = $this->request->getPost();
        $data['provider'] = $tpAPI->provider;
        $data = $this->providerData($data);
        foreach (['r2_access_key_id', 'r2_secret_access_key', 'api_token'] as $field) {
            if (empty($data[$field])) {
                unset($data[$field]);
            }
        }
        $merged = array_merge($tpAPI->toRawArray(), $data);
        $errors = array_merge($this->providerErrors($merged), $this->hostnameErrors($merged, (int)$tpAPI->id));
        if (! empty($errors)) {
            return redirect()->to(admin_url('/third-party-apis/edit?id=' . (int)$tpAPI->id))->withInput()->with('errors', $errors);
        }

        $tpAPI->fill($data);

        if($tpAPI->hasChanged()){
            if($this->model->save( $tpAPI )){

                return redirect()->to(admin_url( '/third-party-apis' ))
                                  ->with('success', $tpAPI->name . ' API access updated successfully');
            }else{
                return redirect()->to(admin_url('/third-party-apis/edit?id=' . (int)$tpAPI->id))->withInput()
                                 ->with('errors', $this->model->errors());
            }
        }

        return redirect()->to(admin_url( '/third-party-apis' ));

    }

    public function delete()
    {
        if ($error = $this->model->schemaError()) {
            return redirect()->to(admin_url('/third-party-apis'))->with('errors', [$error]);
        }
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
        $api = $this->model->where('id', $id)->whereIn('provider', ['cloudflare_r2', 'upnshare', 'custom_http', 'vod_catalog', 'serverdothost'])->first();

        if($api === null){
            throw new PageNotFoundException('Third party API not found');
        }

        return $api;
    }

    private function providerData(array $data): array
    {
        if ($data['provider'] === 'vod_catalog') {
            return ['name'=>$data['name'] ?? '', 'provider'=>'vod_catalog', 'status'=>$data['status'] ?? 'active', 'api_base_url'=>strtolower(trim((string)($data['api_base_url'] ?? ''))), 'embed_domains'=>strtolower(trim((string)($data['embed_domains'] ?? '')))];
        }
        $fields = in_array($data['provider'], ['upnshare','custom_http','serverdothost'], true)
            ? ['name', 'provider', 'status', 'api_token', 'embed_domains']
            : ['name', 'provider', 'status', 'r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket', 'r2_public_url'];
        $data = array_intersect_key($data, array_flip($fields));
        if (in_array($data['provider'], ['upnshare','custom_http','serverdothost'], true)) {
            $data['api_base_url'] = $data['provider'] === 'serverdothost' ? 'https://serverdothost.com/api/v1' : 'https://upnshare.com/api/v1';
            $data['api_token'] = trim((string) ($data['api_token'] ?? ''));
            $data['embed_domains'] = strtolower(trim((string) ($data['embed_domains'] ?? '')));
        }
        if ($data['provider'] === 'custom_http') { $data['api_token'] = null; $data['api_base_url'] = null; }
        return $data;
    }

    private function hostnameErrors(array $data, int $currentId = 0): array
    {
        if (!in_array($data['provider'] ?? '', ['upnshare','custom_http','vod_catalog','serverdothost'], true) || ($data['status'] ?? 'active') !== 'active') { return []; }
        $domains = preg_split('/[\s,]+/', strtolower(trim((string)($data['embed_domains'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($this->model->whereIn('provider', ['upnshare','custom_http','vod_catalog','serverdothost'])->where('status', 'active')->findAll() as $api) {
            if ((int)$api->id === $currentId) { continue; }
            $existing = preg_split('/[\s,]+/', strtolower(trim((string)$api->embed_domains)), -1, PREG_SPLIT_NO_EMPTY);
            if (array_intersect($domains, $existing)) { return ['An embed hostname is already assigned to another active API. Remove it there or pause that configuration first.']; }
        }
        return [];
    }

    private function providerErrors(array $data): array
    {
        if (($data['provider'] ?? '') === 'vod_catalog') {
            try {
                \App\Libraries\VodCatalog::hostname((string)($data['api_base_url'] ?? ''));
                $domains = (string)($data['embed_domains'] ?? '');
                if (strlen($domains) > 1000) { return ['Embed hostnames maksimal 1000 karakter.']; }
                foreach (preg_split('/[\s,]+/', $domains, -1, PREG_SPLIT_NO_EMPTY) as $domain) { \App\Libraries\VodCatalog::hostname($domain); }
                return [];
            }
            catch (\InvalidArgumentException $e) { return [$e->getMessage()]; }
        }
        if (!in_array($data['provider'] ?? '', ['upnshare','custom_http','serverdothost'], true)) { return $this->r2Errors($data); }
        $errors = [];
        $token = trim((string) ($data['api_token'] ?? ''));
        if (($data['provider'] ?? '') !== 'custom_http' && ($token === '' || strlen($token) > 255 || preg_match('/[\r\n]/', $token))) { $errors[] = 'A valid provider API token is required (maximum 255 characters).'; }
        $domains = trim((string) ($data['embed_domains'] ?? ''));
        if (($data['provider'] ?? '') === 'serverdothost' && !preg_match('/^bkp_[a-zA-Z0-9]{64}$/D', $token)) { $errors[] = 'Gunakan Bearer token Bangkong dengan scope videos:read (bkp_ + 64 karakter).'; }
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
