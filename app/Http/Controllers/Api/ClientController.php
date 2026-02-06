<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitCampaignRequest;
use App\Services\ClientCampaignService;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    protected $clientCampaignService;

    public function __construct(ClientCampaignService $clientCampaignService)
    {
        $this->clientCampaignService = $clientCampaignService;
    }

    public function submitCampaign(SubmitCampaignRequest $request): JsonResponse
    {
        try {
            $result = $this->clientCampaignService->submitCampaign($request->validated());

            return response()->json($result, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'validation_error',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while submitting your campaign. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
