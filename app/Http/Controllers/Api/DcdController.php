<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDcdRequest;
use App\Services\DcdCreationService;
use Illuminate\Http\JsonResponse;

class DcdController extends Controller
{
    protected $dcdCreationService;

    public function __construct(DcdCreationService $dcdCreationService)
    {
        $this->dcdCreationService = $dcdCreationService;
    }

    public function create(StoreDcdRequest $request): JsonResponse
    {
        try {
            $result = $this->dcdCreationService->createDcd($request->validated());

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
