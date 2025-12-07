<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\QRCodeService;
use Illuminate\Http\Request;

class ScanRedirectController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function handle(Request $request)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired QR code');
        }

        $dcdId = (int) $request->query('dcd');
        $campaignId = (int) $request->query('campaign');

        try {
            // Record scan and then redirect user
            $this->qrCodeService->recordCampaignScan($dcdId, $campaignId, null);

            $campaign = \App\Models\Campaign::findOrFail($campaignId);

            return redirect()->away($campaign->digital_product_link);
        } catch (\Exception $e) {
            // Log and show a friendly page or fallback
            \Log::warning('Failed to record scan redirect: ' . $e->getMessage());
            abort(400, 'Invalid scan');
        }
    }

    /**
     * Handle DCD-specific QR scan with smart campaign selection
     */
    public function handleDcd(Request $request)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired QR code');
        }

        $dcdId = (int) $request->query('dcd');

        try {
            // Use smart campaign selection and record scan
            $result = $this->qrCodeService->recordDcdScan($dcdId, null);
            $campaign = $result['campaign'];

            return redirect()->away($campaign->digital_product_link);
        } catch (\Exception $e) {
            // Log and show user-friendly message
            \Log::warning('DCD QR scan error: ' . $e->getMessage(), [
                'dcd_id' => $dcdId,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);

            // Show "no active campaigns" message using a simple view
            return response()->view('errors.no-active-campaigns', [
                'message' => 'No active campaigns right now, try again later'
            ], 404);
        }
    }
}
