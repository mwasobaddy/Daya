<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCampaignActionRequest;
use App\Http\Requests\AdminValidationRequest;
use App\Models\Campaign;
use App\Services\AdminService;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Approve a pending campaign
     */
    public function approveCampaign(AdminCampaignActionRequest $request, $campaignId)
    {
        if (! $request->authenticateAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'pending') {
            return response()->json(['message' => 'Campaign is not pending'], 400);
        }

        $this->adminService->approveCampaign($campaign);

        return response()->json(['message' => 'Campaign approved successfully']);
    }

    /**
     * Mark a campaign as completed and allocate venture shares
     */
    public function completeCampaign(AdminCampaignActionRequest $request, $campaignId)
    {
        if (! $request->authenticateAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'approved') {
            return response()->json(['message' => 'Campaign must be approved first'], 400);
        }

        $this->adminService->completeCampaign($campaign);

        return response()->json(['message' => 'Campaign completed successfully']);
    }

    /**
     * Get all campaigns for admin review
     */
    public function getCampaigns(AdminCampaignActionRequest $request)
    {
        if (! $request->authenticateAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaigns = $this->adminService->getCampaigns();

        return response()->json($campaigns);
    }

    /**
     * Get venture shares summary for all users
     */
    public function getVentureSharesSummary(AdminCampaignActionRequest $request)
    {
        if (! $request->authenticateAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $users = $this->adminService->getVentureSharesSummary();

        return response()->json($users);
    }

    /**
     * Validate a referral code
     */
    public function validateReferralCode(AdminValidationRequest $request)
    {
        $result = $this->adminService->validateReferralCode($request->referral_code);

        $status = $result['valid'] ? 200 : 400;

        return response()->json($result, $status);
    }

    /**
     * Get the admin's referral code
     */
    public function getAdminReferralCode()
    {
        $result = $this->adminService->getAdminReferralCode();

        if (! $result) {
            return response()->json([
                'error' => 'Admin referral code not found',
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * Validate an email address for uniqueness
     */
    public function validateEmail(AdminValidationRequest $request)
    {
        $result = $this->adminService->validateEmail($request->email);

        $status = $result['valid'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Validate a national ID for uniqueness
     */
    public function validateNationalId(AdminValidationRequest $request)
    {
        $result = $this->adminService->validateNationalId($request->national_id);

        $status = $result['valid'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Validate a phone number for uniqueness
     */
    public function validatePhone(AdminValidationRequest $request)
    {
        $result = $this->adminService->validatePhone($request->phone);

        $status = $result['valid'] ? 200 : 422;

        return response()->json($result, $status);
    }
}
