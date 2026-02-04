<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Record a scan event when a client scans a DCD QR code
     */
    public function recordScan(Request $request)
    {
        $request->validate([
            'dcd_id' => 'required|integer|exists:users,id',
        ]);

        try {
            // Get client location data if available
            $geoData = null;
            if ($request->has(['latitude', 'longitude'])) {
                $geoData = [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy' => $request->accuracy,
                ];
            }

            $scan = $this->qrCodeService->recordScan(
                $request->dcd_id,
                $request->ip(),
                $geoData
            );

            return response()->json([
                'message' => 'Scan recorded successfully',
                'scan_id' => $scan->id,
                'scanned_at' => $scan->scanned_at,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get scan statistics for a DCD (requires authentication in production)
     */
    public function getScanStats(Request $request, $userId)
    {
        $request->validate([
            'admin_token' => 'required|string', // Simple auth for demo
        ]);

        // Simple admin authentication
        if (!hash_equals(hash('sha256', 'daya_admin_2024'), hash('sha256', $request->admin_token))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::findOrFail($userId);

        if ($user->role !== 'dcd') {
            return response()->json(['message' => 'User is not a DCD'], 400);
        }

        $stats = $this->qrCodeService->getScanStats($user);

        return response()->json($stats);
    }

    /**
     * Get admin scan statistics
     */
    public function getAdminScanStats(Request $request)
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        // Simple admin authentication
        if (!hash_equals(hash('sha256', 'daya_admin_2024'), hash('sha256', $request->admin_token))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $stats = $this->qrCodeService->getAdminScanStats();

        return response()->json($stats);
    }

    /**
     * Regenerate QR code for a user
     */
    public function regenerateQRCode(Request $request, $userId)
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        // Simple admin authentication
        if (!hash_equals(hash('sha256', 'daya_admin_2024'), hash('sha256', $request->admin_token))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::findOrFail($userId);

        try {
            $qrCodeFilename = $this->qrCodeService->regenerateQRCode($user);
            $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

            return response()->json([
                'message' => 'QR code regenerated successfully',
                'qr_code_url' => $qrCodeUrl,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Record a scan event with device fingerprinting
     */
    public function recordScanWithFingerprint(Request $request)
    {
        $request->validate([
            'dcd_id' => 'required|integer|exists:users,id',
            'fingerprint' => 'nullable|string',
            'signature' => 'nullable|string', // Signature already validated when serving the page
        ]);

        try {
            $dcdId = $request->dcd_id;
            $campaignId = $request->campaign_id;

            // Prepare geoData with fingerprint
            $geoData = [
                'fingerprint' => $request->fingerprint,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $redirectUrl = null;

            if ($campaignId) {
                // Direct campaign scan
                $scan = $this->qrCodeService->recordCampaignScan($dcdId, $campaignId, $geoData);
                $campaign = \App\Models\Campaign::findOrFail($campaignId);
                $redirectUrl = $campaign->digital_product_link;
            } else {
                // DCD smart selection scan
                $result = $this->qrCodeService->recordDcdScan($dcdId, $geoData);
                $campaign = $result['campaign'];
                $redirectUrl = $campaign->digital_product_link;
            }

            return response()->json([
                'message' => 'Scan recorded successfully',
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\InvalidArgumentException $e) {
            $errorType = 'general_error';
            $message = $e->getMessage();
            
            if (str_contains($message, 'No active campaigns found for this DCD')) {
                $errorType = 'no_campaigns';
            } elseif (str_contains($message, 'Campaign has reached its budget limit')) {
                $errorType = 'budget_exhausted';
            }
            
            return response()->json([
                'message' => $message,
                'error_type' => $errorType
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Fingerprint scan recording failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Scan recording failed',
                'error_type' => 'system_error'
            ], 500);
        }
    }
}
