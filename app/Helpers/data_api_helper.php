<?php


if(! function_exists( 'append_omdb_data' ))
{
    function append_omdb_data($obj, $data = [])
    {
        if(empty($data) || ! is_array($data))
            return $obj;

        $omdb = service('omdb');
        $omdbResults =  $omdb->findByImdbId( $obj->imdb_id );
        if($omdbResults !== null){
            foreach ($data as $val) {
                if(property_exists($omdbResults, $val)){
                    $obj->$val = $omdbResults->$val;
                }
            }
        }

        return $obj;

    }
}


