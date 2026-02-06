<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDcdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Referral & Identification
            'referral_code' => 'nullable|string|exists:users,referral_code',
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:users,national_id',
            'dob' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'required|email|unique:users',
            'ward_id' => 'required|exists:wards,id',
            'business_address' => 'required|string',
            'phone' => 'required|string|max:10|regex:/^0[\d\s\-()]{9}$/',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            // Business Information
            'business_name' => 'nullable|string',
            'business_types' => 'required|array|min:1',
            'business_types.*' => 'string',
            'other_business_type' => 'required_if:business_types.*,other|string|nullable',

            // Business Traffic & Hours
            'daily_foot_traffic' => 'required|in:1-10,11-50,51-100,101-500,500+',
            'operating_hours_start' => 'nullable|date_format:H:i',
            'operating_hours_end' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            // Campaign Preferences
            'campaign_types' => 'required|array|min:1',
            'campaign_types.*' => 'in:music,movies,games,mobile_apps,product_launch,apartment_listing,surveys,events,education',

            // Music Preferences
            'music_genres' => 'required_if:campaign_types.*,music|array',
            'music_genres.*' => 'string',

            // Content Safety
            'safe_for_kids' => 'boolean',

            // Wallet Setup
            'wallet_type' => 'required|in:personal,business,both',
            'wallet_pin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'confirm_pin' => 'required|string|same:wallet_pin',

            // Agreement
            'terms' => 'required|accepted',
            'turnstile_token' => ['required', new \App\Rules\TurnstileToken],
        ];
    }
}
