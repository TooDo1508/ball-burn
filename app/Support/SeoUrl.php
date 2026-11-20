<?php

namespace App\Support;

class SeoUrl
{
    public static function to(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        return rtrim((string) config('seo.canonical_url'), '/').$path;
    }
}
