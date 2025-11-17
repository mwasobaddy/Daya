<?php

namespace App\Http\Controllers;

use App\Services\AdminActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminActionController extends Controller
{
    protected $adminActionService;

    public function __construct(AdminActionService $adminActionService)
    {
        $this->adminActionService = $adminActionService;
    }

    public function handleAction(Request $request, $action, $token)
    {
        try {
            $result = $this->adminActionService->executeAction($token, $action);

            if (!$result['success']) {
                return response()->view('admin-action.error', [
                    'message' => $result['message']
                ], 400);
            }

            return response()->view('admin-action.success', [
                'message' => $result['message'],
                'action' => $action
            ]);

        } catch (\InvalidArgumentException $e) {
            // Known invalid/expired or domain specific error - return 400
            \Log::warning('Admin action validation error', [
                'action' => $action,
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->view('admin-action.error', [
                'message' => config('app.debug') ? $e->getMessage() : 'Invalid or expired admin action link.'
            ], 400);
        } catch (\Exception $e) {
            // Log the exception details for debugging
            \Log::error('Admin action failed', [
                'action' => $action,
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->view('admin-action.error', [
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.'
            ], 500);
        }
    }
}
