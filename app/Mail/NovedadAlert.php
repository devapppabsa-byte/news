<?php

namespace App\Mail;

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovedadAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly MonitoredTopic $topic,
        public readonly NewsItem $item,
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
            subject: __('New novedad: :title', ['title' => $this->item->title]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.novedades.alert',
            with: [
                'topicName' => $this->topic->name,
                'title' => $this->item->title,
                'summary' => $this->item->body ?: $this->item->ai_summary,
                'source' => $this->item->source_domain,
                'url' => $this->item->url,
                'relevance' => $this->item->relevance,
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
