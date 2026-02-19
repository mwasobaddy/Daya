<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referral_code' => 'nullable|string|regex:/^[A-Za-z0-9]{6,8}$/',
            'referrer_id' => 'nullable|exists:users,id',
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:users,national_id',
            'dob' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'required|email|unique:users',
            'ward_id' => 'required|exists:wards,id',
            'address' => 'required|string',
            'phone' => 'required|string|max:11|regex:/^0[\d\s\-()]{10}$/',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'string',
            'followers' => 'required|in:less_than_1k,1k_10k,10k_50k,50k_100k,100k_plus',
            'communication_channel' => 'required|in:whatsapp,telegram,email,phone',
            'wallet_type' => 'required|in:personal,business,both',
            'wallet_pin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'confirm_pin' => 'required|string|same:wallet_pin',
            'terms' => 'required|accepted',
            'turnstile_token' => ['required', new \App\Rules\TurnstileToken],
        ];
    }
}
