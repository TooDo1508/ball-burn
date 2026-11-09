<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SiteSettingRepository;
use Illuminate\Support\Facades\Storage;

class ContactSettingsService
{
    public function __construct(private readonly SiteSettingRepository $settings) {}

    public function publicSettings(): array
    {
        $settings = $this->adminSettings();
        unset($settings['payment_account_number']);

        return $settings;
    }

    public function adminSettings(): array
    {
        $settings = array_merge(self::defaults(), $this->storedSettings());
        unset($settings['payment_qr_path'], $settings['payment_qr_url']);

        return $settings;
    }

    public function paymentAccountNumber(): ?string
    {
        $accountNumber = trim((string) ($this->storedSettings()['payment_account_number'] ?? ''));

        return $accountNumber !== '' ? $accountNumber : null;
    }

    public function update(array $contact, int $updatedBy): void
    {
        $existing = $this->storedSettings();
        $legacyQrPath = $existing['payment_qr_path'] ?? null;
        $settings = array_merge(self::defaults(), $contact);
        unset($settings['payment_qr_path'], $settings['payment_qr_url']);

        $this->settings->updateOrCreate('contact_info', [
            'setting_value' => $settings,
            'description' => 'Public website contact details and VietQR payment account',
            'updated_by' => $updatedBy,
        ]);

        if ($legacyQrPath) {
            Storage::disk('public')->delete($legacyQrPath);
        }
    }

    public static function defaults(): array
    {
        return [
            'email' => '',
            'payment_account_number' => '',
            'phone' => '0886 688 614',
            'address' => '614 Lạc Long Quân, Tây Hồ, Hà Nội 100000',
            'business_hours' => 'Mỗi ngày · 05:00–24:00',
            'venue_description' => '8 sân pickleball và khu Bistro luôn sẵn sàng đón bạn.',
            'map_url' => 'https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9',
            'facebook_url' => 'https://www.facebook.com/ballburnvietnam',
            'instagram_url' => 'https://www.instagram.com/ballburnpickleclubbistro',
        ];
    }

    private function storedSettings(): array
    {
        return $this->settings->findByKey('contact_info')?->setting_value ?? [];
    }
}
