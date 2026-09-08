<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Models\MovieModel;
use App\Models\SeriesModel;



/**
 * Class Suggest
 * @package App\Controllers\Admin\Ajax
 * @author John Antonio
 */
class Suggest extends BaseAjax
{
    public function index()
    {
        $title = $this->request->getGet('title');
        $type = $this->request->getGet('type');
        $content = '';

        if ($type === 'movie' && is_string($title) && trim($title) !== '') {
            $apis = (new \App\Models\ThirdPartyApi())->where('provider', 'vod_catalog')->where('status', 'active')->orderBy('id', 'ASC')->findAll(3);
            if ($apis) {
                $items = []; $errors = [];
                foreach ($apis as $api) {
                    try {
                        $key = 'vod_search_v2_' . hash('sha256', $api->api_base_url . ':' . mb_substr(trim($title), 0, 150));
                        $found = cache()->get($key);
                        if (!is_array($found)) { $found = (new \App\Libraries\VodCatalog())->search((string)$api->api_base_url, trim($title)); cache()->save($key, $found, 120); }
                        $items = array_merge($items, $found);
                    } catch (\Throwable $e) { $errors[] = 'API ' . $api->name . ' tidak dapat dimuat. Periksa hostname dan format JSON.'; }
                }
                $this->addData(['vod_items'=>$items, 'vod_errors'=>$errors, 'results'=>'']);
                return $this->jsonResponse();
            }
        }
        if(! empty($title)){

            $tmdb = service('tmdb');
            $results = $tmdb->translate()
                            ->search( $title, $type );


            if(! empty( $results )){

                $movieModel = new MovieModel();
                $seriesModel = new SeriesModel();

                foreach ($results as $key => $val) {

                    $results[$key]['is_exist'] = false;

                    if($type == 'movie'){
                        $movie = $movieModel->select('movies.id')
                                            ->movies()
                                            ->getMovieByUniqId( $val['tmdb_id'], 'tmdb_id', false );
                    }else{
                        $movie = $seriesModel->select('id')
                                             ->getSeriesByTmdbId($val['tmdb_id']);
                    }

                    if($movie !== null){
                        $results[$key]['is_exist'] = true;
                    }
                }

            }

            ob_start();
            the_admin_suggest_page( $results );
            $content .= ob_get_clean();

            $this->addData( ['results' => $content] );
        }

        return $this->jsonResponse();
    }

}