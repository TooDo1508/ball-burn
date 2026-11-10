<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_MAP_URL = 'https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9';

    private const MAP_URL = 'https://maps.app.goo.gl/q4FVTh8toEBiqxgo9';

    private const MAP_COORDINATES = '21.075256962736187, 105.81640685092688';

    public function up(): void
    {
        $setting = DB::table('site_settings')->where('setting_key', 'contact_info')->first(['id', 'setting_value']);

        if (! $setting) {
            return;
        }

        $value = is_array($setting->setting_value)
            ? $setting->setting_value
            : json_decode((string) $setting->setting_value, true);

        if (! is_array($value) || ($value['map_url'] ?? null) !== self::LEGACY_MAP_URL) {
            return;
        }

        $value['map_url'] = self::MAP_URL;
        $value['map_coordinates'] = self::MAP_COORDINATES;

        DB::table('site_settings')->where('id', $setting->id)->update([
            'setting_value' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function down(): void
    {
        $setting = DB::table('site_settings')->where('setting_key', 'contact_info')->first(['id', 'setting_value']);

        if (! $setting) {
            return;
        }

        $value = is_array($setting->setting_value)
            ? $setting->setting_value
            : json_decode((string) $setting->setting_value, true);

        if (! is_array($value)
            || ($value['map_url'] ?? null) !== self::MAP_URL
            || ($value['map_coordinates'] ?? null) !== self::MAP_COORDINATES) {
            return;
        }

        $value['map_url'] = self::LEGACY_MAP_URL;
        unset($value['map_coordinates']);

        DB::table('site_settings')->where('id', $setting->id)->update([
            'setting_value' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
};
