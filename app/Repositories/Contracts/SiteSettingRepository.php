<?php

namespace App\Repositories\Contracts;

use App\Models\SiteSetting;

interface SiteSettingRepository
{
    public function findByKey(string $key): ?SiteSetting;

    public function updateOrCreate(string $key, array $attributes): SiteSetting;
}
