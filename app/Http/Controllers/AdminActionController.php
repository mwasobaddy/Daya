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
            $result = $this->adminActionService->executeAction($action, $token);

            if (!$result['success']) {
                return response()->view('admin-action.error', [
                    'message' => $result['message']
                ], 400);
            }

            return response()->view('admin-action.success', [
                'message' => $result['message'],
                'action' => $action
            ]);

        } catch (\Exception $e) {
            return response()->view('admin-action.error', [
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
}
