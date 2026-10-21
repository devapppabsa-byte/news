<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailTest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{address: string, name: string}|array{}  $mailFrom
     */
    public function __construct(
        public readonly array $mailFrom = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->mailFrom === [] ? null : new Address($this->mailFrom['address'], $this->mailFrom['name']),
            subject: __('News Monitor test email'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.test',
            with: [
                'appName' => config('app.name'),
                'sentAt' => now()->toDateTimeString(),
            ],
        );
    }
}
