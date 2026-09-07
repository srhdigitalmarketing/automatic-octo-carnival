<?php

namespace App\Models\Translations;

use App\Models\BaseTranslationModel;


/**
 * Class PageTranslationsModel
 * @package App\Models\Translations
 * @author John Antonio
 */
class PageTranslationsModel extends BaseTranslationModel
{
    protected $table = 'page_translations';
    protected $allowedFields = [ 'page_id', 'lang', 'title', 'content'];
    protected $validationRules = [
        'page_id' => 'required|is_natural_no_zero|exist[pages.id]',
        'lang' => 'required|alpha|max_length[5]|valid_lang_code|both_unique[page_translations.page_id,lang]'
    ];


    public function findByPageId(int $pageId, $lang = '')
    {
        if(empty( $lang )){

            $results = $this->where('page_id', $pageId)
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

            $results = $this->where('page_id', $pageId)
                            ->where('lang', $lang)
                            ->first();

        }

        return $results;
    }





}