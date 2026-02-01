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

        // Instead of redirecting immediately, serve the fingerprinting page
        return response()->view('scan-processing', [
            'dcdId' => $dcdId,
            'campaignId' => $campaignId,
            'signature' => $request->query('signature')
        ]);
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

        // Instead of redirecting immediately, serve the fingerprinting page
        return response()->view('scan-processing', [
            'dcdId' => $dcdId,
            'campaignId' => null, // Will be determined by smart selection
            'signature' => $request->query('signature')
        ]);
    }
}
