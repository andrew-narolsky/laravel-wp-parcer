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

    public int $total;
    public int $working;
    public int $broken;

    /** @param Collection<\App\DTO\LinkCheckResult> $results */
    public function __construct(public readonly Collection $results)
    {
        $this->total   = $results->count();
        $this->working = $results->filter->isWorking()->count();
        $this->broken  = $results->reject->isWorking()->count();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Links Analysis Report — {$this->broken} broken of {$this->total}");
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
