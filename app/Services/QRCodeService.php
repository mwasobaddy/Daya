<?php

namespace App\Services;

use App\Models\User;
use App\Models\Scan;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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

        // Create QR code data - URL that clients can scan
        $qrData = route('dds.campaign.submit') . '?dcd_qr=' . urlencode($user->qr_code);

        // Generate SVG QR code
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svgContent = $writer->writeString($qrData);

        // Generate unique filename
        $filename = 'qr-codes/' . $user->id . '_' . time() . '.svg';

        // Store the QR code
        Storage::disk('public')->put($filename, $svgContent);

        // Update user with QR code path
        $user->update(['qr_code' => $filename]);

        return $filename;
    }

    /**
     * Generate QR code for a DA's referral
     */
    public function generateDAReferralQRCode(User $user): string
    {
        if ($user->role !== 'da') {
            throw new \InvalidArgumentException('User must be a DA');
        }

        // Create QR code data - URL for DCD registration with referral code
        $qrData = route('dds.dcd.register') . '?ref=' . urlencode($user->referral_code);

        // Generate SVG QR code
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svgContent = $writer->writeString($qrData);

        // Generate unique filename
        $filename = 'qr-codes/da_' . $user->id . '_' . time() . '.svg';

        // Store the QR code
        Storage::disk('public')->put($filename, $svgContent);

        return $filename;
    }

    /**
     * Record a scan event
     */
    public function recordScan(string $dcdQrCode, ?string $clientIp = null, ?array $geoData = null): Scan
    {
        $dcd = User::where('qr_code', $dcdQrCode)->where('role', 'dcd')->first();

        if (!$dcd) {
            throw new \InvalidArgumentException('Invalid DCD QR code');
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