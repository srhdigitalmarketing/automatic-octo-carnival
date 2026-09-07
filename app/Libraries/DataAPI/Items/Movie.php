<?php

namespace App\Libraries\DataAPI\Items;




class Movie extends Item {

    protected $title = '';
    protected $description = '';
    protected $imdb_id = '';
    protected $tmdb_id = '';
    protected $imdb_rate = '';
    protected $duration = '';
    protected $poster_url = '';
    protected $banner_url = '';
    protected $year = '';
    protected $released_at = '';
    protected $trailer = '';
    protected $language = '';
    protected $country = '';

    protected $type = 'movie';

    protected function cleanOmdbData()
    {
        $this->title = $this->data['Title'] ?? '';
        $this->description = $this->data['Plot'] ?? '';
        $this->imdb_rate = is_numeric( $this->data['imdbRating'] ) ? $this->data['imdbRating'] : '';
        $this->duration = $this->data['Runtime'] ?? '';
        $this->imdb_id = $this->data['imdbID'] ?? '';
        $this->year = $this->data['Year'] ?? '';
        $this->country = $this->data['Country'] ?? '';

        if(! empty($this->duration)){
            preg_match("/([0-9]+)/", $this->duration, $matches);
            $this->duration = $matches[1] ?? 0;
        }

    }

    protected function cleanTmdbData()
    {
        $this->tmdb_id = $this->data['id'] ?? '';
        $this->imdb_id = $this->data['external_ids']['imdb_id'] ?? '';
        $this->title = $this->data['title'] ?? '';
        $this->poster_url = $this->data['poster_path'] ?? '';
        $this->description = $this->data['overview'] ?? '';
        $this->banner_url = $this->data['backdrop_path'] ?? '';
        $this->duration = $this->data['runtime'] ?? '';

        $releaseDate = $this->data['release_date'] ?? '';
        if(empty($releaseDate)){
            $releaseDate = $this->data['air_date'] ?? '';
        }

        if(! empty($releaseDate)){
            $this->year = date('Y', strtotime($releaseDate));
            $this->released_at = date('Y-m-d', strtotime($releaseDate));
        }

        if(isset( $this->data['genres'] ))
            $this->genres = extract_array_data($this->data['genres'], 'name');

        if(! empty($this->poster_url))
            $this->poster_url = "https://image.tmdb.org/t/p/w300{$this->poster_url}";

        if(! empty($this->banner_url))
            $this->banner_url = "https://image.tmdb.org/t/p/original{$this->banner_url}";

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