<?php

namespace App\Validation;


use Config\Database;

class CustomRules
{
    public function exist($str, $value, $data, &$error = '')
    {


        list($table, $column) = explode('.', $value, 2);
        $results = Database::connect()->table($table)
                                      ->where($column, $str)
                                      ->select('id')
                                      ->get(1);

        if(! $results->getNumRows() > 0) {
            $error = lang("{$table} does not exist");
            return false;
        }

        return true;
    }

    public function both_unique($str, $value, $data, &$error = '')
    {
        list($table, $columns) = explode('.', $value, 2);
        $columns = explode(',', $columns);

        $builder = Database::connect()->table($table);

        foreach ($columns as  $val) {
            if(array_key_exists($val, $data)){
                $builder->where($val, $data[$val]);
            }
        }

        if(isset($data['id'])) {
            $builder->whereNotIn('id',  [$data['id']]);
        }

        $results = $builder->get(1)->getNumRows();

        if($results > 0){
            return false;
        }

        return $results === 0;
    }

    public function valid_imdb_id($str = null, &$error = null)
    {
        if (empty($str)) {
            return false;
        }

        if(! preg_match('/^tt[0-9]+$/', $str)) {
            $error = lang("Invalid Imdb Id");
            return false;
        }

        return true;
    }

    public function valid_tmdb_id($str = null, &$error = null)
    {
        if (empty($str)) {
            return false;
        }

        if(! preg_match('/^[0-9]+$/', $str)) {
            $error = lang("Invalid Tmdb Id");
            return false;
        }

        return true;
    }

    public function valid_movie_id($str = null, &$error = null)
    {
        if (empty($str)) {
            return false;
        }

        if(! preg_match('/^[tt]{0,2}[0-9]+$/', $str)) {
            $error = lang("Invalid Imdb or Tmdb Id");
            return false;
        }

        return true;
    }

    public function valid_lang_code($str = null, &$error = null)
    {
        if (empty($str)) {
            return false;
        }

        $langList = get_lang_list( true );

        if(empty($langList) || ! array_key_exists($str, $langList)) {
            $error = lang("Invalid language code");
            return false;
        }

        return true;
    }

    public function valid_math_captcha($str = null, &$error = null)
    {
        if(empty($str))
            return null;

        $captcha = service('math_captcha');
        $success = $captcha->isValid( $str );

        if(! $success) {
            $error = lang('Captcha.invalid');
        }

        $captcha->destroy();

        return  $success;


    }

}