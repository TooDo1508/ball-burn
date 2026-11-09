<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepository;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ContactSettingsServiceTest extends TestCase
{
    public function test_public_settings_exposes_a_url_for_the_shared_payment_qr_without_exposing_its_storage_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-qr/current.png', 'qr-image');

        $repository = Mockery::mock(SiteSettingRepository::class);
        $repository->shouldReceive('findByKey')
            ->once()
            ->with('contact_info')
            ->andReturn(new SiteSetting(['setting_value' => ['payment_qr_path' => 'payment-qr/current.png']]));

        $settings = (new ContactSettingsService($repository))->publicSettings();

        $this->assertSame(Storage::disk('public')->url('payment-qr/current.png'), $settings['payment_qr_url']);
        $this->assertArrayNotHasKey('payment_qr_path', $settings);
    }

    public function test_admin_can_replace_the_shared_payment_qr_and_the_old_file_is_removed(): void
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
                $path = $attributes['setting_value']['payment_qr_path'] ?? null;

                return is_string($path)
                    && str_starts_with($path, 'payment-qr/')
                    && $attributes['description'] === 'Public website contact details and payment QR code';
            }))
            ->andReturn(new SiteSetting());

        (new ContactSettingsService($repository))->update(
            ContactSettingsService::defaults(),
            7,
            UploadedFile::fake()->create('bank-qr.png', 50, 'image/png'),
        );

        Storage::disk('public')->assertMissing('payment-qr/old.png');
        $this->assertCount(1, Storage::disk('public')->allFiles('payment-qr'));
    }

    public function test_admin_can_remove_the_shared_payment_qr(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-qr/current.png', 'qr-image');

        $repository = Mockery::mock(SiteSettingRepository::class);
        $repository->shouldReceive('findByKey')
            ->once()
            ->with('contact_info')
            ->andReturn(new SiteSetting(['setting_value' => ['payment_qr_path' => 'payment-qr/current.png']]));
        $repository->shouldReceive('updateOrCreate')
            ->once()
            ->with('contact_info', Mockery::on(fn (array $attributes): bool => $attributes['setting_value']['payment_qr_path'] === null))
            ->andReturn(new SiteSetting());

        (new ContactSettingsService($repository))->update(ContactSettingsService::defaults(), 7, null, true);

        Storage::disk('public')->assertMissing('payment-qr/current.png');
    }
}
