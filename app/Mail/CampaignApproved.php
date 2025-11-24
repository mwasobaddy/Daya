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
    public $qrCodeBase64;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $client, $qrCodeBase64 = null)
    {
        $this->campaign = $campaign;
        $this->client = $client;
        $this->qrCodeBase64 = $qrCodeBase64;
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
        if ($this->qrCodeBase64) {
            return [
                Attachment::fromData(base64_decode($this->qrCodeBase64), 'campaign-qr.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
