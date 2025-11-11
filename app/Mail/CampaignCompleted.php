<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $otherUser; // Could be client or DCD depending on recipient

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $otherUser)
    {
        $this->campaign = $campaign;
        $this->otherUser = $otherUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Completed Successfully - ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_completed',
            with: ['campaign' => $this->campaign, 'otherUser' => $this->otherUser],
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
