<?php
require __DIR__ . '/../app/Libraries/GoogleAnalyticsAudience.php';
function check($ok, $message) { if (! $ok) { throw new RuntimeException($message); } }
$dir = sys_get_temp_dir() . '/ga4-test-' . bin2hex(random_bytes(6)) . '/';
mkdir($dir); mkdir($dir . 'cache');
define('WRITEPATH', $dir);
class TestAudience extends \App\Libraries\GoogleAnalyticsAudience {
    public $calls = 0;
    public $publicKey;
    public $fail = false;
    protected function request(string $url, string $body, array $headers): array {
        $this->calls++;
        if ($this->fail) { throw new RuntimeException('Private diagnostic must not reach dashboard'); }
        if ($url === 'https://oauth2.googleapis.com/token') {
            parse_str($body, $form);
            $parts = explode('.', $form['assertion']);
            $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            check($claims['scope'] === 'https://www.googleapis.com/auth/analytics.readonly', 'Read-only scope');
            check($claims['aud'] === $url, 'OAuth audience');
            check(openssl_verify($parts[0] . '.' . $parts[1], base64_decode(strtr($parts[2], '-_', '+/')), $this->publicKey, OPENSSL_ALGO_SHA256) === 1, 'JWT signature');
            return ['access_token' => 'test-token'];
        }
        check(strpos($url, '/properties/15734353311:batchRunReports') !== false, 'Property ID');
        $requests = json_decode($body, true)['requests'];
        check(count($requests) === 3, 'Three reports');
        check($requests[0]['dimensionFilter']['andGroup']['expressions'][0]['filter']['stringFilter']['value'] === '/embed-player', 'Embed filter');
        check($requests[0]['metrics'][0]['name'] === 'totalUsers', 'User metric');
        $date = (new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta')))->format('Ymd');
        return ['reports' => [
            ['rows' => [['dimensionValues' => [['value' => $date]], 'metricValues' => [['value' => '12']]]]],
            ['rows' => [
                ['dimensionValues' => [['value' => 'desktop']], 'metricValues' => [['value' => '7']]],
                ['dimensionValues' => [['value' => 'mobile']], 'metricValues' => [['value' => '5']]],
                ['dimensionValues' => [['value' => 'tablet']], 'metricValues' => [['value' => '2']]],
                ['dimensionValues' => [['value' => '(not set)']], 'metricValues' => [['value' => '1']]],
            ]],
            ['rows' => [['metricValues' => [['value' => '10']]]]],
        ]];
    }
}
try {
    $settings = (object) ['propertyId' => '15734353311', 'credentialsPath' => $dir . 'key.json', 'timezone' => 'Asia/Jakarta'];
    $client = new TestAudience($settings);
    $missing = $client->summary();
    check(! $missing['tracking_ready'] && $client->calls === 0 && $missing['notice'] !== '', 'Missing credentials handling');
    file_put_contents($dir . 'openssl.cnf', "[req]\ndistinguished_name=req_dn\n[req_dn]\n");
    $keyOptions = ['config' => $dir . 'openssl.cnf', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    $key = openssl_pkey_new($keyOptions);
    openssl_pkey_export($key, $privateKey, null, $keyOptions);
    $client->publicKey = openssl_pkey_get_details($key)['key'];
    file_put_contents($settings->credentialsPath, json_encode(['type' => 'service_account', 'client_email' => 'test@example.invalid', 'private_key' => $privateKey]));
    $summary = $client->summary();
    check($summary['tracking_ready'] && $summary['total'] === 10, 'Use GA4 unique total instead of sum');
    check(count($summary['daily']) === 30 && $summary['daily'][29] === 12 && $summary['daily'][0] === 0, 'Daily dates and zero filling');
    check($summary['platforms'] === ['desktop' => 7, 'mobile' => 5, 'tablet' => 2, 'other' => 1], 'Device mapping');
    check($client->summary() === $summary && $client->calls === 2, 'File cache prevents duplicate API calls');
    foreach (glob($dir . 'cache/*') as $file) { check(strpos(file_get_contents($file), 'test-token') === false, 'Token not cached'); unlink($file); }
    $client->fail = true;
    $failed = $client->summary();
    check(! $failed['tracking_ready'] && strpos($failed['notice'], 'Private diagnostic') === false, 'API error notice');
    $calls = $client->calls;
    $client->summary();
    check($client->calls === $calls, 'Failure cache');
    check(strpos(file_get_contents(__DIR__ . '/../app/Controllers/Traffic.php'), 'recordDailyEmbedVisitor') === false, 'No daily visitor writes');
    check(strpos(file_get_contents(__DIR__ . '/../app/Controllers/Admin/Dashboard.php'), 'traffic_daily_visitors') === false, 'No local audience reads');
    echo "PASS: credentials, OAuth signature, reports, totals, devices, file caching, errors, no local visitor reads/writes.\n";
} finally {
    foreach (glob($dir . 'cache/*') as $file) { unlink($file); }
    if (is_file($dir . 'key.json')) { unlink($dir . 'key.json'); }
    if (is_file($dir . 'openssl.cnf')) { unlink($dir . 'openssl.cnf'); }
    rmdir($dir . 'cache'); rmdir($dir);
}
