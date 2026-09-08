<?php
namespace App\Libraries;
class LocalBannerPath
{
    public static function resolve(string $directory, string $name): string
    {
        $root=realpath($directory);
        if (!$root || strpos($name, "\0") !== false || preg_match('~^(?:[/\\\\]|[a-z]:)~i',$name)) throw new \RuntimeException('Invalid local banner');
        $path=realpath($root.DIRECTORY_SEPARATOR.$name);
        $prefix=rtrim(str_replace('\\','/',$root),'/').'/';
        $normalized=str_replace('\\','/',(string)$path);
        if (!$path || strpos($normalized,$prefix)!==0 || !is_file($path) || !is_readable($path)) throw new \RuntimeException('Banner outside directory or missing');
        return $path;
    }
}
