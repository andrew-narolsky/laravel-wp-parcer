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

    /** @param Collection<\App\Models\Link> $links */
    public function __construct(public readonly Collection $links) {}

    public function envelope(): Envelope
    {
        $broken = $this->links->where('check_status', 'not_found')->count();
        $total  = $this->links->count();

        return new Envelope(subject: "Links Analysis Report — {$broken} broken of {$total}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.links-report');
    }
}
