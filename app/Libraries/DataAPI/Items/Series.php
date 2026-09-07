<?php

namespace App\Libraries\DataAPI\Items;


class Series extends Item {

    protected $title = '';
    protected $imdb_id = '';
    protected $tmdb_id = '';
    protected $imdb_rate = '';
    protected $total_episodes = 0;
    protected $total_seasons = 0;
    protected $poster_url = '';
    protected $banner_url = '';
    protected $genres = [];
    protected $status = '';
    protected $year = '';
    protected $released_at = '';
    protected $trailer = '';
    protected $language = '';
    protected $country = '';

    protected $type = 'series';

    protected function cleanOmdbData()
    {
        $this->imdb_id = $this->data['imdbID'] ?? '';
        $this->title = $this->data['Title'] ?? '';
        $this->imdb_rate = is_numeric( $this->data['imdbRating'] ) ? $this->data['imdbRating'] : '';
        $this->total_seasons = $this->data['totalSeasons'] ?? '';
        $this->country = $this->data['Country'] ?? '';
    }

    protected function cleanTmdbData()
    {
        $this->tmdb_id = $this->data['id'] ?? '';
        $this->title = $this->data['original_name'] ?? '';
        $this->imdb_id = $this->data['external_ids']['imdb_id'] ?? '';
        $this->total_seasons = $this->data['number_of_seasons'] ?? '';
        $this->total_episodes = $this->data['number_of_episodes'] ?? '';
        $this->poster_url = $this->data['poster_path'] ?? '';
        $this->banner_url = $this->data['backdrop_path'] ?? '';


        if(isset( $this->data['genres'] )) {
            $this->genres = fix_arr_data( extract_array_data($this->data['genres'], 'name'), '&');
            $this->genres = array_values($this->genres);
        }

        if(isset( $this->data['status'] ))
            $this->status = $this->data['status'] == 'status' ? 'returning' : 'ended';

        if(! empty($this->poster_url))
            $this->poster_url = "https://image.tmdb.org/t/p/w300{$this->poster_url}";

        if(! empty($this->banner_url))
            $this->banner_url = "https://image.tmdb.org/t/p/original{$this->banner_url}";

        $releaseDate = $this->data['first_air_date'] ?? '';

        if(! empty($releaseDate)){
            $this->year = date('Y', strtotime($releaseDate));
            $this->released_at = date('Y-m-d', strtotime($releaseDate));
        }

        if(isset($this->data['videos']['results'][0])){
            $videoData = $this->data['videos']['results'][0];
            if($videoData['site'] == 'YouTube'){
                $this->trailer = 'https://youtube.com/embed/' . $videoData['key'];
            }
        }

        if(! empty($this->data['original_language'])){
            $this->language = strtolower(\Locale::getDisplayLanguage($this->data['original_language']));
        }


    }



}