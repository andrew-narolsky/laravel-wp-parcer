<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class LinksReportMail extends Mailable
{
    use Queueable;

    /** @param Collection<\App\DTO\LinkCheckResult> $results */
    public function __construct(public readonly Collection $results) {}

    public function envelope(): Envelope
    {
        $broken = $this->results->reject->isWorking()->count();
        $total  = $this->results->count();

        return new Envelope(subject: "Links Analysis Report — {$broken} broken of {$total}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.links-report');
    }

    public function attachments(): array
    {
        return [];
    }
}
