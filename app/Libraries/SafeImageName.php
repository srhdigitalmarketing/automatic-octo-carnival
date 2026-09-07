<?php
namespace App\Libraries;

class SafeImageName
{
    public static function extension($file): string
    {
        $image = @getimagesize($file->getPathname());
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($image === false || ! isset($types[$image['mime'] ?? ''])) {
            throw new \RuntimeException('Only JPG, PNG, and WebP image content is allowed.');
        }
        return $types[$image['mime']];
    }

    public static function random($file): string
    {
        return bin2hex(random_bytes(16)) . '.' . self::extension($file);
    }
}
