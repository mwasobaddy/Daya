<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountriesController extends Controller
{
    public function index()
    {
        $countries = Country::select('id', 'name', 'code', 'county_label', 'subcounty_label')->orderBy('name')->get();

        return response()->json($countries);
    }
}
