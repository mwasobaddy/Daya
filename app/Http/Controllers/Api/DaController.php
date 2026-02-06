<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDaRequest;
use App\Services\DaCreationService;
use App\Services\QRCodeService;
use App\Services\VentureShareService;

class DaController extends Controller
{
    protected $ventureShareService;

    protected $qrCodeService;

    protected $daCreationService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService, DaCreationService $daCreationService)
    {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
        $this->daCreationService = $daCreationService;
    }

    public function create(StoreDaRequest $request)
    {
        try {
            \Log::info('DA Create request received', $request->all());

            $user = $this->daCreationService->createDa($request->validated());

            \Log::info('DA created successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'message' => 'DA account created successfully! Welcome to Daya.',
                'user_id' => $user->id,
                'referral_code' => $user->referral_code,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('DA creation failed: '.$e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create DA account. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
