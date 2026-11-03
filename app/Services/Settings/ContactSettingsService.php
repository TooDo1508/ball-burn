<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SiteSettingRepository;

class ContactSettingsService
{
    public function __construct(private readonly SiteSettingRepository $settings) {}

    public function publicSettings(): array
    {
        return array_merge(
            self::defaults(),
            $this->settings->findByKey('contact_info')?->setting_value ?? [],
        );
    }

    public function update(array $contact, int $updatedBy): void
    {
        $this->settings->updateOrCreate('contact_info', [
            'setting_value' => array_merge(self::defaults(), $contact),
            'description' => 'Public website contact details and primary contact email',
            'updated_by' => $updatedBy,
        ]);
    }

    public static function defaults(): array
    {
        return [
            'email' => '',
            'phone' => '0886 688 614',
            'address' => '614 Lạc Long Quân, Tây Hồ, Hà Nội 100000',
            'business_hours' => 'Mỗi ngày · 05:00–24:00',
            'venue_description' => '8 sân pickleball và khu Bistro luôn sẵn sàng đón bạn.',
            'map_url' => 'https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9',
            'facebook_url' => 'https://www.facebook.com/ballburnvietnam',
            'instagram_url' => 'https://www.instagram.com/ballburnpickleclubbistro',
        ];
    }
}
