<?php
// Render the real views with deterministic settings; never read a site's database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('FCPATH', dirname(__DIR__, 2) . '/public/');
require dirname(__DIR__, 2) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require dirname(__DIR__, 2) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$testSettings = ['default_theme'=>'pirate', 'player_bunny_cdn_enabled'=>false,
    'player_button_color'=>'#d28a15', 'player_loading_color'=>'#21c4b5'];
if (($argv[2] ?? '') === 'invalid') $testSettings['player_loading_color'] = '</style><script>alert(1)</script>';
if (($argv[2] ?? '') === 'missing') unset($testSettings['player_loading_color']);
function get_config($name) { global $testSettings; return $testSettings[$name] ?? null; }
function encode_id($id) { return 'movie'; }
function default_banner_uri() { return 'https://player.example/poster-fallback.svg'; }
helper(['url', 'form', 'config', 'template', 'media_files']);
config('App')->baseURL = 'https://player.example/';
config('App')->indexPage = '';
$policy = new ReflectionProperty(App\Libraries\SiteIndexing::class, 'noIndex');
$policy->setAccessible(true); $policy->setValue(null, true);
if (($argv[1] ?? '') === 'settings') {
    // Only the layout is omitted; controls and preview script come from the real view.
    $renderer = new class {
        public function extend($name) {}
        public function section($name) {}
        public function endSection() {}
        public function render() { require dirname(__DIR__, 2).'/app/Views/admin/settings/player.php'; }
    };
    $renderer->render();
} else {
    $links = [1=>'test']; $serverNotFound = false;
    $movie = new class {
        public $id = 1;
        public $banner = 'https://player.example/poster.svg?title="sample"&size=large';
        public function getMovieTitle() { return 'Player preview'; }
    };
    require dirname(__DIR__, 2).'/app/Views/themes/pirate/embed.php';
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
