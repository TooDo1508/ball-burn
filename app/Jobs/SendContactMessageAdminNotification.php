<?php

namespace App\Jobs;

use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendContactMessageAdminNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 180];

    public function __construct(
        public ContactMessage $contactMessage,
        public string $recipient,
    ) {}

    public function handle(): void
    {
        Mail::to($this->recipient)
            ->send(new NewContactMessageReceived($this->contactMessage));
    }
}
