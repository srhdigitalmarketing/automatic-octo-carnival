<?php
namespace App\Libraries;

/** Browser-origin guard for legacy admin forms, AJAX and destructive GET links. */
class AdminOrigin
{
    public static function matches(string $source, string $target): bool
    {
        $left = parse_url($source); $right = parse_url($target);
        if (! is_array($left) || ! is_array($right)) { return false; }
        foreach ([$left, $right] as $parts) {
            if (! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) { return false; }
        }
        return strtolower($left['scheme']) === strtolower($right['scheme'])
            && strtolower($left['host']) === strtolower($right['host'])
            && ($left['port'] ?? (strtolower($left['scheme']) === 'https' ? 443 : 80))
                === ($right['port'] ?? (strtolower($right['scheme']) === 'https' ? 443 : 80));
    }
}
