<?php
namespace App\Libraries;
class VideoPopularity
{
    public static function record(int $id): void
    {
        try {
            $db=db_connect();
            if (!$db->tableExists('video_daily_views')) return;
            $db->query('INSERT INTO video_daily_views (movie_id, visit_date, views) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1',[$id,date('Y-m-d')]);
        } catch (\Throwable $e) { log_message('error','Daily video views could not be recorded.'); }
    }
    public static function top(int $days): array
    {
        $days=max(1,min(30,$days)); $key='video_popularity_'.$days.'_'.date('Ymd');
        if (is_array($cached=cache()->get($key))) return $cached;
        $db=db_connect(); if (!$db->tableExists('video_daily_views')) return [];
        $rows=$db->table('video_daily_views v')->select('m.id, m.title')->selectSum('v.views','views')->join('movies m','m.id=v.movie_id')->where('m.type','movie')->where('v.visit_date >=',date('Y-m-d',strtotime('-'.($days-1).' days')))->where('v.visit_date <=',date('Y-m-d'))->groupBy(['m.id','m.title'])->orderBy('views','DESC')->orderBy('m.id','DESC')->get(10)->getResult();
        cache()->save($key,$rows,60); return $rows;
    }
}
