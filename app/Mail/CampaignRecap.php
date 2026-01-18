<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Earning;

class CampaignRecap extends Mailable
{
    use Queueable, SerializesModels;

    public Campaign $campaign;
    public User $recipient;
    public string $recipientType;
    public array $stats;

    /**
     * Create a new message instance.
     */
    public function __construct(Campaign $campaign, User $recipient, string $recipientType)
    {
        $this->campaign = $campaign;
        $this->recipient = $recipient;
        $this->recipientType = $recipientType; // 'client', 'dcd', 'da', 'admin'
        $this->stats = $this->calculateStats();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Campaign Completed: {$this->campaign->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_recap',
            with: [
                'campaign' => $this->campaign,
                'recipient' => $this->recipient,
                'recipientType' => $this->recipientType,
                'stats' => $this->stats,
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

    /**
     * Calculate campaign statistics
     */
    private function calculateStats(): array
    {
        $campaign = $this->campaign;
        
        // Get earnings for this recipient
        $recipientEarnings = Earning::where('campaign_id', $campaign->id)
            ->where('user_id', $this->recipient->id)
            ->whereIn('type', ['campaign_approval', 'commission'])
            ->sum('amount');

        // Calculate budget utilization
        $budgetUtilization = $campaign->budget > 0 
            ? round(($campaign->spent_amount / $campaign->budget) * 100, 1)
            : 0;

        // Calculate average cost per scan
        $avgCostPerScan = $campaign->total_scans > 0
            ? round($campaign->spent_amount / $campaign->total_scans, 2)
            : 0;

        // Get campaign duration
        $duration = null;
        if ($campaign->created_at && $campaign->completed_at) {
            $duration = $campaign->created_at->diffInDays($campaign->completed_at);
        }

        return [
            'total_scans' => $campaign->total_scans,
            'budget' => $campaign->budget,
            'spent_amount' => $campaign->spent_amount,
            'remaining_credit' => $campaign->campaign_credit,
            'budget_utilization' => $budgetUtilization,
            'avg_cost_per_scan' => $avgCostPerScan,
            'max_scans' => $campaign->max_scans,
            'recipient_earnings' => $recipientEarnings,
            'duration_days' => $duration,
            'completed_at' => $campaign->completed_at,
        ];
    }
}
