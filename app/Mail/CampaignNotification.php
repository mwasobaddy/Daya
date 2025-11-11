<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $client;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign, $type = 'new_campaign', $client = null)
    {
        $this->campaign = $campaign;
        $this->type = $type;
        $this->client = $client ?? $campaign->client;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'duplicate_attempt' => 'Campaign Submission Blocked - Active Campaign Exists',
            'new_campaign' => 'New Campaign Submitted - ' . $this->campaign->title,
            default => 'Campaign Notification - ' . $this->campaign->title,
        };

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_notification',
            with: [
                'campaign' => $this->campaign,
                'client' => $this->client,
                'type' => $this->type
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
