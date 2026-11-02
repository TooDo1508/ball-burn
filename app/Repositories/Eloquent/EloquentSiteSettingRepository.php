<?php

namespace App\Repositories\Eloquent;

use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepository;

class EloquentSiteSettingRepository implements SiteSettingRepository
{
    public function findByKey(string $key): ?SiteSetting
    {
        return SiteSetting::query()->where('setting_key', $key)->first();
    }

    public function updateOrCreate(string $key, array $attributes): SiteSetting
    {
        return SiteSetting::query()->updateOrCreate(['setting_key' => $key], $attributes);
    }
}
