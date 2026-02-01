<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    protected $secretKey;
    protected $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct()
    {
        $this->secretKey = config('services.turnstile.secret_key');
    }

    /**
     * Verify a Turnstile token
     *
     * @param string $token The Turnstile token from the frontend
     * @param string|null $ip The user's IP address (optional)
     * @return bool
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        // Bypass Turnstile verification in development environment
        if (app()->environment('local')) {
            Log::info('Turnstile verification bypassed in development environment');
            return true;
        }

        if (empty($token)) {
            Log::warning('Turnstile verification failed: Empty token provided');
            return false;
        }

        if (empty($this->secretKey)) {
            Log::error('Turnstile verification failed: Secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post($this->verifyUrl, [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            if (!$response->successful()) {
                Log::error('Turnstile API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $result = $response->json();

            if (isset($result['success']) && $result['success'] === true) {
                Log::info('Turnstile verification successful', [
                    'challenge_ts' => $result['challenge_ts'] ?? null,
                    'hostname' => $result['hostname'] ?? null,
                ]);
                return true;
            }

            Log::warning('Turnstile verification failed', [
                'error_codes' => $result['error-codes'] ?? [],
                'messages' => $result['messages'] ?? [],
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get detailed verification result with error messages
     *
     * @param string $token The Turnstile token from the frontend
     * @param string|null $ip The user's IP address (optional)
     * @return array
     */
    public function verifyWithDetails(string $token, ?string $ip = null): array
    {
        // Bypass Turnstile verification in development environment
        if (app()->environment('local')) {
            Log::info('Turnstile verification bypassed in development environment');
            return [
                'success' => true,
                'bypassed' => true,
                'environment' => 'development',
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'error' => 'No verification token provided',
            ];
        }

        if (empty($this->secretKey)) {
            return [
                'success' => false,
                'error' => 'Turnstile is not properly configured',
            ];
        }

        try {
            $response = Http::asForm()->post($this->verifyUrl, [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to verify with Turnstile service',
                ];
            }

            $result = $response->json();

            if (isset($result['success']) && $result['success'] === true) {
                return [
                    'success' => true,
                    'challenge_ts' => $result['challenge_ts'] ?? null,
                    'hostname' => $result['hostname'] ?? null,
                ];
            }

            $errorCodes = $result['error-codes'] ?? [];
            $errorMessages = [
                'missing-input-secret' => 'The secret parameter is missing',
                'invalid-input-secret' => 'The secret parameter is invalid or malformed',
                'missing-input-response' => 'The response parameter is missing',
                'invalid-input-response' => 'The response parameter is invalid or malformed',
                'timeout-or-duplicate' => 'The response is no longer valid (timeout or duplicate)',
                'internal-error' => 'An internal error happened while validating the response',
            ];

            $humanReadableErrors = array_map(
                fn($code) => $errorMessages[$code] ?? $code,
                $errorCodes
            );

            return [
                'success' => false,
                'error' => 'Verification failed: ' . implode(', ', $humanReadableErrors),
                'error_codes' => $errorCodes,
            ];
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred during verification',
            ];
        }
    }
}
