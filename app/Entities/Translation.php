<?php

namespace App\Entities;


class Translation extends \CodeIgniter\Entity\Entity
{


    public function getLangName()
    {
        if(! empty( $this->lang )){

            $languages = get_lang_list();
            if(! empty( $languages ) && array_key_exists( $this->lang, $languages )){

                return $languages[$this->lang];

            }

        }

        return 'Unknown Language';
    }

}