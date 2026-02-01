<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class AdminCampaignMatched extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $dcd;
    public $client;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $dcd, $client)
    {
        $this->campaign = $campaign;
        $this->dcd = $dcd;
        $this->client = $client;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Matched: ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_campaign_matched',
            with: [
                'campaign' => $this->campaign,
                'dcd' => $this->dcd,
                'client' => $this->client,
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
