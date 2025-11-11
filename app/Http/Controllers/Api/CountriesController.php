<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountriesController extends Controller
{
    public function index()
    {
        $countries = Country::select('id', 'name', 'code')->orderBy('name')->get();

        return response()->json($countries);
    }
}
