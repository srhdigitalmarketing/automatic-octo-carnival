<?php
namespace App\Libraries;
class SelectedPageCache
{
    public static function enabled(string $page): bool
    {
        if (!get_config('web_page_cache')) return false;
        $selected=get_config('web_page_cache_types');
        return $selected === null ? in_array($page,['embed','view','download'],true) : in_array($page,is_array($selected)?$selected:[],true);
    }
    public static function register(int $movieId, $request): void
    {
        if ($movieId < 1) return;
        $uri=$request->getUri();
        $name=\CodeIgniter\HTTP\URI::createURIString($uri->getScheme(),$uri->getAuthority(),$uri->getPath(),config('Cache')->cacheQueryString ? $uri->getQuery() : '');
        if (!empty($_COOKIE['lang']) && strlen($_COOKIE['lang'])===2) $name=$_COOKIE['lang'].'.'.$name;
        $index='video_page_keys_'.$movieId;
        $keys=cache()->get($index); $keys=is_array($keys)?$keys:[];
        $keys[md5($name)]=true;
        cache()->save($index,$keys,max(60,(int)web_page_cache_time())+60);
    }
    public static function clear(int $movieId): void
    {
        $index='video_page_keys_'.$movieId;
        foreach ((array)cache()->get($index) as $key=>$unused) { if (preg_match('/^[a-f0-9]{32}$/D',$key)) cache()->delete($key); }
        cache()->delete($index);
    }
}
