<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SiteSettingRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ContactSettingsService
{
    public function __construct(private readonly SiteSettingRepository $settings) {}

    public function publicSettings(): array
    {
        $settings = array_merge(
            self::defaults(),
            $this->settings->findByKey('contact_info')?->setting_value ?? [],
        );

        $paymentQrPath = $settings['payment_qr_path'] ?? null;
        unset($settings['payment_qr_path']);
        $settings['payment_qr_url'] = $paymentQrPath
            ? Storage::disk('public')->url($paymentQrPath)
            : null;

        return $settings;
    }

    public function update(
        array $contact,
        int $updatedBy,
        ?UploadedFile $paymentQr = null,
        bool $removePaymentQr = false,
    ): void
    {
        $existing = $this->settings->findByKey('contact_info')?->setting_value ?? [];
        $oldPaymentQrPath = $existing['payment_qr_path'] ?? null;
        $paymentQrPath = $oldPaymentQrPath;
        $newPaymentQrPath = null;

        if ($paymentQr) {
            $newPaymentQrPath = $paymentQr->store('payment-qr', 'public');
            if (! $newPaymentQrPath) {
                throw new RuntimeException('Không thể lưu ảnh QR thanh toán.');
            }
            $paymentQrPath = $newPaymentQrPath;
        } elseif ($removePaymentQr) {
            $paymentQrPath = null;
        }

        try {
            $this->settings->updateOrCreate('contact_info', [
                'setting_value' => array_merge(self::defaults(), $contact, ['payment_qr_path' => $paymentQrPath]),
                'description' => 'Public website contact details and payment QR code',
                'updated_by' => $updatedBy,
            ]);
        } catch (Throwable $exception) {
            if ($newPaymentQrPath) {
                Storage::disk('public')->delete($newPaymentQrPath);
            }

            throw $exception;
        }

        if ($oldPaymentQrPath && $oldPaymentQrPath !== $paymentQrPath) {
            Storage::disk('public')->delete($oldPaymentQrPath);
        }
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
