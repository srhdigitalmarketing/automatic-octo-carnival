<?php

namespace App\Models\Translations;

use App\Models\BaseTranslationModel;


/**
 * Class TranslationsModel
 * @package App\Models\Translations
 * @author John Antonio
 */
class MovieTranslationsModel extends BaseTranslationModel
{
    protected $table = 'movie_translations';
    protected $allowedFields = [ 'movie_id', 'lang', 'title', 'description'];
    protected $validationRules = [
        'movie_id' => 'required|is_natural_no_zero|exist[movies.id]',
        'lang' => 'required|alpha_dash|max_length[5]|valid_lang_code|both_unique[movie_translations.movie_id,lang]'
    ];

    
    protected function changeArrayKey(array $data): array
    {

        if($data['method'] != 'first'){

            if(is_array($data['data'])){

                foreach ($data['data'] as $key => $val) {

                    if($val instanceof \App\Entities\Translation){

                        $newKey = $val->lang;
                        unset($data['data'][$key]);

                        $data['data'][$newKey] = $val;

                    }

                }

            }

        }

        return $data;

    }

    public function findByMovieId(int $movieId, $lang = '')
    {
        if(empty( $lang )){

            $results = $this->where('movie_id', $movieId)
                            ->findAll();

            $selectedLanguages = get_selected_languages();
            $resultsLanguages = array_keys( $results );

            $diff = array_diff($selectedLanguages, $resultsLanguages);
            if(! empty( $diff )){
                foreach ($diff as $tmp){
                    $results[$tmp] = $this->getDummyEntity( $tmp );
                }
            }


        }else{

            $results = $this->where('movie_id', $movieId)
                            ->where('lang', $lang)
                            ->first();

        }

        return $results;
    }














}