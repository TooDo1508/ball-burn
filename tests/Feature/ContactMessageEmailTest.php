<?php

namespace Tests\Feature;

use App\Jobs\SendContactMessageAdminNotification;
use App\Jobs\SendContactMessageAcknowledgement;
use App\Mail\ContactMessageReceived;
use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepository;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ContactMessageEmailTest extends TestCase
{
    public function test_successful_contact_submission_queues_an_acknowledgement_for_the_sender(): void
    {
        Queue::fake();

        $message = new ContactMessage([
            'name' => 'Nguyễn An',
            'email' => 'an@example.com',
            'subject' => 'Tư vấn đặt sân',
            'message' => 'Tôi muốn hỏi về lịch sân.',
            'status' => 'NEW',
        ]);

        $repository = Mockery::mock(ContactMessageRepository::class);
        $repository->shouldReceive('create')->once()->andReturn($message);
        $this->app->instance(ContactMessageRepository::class, $repository);

        $settings = Mockery::mock(ContactSettingsService::class);
        $settings->shouldReceive('publicSettings')->once()->andReturn(['email' => 'admin@ballburn.test']);
        $this->app->instance(ContactSettingsService::class, $settings);

        $response = $this->postJson('/contact', [
            'name' => 'Nguyễn An',
            'email' => 'an@example.com',
            'subject' => 'Tư vấn đặt sân',
            'message' => 'Tôi muốn hỏi về lịch sân.',
        ]);

        $response->assertCreated();
        Queue::assertPushed(SendContactMessageAcknowledgement::class, fn (SendContactMessageAcknowledgement $job): bool =>
            $job->contactMessage->email === 'an@example.com'
        );
        Queue::assertPushed(SendContactMessageAdminNotification::class, fn (SendContactMessageAdminNotification $job): bool =>
            $job->contactMessage->email === 'an@example.com'
                && $job->recipient === 'admin@ballburn.test'
        );
    }

    public function test_acknowledgement_job_sends_the_vietnamese_email_to_the_contact_email(): void
    {
        Mail::fake();

        $message = new ContactMessage([
            'name' => 'Nguyễn An',
            'email' => 'an@example.com',
        ]);

        (new SendContactMessageAcknowledgement($message))->handle();

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail): bool {
            return $mail->hasTo('an@example.com')
                && str_contains($mail->render(), 'Cảm ơn Nguyễn An đã liên hệ!');
        });
    }

    public function test_admin_notification_job_sends_the_inquiry_to_the_configured_website_email(): void
    {
        Mail::fake();

        $message = new ContactMessage([
            'name' => 'Nguyễn An',
            'email' => 'an@example.com',
            'phone' => '0900000000',
            'subject' => 'Tư vấn đặt sân',
            'message' => 'Tôi muốn hỏi về lịch sân.',
        ]);

        (new SendContactMessageAdminNotification($message, 'admin@ballburn.test'))->handle();

        Mail::assertSent(NewContactMessageReceived::class, function (NewContactMessageReceived $mail): bool {
            $rendered = $mail->render();

            return $mail->hasTo('admin@ballburn.test')
                && str_contains($rendered, 'Nguyễn An')
                && str_contains($rendered, 'Tôi muốn hỏi về lịch sân.');
        });
    }
}
