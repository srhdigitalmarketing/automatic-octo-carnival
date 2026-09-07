<?php

if(! function_exists( 'delete_media_file' ))
{
    function delete_media_file($filepath)
    {
        $file = realpath( FCPATH . 'uploads' . $filepath );
        if($file && is_file($file)) {
            if (unlink($file))
                return true;
        }
        return false;
    }
}

if(! function_exists( 'media_uri' )) {
    function media_uri($filepath): string
    {
        if(file_exists( FCPATH . 'uploads' . $filepath  )){
            return site_url('/uploads' . $filepath );
        }
        return '';
    }
}



if(! function_exists( 'delete_poster' ))
{
    function delete_poster($filename)
    {
        return delete_media_file("/". posters_dir_name() ."/{$filename}");
    }
}

if(! function_exists( 'delete_banner' ))
{
    function delete_banner($filename)
    {
        if (filter_var($filename, FILTER_VALIDATE_URL) !== false) {
            try {
                $storage = \App\Libraries\CloudflareR2Storage::active();
                if ($storage !== null && $storage->isManagedUrl($filename)) {
                    return $storage->deletePublicUrl($filename);
                }
            } catch (\Throwable $exception) {
                log_message('warning', 'Unable to remove Cloudflare R2 banner: {message}', ['message' => $exception->getMessage()]);
            }

            // Never attempt to delete an arbitrary remote URL.
            return false;
        }

        return delete_media_file("/". banners_dir_name() ."/{$filename}");
    }
}

if(! function_exists( 'poster_uri' ))
{
    function poster_uri($filename)
    {
        $poster = '';

        if(! empty($filename)){
            if(filter_var($filename, FILTER_VALIDATE_URL) === FALSE){
                $poster = media_uri("/" . posters_dir_name() . "/{$filename}");
            }else{
                $poster = $filename;
            }
        }

        if(empty($poster))
            $poster = default_poster_uri();

        return $poster;
    }
}

if(! function_exists( 'default_poster_uri' ))
{
    function default_poster_uri()
    {
        $posterName = get_config('default_poster');
        return media_uri("/{$posterName}");
    }
}

if(! function_exists( 'default_banner_uri' ))
{
    function default_banner_uri()
    {
        $bannerName = get_config('default_banner');
        return media_uri("/{$bannerName}");
    }
}

if(! function_exists( 'banner_uri' ))
{
    function banner_uri($filename)
    {
        $banner = '';

        if(! empty($filename)){
            if(filter_var($filename, FILTER_VALIDATE_URL) === FALSE){
                $banner = media_uri("/" . banners_dir_name() . "/{$filename}");
            }else{
                $banner = $filename;
            }
        }

        if(empty($banner))
            $banner = default_banner_uri();

        return $banner;
    }
}

if(! function_exists( 'posters_dir_name' ))
{
    function posters_dir_name()
    {
        return Config('MediaFiles')->postersDir;
    }
}

if(! function_exists( 'banners_dir_name' ))
{
    function banners_dir_name()
    {
        return Config('MediaFiles')->bannersDir;
    }
}

if(! function_exists( 'poster_dir' ))
{
    function poster_dir()
    {
        $dir = FCPATH . 'uploads/'. posters_dir_name() .'/';

        if(! is_dir($dir))
            @mkdir($dir, 0777, true);

        return $dir;
    }
}

if(! function_exists( 'banner_dir' ))
{
    function banner_dir()
    {
        $dir = FCPATH . 'uploads/'. banners_dir_name() .'/';

        if(! is_dir($dir))
            @mkdir($dir, 0777, true);

        return $dir;
    }
}
