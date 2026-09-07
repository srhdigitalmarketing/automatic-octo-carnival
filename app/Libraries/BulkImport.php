<?php

namespace App\Libraries;


use App\Models\MovieModel;
use App\Models\SeriesModel;

class BulkImport
{

    /**
     * Import data type
     * @var string movies|series|episode
     */
    protected $type = 'movies';


    protected $dataApi = null;
    protected $uniqIds = [];
    protected $results = [];
    protected $sleep = 1; // 1 second

    protected $movieModel = null;
    protected $seriesModel = null;

    protected $quickInserter = null;

    protected $activeId = null;


    public function __construct()
    {
        helper('data_api');
        $this->dataApi = service('data_api');

        $this->movieModel = new MovieModel();
        $this->seriesModel = new SeriesModel();

        $this->quickInserter = service('quick_insert');
    }

    public function run()
    {
        if(! empty( $this->uniqIds )){

            //before run
            $this->initResults();
            $this->cleanUniqIds();

            while(! $this->isQueryEmpty()) {

                switch ($this->type) {
                    case 'movies':
                        $this->importMovies();
                        break;
                    case 'series':
                        $this->importSeries();
                        break;
                    case 'episodes':
                        $this->importEpisodes();
                        break;
                }

                if(! $this->isQueryEmpty()){
                    $this->sleep();
                }
            }

        }


    }

    protected function importMovies()
    {
        $movie = null;
        $results = $this->dataApi->setUniqId( $this->getUniqId() )
                                 ->getMovie();

        if($results !== null){

            $uniqId = ! empty($results->imdb_id) ? $results->imdb_id : $results->tmdb_id;
            $movie = $this->movieModel->getMovieByUniqId( $uniqId );

            if($movie === null){

                //save
                $movie = $this->quickInserter->movie()
                                                ->set( $results->getData() )
                                                ->save();
                if($movie === null){

                    $this->addError( $this->quickInserter->getError() );

                }

            }else{

                $this->exist();
                $this->addError( 'Movie is already exist');

            }

        }else{

            $this->addError( 'TMDB data not found');

        }

        //set data
        if($movie !== null){

            $data = [
                'id' => $movie->id,
                'title' => $movie->getMovieTitle(),
                'source_id' => $this->activeId,
                'imdb_id' => $movie->imdb_id
            ];

            $this->success();
            $this->setData( $data );
        }

    }

    protected function importSeries()
    {
        $series = null;
        $results = $this->dataApi->setUniqId( $this->getUniqId() )
                                 ->getTv();

        if($results !== null){

            $uniqId = ! empty($results->imdb_id) ? $results->imdb_id : $results->tmdb_id;
            $series = $this->seriesModel->getSeriesByUniqId( $uniqId );

            if($series === null){

                $series = $this->quickInserter->series()
                                              ->set( $results->getData() )
                                              ->save();

                if($series === null){

                    $this->addError( $this->quickInserter->getError() );

                }

            }else{

                $this->exist();
                $this->addError( 'Series already exist' );

            }

        }else{

            $this->addError( 'TMDB data not found' );

        }


        if($series !== null){

            if(! empty( $results->data['seasons'] )){
                foreach ($results->data['seasons'] as $season){
                    if($season['season_number'] > 0){
                        $seaNum = $season['season_number'];
                        if(! $this->isAllEpisodes()){
                            if(isset( $this->results[$this->activeId]['meta']['seasons'][$seaNum] )){
                                $this->results[$this->activeId]['meta']['seasons'][$seaNum]['total_episodes'] = $season['episode_count'];
                            }
                        }else{
                            $this->results[$this->activeId]['meta']['seasons'][$seaNum]['total_episodes'] = $season['episode_count'];
                        }
                    }
                }
            }


            $episodes = $this->importEpisodes( $series );

            $data = [
                'id' => $series->id,
                'title' => $series->title,
                'source_id' => $this->activeId,
                'imdb_id' => $series->imdb_id,
                'episodes' => $episodes
            ];

            $this->success();
            $this->setData( $data );
        }


    }

    protected function importEpisodes(\App\Entities\Series $series)
    {

        $resp = [];
        $seasons = $this->getSeasons();

        $response = [
            'success' => false,
            'is_exist' => false,
            'data' => null,
            'type' => 'episodes',
            'error' => null
        ];

        $this->dataApi->setImdbId( $series->imdb_id )
                      ->setTmdbId( $series->tmdb_id );

        $seasons = ! empty($seasons) ? array_keys($seasons) : [];
        if($this->isAllEpisodes()){
            $seasons = [];
            for($sea = 1; $sea <= $series->total_seasons; $sea++){
                $seasons[] = $sea;
            }
        }

        if(! empty($seasons)){

            foreach ( $seasons as $sea) {

                $seasonsMeta = $this->getSeasonsMeta( $sea );

                $totalEpisodes = $seasonsMeta['total_episodes'] ?? 0;
                $epiStart = ! empty($seasonsMeta['episodes_start']) ? $seasonsMeta['episodes_start'] : 1;
                $epiEnd = ! empty($seasonsMeta['episodes_end']) ? $seasonsMeta['episodes_end'] : 0;



                if($totalEpisodes > 0){

                    //filter
                    $epiStart = ! $this->isAllEpisodes() && $epiStart !== null ? $epiStart : 1;
                    $epiEnd = ! $this->isAllEpisodes() && $epiEnd <= $totalEpisodes && $epiEnd != null ? $epiEnd : $totalEpisodes;

                    for($epi = $epiStart; $epi <= $epiEnd; $epi++){

                        $episode = null;
                        $epiResponse = $response;
                        $results = $this->dataApi->getEpisode($sea, $epi);
                        if($results !== null){

                            $uniqId = ! empty($results->imdb_id) ? $results->imdb_id : $results->tmdb_id;
                            $episode = $this->movieModel->getMovieByUniqId( $uniqId );

                            if($episode === null){

                                //save
                                $data = $results->getData();
                                $data['series_id'] = $series->id;

                                $episode = $this->quickInserter->episode( $series->id )
                                                               ->set( $data )
                                                               ->save();

                                if($episode === null){

                                    $epiResponse['error'] = $this->quickInserter->getError();

                                }

                            }else{

                                $epiResponse['is_exist'] = true;
                                $epiResponse['error'] = 'Episode already exist';

                            }

                        }else{

                            $epiResponse['error'] = 'TMDB data not found';

                        }

                        if($episode !== null){

                            $episode->series_title = $series->title;
                            $episode->season = $sea;

                            $epiResponse['success'] = true;
                            $epiResponse['data'] = [
                                'id' => $episode->id,
                                'title' => $episode->getMovieTitle(),
                                'imdb_id' => $episode->imdb_id,
                                'episode_number' => $epi
                            ];
                        }

                        $fsea = sprintf("%02d", $sea);
                        $fepi = sprintf("%02d", $epi);

                        $uniqKey = $this->activeId . "[S{$fsea}E{$fepi}]";

                        $resp[$sea]['season_number'] = $sea;
                        $resp[$sea]['episodes'][$uniqKey] = $epiResponse;

                    }

                }

                $this->sleep();
            }

        }

        return $resp;

    }

