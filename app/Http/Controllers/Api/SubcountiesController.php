<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subcounty;
use Illuminate\Http\Request;

class SubcountiesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'county_id' => 'required|exists:counties,id',
        ]);

        $subcounties = Subcounty::where('county_id', $request->county_id)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($subcounties);
    }
}