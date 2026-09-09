<?php
namespace App\Libraries;

final class ReportedLinkTools
{
    private $db;
    public function __construct($db = null) { $this->db = $db ?? db_connect(); }
    public static function host($value): string
    {
        if (!is_string($value) && $value !== null) throw new \InvalidArgumentException('Hostname tidak valid.');
        $host = strtolower(trim((string)$value));
        if ($host !== '' && (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strpos($host, '.') === false)) {
            throw new \InvalidArgumentException('Pilih hostname yang valid atau All hosts.');
        }
        return $host;
    }
    private function query(string $host)
    {
        $builder = $this->db->table('links')->groupStart()->where('links.reports_wrong_link >', 0)->orWhere('links.reports_not_working >', 0)->groupEnd();
        if ($host !== '') {
            $builder->where('links.type','stream')->where('LOWER(links.link) REGEXP '.$this->db->escape('^https?://'.str_replace('.', '[.]', $host).'([/:?#]|$)'), null, false);
        }
        return $builder;
    }
    public function start(string $host): array
    {
        $host=self::host($host);
        $row=$this->query($host)->select('COUNT(*) AS total, MAX(links.id) AS max_id',false)->get()->getRowArray();
        return ['total'=>(int)$row['total'],'max_id'=>(int)$row['max_id']];
    }
    public function batch(string $host, int $cursor, int $maxId, bool $clear): array
    {
        $host=self::host($host);
        $query=$this->query($host)->where('links.id >',max(0,$cursor))->where('links.id <=',max(0,$maxId))->orderBy('links.id','ASC');
        if ($clear) $query->select('links.id');
        else $query->select('links.id, links.link, movies.title')->join('movies','movies.id = links.movie_id','left');
        $rows=$query->get(250)->getResultArray();
        if (!$rows) return ['done'=>true,'cursor'=>$cursor,'count'=>0,'rows'=>[]];
        $ids=array_column($rows,'id');
        $count=count($rows);
        if ($clear) {
            if (!$this->query($host)->whereIn('links.id',$ids)->update(['reports_not_working'=>0,'reports_wrong_link'=>0])) throw new \RuntimeException('Clear gagal; silakan periksa log server.');
            $count=$this->db->affectedRows();
        } else {
            foreach ($rows as &$row) { $row['title']=(string)($row['title'] ?? '[Video tidak ditemukan]'); $row['link']=(string)$row['link']; }
            unset($row);
        }
        return ['done'=>count($rows)<250,'cursor'=>(int)end($ids),'count'=>$count,'rows'=>$clear?[]:$rows];
    }
}
