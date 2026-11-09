<?php

namespace Tests\Unit;

use App\Services\Payments\VietQrImageUrlService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VietQrImageUrlServiceTest extends TestCase
{
    public function test_it_builds_a_vpbank_qr_url_with_server_amount_and_plain_random_transfer_reference(): void
    {
        $url = (new VietQrImageUrlService())->imageUrl(
            '001234567890',
            '150000.00',
            'VND',
            'BBPAY-A1B2C3D4E5',
        );

        $this->assertSame(
            'https://img.vietqr.io/image/VPB-001234567890-compact2.png?amount=150000&addInfo=BBPAYA1B2C3D4E5',
            $url,
        );
    }

    public function test_it_rejects_invalid_account_numbers_and_non_vnd_amounts(): void
    {
        $service = new VietQrImageUrlService();

        try {
            $service->imageUrl('abc123', '150000.00', 'VND', 'BBPAY-A1B2C3D4E5');
            $this->fail('Invalid account number should not produce a payment QR.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_account_number', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $service->imageUrl('001234567890', '150000.00', 'USD', 'BBPAY-A1B2C3D4E5');
    }
}
