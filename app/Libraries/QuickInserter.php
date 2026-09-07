<?php

namespace App\Libraries;


use App\Entities\Movie;
use App\Entities\Series;
use App\Models\GenreModel;
use App\Models\MovieModel;
use App\Models\SeriesModel;


class QuickInserter
{

    protected $type = null;
    protected $data = [];
    protected $errors = null;

    public function save()
    {
        $response = null;
        if(! empty($this->data)){

            switch ($this->type){
                case 'movie':
                case 'episode':

                    $response = $this->saveMovie();

                    break;
                case 'series':

                    $response = $this->saveSeries();

                    break;
            }

        }

        //reset inserter
        $this->reset();

        return $response;
    }

    protected function saveMovie()
    {
        $movieModel = new MovieModel();
        $movie = new Movie( $this->data );

        if(! is_media_download_to_server()){
            $movie->poster = $movie->poster_url;
            $movie->banner = $movie->banner_url;
        }

        if($movieModel->saveMovie( $movie )){

            $movie = $movieModel->getMovie( $movieModel->getInsertID() );

            //add translations
            if( is_multi_languages_enabled() ){

                $tmpTranslations = $this->data['translations'] ?? '';
                $translations = [];

                if(! empty($tmpTranslations)){

                    foreach ($tmpTranslations as $val) {

                        $translations[$val['lang']] = [
                            'title' => $val['title'],
                            'description' => $val['description']
                        ];

                    }

                    $movieModel->addTranslations(
                        $movie->id,
                        $translations
                    );
                }

            }

            //save genres
            if(! $movie->isEpisode()){
                $genreIds = $this->getGenresIds( $this->data['genres'] );
                $movieModel->addGenres($movie->id, $genreIds);
            }

            if( is_media_download_to_server() ){
                //attempt to save media files
                $this->addMediaFiles( $movie );
            }

            if($movie->hasChanged()) {
                try{
                    $movieModel->save( $movie );
                }catch (\ReflectionException $e){ }
            }

            return $movie;
        }else{

            $this->errors = $movieModel->errors();

        }

        return null;
    }

    protected function saveSeries()
    {
        $seriesModel = new SeriesModel();
        $series = new Series( $this->data );

        if(! is_media_download_to_server()){
            $series->poster = $series->poster_url;
            $series->banner = $series->banner_url;
        }

        try{
            if($seriesModel->insert( $series )){

                $series = $seriesModel->getSeries( $seriesModel->getInsertID() );

                if( is_media_download_to_server() ){

                    $this->addMediaFiles( $series );

                    if($series->hasChanged()){
                        try{
                            $seriesModel->save( $series );
                        }catch (\ReflectionException $e){ }
                    }
                }


                return $series;

            }else{

                $this->errors = $seriesModel->errors();

            }
        }catch (\ReflectionException $e){}

        return null;
    }

    public function set( $data )
    {
        $this->data = $data;
        return $this;
    }

    public function movie()
    {
        $this->type = 'movie';
        return $this;
    }

    public function episode()
    {
        $this->type = 'episode';
        return $this;
    }

    public function series()
    {
        $this->type = 'series';
        return $this;
    }

    /**
     * Add media files after saved movie or tv show
     * @param $movie
     */
    protected function addMediaFiles( $movie )
    {

        $posterUrl = $this->data['poster_url'] ?? '';
        $bannerUrl = $this->data['banner_url'] ?? '';

        $posterFile = $bannerFile = null;

        //attempt to download poster image
        if(! empty($posterUrl)) {
            helper('remote_download');
            if($filepath = download_image( $posterUrl )){
                $posterFile = new \CodeIgniter\Files\File( $filepath );
            }
        }

        //attempt to download banner image
        if(! empty($bannerUrl)) {
            helper('remote_download');
            if($filepath = download_image( $bannerUrl )){
                $bannerFile = new \CodeIgniter\Files\File( $filepath );
            }
        }

        if($posterFile !== null){
            $movie->addPoster( $posterFile );
        }

        if($bannerFile !== null){
            $movie->addBanner( $bannerFile );
        }

    }

    /**
     * get exist genre ids by names
     * @param array $genres genre name list
     * @return array
     */
    protected function getGenresIds(array $genres): array
    {
        $genreModel = new GenreModel();
        $genreIds = [];
        foreach ($genres as $genre) {
            $results = $genreModel->getGenreByName($genre);
            if($results !== null){
                $genreIds[] = $results->id;
            }
        }
        return $genreIds;
    }


    protected function reset()
    {
        $this->type = null;
        $this->error = null;
        $this->data = [];
    }

    public function getError(): string
    {
        $error = 'Unable to save';
        if(! empty( $this->errors )){
            $error = is_array( $this->errors ) ? array_shift($this->errors) : $this->errors;
        }
        return $error;
    }

}