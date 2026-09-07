<?php

namespace App\Libraries;

use CodeIgniter\Encryption\Encryption;

class UniqToken
{

    protected static $separator = '--';


    public static function create( $data )
    {

        $token = null;

        if(is_array( $data )) {
            $data = implode(self::$separator, $data);
        }

        try{

            $token =  service('encrypter')->encrypt( $data );
            $token = bin2hex( $token );

        }catch (\Exception $e){ }

        return $token;
    }


    public static function decode( $token )
    {
        $data = null;

        try{

            $data = service('encrypter')->decrypt( hex2bin( $token ) );
            $data = explode(self::$separator, $data);

        }catch (\Exception $e) { }

        return $data;
    }



}