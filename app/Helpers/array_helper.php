<?php


if(! function_exists( 'extract_array_data' ))
{
    function extract_array_data($array, $id)
    {
        $result = [];

        if(!empty($array)){
            $result = array_map(function($var) use ($id) { return $var[$id] ?? '';  }, $array);
        }

        return $result;
    }
}

if(! function_exists( 'array_map_assoc' ))
{
    function array_map_assoc(callable $f, array $a)
    {
        return array_column(array_map($f, array_keys($a), $a), 1, 0);
    }
}

if(! function_exists( 'str_to_array' ))
{
    function str_to_array(?string $data, $separator = ',')
    {
        $results = [];
        if(! empty( $data )){
            $data = str_replace(' ', '', $data);
            $results = explode(',', $data);
        }

        return $results;
    }
}

if(! function_exists( 'fix_arr_data' ))
{
    function fix_arr_data(array $data , $separator = ',')
    {
        foreach ($data as $key => $val){
            $data[$key] = trim($val);
            if(strpos($val, $separator) !== false){
                $val = explode($separator, $val);
                foreach ($val as $k => $v) {
                    $v =  trim($v);
                    if(! in_array($v, $data)){
                        $data[] = $v;
                    }
                }
                unset($data[$key]);
            }
        }
        $data = array_filter($data);
        asort($data);
        return $data;
    }
}