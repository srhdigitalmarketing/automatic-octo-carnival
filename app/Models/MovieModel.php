<?php

namespace App\Models;

use App\Entities\Link;
use App\Entities\Movie;
use App\Entities\Translation;
use App\Models\Translations\MovieTranslationsModel;
use CodeIgniter\Model;

/**
 * Class MovieModel
 * @package App\Models
 * @author John Antonio
 */
class MovieModel extends Model
{
    protected $table = 'movies';
    protected $allowedFields = [
        'imdb_id',
        'tmdb_id',
        'title',
        'description',
        'poster',
        'banner',
        'duration',
        'imdb_rate',
        'year',
        'type' ,
        'quality' ,
        'trailer' ,
        'language' ,
        'released_at' ,
        'country',
        'meta_keywords',
        'meta_description',
        'status'
    ];
    protected $returnType = '\App\Entities\Movie';

    // Validation
    protected $validationRules = [
        'imdb_id' => [
            'label' => 'IMDB Id',
            'rules' => 'required|is_unique[movies.imdb_id]|valid_imdb_id'
        ],
        'tmdb_id' => [
            'label' => 'TMDB Id',
            'rules' => 'permit_empty|is_unique[movies.tmdb_id]|valid_tmdb_id'
        ],
        'title'       => 'required|min_length[3]|max_length[255]',
        'duration'    => 'permit_empty|is_natural',
        'year'        => 'permit_empty|less_than_equal_to[2050]|greater_than_equal_to[1900]',
        'imdb_rate'   => 'permit_empty|decimal',
        'type'        => 'required|in_list[movie,episode]',
        'status'      => 'permit_empty|in_list[public,draft]'
    ];
    protected $validationMessages = [
        'imdb_id' => [
            'is_unique' => 'Movie or episode is already exist with this IMDB id'
        ],
        'tmdb_id' => [
            'is_unique' => 'Movie or episode is already exist with this TMDB id'
        ]
    ];

    // Dates
    protected $useTimestamps = true;

    // Callbacks
    protected $beforeInsert = ['setDefaultQuality', 'emptyToNull'];
    protected $afterInsert  = ['removeTmpMovie', 'importRequest'];
    protected $beforeUpdate = ['emptyToNull'];
    protected $beforeDelete = ['setTmpData', 'deleteMediaFiles'];
    protected $afterDelete  = ['cleanSeasonData', 'unimportRequest'];
    protected $afterFind  = ['translateLanguage'];

    protected $tmpDeletedData = null;


    protected function translateLanguage(array $data): array
    {
        if(! is_multi_languages_enabled()){
            return $data;
        }

        $language = current_language();

        if(! has_translate_permit())
            return $data;


        if(empty( $language ) || $language == 'en')
            return $data;



        $movies = $data['data'];
        $isFirst = $data['method'] == 'first';
        if($isFirst) {
            $movies = [ $movies ];
        }

        $movieTranslationModel = new MovieTranslationsModel();

        foreach ($movies as $movie) {
            if($movie instanceof \App\Entities\Movie && ! empty($movie->id)){

                $translate = $movieTranslationModel->findByMovieId($movie->id, $language);
                if($translate !== null){

                    if(! empty( $translate->title ))
                        $movie->title = $translate->title;

                    if(! empty( $translate->description ))
                        $movie->description = $translate->description;

                }

            }
        }


        $data['data'] = $isFirst ? $movies[0] : $movies;

        return $data;
    }



    /**
     * Save movie (or episode)
     * @param Movie $movie
     * @return bool|null
     */
    public function saveMovie(Movie $movie): ?bool
    {


        if($movie->isEpisode()) {

            $seasonModel = new SeasonModel();
            $movie->season_id = $seasonModel->getSeasonId($movie->series_id, $movie->season);

            //validate episode info
            $this->setValidationRule('episode', 'required|is_natural_no_zero|both_unique[movies.season_id,episode]');
            $this->setValidationRule('season_id', 'required|exist[seasons.id]|is_natural_no_zero');
            $this->setValidationMessage('episode', ['both_unique'=>'this episode is already exist']);
            $this->allowedFields = array_merge(
                $this->allowedFields,
                ['season_id', 'episode']
            );

            if(empty($movie->id)) {

                $seriesModel = new SeriesModel();
                $series = $seriesModel->where('id', $movie->series_id)
                                      ->first();

                /*
                    If episode poster is empty, when attempt to set that episode's
                    parent show's poster ( also banner)
                */
                if(! empty($series)) {

                    if(empty($movie->poster) && empty($movie->poster_url) )
                        $movie->poster = $series->poster;

                    if(empty($movie->banner) && empty($movie->banner_url))
                        $movie->banner = $series->banner;

                }

            }

        }

        unset($movie->series_id);
        unset($movie->season);

        //set year
        if($movie->hasChanged('released_at')){
            $year = null;
            if(! empty($movie->released_at)){
                $year = date('Y', strtotime($movie->released_at));
            }
            $movie->year = $year;
        }

        if($movie->hasChanged()) {
            try{
                if(! $this->save( $movie )) {
                    return null;
                }
            }catch (\ReflectionException $e) {
                return null;
            }
        }


        return true;

    }


