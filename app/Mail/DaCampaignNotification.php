<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DaCampaignNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $da;
    public $dcd;
    public $campaign;

    /**
     * Create a new message instance.
     */
    public function __construct($da, $dcd, $campaign)
    {
        $this->da = $da;
        $this->dcd = $dcd;
        $this->campaign = $campaign;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A DCD in Your Network Has a New Campaign 🎉',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.da_campaign_notification',
            with: [
                'da' => $this->da,
                'dcd' => $this->dcd,
                'campaign' => $this->campaign,
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