<?php

if(! function_exists( 'get_config' ))
{
    function get_config( $var = null )
    {

      $config = config('Settings');

      if(empty( $var ))
          return  $config;

      if(property_exists($config, $var)){

          return $config->$var;

      }

      return null;

    }
}


if(! function_exists( 'is_config_init' ))
{
    function is_config_init( $var )
    {
        if(empty($var))
            return null;

        $val  = get_config( $var );

        return ! empty($val);

    }
}



if(! function_exists( 'is_download_enabled' ))
{
    function is_download_enabled()
    {
        return get_config( 'download_system' );
    }
}


if(! function_exists('link_slug'))
{
    function link_slug()
    {
        if(! empty( get_config( 'link_slug' ) )){
            return get_config( 'link_slug' );
        }
        return 'link';
    }
}

if(! function_exists('embed_slug'))
{
    function embed_slug()
    {
        if(! empty( get_config( 'embed_slug' ) )){
            return get_config( 'embed_slug' );
        }
        return 'embed';
    }
}

if(! function_exists('download_slug'))
{
    function download_slug()
    {
        if(! empty( get_config( 'download_slug' ) )){
            return get_config( 'download_slug' );
        }
        return 'download';
    }
}



if(! function_exists('view_slug'))
{
    function view_slug()
    {
        if(! empty( get_config( 'view_slug' ) )){
            return get_config( 'view_slug' );
        }
        return 'view';
    }
}

if(! function_exists('library_slug'))
{
    function library_slug()
    {
        if(! empty( get_config( 'library_slug' ) )){
            return get_config( 'library_slug' );
        }
        return 'library';
    }
}


if(! function_exists('is_web_page_cache_enabled'))
{
    function is_web_page_cache_enabled()
    {
        return get_config( 'web_page_cache' );
    }
}

if(! function_exists('is_countdown_timer_enabled'))
{
    function is_countdown_timer_enabled()
    {
        return get_config( 'is_count_down_timer' );
    }
}

if(! function_exists('is_referer_blocked'))
{
    function is_referer_blocked()
    {
        return get_config( 'is_referer_blocked' );
    }
}

if(! function_exists('footer_txt_content'))
{
    function footer_txt_content()
    {
        return get_config( 'footer_content' );
    }
}

if(! function_exists('site_copyright'))
{
    function site_copyright()
    {
        return get_config( 'site_copyright' );
    }
}


if(! function_exists('is_links_report_enabled'))
{
    function is_links_report_enabled()
    {
        return get_config( 'is_links_report' );
    }
}

if(! function_exists('is_real_time_import_enabled'))
{
    function is_real_time_import_enabled()
    {
        return get_config( 'real_time_import' );
    }
}


if(! function_exists('is_dl_captcha_enabled'))
{
    function is_dl_captcha_enabled()
    {
        return get_config( 'is_download_link_captcha' );
    }
}

if(! function_exists('is_request_captcha_enabled'))
{
    function is_request_captcha_enabled()
    {
        return get_config( 'is_request_captcha_enabled' );
    }
}


if(! function_exists('web_page_cache_time'))
{
    function web_page_cache_time()
    {
        return get_config( 'web_page_cache_duration' );
    }
}

if(! function_exists('footer_custom_codes'))
{
    function footer_custom_codes()
    {
        $codes =  get_config( 'custom_footer_codes' );
        if(! empty($codes)){
            $codes = base64_decode($codes);
        }
        return $codes;
    }
}

if(! function_exists('header_custom_codes'))
{
    function header_custom_codes()
    {
        $codes =  get_config( 'custom_header_codes' );
        if(! empty($codes)){
            $codes = base64_decode($codes);
        }
        return $codes;
    }
}

if(! function_exists('site_name'))
{
    function site_name()
    {
        return get_config( 'site_name' );
    }
}


if(! function_exists('is_omdb_api_enbaled'))
{
    function is_omdb_api_enbaled()
    {
        return ! empty(get_config( 'omdb_api_key'));
    }
}

if(! function_exists('is_tmdb_api_enbaled'))
{
    function is_tmdb_api_enbaled()
    {
        return ! empty(get_config( 'tmdb_api_key'));
    }
}


if(! function_exists('sidebar_disabled'))
{
    function sidebar_disabled()
    {
        return empty(get_config( 'is_sidebar_disabled'));
    }
}

if(! function_exists('is_media_download_to_server'))
{
    function is_media_download_to_server()
    {
        return get_config( 'is_media_download_to_server');
    }
}


if(! function_exists('default_theme_name'))
{
    function default_theme_name()
    {
        $theme = get_config( 'default_theme');
        if(empty($theme) || ! in_array($theme, ['pirate']) ){
            $theme = 'default';
        }
        return $theme;
    }
}


if(! function_exists('smtp_config'))
{
    function smtp_config( $config = null )
    {
        $settings = get_config('smtp_settings');
        if(! empty( $config )){

            if(array_key_exists($config, $settings )){

                return $settings[$config];

            }

            return null;
        }
        return $settings;
    }
}
