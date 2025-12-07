<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DCDCampaignSelectionService;
use App\Services\QRCodeService;
use App\Models\User;
use Illuminate\Http\Request;

class DCDScanController extends Controller
{
    protected $campaignSelectionService;
    protected $qrCodeService;

    public function __construct(DCDCampaignSelectionService $campaignSelectionService, QRCodeService $qrCodeService)
    {
        $this->campaignSelectionService = $campaignSelectionService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Handle DCD-specific QR code scans
     * URL: /scan/dcd/{dcd_id}
     */
    public function handleDCDScan(Request $request, $dcdId)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            return $this->showErrorPage('Invalid or expired QR code');
        }

        try {
            $dcd = User::where('id', $dcdId)->where('role', 'dcd')->firstOrFail();
        } catch (\Exception $e) {
            return $this->showErrorPage('Invalid DCD code');
        }

        // Find active campaign for this DCD
        $activeCampaign = $this->campaignSelectionService->getActiveCampaignForDCD($dcd);

        if (!$activeCampaign) {
            $message = $this->campaignSelectionService->getNoActiveCampaignMessage();
            return $this->showErrorPage($message);
        }

        try {
            // Record the scan
            $this->qrCodeService->recordCampaignScan($dcd->id, $activeCampaign->id, null);

            // Redirect to campaign's digital product
            return redirect()->away($activeCampaign->digital_product_link);
        } catch (\Exception $e) {
            \Log::warning('DCDScanController: Failed to record scan', [
                'dcd_id' => $dcd->id,
                'campaign_id' => $activeCampaign->id,
                'error' => $e->getMessage()
            ]);
            return $this->showErrorPage('Unable to process scan at this time');
        }
    }

    /**
     * Show a simple error page for scan issues
     */
    private function showErrorPage(string $message)
    {
        return response()->view('scan.error', ['message' => $message], 400);
    }
}