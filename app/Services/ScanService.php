<?php

namespace App\Services;

use App\Models\User;

class ScanService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function authenticateAdmin(string $adminToken): bool
    {
        return hash_equals(hash('sha256', 'daya_admin_2024'), hash('sha256', $adminToken));
    }

    public function recordScan(int $dcdId, string $ip, ?array $geoData = null): object
    {
        return $this->qrCodeService->recordScan($dcdId, $ip, $geoData);
    }

    public function getScanStats(User $user): array
    {
        if ($user->role !== 'dcd') {
            throw new \InvalidArgumentException('User is not a DCD');
        }

        return $this->qrCodeService->getScanStats($user);
    }

    public function getAdminScanStats(): array
    {
        return $this->qrCodeService->getAdminScanStats();
    }

    public function regenerateQRCode(User $user): array
    {
        $qrCodeFilename = $this->qrCodeService->regenerateQRCode($user);
        $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

        return [
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    public function recordScanWithFingerprint(array $data): array
    {
        $dcdId = $data['dcd_id'];
        $campaignId = $data['campaign_id'] ?? null;

        $geoData = [
            'fingerprint' => $data['fingerprint'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ];

        if ($campaignId) {
            $scan = $this->qrCodeService->recordCampaignScan($dcdId, $campaignId, $geoData);
            $campaign = \App\Models\Campaign::findOrFail($campaignId);
            $redirectUrl = $campaign->digital_product_link;
        } else {
            $result = $this->qrCodeService->recordDcdScan($dcdId, $geoData);
            $campaign = $result['campaign'];
            $redirectUrl = $campaign->digital_product_link;
        }

        return [
            'redirect_url' => $redirectUrl,
        ];
    }

    public function determineScanErrorType(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'No active campaigns found for this DCD')) {
            return 'no_campaigns';
        } elseif (str_contains($message, 'Campaign has reached its budget limit')) {
            return 'budget_exhausted';
        }

        return 'general_error';
    }
}
