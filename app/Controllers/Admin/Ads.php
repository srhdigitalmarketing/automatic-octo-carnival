<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AdRevenueToday;
use App\Models\AdsModel;
use App\Models\PopupAdUnitModel;

class Ads extends BaseController
{

    protected $model;
    protected $popupAdModel;

    public function __construct()
    {
        $this->model = new AdsModel();
        $this->popupAdModel = new PopupAdUnitModel();
    }

    public function index()
    {
        //
    }

    public function home_page()
    {
        $title = 'Ads - Home Page ';

        $ads = $this->model->where('page', 'home')
                           ->findAll();

        $topAd = $ads['home.banner.top'] ?? '';
        $playerRightAd = $ads['home.banner.player-right'] ?? '';
        $playerBottomAd = $ads['home.banner.player-bottom'] ?? '';
        $popAds = $ads['home.popad'] ?? '';


        $data = compact('title', 'topAd', 'playerRightAd', 'playerBottomAd', 'popAds');

        return view('admin/ads/home', $data);
    }

    public function embed_page()
    {
        $title = 'Ads - Embed Page ';

        $ads = $this->model->where('page', 'embed')
            ->findAll();

        $popAds = $ads['embed.popad'] ?? '';

        $popupAdUnits = [];
        $popupAdUnitsLoadError = false;
        $popupAdUnitsUnavailable = ! db_connect()->tableExists('popup_ad_units');
        $popupAdCredentialsUnavailable = false;

        if (! $popupAdUnitsUnavailable) {
            try {
                $popupAdCredentialsUnavailable = ! $this->popupAdCredentialsAvailable();
                $popupAdUnits = $this->popupAdModel
                    ->where('page', 'embed')
                    ->orderBy('id', 'ASC')
                    ->findAll();

                foreach ($popupAdUnits as $index => $unit) {
                    $popupAdUnits[$index]['api_token_configured'] = ! empty($unit['api_token']);
                    unset($popupAdUnits[$index]['api_token']);
                }
            } catch (\Throwable $exception) {
                log_message('error', 'Unable to load managed popup ads: {message}', [
                    'message' => $exception->getMessage(),
                ]);
                $popupAdUnitsLoadError = true;
            }
        }

        $data = compact('title', 'popAds', 'popupAdUnits', 'popupAdUnitsUnavailable', 'popupAdCredentialsUnavailable', 'popupAdUnitsLoadError');

        return view('admin/ads/embed', $data);
    }

    public function view_page()
    {
        $title = 'Ads - View Page ';

        $ads = $this->model->where('page', 'view')
            ->findAll();

        $playerTopAd = $ads['view.banner.player-top'] ?? '';
        $playerBottomAd = $ads['view.banner.player-bottom'] ?? '';
        $playerSidebarAd = $ads['view.banner.sidebar'] ?? '';
        $popAds = $ads['view.popad'] ?? '';

        $data = compact('title', 'playerTopAd',  'playerBottomAd','playerSidebarAd', 'popAds');

        return view('admin/ads/view', $data);
    }

    public function download_page()
    {
        $title = 'Ads - Download Page ';

        $ads = $this->model->where('page', 'download')
                           ->findAll();

        $playerTopAd = $ads['download.banner.title-bottom'] ?? '';
        $playerBottomAd = $ads['download.banner.links-group-middle'] ?? '';
        $popAds = $ads['download.popad'] ?? '';

        $data = compact('title', 'playerTopAd',  'playerBottomAd', 'popAds');

        return view('admin/ads/download', $data);
    }


    public function link_page()
    {
        $title = 'Ads - Link Page ';

        $ads = $this->model->where('page', 'link')
            ->findAll();

        $counterTop = $ads['link.banner.counter-top'] ?? '';
        $counterBottom = $ads['link.banner.counter-bottom'] ?? '';
        $popAds = $ads['link.popad'] ?? '';

        $data = compact('title', 'counterTop',  'counterBottom', 'popAds');

        return view('admin/ads/link', $data);
    }

    public function update()
    {
        $data = $this->request->getPost();
        $ads = $data['ads'] ?? [];

        if(! empty($ads)) {
            foreach ($ads as $key => $val) {

                if(empty($val['id']))
                    continue;

                $ad = $this->model->where('id', $val['id'])
                                  ->first();

                if($ad !== null){

                    $val['ad_code'] = base64_encode( $val['ad_code'] );
                    $ad->fill( $val );


                    if($ad->hasChanged()){

                        $this->model->save( $ad);

                    }

                }

            }
        }


        return redirect()->back()
                         ->with('success', 'data updated successfully');


    }

