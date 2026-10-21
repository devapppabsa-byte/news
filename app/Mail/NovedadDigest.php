<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NovedadDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    public function __construct(
        public readonly string $userName,
        public readonly Collection $items,
        public readonly string $period,
        /** @var array{address: string, name: string}|array{} */
        public readonly array $mailFrom = [],
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->mailFrom === [] ? null : new Address($this->mailFrom['address'], $this->mailFrom['name']),
            subject: __('Your novedades summary for :period', ['period' => $this->period]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.novedades.digest',
            with: [
                'userName' => $this->userName,
                'items' => $this->items,
                'period' => $this->period,
                'count' => $this->items->count(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
