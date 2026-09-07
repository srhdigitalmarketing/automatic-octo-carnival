<?php

namespace App\Libraries\DataAPI;


use App\Libraries\DataAPI\Items\Episode;
use App\Libraries\DataAPI\Items\Item;
use App\Libraries\DataAPI\Items\Movie;
use App\Libraries\DataAPI\Items\Series;

class Tmdb
{

    protected $apiKey;
    protected $baseUrl = 'https://api.themoviedb.org/3';
    protected $httpClient;
    protected $translate = false;

    public function __construct()
    {
        $options = [
            'timeout' => 5,
            'http_errors' => false
        ];
        $this->httpClient = \Config\Services::curlrequest( $options );
        $this->apiKey = get_config( 'tmdb_api_key' );
    }

    public function translate(): Tmdb
    {
        $this->translate = true;
        return $this;
    }

    public function search( $query, $type = 'movie',  $page = 1)
    {

        $t = $type == 'movie' ? 'movie' : 'tv';
        $options = [
            'query' => $query,
            'page' => $page
        ];
        $results = $this->getData("/search/{$t}", $options);

        $data = [];
        if(! empty( $results['results'] )){

            $results = $results['results'];
            foreach ($results as $result) {

                $title = $result['title'] ?? $result['original_name'];
                $poster = default_poster_uri();
                if(! empty($result['backdrop_path'])){
                    $poster = "https://image.tmdb.org/t/p/w300" . $result['poster_path'];
                }

                $data[] = [
                    'tmdb_id' => $result['id'],
                    'poster' => $poster,
                    'title'  => $title
                ];

            }

        }

        return $data;
    }

    public function discover($t = 'movie', $options = [])
    {
        $t = $t == 'movie' ? 'movie' : 'tv';
        $results = $this->getData("/discover/{$t}", $options);

        $data = [];

        if(! empty($results['results'])){
            foreach ($results['results'] as $result) {
                if($t == 'movie'){
                    $movie = new Items\Movie( 'tmdb' , $result );
                }else{
                    $movie = new Items\Series( 'tmdb',  $result );
                }

                $movie->cleanData();
                $data[] = $movie;
            }
            $results['results'] = $data;
        }

        return is_array($results) ? $results : [];
    }



    public function getGenres($t = 'movie')
    {
        $t = $t == 'movie' ? 'movie' : 'tv';
        $results = $this->getData("/genre/{$t}/list");
        return $results['genres'] ?? [];

    }

    public function getMovie($id)
    {
        $results = $this->getData("/movie/{$id}", [
            'append_to_response' => 'external_ids,videos,translations'
        ]);

        if($results !== null){
            $movie = new Items\Movie( 'tmdb' , $results );
            $movie->cleanData();

            return $movie;
        }

        return null;
    }

    public function getTv($id)
    {
        $results = $this->getData("/tv/{$id}", [
            'append_to_response' => 'external_ids,videos,translations'
        ]);


        if($results !== null){
            $series = new Items\Series( 'tmdb',  $results );
            $series->cleanData();

            return $series;
        }

        return null;
    }

    public function getSeason(int $tvId, int $season = 1)
    {
        $results = $this->getData("/tv/{$tvId}/season/{$season}");

        if($results !== null){
            $season = new Items\Season( 'tmdb',  $results );
            $season->cleanData();

            return $season;
        }

        return null;
    }

    public function getEpisode($tvId, $season, $episode)
    {
        $results = $this->getData("/tv/{$tvId}/season/{$season}/episode/{$episode}", [
            'append_to_response' => 'external_ids,videos,translations'
        ]);

        if($results !== null){

            $episode = new Items\Episode( 'tmdb',  $results );
            $episode->cleanData();

            return $episode;
        }

        return null;
    }



    public function findByImdbId($imdbId, $type = null)
    {
        $results = $this->getData("/find/{$imdbId}", [
            'external_source' => 'imdb_id'
        ]);

        if($type == null) {

            if(isset( $results['tv_results'][0] ))
                $type = 'series';

            if(isset( $results['movie_results'][0] ))
                $type = 'movie';

            if(isset( $results['tv_episode_results'][0] ))
                $type = 'episode';

        }

        if($results !== null) {

            switch ($type) {
                case 'series':

                    if(isset($results['tv_results'][0])){

                        $tvData = $results['tv_results'][0];
                        $tvData = $this->getTv( $tvData['id'] );

                        if($tvData !== null){

                            $series = new Series( 'tmdb', $tvData->data );
                            $series->cleanData();
                            $series->fill( ['imdb_id' => $imdbId] );

                            return $series;

                        }
                    }

                    break;

                case 'movie':

                    if(isset($results['movie_results'][0])){

                        $movieData = $results['movie_results'][0];
                        $movieData = $this->getMovie($movieData['id']);

                        if($movieData !== null) {

                            $movie = new Movie( 'tmdb', $movieData->data );
                            $movie->cleanData();

                            return $movie;
                        }

                    }

                    break;

                case 'episode':

                    if(isset($results['tv_episode_results'][0])){

                        $episode = new Episode( 'tmdb', $results['tv_episode_results'][0] );
                        $episode->cleanData();
                        $episode->fill( ['imdb_id' => $imdbId] );

                        return $episode;

                    }

                    break;

            }


        }

        return null;

    }




    protected function getData($path, $options = [])
    {
        $url = "{$this->baseUrl}{$path}";

        $options['api_key'] = $this->apiKey;

        if($this->translate || ! is_multi_languages_enabled()){
            $options['language'] = current_language();
            $this->translate = false;
        }

        $response = $this->httpClient->request('get', $url, [
            'headers' => [
                'Accept'     => 'application/json'
            ],
            'query' => $options
        ]);

        if($response->getStatusCode() == 200) {
            if (strpos($response->getHeader('content-type'), 'application/json') !== false) {
                return json_decode( $response->getBody(), true );
            }
        }

        return null;
    }









}