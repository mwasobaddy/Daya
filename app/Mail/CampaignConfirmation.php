<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $dcd;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $dcd)
    {
        $this->campaign = $campaign;
        $this->dcd = $dcd;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Submitted Successfully - ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_confirmation',
            with: ['campaign' => $this->campaign, 'dcd' => $this->dcd],
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
