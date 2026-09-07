<?php

if(! function_exists( 'download_image' ))
{
    function download_image($imgUrl, $dir = null)
    {
        set_time_limit(300);
        $options = [
            'timeout' => 15,
            'http_errors' => false,
            'verify' => false
        ];
        $httpClient = \Config\Services::curlrequest( $options, null,null, false );
        $response = $httpClient->get($imgUrl);

        if(empty($dir)) {
            $dir = WRITEPATH . 'tmp';
            if(! is_dir($dir)) {
                @mkdir($dir);
            }
        }


        $dir = rtrim($dir, '/');

        $success = false;
        $savePath = '';

        if($response->getStatusCode() == 200) {
            $contentType = trim( $response->getHeaderLine('Content-Type') );
            if(preg_match('/^image\/(jpe?g|png)$/', $contentType, $matches)){
                $extension = $matches[1];
                $filename = random_string() . '.' . $extension;
                if(is_dir($dir)) {
                    $savePath = "$dir/$filename";
                    if(file_put_contents($savePath, $response->getBody())){
                        $success = true;
                    }
                }
            }
        }

        return $success ? $savePath : null;


    }
}