    public function addTranslations(int $movieId, ?array $translations)
    {

        if(empty( $translations )){

            return false;

        }

        $translationsModel = new MovieTranslationsModel();
        $existTranslations = $translationsModel->findByMovieId( $movieId );


        foreach ($translations as $langCode => $translation) {

            $data = [
                'movie_id' => (string) $movieId,
                'lang' => $langCode,
                'title' => $translation['title'],
                'description' => $translation['description']
            ];

            if(array_key_exists( $langCode, $existTranslations )){

                $tmpTranslation = $existTranslations[$langCode];
                $tmpTranslation->fill( $data );

            }else{

                $tmpTranslation = new Translation( $data );

            }

            if($tmpTranslation->hasChanged()){
                try{

                    //we does not care about saving errors
                    $translationsModel->save( $tmpTranslation );
                }catch (\ReflectionException $e){}
            }

        }

        return true;
    }

    /**
     * Add genres to movie (or episode)
     * @param int $movieId
     * @param array|null $genres
     */
    public function addGenres(int $movieId, ?array $genres)
    {
        $movieGenreModel = new MovieGenreModel();
        $existGenres = $movieGenreModel->getGenresByMovieId( $movieId );

        $func = function ($k, $v) {
            return [$v,$v];
        };

        if(! empty($genres))
            $genres = array_map_assoc($func, $genres);

        if(! empty($existGenres)) {

            foreach ($existGenres as $genre) {

                if(! in_array($genre->id, $genres)) {

                    $movieGenreModel->where('movie_id', $genre->movie_id)
                                    ->where('genre_id', $genre->id)
                                    ->delete();

                }else{

                    $existKey = array_search($genre->id, $genres);
                    if($existKey !== false) {
                        unset($genres[$existKey]);
                    }

                }

            }

        }

        //insert new genres
        if(! empty($genres)) {
            $genreList = [];
            foreach ($genres as $genre) {
                $data = [
                    'movie_id' => $movieId,
                    'genre_id' => $genre
                ];
                $genreList[] = $data;
            }
            try{
                $movieGenreModel->insertBatch($genreList);
            }catch (\ReflectionException $e) {}
        }

    }

    /**
     * Add stream or download links to movie (or episode)
     * @param int $movieId current movie id
     * @param array $links links list
     * @param string $type  stream, direct_download, torrent_download
     * @return null
     */
    public function addLinks(int $movieId, array $links,string $type = 'stream')
    {
        if(empty( $links ))
            return null;

        if(! in_array($type, ['stream','direct_download', 'torrent_download'])) {
            return null;
        }

        foreach ($links as $link) {

            $linkModel = new LinkModel();

            $url = $link['url'] ?? '';
            $resolution = $link['resolution'] ?? null;
            $quality = $link['quality'] ?? null;
            $api_id = $link['api_id'] ?? null;
            $id = $link['id'] ?? '';
            $sizeVal = $link['size_val'] ?? '0';
            $sizeLbl = $link['size_lbl'] ?? 'MB';
            $dlLink = null;

            if(! empty($id)) {

                if(empty($url)) {

                    //remove deleted links
                    $linkModel->where('movie_id', $movieId)
                                      ->where('id', $id)
                                      ->where('type', $type)
                                      ->delete();

                    continue;

                }

                //get exist link
                $dlLink = $linkModel->where('id', $id)
                                    ->where('type', $type)
                                    ->first();

            }

            if($dlLink === null) {
                if(empty( $url ))
                    continue;

                $dlLink = new Link();
            }

            //link data
            $data = [
                'movie_id' => (string) $movieId,
                'api_id' => $api_id,
                'link' =>  $url,
                'resolution' => $resolution,
                'type' => $type,
                'quality' => $quality,
                'size_val' => $sizeVal,
                'size_lbl' => $sizeLbl,
            ];

            if ($type === 'stream' && $linkModel->supportsStreamHealthFields()) {
                $submittedPriority = trim((string) ($link['host_priority'] ?? ''));
                if ($submittedPriority === '' && $dlLink !== null && $dlLink->host_priority !== null) {
                    $priority = (int) $dlLink->host_priority;
                } else {
                    $priority = filter_var($submittedPriority, FILTER_VALIDATE_INT);
                    $priority = $priority !== false && $priority >= 0 && $priority <= 65535 ? $priority : 100;
                }

                $data['host_priority'] = $priority;
                $data['upnshare_video_id'] = trim((string) ($link['upnshare_video_id'] ?? '')) ?: null;
            }

            $dlLink->fill( $data );

            if($dlLink->hasChanged()){

                try{
                    //we does not care about saving errors
                    $linkModel->save( $dlLink );
                }catch (\ReflectionException $e){}
            }

        }

    }



