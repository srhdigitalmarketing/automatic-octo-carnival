<?php
namespace App\Libraries;

use App\Models\LinkModel;

/** One API call per step; append stream links only after a unique, ready title match. */
class ServerDotHostStreamGrab
{
    public const MAX_PAGES = 20;
    private $db;
    private ServerDotHostCatalog $catalog;

    public function __construct($db = null, ?ServerDotHostCatalog $catalog = null)
    {
        $this->db = $db ?? db_connect();
        $this->catalog = $catalog ?? new ServerDotHostCatalog();
    }

    public static function titleKey(string $title): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\p{Z}]+/u', ' ', $title) ?? ''), 'UTF-8');
    }

    public static function apiFingerprint(object $api): string
    {
        return hash('sha256', $api->id . "\n" . $api->api_token . "\n" . $api->embed_domains);
    }

    public function start(object $api, string $owner): array
    {
        $max = (int)($this->videos()->selectMax('id')->get()->getRowArray()['id'] ?? 0);
        return ['owner'=>$owner, 'api_id'=>(int)$api->id, 'api_fingerprint'=>self::apiFingerprint($api),
            'max_id'=>$max, 'cursor'=>0, 'pending'=>null, 'done'=>false,
            'total'=>$this->videos()->where('id <=', $max)->countAllResults(),
            'processed'=>0, 'success'=>0, 'skipped'=>0];
    }

    public function step(array &$job, object $api): array
    {
        if (($api->provider ?? '') !== 'serverdothost' || ($api->status ?? '') !== 'active'
            || !hash_equals($job['api_fingerprint'], self::apiFingerprint($api))) {
            throw new \RuntimeException('Pengaturan API berubah atau tidak aktif. Mulai kembali.');
        }
        if ($job['done']) return $this->progress($job);
        if (!$job['pending']) {
            $movie = $this->videos()->select('id,title,type')->where('id >', $job['cursor'])
                ->where('id <=', $job['max_id'])->orderBy('id', 'ASC')->get(1)->getRowArray();
            if (!$movie) { $job['done'] = true; return $this->progress($job); }
            $job['pending'] = ['movie'=>$movie, 'page'=>1, 'last_page'=>null, 'candidate'=>null, 'phase'=>'search'];
            if (self::titleKey((string)$movie['title']) === '') return $this->finish($job, 'skipped', 'Judul kosong.');
        }
        $pending = &$job['pending'];
        $movie = $pending['movie'];
        if ($pending['phase'] === 'search') {
            $page = $this->catalog->page($api->api_token, $movie['title'], $pending['page'], $api->embed_domains);
            if ($page['current_page'] !== $pending['page']
                || ($pending['last_page'] !== null && $page['last_page'] !== $pending['last_page'])) {
                throw new \RuntimeException('Daftar ServerDotHost berubah saat dibaca. Mulai kembali.');
            }
            $pending['last_page'] = $page['last_page'];
            foreach ($page['items'] as $item) {
                if (self::titleKey($item['title']) !== self::titleKey($movie['title'])) continue;
                if ($pending['candidate'] && $pending['candidate']['source_id'] !== $item['source_id']) {
                    return $this->finish($job, 'skipped', 'Lebih dari satu video memiliki judul yang sama di ServerDotHost.');
                }
                $pending['candidate'] = array_intersect_key($item, array_flip(['source_id','processing_status','moderation_status']));
            }
            if ($page['next_page'] !== null) {
                if ($pending['page'] >= self::MAX_PAGES) {
                    return $this->finish($job, 'skipped', 'Hasil melebihi 20 halaman; kecocokan unik belum dapat dipastikan.');
                }
                $pending['page'] = $page['next_page'];
                return $this->progress($job) + ['title'=>$movie['title'], 'message'=>'Mencari judul di halaman '.$pending['page'].'…'];
            }
            if (!$pending['candidate']) return $this->finish($job, 'skipped', 'Video belum diunggah atau judul belum ditemukan di ServerDotHost.');
            if ($pending['candidate']['processing_status'] !== 'ready') return $this->finish($job, 'skipped', 'Video belum selesai diunggah atau diproses.');
            if ($pending['candidate']['moderation_status'] === 'blocked') return $this->finish($job, 'skipped', 'Video diblokir di ServerDotHost.');
            $pending['phase'] = 'detail';
            return $this->progress($job) + ['title'=>$movie['title'], 'message'=>'Memeriksa link embed video yang cocok…'];
        }
        try {
            $item = $this->catalog->detail($api->api_token, $pending['candidate']['source_id'], $api->embed_domains);
        } catch (\RuntimeException $error) {
            if ($error->getCode() === 404) return $this->finish($job, 'skipped', 'Video sudah tidak tersedia di ServerDotHost.');
            throw $error;
        }
        if (self::titleKey($item['title']) !== self::titleKey($movie['title'])) return $this->finish($job, 'skipped', 'Judul remote berubah saat diproses.');
        if ($item['processing_status'] !== 'ready') return $this->finish($job, 'skipped', 'Video belum siap diputar.');
        if ($item['moderation_status'] === 'blocked' || !$item['stream_urls']) {
            return $this->finish($job, 'skipped', 'Link embed belum tersedia, diblokir, atau domain belum diizinkan.');
        }
        $result = $this->append($movie, $item['stream_urls'][0], (int)$api->id);
        return $this->finish($job, $result['state'], $result['message']);
    }

    private function videos()
    {
        return $this->db->table('movies')->whereIn('type', ['movie','episode']);
    }

    private function append(array $movie, string $url, int $apiId): array
    {
        // Remote requests have finished before starting this short transaction.
        if (!$this->db->transBegin()) throw new \RuntimeException('Transaksi link tidak dapat dimulai.');
        try {
            $table = $this->db->protectIdentifiers($this->db->prefixTable('movies'));
            $current = $this->db->query('SELECT id,title,type FROM '.$table.' WHERE id = ? FOR UPDATE', [$movie['id']])->getRowArray();
            if (!$current || $current['title'] !== $movie['title'] || $current['type'] !== $movie['type']) {
                $this->db->transRollback();
                return ['state'=>'skipped', 'message'=>'Video lokal telah diubah atau dihapus; link tidak ditambahkan.'];
            }
            if ($this->db->table('links')->where('movie_id', $movie['id'])->where('type', 'stream')->where('link', $url)->countAllResults()) {
                $this->db->transRollback();
                return ['state'=>'skipped', 'message'=>'Stream link sudah ada.'];
            }
            $data = ['movie_id'=>(int)$movie['id'], 'type'=>'stream', 'link'=>$url];
            $fields = $this->db->getFieldNames('links');
            if (in_array('api_id', $fields, true)) $data['api_id'] = $apiId;
            if (in_array('host_priority', $fields, true)) $data['host_priority'] = 100;
            if (!(new LinkModel($this->db))->insert($data) || !$this->db->transStatus() || !$this->db->transCommit()) {
                throw new \RuntimeException('Stream link gagal disimpan.');
            }
            return ['state'=>'success', 'message'=>'Stream link ServerDotHost ditambahkan.'];
        } catch (\Throwable $error) {
            $this->db->transRollback();
            throw $error;
        }
    }

    private function finish(array &$job, string $state, string $message): array
    {
        $movie = $job['pending']['movie'];
        $job['cursor'] = (int)$movie['id'];
        $job['pending'] = null;
        $job['processed']++;
        $job[$state]++;
        $job['done'] = $job['cursor'] >= $job['max_id'];
        return $this->progress($job) + ['id'=>(int)$movie['id'], 'title'=>$movie['title'], 'state'=>$state, 'message'=>$message];
    }

    private function progress(array $job): array
    {
        return array_intersect_key($job, array_flip(['total','processed','success','skipped','done']));
    }
}
