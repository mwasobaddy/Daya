<?php

namespace App\Services;

use App\Models\User;
use App\Models\Scan;
use App\Services\ScanRewardService;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
// Image backends are unused — we use GDLibRenderer directly for PNG/JPEG
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;

class QRCodeService
{
    /**
     * Generate QR code for a DCD
     */
    public function generateDCDQRCode(User $user): string
    {
        if ($user->role !== 'dcd') {
            throw new \InvalidArgumentException('User must be a DCD');
        }

        // Create QR code data - URL that clients can scan using DCD ID
        $qrData = route('dds.campaign.submit') . '?dcd_id=' . $user->id;

        // Generate PNG QR code
        $renderer = new GDLibRenderer(400, 4, 'png');
        $writer = new Writer($renderer);
        $pngContent = $writer->writeString($qrData);

        // Embed PNG into HTML via data URI (avoid file path / chroot issues)
        $b64Png = base64_encode($pngContent);
        $html = '<html>
            <head>
                <meta charset="utf-8">
                <style>
                    body {
                        font-family: "Helvetica", Arial, sans-serif;
                        padding: 0;
                        display: block;
                        margin: auto;
                        background: #fefbf0;
                        border-radius: 8px;
                    }
                    .poster {
                        display: block;
                        margin: 70px auto;
                    }
                    .logo {
                        display: block;
                        margin: auto;
                        width: 100%;
                        text-align: center;
                    }
                    .logo img {
                        width: 340px;
                        max-width: 90%;
                        height: auto;
                        display: block;
                        margin: 0 auto;
                        border-radius: 8px;
                    }
                    h1.title {
                        font-size: 36px;
                        margin: 18px 0 6px;
                        color:#0a0a0a;
                        text-align: center;
                    }
                    .qr {
                        margin-top: 8px;
                        text-align: center;
                    }
                    .qr img {
                        width: 400px;
                        height: 400px;
                    }
                    p.caption {
                        font-size: 36px;
                        font-weight: bold;
                        margin-top: 12px;
                        text-align: center;
                    }
                    .footer {
                        margin-top: 18px;
                        font-size: 12px;
                        color:#333;
                    }
                </style>
            </head>
            <body>
                <div class="poster">';

                    if ($logoSvg) {
                        $html .= '<div class="logo">
                                    <img src="' . $logoSvg . '" alt="Daya logo" />
                                </div>';
                    }

                    $html .= '<h1 class="title">Discover with Daya</h1>';
                    $html .= '<div class="qr"><img src="data:image/png;base64,' . $b64Png . '" alt="Referral QR" /></div>';
                    $html .= '<p class="caption">Scan to register</p>';
                    $html .= '<div class="footer">&nbsp;</div>';
                    $html .= '
                </div>
            </body>
        </html>';

        // Generate PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        // Store PDF to storage/public/qrcodes and return filename
        $filename = 'qrcodes/dcd_' . $user->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($filename, $pdfContent);

        // Update the user with the stored filename (not base64)
        $user->update(['qr_code' => $filename]);

        return $filename;
    }

    /**
     * Generate a campaign-specific QR code for a DCD and campaign.
     * Returns base64-encoded PDF content.
     */
    public function generateDcdCampaignQr(User $dcd, \App\Models\Campaign $campaign): string
    {
        if ($dcd->role !== 'dcd') {
            throw new \InvalidArgumentException('User must be a DCD');
        }

        // Signed URL that records a scan and redirects to the campaign url
        $qrData = \Illuminate\Support\Facades\URL::temporarySignedRoute('scan.redirect', now()->addYears(1), [
            'dcd' => $dcd->id,
            'campaign' => $campaign->id,
        ]);

        $renderer = new GDLibRenderer(400, 4, 'png');
        $writer = new Writer($renderer);
        $pngContent = $writer->writeString($qrData);

        // Embed PNG into HTML via data URI (avoid file path / chroot issues)
        $b64Png = base64_encode($pngContent);
        $html = '<html><head><style>body { text-align: center; padding: 20px; }</style></head><body><img src="data:image/png;base64,' . $b64Png . '" style="width:400px;height:400px;" /></body></html>';

        // Generate PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        // Store the campaign QR PDF into storage and return filename
        $filename = 'qrcodes/campaign_' . $dcd->id . '_' . $campaign->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($filename, $pdfContent);

        return $filename;
    }

    /**
     * Record a campaign scan by DCD and campaign ids
     */
    public function recordCampaignScan(int $dcdId, int $campaignId, ?array $geoData = null)
    {
        $dcd = User::findOrFail($dcdId);
        $campaign = \App\Models\Campaign::findOrFail($campaignId);

        // Basic validation
        if ($campaign->dcd_id !== $dcd->id) {
            throw new \InvalidArgumentException('Campaign is not assigned to this DCD');
        }

        $deviceFingerprint = $geoData['fingerprint'] ?? null;
        $scan = \App\Models\Scan::create([
            'dcd_id' => $dcdId,
            'campaign_id' => $campaignId,
            'scanned_at' => now(),
            'geo' => $geoData,
            'device_fingerprint' => $deviceFingerprint,
        ]);

        // Use the ScanRewardService to credit and dedupe earnings
        try {
            $svc = app(ScanRewardService::class);
            $svc->creditScanReward($scan);
        } catch (\Exception $e) {
            \Log::warning('Failed to credit scan reward via ScanRewardService: ' . $e->getMessage());
        }

        return $scan;
    }

    // Compute pay per scan was moved to ScanRewardService

