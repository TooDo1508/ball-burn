<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactMessageReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: 'Liên hệ mới từ website BALLBURN',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.admin-notification',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
