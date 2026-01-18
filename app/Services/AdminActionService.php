<?php

namespace App\Services;

use App\Models\AdminAction;
use App\Models\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AdminActionService
{
    /**
     * Generate a secure admin action link
     */
    public function generateActionLink(string $action, int $resourceId, array $metadata = []): string
    {
        // Create a secure token
        $token = Str::random(64);

        // Store the action in database
        AdminAction::create([
            'action' => $action,
            'resource_type' => $this->getResourceType($action),
            'resource_id' => $resourceId,
            'token' => $token,
            'metadata' => $metadata,
            'expires_at' => Carbon::now()->addHours(24), // 24 hour expiry
            'used_at' => null,
        ]);

        // Generate signed URL
        return URL::signedRoute('admin.action', [
            'token' => $token,
            'action' => $action,
        ]);
    }

    /**
     * Validate and execute an admin action
     */
    public function executeAction(string $token, string $action): array
    {
        $adminAction = AdminAction::where('token', $token)
                                 ->where('action', $action)
                                 ->first();

        if (!$adminAction) {
            throw new \InvalidArgumentException('Invalid admin action link');
        }

        if ($adminAction->isUsed()) {
            throw new \InvalidArgumentException('Admin action link has already been used');
        }

        if ($adminAction->isExpired()) {
            throw new \InvalidArgumentException('Admin action link has expired');
        }

        // Mark as used
        $adminAction->update(['used_at' => Carbon::now()]);

        // Execute the action
        return $this->performAction($adminAction);
    }

    /**
     * Distribute campaign budget upfront when approved
     * 60% to DCD, 10% to DA/Admin (whoever referred the DCD), 30% to DAYA
     */
    private function distributeUpfrontEarnings(Campaign $campaign): void
    {
        $budget = (float) $campaign->budget;
        $dcd = $campaign->dcd;

        if (!$dcd) {
            \Log::warning('Cannot distribute upfront earnings: No DCD assigned to campaign ' . $campaign->id);
            return;
        }

        try {
            // 60% to DCD
            $dcdAmount = round($budget * 0.60, 2);
            \App\Models\Earning::create([
                'user_id' => $dcd->id,
                'campaign_id' => $campaign->id,
                'scan_id' => null,
                'amount' => $dcdAmount,
                'commission_amount' => $dcdAmount,
                'type' => 'campaign_approval',
                'description' => "DCD upfront payment (60% of budget) for campaign: {$campaign->title}",
                'status' => 'pending',
            ]);
            \Log::info("Distributed {$dcdAmount} (60%) to DCD {$dcd->id} for campaign {$campaign->id}");

            // 10% to DA or Admin (whoever referred the DCD)
            $referral = $dcd->referralsReceived()->where('type', 'da_to_dcd')->first();
            if (!$referral) {
                // Check if admin referred this DCD
                $referral = $dcd->referralsReceived()->where('type', 'admin_to_dcd')->first();
            }

            if ($referral && $referral->referrer) {
                $referrer = $referral->referrer;
                $referrerAmount = round($budget * 0.10, 2);
                $referrerType = $referrer->role === 'admin' ? 'Admin' : 'DA';
                
                \App\Models\Earning::create([
                    'user_id' => $referrer->id,
                    'campaign_id' => $campaign->id,
                    'scan_id' => null,
                    'amount' => $referrerAmount,
                    'commission_amount' => $referrerAmount,
                    'type' => 'campaign_approval',
                    'description' => "{$referrerType} upfront payment (10% of budget) for DCD referral - Campaign: {$campaign->title}",
                    'status' => 'pending',
                ]);
                \Log::info("Distributed {$referrerAmount} (10%) to {$referrerType} {$referrer->id} for campaign {$campaign->id}");
            } else {
                \Log::warning("No DA or Admin referrer found for DCD {$dcd->id} on campaign {$campaign->id}");
            }

            // 30% to DAYA (platform)
            // You can track this separately or create an earning for a system/admin user
            $dayaAmount = round($budget * 0.30, 2);
            \Log::info("DAYA platform earned {$dayaAmount} (30%) from campaign {$campaign->id}");
            
            // Optional: Create earning for platform tracking
            // Find or create a system user for DAYA platform earnings
            $systemUser = \App\Models\User::where('email', 'system@daya.africa')->first();
            if ($systemUser) {
                \App\Models\Earning::create([
                    'user_id' => $systemUser->id,
                    'campaign_id' => $campaign->id,
                    'scan_id' => null,
                    'amount' => $dayaAmount,
                    'commission_amount' => $dayaAmount,
                    'type' => 'platform_fee',
                    'description' => "Platform fee (30% of budget) for campaign: {$campaign->title}",
                    'status' => 'pending',
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to distribute upfront earnings for campaign ' . $campaign->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Get resource type from action
     */
    private function getResourceType(string $action): string
    {
        $actionMap = [
            'approve_campaign' => 'campaign',
            'reject_campaign' => 'campaign',
            'complete_campaign' => 'campaign',
            'mark_payment_complete' => 'earning',
            'send_monthly_report' => 'user',
        ];

        return $actionMap[$action] ?? 'unknown';
    }

    /**
     * Perform the actual action
     */
    private function performAction(AdminAction $adminAction): array
    {
        switch ($adminAction->action) {
            case 'approve_campaign':
                return $this->approveCampaign($adminAction->resource_id);

            case 'reject_campaign':
                return $this->rejectCampaign($adminAction->resource_id);

            case 'complete_campaign':
                return $this->completeCampaign($adminAction->resource_id);

            case 'mark_payment_complete':
                return $this->markPaymentComplete($adminAction->resource_id);

            default:
                throw new \InvalidArgumentException('Unknown admin action');
        }
    }

    /**
     * Approve a campaign
     */
    private function approveCampaign(int $campaignId): array
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'under_review') {
            throw new \InvalidArgumentException('Campaign is not under review');
        }

        $campaign->update([
            'status' => 'approved',
            'campaign_credit' => $campaign->budget, // Initialize campaign credit with full budget
        ]);

        // Distribute budget upfront: 60% DCD, 10% DA/Admin, 30% DAYA
        $this->distributeUpfrontEarnings($campaign);

        // Try to auto-match a DCD to this campaign
        $matchingService = app(\App\Services\CampaignMatchingService::class);
        $dcd = null;
        try {
            $dcd = $matchingService->assignDcd($campaign);
        } catch (\Exception $e) {
            \Log::warning('Failed to auto-match DCD: ' . $e->getMessage());
        }

        $client = $campaign->client;

        if ($dcd) {
            // Use existing DCD QR code (no need to regenerate)
            $qrFilename = $dcd->qr_code;

            try {
                \Mail::to($dcd->email)->send(new \App\Mail\CampaignApproved($campaign, $client));
            } catch (\Exception $e) {
                \Log::warning('Failed to send CampaignApproved email to DCD: ' . $e->getMessage());
            }

            // Notify the DA who referred this DCD
            $referral = $dcd->referralsReceived()->where('type', 'da_to_dcd')->first();
            if ($referral && $referral->referrer) {
                $da = $referral->referrer;
                try {
                    \Mail::to($da->email)->send(new \App\Mail\DaCampaignNotification($da, $dcd, $campaign));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send DaCampaignNotification email to DA: ' . $e->getMessage());
                }
            }
        } else {
            // No matched DCD - notify client and admins about the unassigned campaign
            try {
                \Mail::to($client->email)->send(new \App\Mail\CampaignApproved($campaign, $client));
            } catch (\Exception $e) {
                \Log::warning('Failed to send CampaignApproved email to client: ' . $e->getMessage());
            }
            // Notify admins to manually assign a DCD
            try {
                $adminUsers = \App\Models\User::where('role', 'admin')->get();
                foreach ($adminUsers as $admin) {
                    \Mail::to($admin->email)->send(new \App\Mail\AdminCampaignPending($campaign));
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to notify admin of unassigned campaign: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => 'Campaign approved successfully',
            'campaign_id' => $campaignId,
            'dcd' => $dcd ? ['id' => $dcd->id, 'name' => $dcd->name, 'email' => $dcd->email] : null,
        ];
    }

    /**
     * Reject a campaign
     */
    private function rejectCampaign(int $campaignId): array
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'under_review') {
            throw new \InvalidArgumentException('Campaign is not under review');
        }

        $campaign->update(['status' => 'rejected']);

        // Notify client
        $client = $campaign->client;
        try {
            \Mail::to($client->email)->send(new \App\Mail\CampaignRejected($campaign));
        } catch (\Exception $e) {
            \Log::warning('Failed to send CampaignRejected email to client: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Campaign rejected',
            'campaign_id' => $campaignId,
        ];
    }

    /**
     * Complete a campaign
     */
    private function completeCampaign(int $campaignId): array
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'approved') {
            throw new \InvalidArgumentException('Campaign must be approved first');
        }

        $campaign->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

        // Allocate venture shares
        $ventureShareService = app(\App\Services\VentureShareService::class);
        $dcd = $campaign->dcd;
        $ventureShareService->allocateSharesForCampaignCompletion($dcd, $campaign->budget);

        // Create earning record for DCD commission
        $commissionAmount = $campaign->budget * 0.20; // 20% commission
        $earning = \App\Models\Earning::create([
            'user_id' => $dcd->id,
            'amount' => $commissionAmount,
            'type' => 'commission',
            'description' => "Commission for completed campaign: {$campaign->title}",
            'related_id' => $campaign->id,
            'status' => 'pending',
            'month' => Carbon::now()->format('Y-m'),
        ]);

        // Notify admin of pending payment
        $this->notifyAdminOfPendingPayment($earning);

        // Notify both parties
        $client = $campaign->client;
        try {
            \Mail::to($client->email)->send(new \App\Mail\CampaignCompleted($campaign, $dcd));
        } catch (\Exception $e) {
            \Log::warning('Failed to send CampaignCompleted email to client: ' . $e->getMessage());
        }
        try {
            \Mail::to($dcd->email)->send(new \App\Mail\CampaignCompleted($campaign, $client));
        } catch (\Exception $e) {
            \Log::warning('Failed to send CampaignCompleted email to DCD: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Campaign completed successfully',
            'campaign_id' => $campaignId,
        ];
    }

    /**
     * Mark payment as complete
     */
    private function markPaymentComplete(int $earningId): array
    {
        $earning = \App\Models\Earning::findOrFail($earningId);

        if ($earning->status !== 'pending') {
            throw new \InvalidArgumentException('Payment is not pending');
        }

        $earning->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        // Notify user
        $user = $earning->user;
        try {
            \Mail::to($user->email)->send(new \App\Mail\PaymentCompleted($earning));
        } catch (\Exception $e) {
            \Log::warning('Failed to send PaymentCompleted email to user: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Payment marked as complete',
            'earning_id' => $earningId,
        ];
    }

    /**
     * Send admin notification emails
     */
    public function sendAdminNotifications(): void
    {
        // Send pending campaign notifications
        $this->sendPendingCampaignNotifications();

        // Send pending payment notifications
        $this->sendPendingPaymentNotifications();
    }

    /**
     * Notify admin of pending campaign
     */
    public function notifyAdminOfPendingCampaign(Campaign $campaign): void
    {
        // Check if we already sent a notification recently
        $recentNotification = \DB::table('admin_notifications')
                                ->where('resource_type', 'campaign')
                                ->where('resource_id', $campaign->id)
                                ->where('created_at', '>', Carbon::now()->subHours(24))
                                ->exists();

        if (!$recentNotification) {
            \Mail::to('admin@daya.com')->send(new \App\Mail\AdminCampaignPending($campaign));
            \DB::table('admin_notifications')->insert([
                'resource_type' => 'campaign',
                'resource_id' => $campaign->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Notify all admin users of pending campaign
     */
    public function notifyAllAdminsOfPendingCampaign(Campaign $campaign): void
    {
        // Update campaign status to under_review when notifying admins
        $campaign->update(['status' => 'under_review']);

        // Get all users with admin role
        $adminUsers = \App\Models\User::where('role', 'admin')->get();

        if ($adminUsers->isEmpty()) {
            \Log::warning('No admin users found to notify about pending campaign');
            return;
        }

        foreach ($adminUsers as $admin) {
            try {
                \Mail::to($admin->email)->send(new \App\Mail\AdminCampaignPending($campaign));
            } catch (\Exception $e) {
                \Log::error('Failed to send admin notification to ' . $admin->email . ': ' . $e->getMessage());
            }
        }

        // Log the notification (optional - you might want to track this differently)
        \Log::info('Notified ' . $adminUsers->count() . ' admin users about pending campaign', [
            'campaign_id' => $campaign->id,
            'admin_emails' => $adminUsers->pluck('email')->toArray(),
        ]);
    }

    /**
     * Notify admin of pending payment
     */
    public function notifyAdminOfPendingPayment(\App\Models\Earning $earning): void
    {
        // Check if we already sent a notification recently
        $recentNotification = \DB::table('admin_notifications')
                                ->where('resource_type', 'earning')
                                ->where('resource_id', $earning->id)
                                ->where('created_at', '>', Carbon::now()->subHours(24))
                                ->exists();

        if (!$recentNotification) {
            \Mail::to('admin@daya.com')->send(new \App\Mail\AdminPaymentPending($earning));
            \DB::table('admin_notifications')->insert([
                'resource_type' => 'earning',
                'resource_id' => $earning->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Send notifications for pending campaigns
     */
    private function sendPendingCampaignNotifications(): void
    {
        $pendingCampaigns = Campaign::where('status', 'pending')
                                   ->where('created_at', '<', Carbon::now()->subHours(1))
                                   ->with(['client', 'dcd'])
                                   ->get();

        foreach ($pendingCampaigns as $campaign) {
            // Check if we already sent a notification recently
            $recentNotification = \DB::table('admin_notifications')
                                    ->where('resource_type', 'campaign')
                                    ->where('resource_id', $campaign->id)
                                    ->where('created_at', '>', Carbon::now()->subHours(24))
                                    ->exists();

            if (!$recentNotification) {
                \Mail::to('admin@daya.com')->send(new \App\Mail\AdminCampaignPending($campaign));
                \DB::table('admin_notifications')->insert([
                    'resource_type' => 'campaign',
                    'resource_id' => $campaign->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Send notifications for pending payments
     */
    private function sendPendingPaymentNotifications(): void
    {
        $pendingEarnings = \App\Models\Earning::where('status', 'pending')
                                             ->where('created_at', '<', Carbon::now()->subDays(7))
                                             ->with('user')
                                             ->get();

        foreach ($pendingEarnings as $earning) {
            // Check if we already sent a notification recently
            $recentNotification = \DB::table('admin_notifications')
                                    ->where('resource_type', 'earning')
                                    ->where('resource_id', $earning->id)
                                    ->where('created_at', '>', Carbon::now()->subHours(24))
                                    ->exists();

            if (!$recentNotification) {
                \Mail::to('admin@daya.com')->send(new \App\Mail\AdminPaymentPending($earning));
                \DB::table('admin_notifications')->insert([
                    'resource_type' => 'earning',
                    'resource_id' => $earning->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}