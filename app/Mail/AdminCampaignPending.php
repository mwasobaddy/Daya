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
    public $countryName;
    public $countyName;
    public $subcountyName;
    public $wardName;
    public $currencySymbol;

    /**
     * Create a new message instance.
     */
    public function __construct($campaign)
    {
        // Ensure the client relation is loaded and set simple name/email values for the view
        $this->campaign = $campaign->loadMissing('client');
        $this->clientName = optional($this->campaign->client)->name ?? null;
        $this->clientEmail = optional($this->campaign->client)->email ?? null;

        // Load geographic data for display
        $this->loadGeographicData();
        
        // Set currency symbol
        $this->setCurrencySymbol();

        $adminActionService = app(\App\Services\AdminActionService::class);
        $this->approveUrl = $adminActionService->generateActionLink('approve_campaign', $campaign->id);
        $this->rejectUrl = $adminActionService->generateActionLink('reject_campaign', $campaign->id);
    }

    /**
     * Load geographic names from IDs stored in metadata
     */
    private function loadGeographicData()
    {
        $metadata = $this->campaign->metadata ?? [];
        
        // Get country name from target_country code
        if (isset($metadata['target_country'])) {
            $country = \App\Models\Country::where('code', $metadata['target_country'])->first();
            $this->countryName = $country ? $country->name : strtoupper($metadata['target_country']);
        }
        
        // Get county name from target_county ID
        if (isset($metadata['target_county'])) {
            $county = \App\Models\County::find($metadata['target_county']);
            $this->countyName = $county ? $county->name : 'County ID: ' . $metadata['target_county'];
        }
        
        // Get subcounty name from target_subcounty ID
        if (isset($metadata['target_subcounty'])) {
            $subcounty = \App\Models\Subcounty::find($metadata['target_subcounty']);
            $this->subcountyName = $subcounty ? $subcounty->name : 'Subcounty ID: ' . $metadata['target_subcounty'];
        }
        
        // Get ward name from target_ward ID
        if (isset($metadata['target_ward'])) {
            $ward = \App\Models\Ward::find($metadata['target_ward']);
            $this->wardName = $ward ? $ward->name : 'Ward ID: ' . $metadata['target_ward'];
        }
    }
    
    /**
     * Set currency symbol based on country code
     */
    private function setCurrencySymbol()
    {
        $metadata = $this->campaign->metadata ?? [];
        $countryCode = $metadata['target_country'] ?? 'KE';
        
        $currencyMap = [
            'KE' => 'KSh',
            'NG' => '₦',
            // Add more countries as needed
        ];
        
        $this->currencySymbol = $currencyMap[strtoupper($countryCode)] ?? '$';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $budget = number_format($this->campaign->budget, 0);
        $objective = ucwords(str_replace('_', ' ', $this->campaign->campaign_objective));
        
        return new Envelope(
            subject: "🚀 Campaign Approval Required: {$this->campaign->title} ({$this->currencySymbol}{$budget} {$objective})",
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
