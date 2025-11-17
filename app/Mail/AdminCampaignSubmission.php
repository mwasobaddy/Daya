<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCampaignSubmission extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $client;
    public $referrer;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $client, $referrer = null)
    {
        $this->campaign = $campaign;
        $this->client = $client;
        $this->referrer = $referrer;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Campaign Submission - ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_campaign_submission',
            with: [
                'campaign' => $this->campaign,
                'client' => $this->client,
                'referrer' => $this->referrer,
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