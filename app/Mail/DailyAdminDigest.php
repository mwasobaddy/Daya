<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyAdminDigest extends Mailable
{
    use Queueable, SerializesModels;

    public array $digestData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $digestData)
    {
        $this->digestData = $digestData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $scansCount = $this->digestData['scans']['total'] ?? 0;
        $campaignsCount = $this->digestData['campaigns']['new_count'] ?? 0;
        $date = $this->digestData['date'] ?? date('M d, Y');

        return new Envelope(
            subject: "Daya Daily Report - {$date} | {$scansCount} Scans | {$campaignsCount} New Campaigns",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_admin_digest',
            with: [
                'data' => $this->digestData,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
