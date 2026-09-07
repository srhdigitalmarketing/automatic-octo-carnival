<?php

namespace App\Libraries\DataAPI\Items;

abstract class Item
{
    protected $source;
    protected $type = null;
    protected $data = null;
    protected $translations = null;

    abstract protected function cleanOmdbData( );
    abstract protected function cleanTmdbData( );

    public function __construct($source = 'imdb', array $data = [])
    {
        $this->data = $data;
        $this->source = $source;
    }

    public function fill(?array $data = null)
    {

        if($data === null)
            return false;

        foreach ($data as $k => $val) {
            $this->__set($k, $val);
        }

        return true;

    }

    public function cleanData(){
        if( $this->source == 'tmdb' ) {
            $this->cleanTmdbData();
            $this->loadTranslations();
        }else{
            $this->cleanOmdbData();
        }


    }

    public function getData()
    {
        $data = get_object_vars( $this );
        unset( $data['data'] );
        return $data;
    }

    protected function loadTranslations()
    {

        if( is_multi_languages_enabled() ){

            $languages = [];
            if(! empty( $this->data['translations']['translations'] )){

                $translations = $this->data['translations']['translations'];
                $selectedLanguages = get_selected_languages();

                foreach ($translations as $translation) {

                    $langCode = $translation['iso_639_1'] . '-' . $translation['iso_3166_1'];

                    if(in_array($langCode, $selectedLanguages)){
                        $data = [
                            'lang' => $langCode,
                            'title' => $translation['data']['title'] ?? '',
                            'description' => $translation['data']['overview'] ?? ''
                        ];

                        $languages[$langCode] = $data;
                    }

                }

            }

            $this->translations = array_values( $languages );

        }

    }

    public function __set($obj, $attr)
    {
        $this->$obj = $attr;

    }

    public function __get($attr)
    {
        if(property_exists($this, $attr)) {
            return $this->$attr;
        }
        return '';
    }

    public function getTmdbUrl()
    {
        $url = '#';
        if(class_basename($this) == 'Movie'){
            $url = 'https://www.themoviedb.org/movie/' . $this->tmdb_id;
        }else if(class_basename($this) == 'Series'){
            $url = 'https://www.themoviedb.org/tv/' . $this->tmdb_id;
        }
        return $url;
    }


}