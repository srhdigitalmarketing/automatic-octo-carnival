<?php
namespace App\Libraries;
class AutoGrabMatch
{
    public static function code(string $title): string
    {
        if (stripos($title, 'English-Subtitle') !== false) { return ''; }
        $title = trim($title);
        if (preg_match('/^\[([a-z0-9]+(?:-[a-z0-9]+)*)\]/i', $title, $m)) { return strtolower($m[1]); }
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/iD', $title) ? strtolower($title) : '';
    }
    public static function select(string $code, array $items): ?array
    {
        $matches = [];
        foreach ($items as $item) {
            if (stripos($item['title'], 'English-Subtitle') !== false || stripos($item['movie_code'] ?? '', 'English-Subtitle') !== false) { continue; }
            $candidate = self::code(($item['movie_code'] ?? '') ?: $item['title']);
            if ($candidate === $code && $code !== '' && !empty($item['auto_poster_url'])) { $item['poster_url'] = $item['auto_poster_url']; $matches[] = $item; }
        }
        return count($matches) === 1 ? $matches[0] : null;
    }
}