    /**
     * Find episode by season id
     * @param int $seasonId
     * @param null $episode
     * @return array|object|null
     */
    public function findBySeasonId(int $seasonId, $episode = null)
    {
        if(empty($seasonId))
            return null;

        if(empty($episode)){
            return $this->where('season_id', $seasonId)
                        ->find();
        }

        return $this->where('season_id', $seasonId)
                    ->where('episode', $episode)
                    ->find();

    }

    /**
     * Get next movie by prev id
     * @param int $id
     * @param bool $isMovie
     * @return array|mixed|object|null
     */
    public function getNextMovie(int $id, $isMovie = true)
    {
        $type = $isMovie ? 'movie' : 'episode';
        $sql = "select * from {$this->table} ";
        $sql .= "where id = (select min(id) from {$this->table} where `id` > {$id} AND `type` = '{$type}' ) LIMIT 1";
        return $this->db->query( $sql )->getRow();
    }

    /**
     * Get next episode
     * @param Movie $movie
     * @param string $page view, download
     * @return null
     */
    public function getNextEpisode(\App\Entities\Movie $movie, string $page = 'download')
    {

        if(empty($movie->season))
            return null;

        if($page != 'download')
            $page = 'view';

        $filterMethod = "for{$page}";

        $this->select('MIN(episode) as next_episode');
        $this->where('movies.episode > ', $movie->episode);
        $this->where('movies.season_id', $movie->season_id);
        $this->orderBy('movies.episode', 'asc');
        $this->groupBy('movies.id');
        $result = $this->{$filterMethod}('episode')->first();

        if($result === null){
            //jump to next season

            $this->orderBy('seasons.season', 'asc');
            $this->where('seasons.season > ', $movie->season);

            $result = $this->$filterMethod()->getEpisode( $movie->series_id );

        }else{

            $nextSeason = $movie->season;
            $nextEpisode = $result->next_episode;

            $result = $this->$filterMethod()
                           ->getEpisode($movie->series_id, $nextSeason, $nextEpisode);

        }

        return $result;

    }

    /**
     * Get previous episode
     * @param Movie $movie
     * @param string $page view, download
     * @return null
     */
    public function getPrevEpisode(\App\Entities\Movie $movie, string $page = 'download')
    {

        if(empty($movie->season))
            return null;

        if($movie->season == 1 && $movie->episode == 1)
            return null;

        if($page != 'download')
            $page = 'view';

        $filterMethod = "for{$page}";

        $this->select('MAX(episode) as prev_episode')
             ->where('movies.episode < ', $movie->episode)
             ->where('movies.season_id', $movie->season_id)
             ->groupBy('movies.id')
             ->orderBy('movies.episode', 'desc');

        $result = $this->{$filterMethod}('episode')->first();

        if($result === null){
            //jump to next season
            $this->orderBy('seasons.season', 'desc');
            $this->where('seasons.season < ', $movie->season);

            $result = $this->{$filterMethod}()
                           ->getEpisode( $movie->series_id,null, null, 'desc');

        }else{

            $prevSeason = $movie->season;
            $prevEpisode = $result->prev_episode;

            $result = $this->$filterMethod()
                           ->getEpisode($movie->series_id, $prevSeason, $prevEpisode);

        }

        return $result;
    }

    /**
     * Get movie by ID
     * @param int $id
     * @return array|object|null
     */
    public function getMovie(int $id)
    {
        return $this->where('movies.id', $id)
                    ->first();
    }

