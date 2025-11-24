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

        $campaign->update(['status' => 'approved']);

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
            // Generate campaign-specific QR code and attach to mail
            $qrCodeService = app(\App\Services\QRCodeService::class);
            try {
                $qrCodeBase64 = $qrCodeService->generateDcdCampaignQr($dcd, $campaign);
                // Store QR in campaign metadata for future reference
                $metadata = $campaign->metadata ?? [];
                $metadata['dcd_qr'] = $qrCodeBase64;
                $campaign->metadata = $metadata;
                $campaign->save();
            } catch (\Exception $e) {
                \Log::warning('Failed to generate campaign QR: ' . $e->getMessage());
                $qrCodeBase64 = null;
            }

            try {
                \Mail::to($dcd->email)->send(new \App\Mail\CampaignApproved($campaign, $client, $qrCodeBase64 ?? null));
            } catch (\Exception $e) {
                \Log::warning('Failed to send CampaignApproved email to DCD: ' . $e->getMessage());
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