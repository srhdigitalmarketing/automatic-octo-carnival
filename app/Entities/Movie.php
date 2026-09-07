<?php

namespace App\Entities;


use App\Models\GenreModel;
use App\Models\MovieGenreModel;
use App\Models\SeasonModel;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;

class Movie extends \CodeIgniter\Entity\Entity
{


    protected $tmpLinks = [];
    protected $tmpCategories;

    protected $seasonObj = null;
    protected $genres = null;

    public function getMovieTitle() {

        $title = $this->title;

        if( $this->isEpisode() ){

            if(! empty($this->season) && ! empty($this->series_title)) {

                $sea = sprintf("%02d", $this->season);
                $epi = sprintf("%02d", $this->episode);

                $title =  "{$this->series_title} [S{$sea}E{$epi}] - $this->title";

            }

        }else{
            if(! empty( $this->year )){
                $title .= ' - ' . $this->year;
            }
        }



        return $title;

    }

    public function getMovieTrailer()
    {
        if(! empty( $this->trailer )){
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->trailer, $match);
            if(isset( $match[1] )){

                return "https://www.youtube.com/embed/{$match[1]}";

            }
        }

        return  $this->trailer;
    }

    public function getMovieCountries(): array
    {
        $countries = ! $this->isEpisode() ? $this->country : $this->series_country;
        if(! empty($countries)){
            $countries = fix_arr_data( [ $countries ] ) ;
            if(! empty($countries) && is_array( $countries )){
                return $countries;
            }
        }

        return [];
    }

    public function getMovieLanguages(): array
    {
        $languages = ! $this->isEpisode() ? $this->language : $this->series_language;
        if(! empty($languages)){
            $languages = fix_arr_data( [ $languages ] ) ;
            if(! empty($languages) && is_array( $languages )){
                return $languages;
            }
        }
        return [];
    }

    public function getMovieImdbRate()
    {
        if(! empty( $this->imdb_rate ) && $this->imdb_rate != '0.0') {
            return $this->imdb_rate;
        }
        return  'N/A';
    }

    public function getEmbedLink($short = false)
    {
        $link = null;

        if(! $this->isEpisode()){

            $link = $short ? "/{$this->imdb_id}" : "/movie?imdb={$this->imdb_id}";

        }else{
            if($short) {
                $link = "/{$this->imdb_id}";
            }else{
                $season = $this->season ?? $this->season()->season;
                $episode = $this->episode ?? 1;

                $link = "/series?imdb={$this->imdb_id}&sea={$season}&epi={$episode}";
            }

        }

        return site_url( "/" . embed_slug() .  "{$link}" );

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



    public function season()
    {
        if($this->seasonObj === null){

            $this->seasonObj = new Season();

            if($this->isEpisode()) {
                $seasonModel = new SeasonModel();
                $season = $seasonModel->where('id', $this->season_id)
                                      ->first();
                if(! empty($season)){
                    $this->seasonObj = $season;
                }

                unset($seasonModel);

            }
        }

        return  $this->seasonObj;

    }

    public function series()
    {
        return $this->season()->series();
    }

    public function genres()
    {
        if($this->genres === null){

            $movieGenreModel = new MovieGenreModel();
            $this->genres = [];

            $genres = $movieGenreModel->getGenresByMovieId( $this->id );

            if($genres !== null){
                $this->genres = $genres;
            }

            unset($movieGenreModel);

        }

        return  $this->genres;
    }

    public function links($type = null)
    {
        if(empty( $this->tmpLinks[$type] ) ) {
            $linksModel = new \App\Models\LinkModel();
            $results = $linksModel->findByMovieId( $this->id, $type );
            if( $results !== null ) {
                $this->tmpLinks[$type] = $results;
            }
        }
        return $this->tmpLinks[$type] ?? [];
    }

    public function hasLinks($type = null)
    {
        return !empty( $this->links( $type ) );
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

    public function isEpisode()
    {
        return $this->type  == 'episode';
    }



}