    public function save_popup_units()
    {
        $providers = ['adsterra', 'clickadu', 'clickadilla', 'evadav', 'custom'];
        $units = $this->request->getPost('popup_units') ?? [];
        $removeIds = $this->request->getPost('remove_popup_units') ?? [];

        if (! is_array($units) || count($units) > 20) {
            return redirect()->back()
                ->with('errors', ['You can save a maximum of 20 popup ad units at once.'])
                ->withInput();
        }

        if (! db_connect()->tableExists('popup_ad_units')) {
            return redirect()->back()
                ->with('errors', ['Popup Ads table was not found in the active database. Verify the migration ran against this site database.'])
                ->withInput();
        }

        if (! $this->popupAdCredentialsAvailable()) {
            return redirect()->back()
                ->with('errors', ['Zone ID and API Token columns are not available yet. Run the latest database migration first.'])
                ->withInput();
        }

        try {
            foreach ((array) $removeIds as $id) {
                $id = (int) $id;
                if ($id < 1) {
                    continue;
                }

                $unit = $this->popupAdModel
                    ->where('page', 'embed')
                    ->find($id);

                if ($unit !== null) {
                    $this->popupAdModel->delete($id);
                }
            }

            foreach ($units as $unitData) {
                if (! is_array($unitData)) {
                    continue;
                }

                $id = (int) ($unitData['id'] ?? 0);
                $provider = strtolower(trim((string) ($unitData['provider'] ?? 'custom')));
                $name = trim((string) ($unitData['name'] ?? ''));
                $code = trim((string) ($unitData['ad_code'] ?? ''));
                $zoneId = trim((string) ($unitData['zone_id'] ?? ''));
                $apiToken = trim((string) ($unitData['api_token'] ?? ''));

                if ($id < 1 && $code === '') {
                    continue;
                }

                if (! in_array($provider, $providers, true) || $code === '') {
                    return redirect()->back()
                        ->with('errors', ['Every popup ad needs a supported network and its ad code.'])
                        ->withInput();
                }

                if (mb_strlen($zoneId) > 100 || mb_strlen($apiToken) > 255) {
                    return redirect()->back()
                        ->with('errors', ['Zone ID or API token is too long.'])
                        ->withInput();
                }

                $data = [
                    'page' => 'embed',
                    'provider' => $provider,
                    'name' => $name !== '' ? substr($name, 0, 100) : ucfirst($provider),
                    'ad_code' => $code,
                    'zone_id' => $zoneId,
                    // Retained only for backward-compatible database rows.
                    // Popup selection uses eCPM and current traffic, never weight.
                    'weight' => 1,
                    'status' => ($unitData['status'] ?? 'paused') === 'active' ? 'active' : 'paused',
                ];

                if ($id > 0) {
                    $existing = $this->popupAdModel
                        ->where('page', 'embed')
                        ->find($id);

                    if ($existing === null) {
                        continue;
                    }

                    $data['id'] = $id;
                }

                // A blank token on an existing unit intentionally preserves the
                // secret already stored for that network.
                if ($apiToken !== '') {
                    $data['api_token'] = $apiToken;
                }

                $this->popupAdModel->save($data);
            }
        } catch (\Throwable $exception) {
            return redirect()->back()
                ->with('errors', ['Popup ad units could not be saved. Run the database migration first.'])
                ->withInput();
        }

        AdRevenueToday::forgetCachedRevenue();

        return redirect()->back()
            ->with('success', 'Popup ad units updated successfully.');
    }

    public function save_zode_settings()
    {
        $zodeId = trim((string) $this->request->getPost('zode_id'));
        $zodeApiToken = trim((string) $this->request->getPost('zode_api_token'));

        if (mb_strlen($zodeId) > 100 || mb_strlen($zodeApiToken) > 255) {
            return redirect()->back()
                ->with('errors', ['Zode ID or API token is too long.'])
                ->withInput();
        }

        try {
            $settings = db_connect()->table('settings');
            $this->saveZodeSetting($settings, 'zode_id', $zodeId);

            if ($zodeApiToken !== '') {
                $this->saveZodeSetting($settings, 'zode_api_token', $zodeApiToken);
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Zode settings could not be saved: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()
                ->with('errors', ['Zode settings could not be saved. Run the latest database update first.'])
                ->withInput();
        }

        return redirect()->back()
            ->with('success', 'Zode connection settings updated successfully.');
    }

    private function saveZodeSetting($settings, string $name, string $value): void
    {
        if ($settings->where('name', $name)->countAllResults() === 0) {
            db_connect()->table('settings')->insert([
                'name' => $name,
                'value' => $value,
                'data_type' => 'string',
            ]);
            return;
        }

        db_connect()->table('settings')
            ->where('name', $name)
            ->update(['value' => $value]);
    }

    private function popupAdCredentialsAvailable(): bool
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('popup_ad_units')) {
                return false;
            }

            $fields = $db->getFieldNames('popup_ad_units');
            return in_array('zone_id', $fields, true) && in_array('api_token', $fields, true);
        } catch (\Throwable $exception) {
            log_message('error', 'Unable to inspect popup ad credential fields: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }



}
