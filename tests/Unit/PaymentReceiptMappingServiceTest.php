<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentMappingRepository;
use App\Services\Payments\PaymentReceiptMappingService;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PaymentReceiptMappingServiceTest extends TestCase
{
    public function test_exact_reference_amount_and_currency_map_to_the_pending_booking_without_marking_it_paid(): void
    {
        $payment = $this->payment();
        $repository = Mockery::mock(PaymentMappingRepository::class);
        $repository->shouldReceive('findByPaymentReference')->once()->with('BBPAYABC123')->andReturn(new Collection([$payment]));
        $repository->shouldReceive('hasBankTransactionId')->once()->with('VPB-12345')->andReturnFalse();

        $result = $this->service($repository)->map([
            'payment_code' => ' bbpay-abc123 ',
            'amount' => '75000',
            'currency' => 'vnd',
            'recipient_account' => '00 1234-567890',
            'transaction_id' => 'VPB-12345',
        ]);

        $this->assertSame('MATCHED', $result['status'], json_encode($result));
        $this->assertSame(31, $result['payment_id']);
        $this->assertSame('BB-ORDER-123', $result['booking_order_code']);
        $this->assertSame('MATCHED', $result['checks']['amount']['status']);
        $this->assertSame('MATCHED', $result['checks']['recipient_account']['status']);
        $this->assertSame('********7890', $result['checks']['recipient_account']['expected']);
        $this->assertSame('********7890', $result['checks']['recipient_account']['observed']);
        $this->assertFalse($result['payment_can_be_marked_paid']);
        $this->assertSame('PENDING', $payment->status);
    }

    public function test_amount_mismatch_requires_review(): void
    {
        $payment = $this->payment();
        $repository = Mockery::mock(PaymentMappingRepository::class);
        $repository->shouldReceive('findByPaymentReference')->once()->andReturn(new Collection([$payment]));

        $result = $this->service($repository)->map([
            'payment_reference' => 'BBPAY-ABC123',
            'amount' => '70000.00',
            'currency' => 'VND',
        ]);

        $this->assertSame('NEEDS_REVIEW', $result['status']);
        $this->assertContains('amount_mismatch', $result['reasons']);
        $this->assertFalse($result['payment_can_be_marked_paid']);
    }

    public function test_ocr_recipient_account_mismatch_requires_review(): void
    {
        $repository = Mockery::mock(PaymentMappingRepository::class);
        $repository->shouldReceive('findByPaymentReference')->once()->andReturn(new Collection([$this->payment()]));

        $result = $this->service($repository)->map([
            'payment_reference' => 'BBPAY-ABC123',
            'amount' => '75000.00',
            'currency' => 'VND',
            'recipient_account' => '009999999999',
        ]);

        $this->assertSame('NEEDS_REVIEW', $result['status']);
        $this->assertContains('recipient_account_mismatch', $result['reasons']);
    }

    public function test_unknown_reference_and_ambiguous_reference_require_review(): void
    {
        $repository = Mockery::mock(PaymentMappingRepository::class);
        $repository->shouldReceive('findByPaymentReference')->once()->with('BBPAYMISSING')->andReturn(new Collection());
        $repository->shouldReceive('findByPaymentReference')->once()->with('BBPAYDUPLICATE')->andReturn(new Collection([$this->payment(), $this->payment()]));

        $service = $this->service($repository);
        $unknown = $service->map(['payment_reference' => 'BBPAY-MISSING']);
        $ambiguous = $service->map(['payment_reference' => 'BBPAY-DUPLICATE']);

        $this->assertSame(['payment_reference_unknown'], $unknown['reasons']);
        $this->assertSame(['payment_reference_ambiguous'], $ambiguous['reasons']);
    }

    public function test_duplicate_bank_transaction_and_expired_payment_cannot_be_auto_matched(): void
    {
        $payment = $this->payment(now('UTC')->subSecond());
        $repository = Mockery::mock(PaymentMappingRepository::class);
        $repository->shouldReceive('findByPaymentReference')->once()->andReturn(new Collection([$payment]));
        $repository->shouldReceive('hasBankTransactionId')->once()->with('VPB-12345')->andReturnTrue();

        $result = $this->service($repository)->map([
            'payment_reference' => 'BBPAY-ABC123',
            'amount' => '75000.00',
            'currency' => 'VND',
            'transaction_id' => 'VPB-12345',
        ]);

        $this->assertSame('NEEDS_REVIEW', $result['status']);
        $this->assertContains('transaction_id_already_recorded', $result['reasons']);
        $this->assertContains('payment_window_expired', $result['reasons'], json_encode($result));
        $this->assertFalse($result['payment_can_be_marked_paid']);
    }

    private function payment($deadline = null): Payment
    {
        $order = new BookingOrder([
            'booking_code' => 'BB-ORDER-123',
            'status' => 'PENDING_PAYMENT',
            'payment_deadline_at' => $deadline ?? now('UTC')->addMinutes(5),
        ]);
        $booking = new Booking();
        $booking->setRelation('order', $order);
        $payment = new Payment([
            'amount' => '75000.00',
            'currency' => 'VND',
            'qr_payload' => 'BBPAY-ABC123',
            'provider_metadata' => ['payment_reference' => 'BBPAY-ABC123'],
        ]);
        $payment->forceFill(['status' => 'PENDING']);
        $payment->id = 31;
        $payment->setRelation('booking', $booking);

        return $payment;
    }

    private function service(PaymentMappingRepository $repository): PaymentReceiptMappingService
    {
        $settings = Mockery::mock(ContactSettingsService::class);
        $settings->shouldReceive('paymentAccountNumber')->andReturn('001234567890');

        return new PaymentReceiptMappingService($repository, $settings);
    }
}
