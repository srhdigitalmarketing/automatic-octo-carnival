<?php

namespace App\Libraries\DataAPI\Items;


class Episode extends Movie {

    protected $season = 1;
    protected $episode = 1;
    protected $type = 'episode';

    protected function cleanOmdbData()
    {
        parent::cleanOmdbData();

    }

    protected function cleanTmdbData()
    {
        parent::cleanTmdbData();
        $this->title = $this->data['name'] ?? '';
        $this->season = $this->data['season_number'] ?? '';
        $this->imdb_id = $this->data['external_ids']['imdb_id'] ?? '';
        $this->episode = $this->data['episode_number'] ?? '';
        $this->banner_url = $this->data['still_path'] ?? '';

        $releaseDate = $this->data['air_date'] ?? '';
        if(! empty($releaseDate)){
            $this->year = date('Y', strtotime($releaseDate));
        }

        if(! empty($this->banner_url))
            $this->banner_url = "https://image.tmdb.org/t/p/original{$this->banner_url}";

    }

}