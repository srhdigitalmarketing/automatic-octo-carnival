<?php

namespace App\Libraries\DataAPI\Items;


class Season extends Item {

    protected $total_episodes = 0;


    protected function cleanOmdbData()
    {

    }

    protected function cleanTmdbData()
    {
        $episodes = $this->data['episodes'] ?? [];
        $this->total_episodes = count($episodes);
    }


}