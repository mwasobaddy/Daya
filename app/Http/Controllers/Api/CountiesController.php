<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use Illuminate\Http\Request;

class CountiesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $counties = County::where('country_id', $request->country_id)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($counties);
    }
}