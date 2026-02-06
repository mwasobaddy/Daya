<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    protected $scanService;

    public function __construct(ScanService $scanService)
    {
        $this->scanService = $scanService;
    }

    /**
     * Record a scan event when a client scans a DCD QR code
     */
    public function recordScan(Request $request): JsonResponse
    {
        $request->validate([
            'dcd_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $geoData = null;
            if ($request->has(['latitude', 'longitude'])) {
                $geoData = [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy' => $request->accuracy,
                ];
            }

            $scan = $this->scanService->recordScan(
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
    public function getScanStats(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        if (! $this->scanService->authenticateAdmin($request->admin_token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::findOrFail($userId);

        try {
            $stats = $this->scanService->getScanStats($user);

            return response()->json($stats);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get admin scan statistics
     */
    public function getAdminScanStats(Request $request): JsonResponse
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        if (! $this->scanService->authenticateAdmin($request->admin_token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $stats = $this->scanService->getAdminScanStats();

        return response()->json($stats);
    }

    /**
     * Regenerate QR code for a user
     */
    public function regenerateQRCode(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        if (! $this->scanService->authenticateAdmin($request->admin_token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::findOrFail($userId);

        try {
            $result = $this->scanService->regenerateQRCode($user);

            return response()->json([
                'message' => 'QR code regenerated successfully',
                'qr_code_url' => $result['qr_code_url'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Record a scan event with device fingerprinting
     */
    public function recordScanWithFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'dcd_id' => 'required|integer|exists:users,id',
            'fingerprint' => 'nullable|string',
            'signature' => 'nullable|string',
        ]);

        try {
            $data = [
                'dcd_id' => $request->dcd_id,
                'campaign_id' => $request->campaign_id,
                'fingerprint' => $request->fingerprint,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->scanService->recordScanWithFingerprint($data);

            return response()->json([
                'message' => 'Scan recorded successfully',
                'redirect_url' => $result['redirect_url'],
            ]);
        } catch (\InvalidArgumentException $e) {
            $errorType = $this->scanService->determineScanErrorType($e);

            return response()->json([
                'message' => $e->getMessage(),
                'error_type' => $errorType,
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Fingerprint scan recording failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Scan recording failed',
                'error_type' => 'system_error',
            ], 500);
        }
    }
}
