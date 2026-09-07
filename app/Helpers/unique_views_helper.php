<?php

helper('cookie');

if(! function_exists( 'is_movie_viewed' ))
{
    function is_movie_viewed( $uniqId )
    {
        return get_cookie("viewed_{$uniqId}") == 1;
    }
}

if(! function_exists( 'movie_viewed' ))
{
    function movie_viewed( $uniqId, $path = '/' )
    {
        if(get_cookie("viewed_{$uniqId}") != 1) {
            set_cookie("viewed_{$uniqId}", 1,60 * 60 * 24, '', $path);
        }
    }
}


if(! function_exists( 'has_permit_translate' ))
{
    function has_permit_translate( )
    {

    }
}
