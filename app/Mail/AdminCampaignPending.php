<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCampaignPending extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $approveUrl;
    public $rejectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign)
    {
        $this->campaign = $campaign;

        $adminActionService = app(\App\Services\AdminActionService::class);
        $this->approveUrl = $adminActionService->generateActionLink('approve_campaign', $campaign->id);
        $this->rejectUrl = $adminActionService->generateActionLink('reject_campaign', $campaign->id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Pending Approval - ' . $this->campaign->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_campaign_pending',
            with: [
                'campaign' => $this->campaign,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
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
