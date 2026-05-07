<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class QuoteRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string[]  $attachments
     */
    public function __construct(
        public QuoteRequest $quoteRequest,
        public array $attachmentPaths = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de presupuesto: ' . $this->quoteRequest->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-request-notification',
            with: [
                'attachments' => $this->attachmentPaths,
            ],
        );
    }

    /**
     * @return Attachment[]
     */
    public function attachments(): array
    {
        return collect($this->attachmentPaths)
            ->filter(fn (string $path) => Storage::disk('public')->exists($path))
            ->map(fn (string $path) => Attachment::fromPath(Storage::disk('public')->path($path))
                ->as(basename($path)))
            ->values()
            ->all();
    }
}
