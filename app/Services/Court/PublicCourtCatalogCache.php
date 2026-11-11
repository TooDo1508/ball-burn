<?php

namespace App\Services\Court;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicCourtCatalogCache
{
    private const VERSION_CACHE_KEY = 'public-courts:version';

    public function version(): string
    {
        return (string) Cache::get(self::VERSION_CACHE_KEY, '1');
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_CACHE_KEY, (string) Str::uuid());
    }
}
