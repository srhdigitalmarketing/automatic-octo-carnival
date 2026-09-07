<?php

namespace App\Entities\Cast;

use CodeIgniter\Entity\Cast\BaseCast;

class CastBase64 extends BaseCast
{
    public static function get($value, array $params = [])
    {
        return base64_decode($value);
    }

    public static function set($value, array $params = [])
    {
        return base64_encode($value);
    }
}