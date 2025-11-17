<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class CampaignApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $client;
    public $qrFilename;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $client, $qrFilename = null)
    {
        $this->campaign = $campaign;
        $this->client = $client;
        $this->qrFilename = $qrFilename;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Approved - Start Working on: ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_approved',
            with: ['campaign' => $this->campaign, 'client' => $this->client],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->qrFilename) {
            return [Attachment::fromStorageDisk('public', $this->qrFilename)->as('campaign-qr.svg')];
        }
        return [];
    }
}