    protected function getUniqId()
    {
        $id = array_shift( $this->uniqIds );
        $this->activeId = $id;

        return $id;
    }

    protected function isQueryEmpty(): bool
    {
        return empty( $this->uniqIds );
    }

    protected function sleep()
    {
        sleep( $this->sleep );
    }

    protected function cleanUniqIds()
    {
        foreach ($this->uniqIds as $k => $id) {
            if(! isValidMovieId( $id )){
                $this->results[$id]['error'] = 'Invalid Imdb or Tmdb ID';
                unset($this->uniqIds[$k]);
            }
        }
    }

    protected function initResults()
    {
        foreach ($this->uniqIds as $k => $id) {

            $resp = [
                'success' => false,
                'is_exist' => false,
                'data' => null,
                'type' => $this->type,
                'error' => null
            ];
            if($this->type == 'series'){

                $resp['meta'] = [
                    'all' => false,
                    'seasons' => []
                ];

                if(preg_match('/([1-9a-z].*)\[(\*{1})]$/', $id, $matches)){
                    $uniqId = $matches[1];

                    if(! array_key_exists($uniqId, $this->results)){
                        $this->uniqIds[$k] = $uniqId;
                        $resp['meta']['all'] = true;
                    }else{
                        $this->results[$uniqId]['meta']['all'] = true;
                        unset($this->uniqIds[$k]);
                        continue;
                    }

                }else if(preg_match('/([1-9a-z].*)\[(0?[1-9]|1[0-9]|2[0])(\.(0?[1-9]|1[0-9]|2[0]))?(\-(0?[1-9]|1[0-9]|2[0]))?\]/', $id, $matches)){
                    $uniqId = $matches[1];

                    $season = $matches[2];
                    $seasonData = [
                        'episodes_start' => $matches[4] ?? null,
                        'episodes_end' => $matches[6] ?? null
                    ];


                    if(! array_key_exists($uniqId, $this->results)){
                        $this->uniqIds[$k] = $uniqId;
                        $resp['meta']['seasons'][$season] = $seasonData;
                    }else{
                        $this->results[$uniqId]['meta']['seasons'][$season] = $seasonData;
                        unset($this->uniqIds[$k]);
                        continue;
                    }

                }

            }

            $id = $this->uniqIds[$k];
            $this->results[$id] = $resp;
        }
    }

    public function set(array $ids )
    {
        //remove duplicate values
        $ids = array_unique( $ids );

        $this->uniqIds = $ids;
        return $this;
    }

    public function movies()
    {
        $this->type = 'movies';
        return $this;
    }

    public function series()
    {
        $this->type = 'series';
        return $this;
    }

    public function episodes()
    {
        $this->type = 'episodes';
        return $this;
    }

    protected function addError($error)
    {
        if(isset( $this->results[$this->activeId])){
            $this->results[$this->activeId]['error'] = $error;
        }
    }

    protected function exist()
    {
        if(isset( $this->results[$this->activeId])){
            $this->results[$this->activeId]['is_exist'] = true;
        }
    }

    protected function success()
    {
        if(isset( $this->results[$this->activeId])){
            $this->results[$this->activeId]['success'] = true;
        }
    }

    protected function setData( $data )
    {
        if(isset( $this->results[$this->activeId])){
            $this->results[$this->activeId]['data'] = $data;
        }
    }

    protected function getSeasons()
    {
        if(isset( $this->results[$this->activeId]['meta']['seasons'] )){
            return $this->results[$this->activeId]['meta']['seasons'];
        }
        return [];
    }

    protected function isAllEpisodes(): bool
    {
        if(isset( $this->results[$this->activeId]['meta']['all'] )){
            return $this->results[$this->activeId]['meta']['all'] == true;
        }
        return false;
    }


    protected function getSeasonsMeta($season = 0)
    {
        if(isset( $this->results[$this->activeId]['meta']['seasons'][$season])){
            return $this->results[$this->activeId]['meta']['seasons'][$season];
        }
        return [];
    }

    public function getResults(): array
    {
        return $this->results;
    }

}