    /**
     * Get movie by IMDB id
     * @param $imdbId
     * @return array|object|null
     */
    public function getMovieByImdbId( $imdbId )
    {
        return $this->getMovieByUniqId( $imdbId, 'imdb_id');
    }

    /**
     * Get movie by TMDB id
     * @param $tmdbId
     * @return array|object|null
     */
    public function getMovieByTmdbId( $tmdbId )
    {
        return $this->getMovieByUniqId( $tmdbId, 'tmdb_id');
    }

    /**
     * Update views in movie
     * @param $movieId
     */
    public function updateViews( $movieId )
    {
        try{
            $this->set('views', 'views + 1', FALSE)
                 ->protect(false)
                 ->update($movieId);
        }catch (\ReflectionException $e){}
    }

    /**
     * Get specific episode
     * @param int $seriesId
     * @param null $season
     * @param null $episode
     * @param string $episodeOrder asc, desc
     * @return array|object|null
     */
    public function getEpisode(int $seriesId, $season = null, $episode = null, string $episodeOrder = 'asc')
    {
        $this->join('seasons', 'seasons.id = movies.season_id', 'LEFT');
        $this->join('series', 'series.id = seasons.series_id', 'LEFT');
        $this->where('series.id', $seriesId);

        if(! empty($season))
            $this->where('seasons.season', $season);

        if(! empty($episode))
            $this->where('movies.episode', $episode);

        $this->select("movies.*, seasons.season, seasons.series_id, 
        series.title as series_title, series.imdb_id as series_imdb_id, series.tmdb_id as series_tmdb_id, series.language as series_language, series.country as series_country");

        $this->orderBy('movies.episode', $episodeOrder);
        $this->orderBy('seasons.season', 'asc');
        $this->groupBy('movies.id');
        return $this->first();
    }

    /**
     * Get movie by tmdb or imdb id
     * @param $uniqId
     * @param null $source
     * @return array|object|null
     */
    public function getMovieByUniqId( $uniqId , $source = null, $selection = true)
    {

        if($source !== null && ! in_array($source, ['imdb_id', 'tmdb_id']))
            return null;

        $this->join('seasons', 'seasons.id = movies.season_id', 'LEFT')
             ->join('series', 'series.id = seasons.series_id', 'LEFT');

        if($source !== null){
            $this->where("movies.$source", $uniqId);
        }else{
            $this->where('movies.imdb_id', $uniqId)
                 ->orWhere('movies.tmdb_id', $uniqId);
        }

        $this->groupBy('movies.id');
        if($selection)
            $this->select("movies.*, seasons.season, seasons.series_id, 
            series.title as series_title, series.imdb_id as series_imdb_id, series.tmdb_id as series_tmdb_id, series.language as series_language, series.country as series_country");


        return $this->first();
    }




    /**
     * Select all movies with filters
     * @param array|null $filters
     * @param bool $selection
     * @param null $groupBy
     * @return $this
     */
    public function allMovies(?array $filters = [], bool $selection = true, $groupBy = null): MovieModel
    {

        $dataType = $filters['type'] ?? '';
        $isMovie = empty($dataType) || $dataType == 'movie';

        //filter by qualities
        if(! empty($filters['qualities'])){
            $this->whereIn('movies.quality', $filters['qualities']);
        }

        //filter by year
        if(! empty($filters['year'])){
            if(! $isMovie){
                $this->groupStart()
                    ->where('movies.year', $filters['year'])
                    ->orWhere('series.year', $filters['year'])
                    ->groupEnd();
            }else{
                $this->where('movies.year', $filters['year']);
            }
        }

        //filter by country
        if(! empty($filters['country'])){
            $key = $isMovie ? 'movies.country' : 'series.country';
            $this->like($key, $filters['country']);
        }

        //filter by language
        if(! empty($filters['language'])){
            $key = $isMovie ? 'movies.language' : 'series.language';
            $this->where($key, $filters['language']);
        }

        //filter by imdb rate
        if(! empty($filters['minImdbRate'])){
            $this->where('movies.imdb_rate > ', $filters['minImdbRate']);
        }

        if(empty($dataType) || $dataType == 'movie'){
            if( $selection ){
                $this->select('movies.*');
            }
        }

        //filter by genres
        if(! empty($filters['genreIds'])){
            if($isMovie){
                $this->join('movie_genre as mg', 'mg.movie_id = movies.id', 'LEFT');
                $this->whereIn('mg.genre_id', $filters['genreIds']);
            }else{
                $this->join('series_genre as sg', 'sg.series_id = series.id', 'LEFT');
                $this->whereIn('sg.genre_id', $filters['genreIds']);
            }
        }

        if(! empty($dataType)){
            $this->where('movies.type', $dataType);
        }

        $groupBy = $groupBy !== null ? $groupBy : 'movies.id';
        $this->groupBy($groupBy);

        $this->where('movies.status', 'public');

        return $this;

    }

    /**
     * Select movies (or episodes) for download
     * @param string|null $type movie, episode
     * @return $this|MovieModel
     */
    public function forDownload(?string $type = null): MovieModel
    {
        $this->where('l.type != ', 'stream');
        return $this->publicDataFilter($type);
    }

    /**
     * Select movies (or episodes) for view
     * @param null $type
     * @return $this|MovieModel
     */
    public function forView($type = null): MovieModel
    {
        if(! empty($type)){
            $this->where('movies.type', $type);
        }
        return $this->publicDataFilter( $type );
    }

    /**
     * Select movies (or episode) having links
     * @param string $type stream, download
     * @return $this
     */
    public function notEmptyLinks(string $type = 'stream'): MovieModel
    {
        if($type == 'stream'){
            $this->whereIn('id', function ($model){
                return $model->select('movie_id')
                    ->from('links')
                    ->where('type', 'stream');
            });
        }else{
            $this->whereIn('id', function ($model){
                return $model->select('movie_id')
                    ->from('links')
                    ->where('type !=', 'stream');
            });
        }

        return $this;

    }

    /** Select videos with at least one stream link confirmed healthy. */
    public function withHealthyStreamLinks(): MovieModel
    {
        if (! (new LinkModel())->supportsStreamHealthFields()) {
            return $this->where('1 = 0', null, false);
        }

        return $this->whereIn('id', static function ($model) {
            return $model->select('movie_id')
                ->from('links')
                ->where('type', 'stream')
                ->where('is_broken', 0)
                ->where('last_success_at IS NOT NULL', null, false)
                ->where('last_error IS NULL', null, false);
        });
    }

    /** Select videos with a stream link whose latest health check failed. */
    public function withUnhealthyStreamLinks(): MovieModel
    {
        if (! (new LinkModel())->supportsStreamHealthFields()) {
            return $this->where('1 = 0', null, false);
        }

        return $this->whereIn('id', static function ($model) {
            return $model->select('movie_id')
                ->from('links')
                ->where('type', 'stream')
                ->groupStart()
                    ->where('is_broken', 1)
                    ->orGroupStart()
                        ->where('last_error IS NOT NULL', null, false)
                        ->where('last_error !=', '')
                    ->groupEnd()
                ->groupEnd();
        });
    }



    /**
     * Select movies (or episode) not having links
     * @param string $type stream, download
     * @return $this
     */
    public function emptyLinks(string $type = 'stream'): MovieModel
    {
        if($type == 'stream'){
            $this->whereNotIn('id', function ($model){
                return $model->select('movie_id')
                    ->from('links')
                    ->where('type', 'stream');
            });
        }else{
            $this->whereNotIn('id', function ($model){
                return $model->select('movie_id')
                    ->from('links')
                    ->where('type !=', 'stream');
            });
        }

        return $this;

    }



    /**
     * Remove temporary created movie
     * after that movie imported to active movies list
     * @param array $data
     */
    protected function removeTmpMovie(array $data)
    {

        if(! empty($data['id'])){

            $failedMovies = new FailedMovies();

            $innerData = $data['data'];
            $existMovie = $failedMovies->findByReqId( $innerData['imdb_id'] );

            if($existMovie !== null){
                $failedMovies->delete( $existMovie['id'] );
            }

        }

        return $data;
    }

    protected function importRequest(array $data): array
    {
        if(! empty($data['id'])){

            $requestModel = new RequestsModel();
            $requestModel->itemImported( $data['data'] );

        }

        return $data;
    }

    /**
     * Set default stream quality format
     * @param array $data
     * @return array
     */
    protected function setDefaultQuality(array $data): array
    {
        if(! isset($data['data']['quality'])){
            $streamQualities = get_stream_quality_formats();
            if(! empty($streamQualities)){
                $data['data']['quality'] = $streamQualities[0];
            }
        }

        return $data;
    }

    /**
     * save movie data temporary before delete it
     * @param array $data
     * @return array
     */
    protected function setTmpData(array $data): array
    {
        $this->tmpDeletedData = $this->where('id', $data['id'])
            ->first();
        return $data;
    }

    /**
     * Empty values convert to NULL
     * @param array $data
     * @return array
     */
    protected function emptyToNull(array $data): array
    {
        foreach ($data['data'] as $key => $val) {
            if(empty($val) && ! is_numeric($val)){
                $data['data'][$key] = null;
            }
        }
        return $data;
    }


    /**
     * Delete media files after movie deleted
     * @param array $data
     * @return array
     */
    protected function deleteMediaFiles(array $data): array
    {

        if(empty( $this->tmpDeletedData)){
            $movie = $this->where('id', $data['id'])->first();
        }else{
            $movie = $this->tmpDeletedData;
        }

        if($movie !== null){

            if($movie->series()->poster !== $movie->poster)
                $movie->posterRemoved();

            if($movie->series()->poster !== $movie->banner)
                $movie->bannerRemoved();

        }
        return $data;
    }

    /**
     * Clean empty seasons
     * @param array $data
     * @return array
     */
    protected function cleanSeasonData(array $data): array
    {
        if(!empty($this->tmpDeletedData) && $this->tmpDeletedData->isEpisode()){

            $results = $this->where('season_id', $this->tmpDeletedData->season_id )
                            ->first();

            if(empty($results)) {

                $seasonModel = new SeasonModel();
                $seasonModel->delete( $this->tmpDeletedData->season_id );
                unset($seasonModel);

            }

        }
        return $data;
    }

    protected function unimportRequest(array $data): array
    {
        if(! empty($this->tmpDeletedData) && ! $this->tmpDeletedData->isEpisode()){
            $tmdbId = $this->tmpDeletedData->tmdb_id;
            if(! empty( $tmdbId )){

                $requestModel = new RequestsModel();
                $request = $requestModel->getRequestByTmdbId( $tmdbId );
                if($request !== null){

                    $requestModel->unimport( $request->id );

                }
            }
        }

        return $data;
    }




    /**
     * Data filter for public view
     * @param string|null $type movie, episode
     * @return $this
     */
    protected function publicDataFilter(?string $type = null): MovieModel
    {
        $this->join('links as l', 'l.movie_id = movies.id', 'LEFT');

        $this->where('l.is_broken', 0);
        $this->where('movies.status', 'public');

        if(! empty($type))
            $this->where('movies.type', $type);

        return $this;
    }



    /**
     * Select movies
     * @return $this
     */
    public function movies(): MovieModel
    {
        $this->where('movies.type', 'movie');
        return $this;
    }

    /**
     * Select episodes
     * @return $this
     */
    public function episodes($autoSelect = true, $groupBy = true): MovieModel
    {
        $this->where('movies.type', 'episode');
        $this->join('seasons', 'seasons.id = movies.season_id', 'LEFT')
             ->join('series', 'series.id = seasons.series_id', 'LEFT');

        if( $groupBy ){
            $this->groupBy('movies.id');
        }
        if($autoSelect){
            $this->select("movies.*, seasons.season, seasons.series_id, series.title as series_title, series.imdb_id as series_imdb_id, series.tmdb_id as series_tmdb_id");
        }

        return $this;
    }

    public function trending($t = 'movies'): MovieModel
    {

        if($t == 'movies'){
            $this->orderBy('movies.views', 'desc');
        }else{
            $this->selectSum('movies.views', 'mviews')
                 ->orderBy('mviews', 'desc');
        }

        $this->orderBy('movies.updated_at', 'desc')
             ->where('movies.created_at >= ', 'DATE_SUB(DATE(NOW()), INTERVAL 45 DAY)', false);

        return $this;
    }


    public function getCountries(): array
    {
       $results =  $this->movies()
                        ->orderBy('country', 'asc')
                        ->distinct()
                        ->select('country')
                        ->asArray()
                        ->find();

       if(! empty($results)){
            $countries = fix_arr_data( extract_array_data($results, 'country') );
       }

       return ! empty($countries) ? $countries : [];
    }

    public function getLanguages(): array
    {
        $results =  $this->movies()
                         ->orderBy('language', 'asc')
                         ->distinct()
                         ->select('language')
                         ->asArray()
                         ->find();

        if(! empty($results)){
            $languages = fix_arr_data( extract_array_data($results, 'language') );
        }
        return ! empty($languages) ? $languages : [];
    }





}
