<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class SeasonModel
 * @package App\Models
 * @author John Antonio
 */
class SeasonModel extends Model
{
    protected $table = 'seasons';
    protected $allowedFields = [ 'series_id','season', 'total_episodes' ];
    protected $validationRules = [
        'series_id' => [
            'label' => 'series id',
            'rules' => 'required|is_natural_no_zero|exist[series.id]'
        ],
        'season' => 'required|is_natural_no_zero',
        'total_episodes' => [
            'label' => 'total episodes',
            'rules' => 'permit_empty|is_natural'
        ]
    ];
    protected $beforeInsert = ['addTotalEpisodes'];
    protected $afterFind = ['appendEpisodes'];
    protected $returnType = 'App\Entities\Season';
    protected $withEpisodes = false;


    /**
     * Append episodes to each season after find
     * @param array $data
     * @return array
     */
    protected function appendEpisodes(array $data): array
    {

        if(! $this->withEpisodes)
            return $data;

        $seasons = $data['data'];
        $isFirst = $data['method'] == 'first';
        if($isFirst) {
            $seasons = [ $seasons ];
        }

        $movieModel = new MovieModel();

        foreach ($seasons as $key => $season){

            if($season === null)
                continue;

            $episodes = $movieModel->where('movies.season_id', $season->id)
                                   ->orderBy('movies.episode')
                                   ->forView()
                                   ->allMovies( ['type'=>'episode'] )
                                   ->find();

            $completedRate = 'N/A';
            $rateColorClass = 'secondary';
            if(! empty($season->total_episodes)) {
                $numOfEpisodes = count( $episodes ) ;
                if($season->total_episodes >= $numOfEpisodes) {
                    $completedRate = round( $numOfEpisodes / $season->total_episodes * 100) ;

                    if($completedRate >= 90 ) {
                        $rateColorClass = 'success';
                    }elseif($completedRate >= 50){
                        $rateColorClass = 'warning';
                    }else{
                        $rateColorClass = 'danger';
                    }

                    $completedRate .=  '%';

                }
            }

            $season->completeRate = $completedRate;
            $season->completeRateColorClass = "text-{$rateColorClass}";
            $season->episodes = $episodes;
        }

        $data['data'] = $isFirst ? $seasons[0] : $seasons;

        return $data;

    }

    /**
     * Set total episodes
     * @param array $data
     * @return array
     */
    protected function addTotalEpisodes(array $data): array
    {
        $tmpData = $data['data'];
        if(! isset( $tmpData['series_id'] ) || ! empty( $tmpData['total_episodes'] ))
            return $data;

        //attempt to get total episode in current season
        $seriesModel = new SeriesModel();
        $series = $seriesModel->where('id', $tmpData['series_id'])
                              ->first();

        if(! empty( $series->tmdb_id )) {
            $seasonData = service('tmdb')->getSeason( $series->tmdb_id, $tmpData['season'] );
            if($seasonData !== null ) {
                $tmpData['total_episodes'] = $seasonData->total_episodes;
            }
        }

        $data['data'] = $tmpData;

        return $data;

    }

    /**
     * Find season by series id
     * @param int $seriesId
     * @param null|string $season
     * @return array|object|null
     */
    public function findBySeriesId(int $seriesId, $season = null)
    {
        if(empty($seriesId))
            return null;

        if(empty($season)) {
            return $this->where('series_id', $seriesId)
                        ->orderBy('season')
                        ->find();
        }

        return $this->where('series_id', $seriesId)
                    ->where('season', $season)
                    ->first();
    }

    /**
     * Find seasons with episodes
     * @return $this
     */
    public function withEpisodes(): SeasonModel
    {
        $this->withEpisodes = true;
        return $this;
    }

    /**
     * Get next temporary  season and episode number
     * @param $seriesId
     * @param null $seasonNumber
     * @return array
     */
    public function getNextTmpEpisodeInfo($seriesId, $seasonNumber = null): array
    {
        $nextSeason = $seasonNumber;
        $nextEpisode = $nextSeason = 1;

        if(! empty($seasonNumber))
            $this->where('season', $seasonNumber);

        $season = $this->where('series_id', $seriesId)
                        ->orderBy('season', 'desc')
                        ->withEpisodes()
                        ->first();

        if($season !== null) {

            if(empty($nextSeason))
                $nextSeason = $season->season;

            if($season->hasEpisodes()) {
                $episodes = $season->episodes();
                $lastEpisode = end($episodes) ;

                //set next episode number
                $nextEpisode = $lastEpisode->episode + 1;

                if(! empty($season->total_episodes)){
                    //check is it end of the season
                    if($lastEpisode->episode == $season->total_episodes) {
                        //jump to next season
                        $resp = $this->getNextTmpEpisodeInfo($seriesId, $season->season + 1);
                        $nextSeason = $resp['nextSeason'];
                        $nextEpisode = $resp['nextEpisode'];
                    }
                }

            }
        }

        return [
            'nextSeason' => $nextSeason,
            'nextEpisode' => $nextEpisode
        ];

    }

    /**
     * @param int $seriesId
     * @param $seasonNumber
     * @return int|string|null
     */
    public function getSeasonId(int $seriesId, $seasonNumber)
    {
        $season = $this->findBySeriesId( $seriesId, $seasonNumber );
        $seasonId = null;

        if(empty($season)){

            //create new season
            $seasonData = [
                'series_id' => $seriesId,
                'season' => $seasonNumber
            ];
            try{
                if($this->insert( $seasonData )) {
                    $seasonId = $this->getInsertID();
                }
            }catch (\ReflectionException $e) {}

        }else{

            $seasonId = $season->id;

        }

        return $seasonId;

    }

    /**
     * Get next season
     * @param int $seriesId
     * @param $season
     * @return array|object|null
     */
    public function getNextSeason(int $seriesId, $season )
    {
        $this->orderBy('season', 'asc');
        $this->where('season > ', $season);
        $this->where('series_id', $seriesId);

        return $this->first();

    }

    /**
     * Get previous season
     * @param int $seriesId
     * @param $season
     * @return array|object|null
     */
    public function getPrevSeason(int $seriesId, $season )
    {
        $this->orderBy('season', 'desc');
        $this->where('season < ', $season);
        $this->where('series_id', $seriesId);

        return $this->first();

    }

}