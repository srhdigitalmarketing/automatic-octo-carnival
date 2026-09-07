<?php

namespace App\Models;

use App\Entities\Translation;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;

/**
 * Class BaseTranslationModel
 * @package App\Models
 * @author John Antonio
 */
class BaseTranslationModel extends Model
{
    protected $returnType = 'App\Entities\Translation';
    protected $afterFind = ['changeArrayKey'];

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

    public function getDummyList()
    {

        $selectedLanguages = get_selected_languages();

        $results = [];

        if(! empty( $selectedLanguages )){

            foreach ($selectedLanguages as $langCode) {

                $results[] = $this->getDummyEntity( $langCode );

            }

        }

        return $results;
    }

    protected function getDummyEntity( $langCode )
    {
        $tmpTranslation = new Translation();
        $tmpTranslation->lang = $langCode;

        return $tmpTranslation;
    }

    public function getCrashedLangCodes(): array
    {
        $results = $this->select('lang')
                        ->distinct()
                        ->asArray()
                        ->findAll();

        $list = [];

        if(! empty($results)){
            $results = extract_array_data($results, 'lang');
            foreach ($results as $v) {
                if(! is_selected_language( $v ) || ! is_multi_languages_enabled()){
                    $list[$v] = $v;
                }
            }
        }

        return $list;
    }
}