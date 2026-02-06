<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminValidationRequest extends FormRequest
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
        $method = $this->route()?->getActionMethod();

        return match ($method) {
            'validateReferralCode' => [
                'referral_code' => 'required|string|min:6|max:8|regex:/^[A-Z0-9]{6,8}$/',
            ],
            'validateEmail' => [
                'email' => 'required|email|max:255',
            ],
            'validateNationalId' => [
                'national_id' => 'required|string|max:255|regex:/^\d+$/',
            ],
            'validatePhone' => [
                'phone' => 'required|string|max:20|regex:/^\+?[\d\s\-()]{10,}$/',
            ],
            default => []
        };
    }
}
