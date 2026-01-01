<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileToken implements ValidationRule
{
    protected $turnstileService;

    public function __construct()
    {
        $this->turnstileService = app(TurnstileService::class);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('Please complete the verification challenge.');
            return;
        }

        $ip = request()->ip();
        $result = $this->turnstileService->verifyWithDetails($value, $ip);

        if (!$result['success']) {
            $fail($result['error'] ?? 'Verification failed. Please try again.');
        }
    }
}
