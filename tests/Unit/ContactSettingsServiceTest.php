<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepository;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ContactSettingsServiceTest extends TestCase
{
    public function test_public_settings_hides_the_payment_account_number(): void
    {
        $repository = Mockery::mock(SiteSettingRepository::class);
        $repository->shouldReceive('findByKey')
            ->once()
            ->with('contact_info')
            ->andReturn(new SiteSetting(['setting_value' => [
                'email' => 'hello@ballburn.vn',
                'payment_account_number' => '001234567890',
            ]]));

        $settings = (new ContactSettingsService($repository))->publicSettings();

        $this->assertSame('hello@ballburn.vn', $settings['email']);
        $this->assertArrayNotHasKey('payment_account_number', $settings);
    }

    public function test_admin_settings_and_payment_service_can_read_the_saved_account_number(): void
    {
        $repository = Mockery::mock(SiteSettingRepository::class);
        $repository->shouldReceive('findByKey')
            ->twice()
            ->with('contact_info')
            ->andReturn(new SiteSetting(['setting_value' => ['payment_account_number' => '001234567890']]));

        $settings = new ContactSettingsService($repository);

        $this->assertSame('001234567890', $settings->adminSettings()['payment_account_number']);
        $this->assertSame('001234567890', $settings->paymentAccountNumber());
    }

    public function test_update_saves_account_number_and_removes_a_legacy_uploaded_qr(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-qr/old.png', 'old-image');

        $repository = Mockery::mock(SiteSettingRepository::class);
        $repository->shouldReceive('findByKey')
            ->once()
            ->with('contact_info')
            ->andReturn(new SiteSetting(['setting_value' => ['payment_qr_path' => 'payment-qr/old.png']]));
        $repository->shouldReceive('updateOrCreate')
            ->once()
            ->with('contact_info', Mockery::on(function (array $attributes): bool {
                return $attributes['setting_value']['payment_account_number'] === '001234567890'
                    && ! array_key_exists('payment_qr_path', $attributes['setting_value'])
                    && $attributes['description'] === 'Public website contact details and VietQR payment account';
            }))
            ->andReturn(new SiteSetting());

        (new ContactSettingsService($repository))->update([
            ...ContactSettingsService::defaults(),
            'payment_account_number' => '001234567890',
        ], 7);

        Storage::disk('public')->assertMissing('payment-qr/old.png');
    }
}
