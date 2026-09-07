<?php

namespace App\Libraries;


class Analytics {

    protected $db;

    protected $data;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->initDataMap();
    }

    public function init(): Analytics
    {
        $this->initMovies();
        $this->initSeries();
        $this->initEpisodes();
        $this->initLinks();
        $this->initLinksRequests();
        $this->initReportedLinks();
        $this->initLinksCompletion();
        $this->calcCoverage();

        return $this;

    }

    public function getData($asArray = false)
    {
        $data = $this->data;
        if(! $asArray)
            $data = json_decode( json_encode( $data ) );

        return $data;
    }

    protected function initMovies()
    {
        $builder = $this->db->table('movies');

        //get total movies
        $total = $builder->where('type', 'movie')
                         ->countAllResults();

        //movies with links
        $with_links = $builder->join('links', 'links.movie_id = movies.id')
                              ->select('movies.id')
                              ->where('movies.type', 'movie')
                              ->where('links.type', 'stream')
                              ->groupBy('movies.id')
                              ->countAllResults();

        $without_links = $total - $with_links;
        $links_completion_rate = $total > 0 ? round( $with_links / $total * 100 ) : 0;

        //views
        $views = $builder->selectSum('views')
                         ->where('type', 'movie')
                         ->get()
                         ->getFirstRow()
                         ->views;

        $data = compact('total', 'with_links', 'without_links', 'views', 'links_completion_rate');
        $this->setVal('movies', $data);

    }

    protected function initSeries()
    {
        $builder = $this->db->table('series');

        //get total series
        $total = $builder->countAllResults();

        //completed series
        $completed = $builder->where('is_completed', 1)
                             ->countAllResults();

        $incomplete = $total - $completed;

        //completion rate
        $completion_rate = $total > 0 ? round( $completed / $total * 100 ) : 0;

        $data = compact('total', 'completed', 'incomplete', 'completion_rate');
        $this->setVal('series', $data);

    }

    protected function initEpisodes()
    {
        $builder = $this->db->table('movies');

        //total episodes
        $total = $this->db->table('seasons')
                                  ->selectSum('total_episodes')
                                  ->get()
                                  ->getFirstRow()
                                  ->total_episodes;

        //completed episodes
        $completed = $builder->where('type', 'episode')
                             ->countAllResults();

        $incomplete = $total - $completed;
        if($incomplete < 0) $incomplete = 0;

        //completion rate
        $completion_rate = $total > 0 ? round( $completed / $total * 100 ) : 0;

        //episodes with links
        $with_links = $builder->join('links', 'links.movie_id = movies.id')
                              ->select('movies.id')
                              ->where('movies.type', 'episode')
                              ->where('links.type', 'stream')
                              ->groupBy('movies.id')
                              ->countAllResults();

        $without_links = $completed - $with_links;
        $links_completion_rate = $total > 0 ? round( $with_links / $total * 100 ) : 0;

        //views
        $views = $builder->selectSum('views')
                         ->where('type', 'episode')
                         ->get()
                         ->getFirstRow()
                         ->views;

        $data = compact('total', 'completed', 'incomplete', 'with_links',
            'without_links', 'views', 'links_completion_rate', 'completion_rate');

        $this->setVal('episodes', $data);

    }

    protected function initLinks()
    {
        $builder = $this->db->table('links');

        //total links
        $total = $builder->countAllResults();

        //streaming links
        $stream =  $builder->where('type', 'stream')
                           ->countAllResults();

        //direct download links
        $direct_dl =  $builder->where('type', 'direct_download')
                              ->countAllResults();

        //torrent download links
        $torrent_dl = $total - ( $stream + $direct_dl );

        $data = compact('total', 'stream', 'direct_dl', 'torrent_dl');

        $this->setVal('links', $data);

    }

    protected function initLinksRequests()
    {
        //total links requests
        $total = $this->__countLinksRequests();

        //stream links requests
        $stream = $this->__countLinksRequests('stream');

        //direct links requests
        $direct_dl = $this->__countLinksRequests('direct_download');

        //torrent links requests
        $torrent_dl = $total - ( $stream + $direct_dl );

        $data = compact('total', 'stream', 'direct_dl', 'torrent_dl');
        $this->setVal('links_requests', $data);

    }

    protected function initReportedLinks()
    {
        //total reported links
        $total = $this->__countReportedLinks();

        //reported stream links
        $stream = $this->__countReportedLinks('stream');

        //reported direct download links
        $direct_dl = $this->__countReportedLinks('direct_download');

        //reported torrent download links
        $torrent_dl = $total - ( $stream + $direct_dl  );

        $data = compact('total', 'stream', 'direct_dl', 'torrent_dl');
        $this->setVal('reported_links', $data);

    }

    protected function initLinksCompletion()
    {
        $builder = $this->db->table('movies');

        //get total movies
        $total = $builder->countAllResults();

        //streaming links completion
        $with = $builder->join('links', 'links.movie_id = movies.id')
                        ->select('movies.id')
                        ->where('links.type', 'stream')
                        ->groupBy('movies.id')
                        ->countAllResults();

        $without = $total - $with;
        $completion_rate = 0;
        if($total > 0){
            $completion_rate = $total > 0 ? floor( $with / $total * 100 ) : 0;
        }

        $data['stream'] = compact('with', 'without', 'completion_rate');


        //download links completion
        $with = $builder->join('links', 'links.movie_id = movies.id')
                        ->select('movies.id')
                        ->where('links.type !=', 'stream')
                        ->groupBy('movies.id')
                        ->countAllResults();

        $without = $total - $with;
        $completion_rate = 0;
        if($total > 0){
            $completion_rate = $total > 0 ? floor( $with / $total * 100 ) : 0;
        }

        $data['download'] = compact('with', 'without', 'completion_rate');

        $this->setVal('links_completion', $data);

    }

    protected function calcCoverage()
    {
        //get total movies
        $total = $this->db->table('movies')
                          ->countAllResults();

        //get failed movies
        $failed = $this->db->table('failed_movies')
                           ->countAllResults();

        $value = $total > 0 ? floor( $total / ( $total + $failed ) * 100 ) : 0;

        switch ($value) {
            case $value >= 75:
                $color_class = 'green';
                break;
            case $value >= 50:
                $color_class = 'yellow';
                break;
            case $value > 0:
                $color_class = 'red';
                break;
            default:
                $color_class = 'light-grey';
        }

        $this->setVal('coverage', compact('value', 'color_class'));

    }

    protected function __countLinksRequests(?string $type = '')
    {
        $builder = $this->db->table('links');

        if(! empty( $type ))
            $builder->where('type', $type);

        $requests = $builder->selectSum('requests')
                            ->get()
                            ->getFirstRow()
                            ->requests;

        return is_numeric( $requests ) ? $requests : 0;
    }

    protected function __countReportedLinks(?string $type = '')
    {
        $builder = $this->db->table('links');

        if(! empty( $type ))
            $builder->where('type', $type);

        return $builder->groupStart()
                            ->where('reports_not_working >', 0)
                            ->orWhere('reports_wrong_link >', 0)
                        ->groupEnd()
                       ->countAllResults();
    }


    protected function initDataMap()
    {
        $dataMap = [
            'coverage' => [
                'value' => 0,
                'color_class' => ''
            ],
            'movies' => [
                'total' => 0,
                'views' => 0
            ],
            'series' => [
                'total' => 0,
                'completion_rate' => 0,
                'completed' => 0,
                'incomplete' => 0
            ],
            'episodes' => [
                'total' => 0,
                'completion_rate' => 0,
                'completed' => 0,
                'incomplete' => 0,
                'views' => 0
            ],
            'links' => [
                'total' => 0,
                'stream' => 0,
                'direct_dl' => 0,
                'torrent_dl' => 0
            ],
            'links_requests' => [
                'total' => 0,
                'stream' => 0,
                'direct_dl' => 0,
                'torrent_dl' => 0
            ],
            'reported_links' => [
                'total' => 0,
                'stream' => 0,
                'direct_dl' => 0,
                'torrent_dl' => 0
            ],
            'links_completion' => [
                'stream' => [
                    'with' => 0,
                    'without' => 0,
                    'completion_rate' => 0
                ],
                'download' => [
                    'with' => 0,
                    'without' => 0,
                    'completion_rate' => 0
                ]
            ],
        ];

        $this->data = $dataMap;

    }


    protected function setVal($key , $data)
    {
        if(isset( $this->data[$key] )){
            $this->data[$key] = $data;
        }
    }



}