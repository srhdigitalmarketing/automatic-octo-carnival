<?php


if(! function_exists('validate_gcaptcha'))
{
    function validate_gcaptcha( $response )
    {
        $query = http_build_query([
            'secret' => get_config('gcaptcha_secret_key'),
            'response' => $response
        ]);

        $url = "https://www.google.com/recaptcha/api/siteverify?{$query}";

        $contextOptions = [
            'ssl' => [
                "verify_peer"=> false,
                "verify_peer_name"=> false
            ]
        ];

        // Get verify response data
        $verifyResponse = file_get_contents($url, false, stream_context_create( $contextOptions ));
        if(isJson($verifyResponse)){

            $responseData = json_decode($verifyResponse);
            if($responseData->success)
                return true;

        }

        return false;

    }
}