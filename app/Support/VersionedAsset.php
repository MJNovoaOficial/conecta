<?php

namespace App\Support;

/** Local assets keep their cache until their actual contents change. */
final class VersionedAsset
{
    public static function url(string $path): string
    {
        $url = asset($path);
        $file = public_path($path);
        // A missing/unreadable static file must not turn the login page into a 500.
        $hash = is_file($file) && is_readable($file) ? @hash_file('sha256', $file) : false;

        return $hash === false ? $url : $url.'?v='.$hash;
    }
}
