<?php
namespace App\Controllers {
    class TestResponse {
        public $status=200; public $body=''; public $headers=[];
        public function setHeader($key,$value) { $this->headers[$key]=$value; return $this; }
        public function setStatusCode($value) { $this->status=$value; return $this; }
        public function setBody($value) { $this->body=$value; return $this; }
    }
    class BaseController { public $response; public function __construct() { $this->response=new TestResponse(); } }
}
namespace {
    $values=[];
    require __DIR__.'/../app/Libraries/SiteIndexing.php';
    function config($name) { global $values; return (object)$values; }
    function get_config($key) { global $values; return $values[$key] ?? null; }
    function esc($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function site_url($path) { return 'https://example.test'.$path; }
    function view($name,$data=[]) { extract($data); ob_start(); require __DIR__.'/../app/Views/'.$name.'.php'; return ob_get_clean(); }
    require __DIR__.'/../app/Controllers/Home.php';
    foreach ([false,0,'0'] as $disabled) {
        $values=['homepage_active'=>$disabled]; $home=new App\Controllers\Home(); $response=$home->index();
        if ($response->status !== 403 || strpos($response->body,'403 Forbidden') === false || $response->headers['Cache-Control'] !== 'no-store') throw new RuntimeException('403 behavior failed');
    }
    foreach ([null,true,1,'1'] as $enabled) {
        $values=['homepage_active'=>$enabled,'homepage_heading'=>'<script>alert(1)</script>']; $home=new App\Controllers\Home(); $html=$home->index();
        if ($home->response->status !== 200 || strpos($html,'YOUR VIDEO LIBRARY') === false || strpos($html,'<script>alert') !== false) throw new RuntimeException('Active template/escaping failed');
    }
    foreach ([true,false,null] as $noIndex) {
        $values=['homepage_active'=>true,'site_noindex'=>$noIndex]; App\Libraries\SiteIndexing::reset();
        $html=(new App\Controllers\Home())->index();
        $expected=$noIndex===false ? 'index, follow' : 'noindex, nofollow, noimageindex, nosnippet';
        if (strpos($html,'content="'.$expected.'"')===false) throw new RuntimeException('Homepage indexing policy mismatch');
    }
    echo "PASS: homepage enabled/default 200, disabled 403, no-store, HTML escaping and both indexing modes.\n";
}
