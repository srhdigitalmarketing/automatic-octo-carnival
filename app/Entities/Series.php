<?php

namespace App\Entities;


use App\Models\SeriesGenreModel;
use CodeIgniter\Model;

class Series extends \CodeIgniter\Entity\Entity
{

    protected $genres = null;


    public function getEmbedLink($short = false)
    {
        $link = $short ? "/{$this->imdb_id}" : "/series?imdb={$this->imdb_id}";
        return site_url( "/" . embed_slug() . "{$link}" );

    }

    public function getDownloadLink($short = false)
    {
        $link = $this->getEmbedLink( $short );
        return str_replace('/'.embed_slug().'/', '/'.download_slug().'/', $link);
    }

    public function getViewLink($short = false)
    {
        $viewLink = $this->getEmbedLink($short);
        return str_replace('/'.embed_slug().'/' ,'/'.view_slug().'/', $viewLink);
    }

    public function genres()
    {
        if($this->genres === null){

            $seriesGenreModel = new SeriesGenreModel();
            $this->genres = [];

            $genres = $seriesGenreModel->getGenresBySeriesId( $this->id );

            if($genres !== null){
                $this->genres = $genres;
            }

            unset($seriesGenreModel);

        }

        return  $this->genres;
    }

    public function addPoster($posterFile)
    {
        $this->posterRemoved();
        $posterName = $posterFile->getRandomName();
        $posterFile->move( poster_dir(), $posterName );
        $this->poster = $posterName;
    }

    public function addBanner($bannerFile)
    {
        $this->bannerRemoved();
        $bannerName = $bannerFile->getRandomName();
        $bannerFile->move( banner_dir(), $bannerName );
        $this->banner = $bannerName;
    }


    public function posterRemoved()
    {
        if(! empty($this->poster)){
            delete_poster( $this->poster );
        }
        $this->poster = null;
    }

    public function bannerRemoved()
    {
        if(! empty($this->banner)){
            delete_banner( $this->banner );
        }
        $this->banner = null;
    }

    public function hasPoster()
    {
        return !empty($this->poster);
    }

    public function hasBanner()
    {
        return !empty($this->banner);
    }
}