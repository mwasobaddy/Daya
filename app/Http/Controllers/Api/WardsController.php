<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\Request;

class WardsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'subcounty_id' => 'required|exists:subcounties,id',
        ]);

        $wards = Ward::where('subcounty_id', $request->subcounty_id)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($wards);
    }
}