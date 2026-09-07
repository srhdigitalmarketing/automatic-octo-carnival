<?php

if(! function_exists( 'get_lang_list' ))
{
    function get_lang_list($withEngName = false): array
    {
        $langList = [
            'cs-CZ' => 'Český',
            'de-DE' => 'Deutsch',
            'en-US' => 'English',
            'es-ES' => 'Español',
            'es-MX' => 'Español',
            'el-GR' => 'ελληνικά',
            'fr-FR' => 'Français',
            'fr-CA' => 'Français',
            'fi-FI' => 'suomi',
            'hr-HR' => 'Hrvatski',
            'hu-HU' => 'Magyar',
            'id-ID' => 'Bahasa indonesia',
            'it-IT' => 'Italiano',
            'ja-JP' => '日本語',
            'ko-KR' => '한국어/조선말',
            'nl-NL' => 'Nederlands',
            'nl-BE' => 'Nederlands',
            'la-LA' => 'Latin',
            'pt-PT' => 'Português',
            'pt-BR' => 'Português',
            'pl-PL' => 'Polski',
            'ru-RU' => 'Pусский',
            'ro-RO' => 'ภาษาไทย',
            'sk-SK' => 'Slovenčina',
            'sv-SE' => 'svenska',
            'ta-IN' => 'தமிழ்',
            'th-TH' => 'ภาษาไทย',
            'tr-TR' => 'Türkçe',
            'uk-UA' => 'Український',
            'vi-VN' => 'Tiếng Việt',
            'zh-CN' => '普通话',
            'zh-TW' => '普通话',
            'zh-HK' => '普通话',
            'zh-SG' => '普通话',
        ];


        foreach ($langList as $key => $val) {
            $name = $val . ' ( ' . $key . ' )';
            $langList[$key] = $name;
        }


        return $langList;
    }
}

if(! function_exists( 'current_language' ))
{
    function current_language()
    {
        return service('language')->getLocale();
    }
}

if(! function_exists( 'is_selected_language' ))
{
    function is_selected_language( $lang ): bool
    {
        return in_array($lang, get_selected_languages(true));
    }
}

if(! function_exists( 'get_selected_languages' ))
{
    function get_selected_languages($withEng = false ): array
    {
        $list = get_config( 'selected_languages' );
        if(! $withEng){
            $k = array_search('en-US', $list);
            if($k !== false){
                unset( $list[$k] );
            }
        }
        return $list;
    }
}


if(! function_exists( 'get_main_language' ))
{
    function get_main_language(  ): string
    {
        $lang = get_config( 'main_language' );
        return ! empty( $lang ) ? $lang : 'en-US';
    }
}

if(! function_exists( 'is_multi_languages_enabled' ))
{
    function is_multi_languages_enabled(): bool
    {
        return get_config( 'is_multi_lang' );
    }
}

if(! function_exists( 'lang_name' ))
{
    function lang_name( $langCode = '' ): string
    {
        if(empty( $langCode )){
            return '';
        }

        $langList = get_lang_list();
        $originalName = '';

        if(! empty( $langList )){

            if(array_key_exists( $langCode,  $langList)){
                $originalName = $langList[$langCode];

            }

        }

        return $originalName;
    }
}

if(! function_exists( 'lang_flag' ))
{
    function lang_flag( $langCode = '' ): string
    {
        if(! empty($langCode)){
            list($langCode) = explode('-', $langCode);
        }

        $img =  theme_path('/images/icons/lang_flags/' . $langCode . '.webp');
        if(is_file( FCPATH . "/" . $img )){
            return site_url( $img );
        }

        return site_url(theme_path('/images/icons/lang_flags/default.webp'));
    }
}


if(! function_exists( 'lang_url' ))
{
    function lang_url( $langCode = '' ): string
    {
        return site_url("/lang?l={$langCode}") . '&redirect=' . current_url();
    }
}


if(! function_exists( 'has_translate_permit' ))
{
    function has_translate_permit(  ): bool
    {
        if(service('uri')->getSegment(1) != 'admin') {

            return true;

        }

        return false;
    }
}