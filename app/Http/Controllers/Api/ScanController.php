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
     * Get QR code information for a user
     */
    public function getQRCode(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->qr_code) {
            return response()->json(['message' => 'No QR code found for this user'], 404);
        }

        $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($user->qr_code);

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $user->role,
            'qr_code_url' => $qrCodeUrl,
            'referral_code' => $user->referral_code,
        ]);
    }
}
