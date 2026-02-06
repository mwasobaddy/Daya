<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCampaignActionRequest extends FormRequest
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
            'admin_token' => 'required|string',
        ];
    }

    public function authenticateAdmin(): bool
    {
        return \Illuminate\Support\Facades\Hash::check(
            $this->admin_token,
            hash('sha256', 'daya_admin_2024')
        );
    }
}