    /**
     * Generate QR code for a DA's referral
     */
    public function generateDAReferralQRCode(User $user): string
    {
        if ($user->role !== 'da') {
            throw new \InvalidArgumentException('User must be a DA');
        }

        // Ensure user has a referral code
        if (!$user->referral_code) {
            throw new \InvalidArgumentException('DA user must have a referral code');
        }

        // Create QR code data - URL for DCD registration with referral code
        $qrData = route('dds.dcd.register') . '?ref=' . urlencode($user->referral_code);

        // Generate PNG QR code
        $renderer = new GDLibRenderer(400, 4, 'png');
        $writer = new Writer($renderer);
        $pngContent = $writer->writeString($qrData);

            // Logo embed (optional) - load public/PDFLogo.png and embed base64.
            $logoSvg = null;
            try {
                $logoPath = public_path('PDFLogo.png');
                if (file_exists($logoPath)) {
                    $svgContents = file_get_contents($logoPath);
                    $logoSvg = 'data:image/svg+xml;base64,' . base64_encode($svgContents);
                }
            } catch (\Exception $e) {
                // ignore logo if it can't be read
                $logoSvg = null;
            }

            // Embed PNG QR image as data URI
            $b64Png = base64_encode($pngContent);
        
            // Create a simple poster-like HTML template that resembles the attached design
            $html = '<html>
                        <head>
                            <meta charset="utf-8">
                            <style>
                                body {
                                    font-family: "Helvetica", Arial, sans-serif;
                                    padding: 0;
                                    display: block;
                                    margin: auto;
                                    background: #fefbf0;
                                    border-radius: 8px;
                                }
                                .poster {
                                    display: block;
                                    margin: 70px auto;
                                }
                                .logo {
                                    display: block;
                                    margin: auto;
                                    width: 100%;
                                    text-align: center;
                                }
                                .logo img {
                                    width: 340px;
                                    max-width: 90%;
                                    height: auto;
                                    display: block;
                                    margin: 0 auto;
                                    border-radius: 8px;
                                }
                                h1.title {
                                    font-size: 36px;
                                    margin: 18px 0 6px;
                                    color:#0a0a0a;
                                    text-align: center;
                                }
                                .qr {
                                    margin-top: 8px;
                                    text-align: center;
                                }
                                .qr img {
                                    width: 400px;
                                    height: 400px;
                                }
                                p.caption {
                                    font-size: 36px;
                                    font-weight: bold;
                                    margin-top: 12px;
                                    text-align: center;
                                }
                                .footer {
                                    margin-top: 18px;
                                    font-size: 12px;
                                    color:#333;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="poster">';

                                if ($logoSvg) {
                                    $html .= '<div class="logo">
                                                <img src="' . $logoSvg . '" alt="Daya logo" />
                                            </div>';
                                }

                                $html .= '<h1 class="title">Discover with Daya</h1>';
                                $html .= '<div class="qr"><img src="data:image/png;base64,' . $b64Png . '" alt="Referral QR" /></div>';
                                $html .= '<p class="caption">Scan to register</p>';
                                $html .= '<div class="footer">&nbsp;</div>';
                                $html .= '
                            </div>
                        </body>
                    </html>';

        // Generate PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        // Store the generated DA referral PDF in storage and return filename
        $filename = 'qrcodes/da_' . $user->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($filename, $pdfContent);

        return $filename;
    }

    /**
     * Record a scan event
     */
    public function recordScan(int $dcdId, ?string $clientIp = null, ?array $geoData = null): Scan
    {
        $dcd = User::where('id', $dcdId)->where('role', 'dcd')->first();

        if (!$dcd) {
            throw new \InvalidArgumentException('Invalid DCD ID');
        }

        return Scan::create([
            'dcd_id' => $dcd->id,
            'scanned_at' => now(),
            'ip_address' => $clientIp,
            'geo_location' => $geoData,
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get scan statistics for a DCD
     */
    public function getScanStats(User $user): array
    {
        if ($user->role !== 'dcd') {
            throw new \InvalidArgumentException('User must be a DCD');
        }

        $scans = Scan::where('dcd_id', $user->id);

        return [
            'total_scans' => $scans->count(),
            'scans_today' => (clone $scans)->whereDate('scanned_at', today())->count(),
            'scans_this_week' => (clone $scans)->where('scanned_at', '>=', now()->startOfWeek())->count(),
            'scans_this_month' => (clone $scans)->where('scanned_at', '>=', now()->startOfMonth())->count(),
            'recent_scans' => $scans->orderBy('scanned_at', 'desc')->limit(10)->get(),
        ];
    }

    /**
     * Get scan statistics for admin
     */
    public function getAdminScanStats(): array
    {
        $scans = Scan::with('dcd');

        return [
            'total_scans' => $scans->count(),
            'scans_today' => (clone $scans)->whereDate('scanned_at', today())->count(),
            'scans_this_week' => (clone $scans)->where('scanned_at', '>=', now()->startOfWeek())->count(),
            'scans_this_month' => (clone $scans)->where('scanned_at', '>=', now()->startOfMonth())->count(),
            'top_dcds' => Scan::selectRaw('dcd_id, COUNT(*) as scan_count')
                              ->with('dcd')
                              ->groupBy('dcd_id')
                              ->orderBy('scan_count', 'desc')
                              ->limit(10)
                              ->get(),
        ];
    }

    /**
     * Get QR code URL for display
     */
    public function getQRCodeUrl(string $filename): string
    {
        return Storage::disk('public')->url($filename);
    }

    /**
     * Regenerate QR code for a user
     */
    public function regenerateQRCode(User $user): string
    {
        if ($user->role === 'dcd') {
            return $this->generateDCDQRCode($user);
        } elseif ($user->role === 'da') {
            return $this->generateDAReferralQRCode($user);
        }

        throw new \InvalidArgumentException('User must be a DA or DCD');
    }
}