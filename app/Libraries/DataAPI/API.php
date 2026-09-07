<?php

namespace App\Libraries\DataAPI;


use App\Libraries\DataAPI\Items\Episode;

class API
{

    public $imdbId = null;
    public $tmdbId = null;

    protected $tmdbAPI = null;
    protected $omdbAPI = null;

    public function __construct()
    {
        if( is_tmdb_api_enbaled() ){
            $this->tmdbAPI = new Tmdb();
        }

        if( is_omdb_api_enbaled() ){
            $this->omdbAPI = new Omdb();
        }
    }


    public function discover($t = 'movie', $page = 1, $filters = [], $sort = 'popularity', $sort_dir = 'desc')
    {
        $results = [];
        if($this->isTmdbInit()){
            if(! empty($filters['genres'])){
                $genres = $this->getGenreIds( $filters['genres'],  $t);
                $filters['with_genres'] = implode(',', $genres);
                unset($filters['genres']);
            }
            $filters['page'] = $page;

            if(! empty($sort) && ! empty($sort_dir)){
                $filters['sort_by'] = "{$sort}.{$sort_dir}";
            }

            foreach ($filters as $key => $val){
                if(trim($val) == ''){
                    unset($filters[$key]);
                }
            }

           $results = $this->tmdbAPI->discover($t, $filters);
        }

        return is_array($results) ? $results : [];
    }

    public function getEpisode($season = null, $episode = null) : ?Episode
    {
        $results = null;
        if(! empty( $this->tmdbId ) && $this->isTmdbInit()){
            //attempt to get data from tmdb api
            if(! empty( $season )){
                $results = $this->tmdbAPI->getEpisode($this->tmdbId, $season, $episode );
                if($results !== null){
                    //append imdb rate and run time
                    if($this->isOmdbInit())
                        $this->appendTmdbData($results, ['imdb_rate', 'duration']);
                }
            }
        }

        if($results === null){
            //attempt to get data from omdb api
            if($this->isOmdbInit() && ! empty($this->imdbId)){
                $results = $this->omdbAPI->getEpisode( $this->imdbId, $season, $episode );
            }
        }

        return $results;
    }


    public function getMovie()
    {
        $results = null;
        if( $this->isTmdbInit() ){
            //attempt to get data from tmdb api
            if(! empty( $this->imdbId )){
                $results = $this->tmdbAPI->findByImdbId( $this->imdbId, 'movie');
                if($results === null){
                    if($this->isOmdbInit()){
                        $results = $this->omdbAPI->getMovie( $this->imdbId );
                    }
                }
            }else if(! empty( $this->tmdbId )){
                $results = $this->tmdbAPI->getMovie( $this->tmdbId );
            }
            if($results !== null){
                //append imdb rate
                if($this->isOmdbInit() && $results->source != 'omdb')
                    $this->appendTmdbData($results, ['imdb_rate', 'country']);
            }
        }

        return $results;
    }

    public function getTv()
    {
        $results = null;
        if( $this->isTmdbInit() ){
            //attempt to get data from tmdb api
            if(! empty( $this->imdbId )){
                $results = $this->tmdbAPI->findByImdbId( $this->imdbId, 'series');
                if($results === null){
                    if($this->isOmdbInit()){
                        $results = $this->omdbAPI->getTv( $this->imdbId );
                    }
                }
            }else if(! empty( $this->tmdbId )){
                $results = $this->tmdbAPI->getTv( $this->tmdbId );
            }
            if($results !== null){
                //append imdb rate
                if($this->isOmdbInit() && $results->source != 'omdb')
                    $this->appendTmdbData($results, ['imdb_rate', 'country']);
            }
        }

        return $results;
    }

    public function findByImdbId($imdbId = null, $type = null)
    {
        $imdbId = ! empty($imdbId) ? $imdbId : $this->imdbId;
        $results = null;

        if(! empty($imdbId)){
            if($this->isTmdbInit()){
                $results = $this->tmdbAPI->findByImdbId( $imdbId, $type );
            }else if($this->isOmdbInit()) {
                $results = $this->omdbAPI->findByImdbId( $imdbId, $type );
            }
        }

        return $results;

    }

    public function isTmdbInit(): bool
    {
        return $this->tmdbAPI !== null;
    }

    public function isOmdbInit(): bool
    {
        return $this->omdbAPI !== null;
    }

    public function setTmdbId( $tmdbId ): API
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function setImdbId( $imdbId ): API
    {
        $this->imdbId = $imdbId;
        return $this;
    }

    public function setUniqId( $uniqId )
    {
        if(isValidImdbId( $uniqId )){
            $this->setImdbId( $uniqId );
        }else{
            $this->setTmdbId( $uniqId );
        }

        return $this;
    }

    public function appendTmdbData($obj, $data )
    {
        if(empty($data) || ! is_array($data))
            return $obj;

        if($this->isOmdbInit()){
            $omdbResults =  $this->omdbAPI->findByImdbId( $obj->imdb_id );
            if($omdbResults !== null){
                foreach ($data as $val) {
                    if(property_exists($omdbResults, $val)){
                        $obj->$val = $omdbResults->$val;
                    }
                }
            }
        }


        return $obj;
    }

    public function translate(): API
    {
        $this->tmdbAPI->translate();
        return $this;
    }

    public function getGenres($t = 'movie')
    {
        $genres = $this->getJsonData( "{$t}_genre" );
        return is_array( $genres ) ? $genres : [];
    }


    public function getGenreIds( $genres = [], $t = 'movie' ): array
    {
        $tmdbGenres = $this->getGenres($t);
        $results = [];

        if(! empty($genres) && ! empty($tmdbGenres)){
            foreach ($genres as  $val) {
                $result = preg_grep("/{$val}/i", $tmdbGenres);
                if(! empty($result)){
                    $results[] = array_key_first($result);
                }
            }
        }

        return $results;
    }

    public function getLangList()
    {
        return $this->getJsonData( 'lang' );
    }

    protected function getJsonData($file)
    {
        $file = __DIR__ . "/json/{$file}.json";
        $results = [];
        if(file_exists( $file )){
            $content = file_get_contents($file);
            if(isJson( $content )){
                $results = json_decode( $content, true);
            }
        }
        return $results;
    }
}