<?php

namespace App\Libraries;

use App\Models\MovieGenreModel;
use App\Models\SeriesGenreModel;
use CodeIgniter\Model;

class Recommend
{

    protected $keyVal = null;
    protected $keyName = null;
    protected $baseKeyName = 'recommend_key';
    protected $data = [];
    protected $expire = 60 * 60 * 24 * 30; //30 days


    public function add( $val )
    {
        $numOfReq = 0;
        if($this->has( $val )){
            $numOfReq = $this->data[$val];
            if(! is_numeric($numOfReq) || $numOfReq < 0){
                $numOfReq = 0;
            }
        }

        //increase requests
        $numOfReq += 1;
        $this->data[$val] = $numOfReq;

        return $this;
    }

    public function has( $val ): bool
    {
        //check val exist
        return array_key_exists($val, $this->data);
    }

    public function get(): array
    {
        return $this->data;
    }

    public function remove( $val )
    {
        //remove val if is it exist
        if($this->has( $val )){
            unset( $this->data[$val] );
        }
        return $this;
    }

    public function save()
    {
        if(! empty($this->data) && is_array( $this->data )){

            //create key
            $keyData = [];

            foreach ($this->data as $key => $val) {
                $keyData[] = "$key..$val";
            }

            $key = UniqToken::create( $keyData );

            //update key
            $this->keyVal = $key;

            //save
            set_cookie($this->keyName, $key, $this->expire);
        }
    }

    public function movies()
    {
        $this->keyName = "movies_{$this->baseKeyName}";
        return $this;
    }

    public function shows()
    {
        $this->keyName = "shows_{$this->baseKeyName}";
        return $this;
    }

    public function init()
    {
        helper('cookie');
        $key = get_cookie( $this->keyName );

        if(! empty( $key )){
            $results = UniqToken::decode( $key );
            if(! empty( $results ) && is_array( $results )){

                foreach ($results as $val) {
                    $valData = explode('..', $val);
                    if(count($valData) == 2){
                        list($genreId, $req) = $valData;
                        if(is_numeric($genreId) && $genreId > 0){
                            if(is_numeric($req) && $req >= 0){
                                $this->data[$genreId] = $req;
                            }
                        }
                    }
                }

                $this->keyVal = $key;
            }
        }

        return $this;
    }

    public function detect(\App\Entities\Movie $movie)
    {
        if(! $movie->isEpisode()){

            $movieGenreModel = new MovieGenreModel();
            $genres = $movieGenreModel->getGenresByMovieId( $movie->id );

            $this->movies()->init();
        }else{

            $seriesGenreModel = new SeriesGenreModel();
            $genres = $seriesGenreModel->getGenresBySeriesId( $movie->series_id );

            $this->shows()->init();
        }

        if(! empty($genres)){

            foreach ($genres as $genre){
                $this->add( $genre->id );
            }

            $this->save();
        }
    }

}