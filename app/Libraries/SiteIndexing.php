<?php
namespace App\Libraries;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** One indexing policy per website, reusing the request-local Settings configuration. */
class SiteIndexing
{
    public const NOINDEX = 'noindex, nofollow, noimageindex, nosnippet';
    public const INDEX = 'index, follow';
    public const KEY = 'site_noindex';
    private static ?bool $noIndex = null;

    public static function reset(): void { self::$noIndex = null; }

    public static function noIndex(): bool
    {
        if (self::$noIndex !== null) return self::$noIndex;
        try {
            $value = config('Settings')->{self::KEY} ?? null;
            // Preserve the previous global noindex policy until explicitly changed.
            return self::$noIndex = !in_array($value, [false, 0, '0'], true);
        } catch (\Throwable $error) {
            return self::$noIndex = true;
        }
    }

    public static function robots(): string { return self::noIndex() ? self::NOINDEX : self::INDEX; }

    public static function forResponse(RequestInterface $request, ResponseInterface $response): string
    {
        // Private/admin and technical responses remain excluded in either mode.
        if (!in_array(strtolower($request->getMethod()), ['get','head'], true)
            || $response->getStatusCode() !== 200) return self::NOINDEX;
        $type = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
        if ($type !== '' && !in_array($type, ['text/html','application/xhtml+xml'], true)) return self::NOINDEX;
        $controller = strtolower(ltrim((string)service('router')->controllerName(), '\\'));
        if (strpos($controller, 'app\\controllers\\admin\\') === 0
            || in_array($controller, ['app\\controllers\\api','app\\controllers\\ajax','app\\controllers\\sitemap'], true)) return self::NOINDEX;
        $path = strtolower(trim($request->uri->getPath(), '/'));
        if (preg_match('~(?:^|/)(?:admin(?:/|$)|admin_login(?:/|$)|ajax(?:/|$)|api(?:/|$))~', $path)) return self::NOINDEX;
        return self::robots();
    }

    public static function save(bool $noIndex): void
    {
        $db = \Config\Database::connect();
        $table = $db->protectIdentifiers($db->prefixTable('settings'));
        // settings.name is the existing primary key: one atomic, idempotent upsert.
        $ok = $db->query('INSERT INTO '.$table.' (name,value,data_type) VALUES (?,?,?) '
            .'ON DUPLICATE KEY UPDATE value = ?, data_type = ?',
            [self::KEY, $noIndex ? '1' : '0', 'bool', $noIndex ? '1' : '0', 'bool']);
        if (!$ok) throw new \RuntimeException('Pengaturan indeks gagal disimpan.');
        self::$noIndex = $noIndex;
    }
}
