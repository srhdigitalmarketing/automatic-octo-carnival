<?php
namespace App\Libraries;

final class HistatsCode
{
    // Format check only: this is executable code supplied by a trusted administrator.
    public static function isValid(string $code): bool
    {
        return strlen($code) <= 20000
            && strpos($code, 'Histats.start') !== false
            && strpos($code, '_Hasync') !== false
            && preg_match('~(?:(?:https:)?//)s10\.histats\.com/js15_as\.js~', $code)
            && preg_match('/\.async\s*=\s*(?:true|1)\s*;/', $code)
            && !preg_match('/document\s*\.\s*write(?:ln)?\s*\(/i', $code);
    }
}
