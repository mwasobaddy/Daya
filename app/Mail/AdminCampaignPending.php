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
    public $clientName;
    public $clientEmail;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign)
    {
        // Ensure the client relation is loaded and set simple name/email values for the view
        $this->campaign = $campaign->loadMissing('client');
        $this->clientName = optional($this->campaign->client)->name ?? null;
        $this->clientEmail = optional($this->campaign->client)->email ?? null;

        $adminActionService = app(\App\Services\AdminActionService::class);
        $this->approveUrl = $adminActionService->generateActionLink('approve_campaign', $campaign->id);
        $this->rejectUrl = $adminActionService->generateActionLink('reject_campaign', $campaign->id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $budget = number_format($this->campaign->budget, 0);
        $objective = ucwords(str_replace('_', ' ', $this->campaign->campaign_objective));
        
        return new Envelope(
            subject: "🚀 Campaign Approval Required: {$this->campaign->title} (\${$budget} {$objective})",
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
                'clientName' => $this->clientName,
                'clientEmail' => $this->clientEmail,
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
