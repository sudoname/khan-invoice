<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    protected string $measurementId;
    protected string $apiSecret;
    protected string $endpoint = 'https://www.google-analytics.com/mp/collect';

    public function __construct()
    {
        $this->measurementId = config('services.google_analytics.measurement_id');
        $this->apiSecret = config('services.google_analytics.api_secret');
    }

    /**
     * Track an event to Google Analytics 4
     *
     * @param string $eventName The name of the event (e.g., 'invoice_created')
     * @param array $params Event parameters
     * @param string|null $clientId Unique client identifier (user ID or session ID)
     * @param int|null $userId Optional user ID for user-scoped tracking
     * @return bool Success status
     */
    public function track(string $eventName, array $params = [], ?string $clientId = null, ?int $userId = null): bool
    {
        // Skip if GA4 not configured
        if (empty($this->measurementId) || empty($this->apiSecret)) {
            Log::warning('GA4 tracking skipped: Missing measurement_id or api_secret');
            return false;
        }

        // Generate client ID if not provided (use session ID or generate random)
        if (!$clientId) {
            $clientId = session()->getId() ?: $this->generateClientId();
        }

        // Build the payload
        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => $eventName,
                    'params' => $this->sanitizeParams($params),
                ],
            ],
        ];

        // Add user_id if provided
        if ($userId) {
            $payload['user_id'] = (string) $userId;
        }

        // Add timestamp
        $payload['timestamp_micros'] = (int) (microtime(true) * 1000000);

        // Send to GA4
        try {
            $response = Http::timeout(5)
                ->post($this->endpoint, [
                    'measurement_id' => $this->measurementId,
                    'api_secret' => $this->apiSecret,
                    'payload' => $payload,
                ]);

            // GA4 Measurement Protocol returns 204 on success
            if ($response->successful()) {
                Log::info("GA4 event tracked: {$eventName}", [
                    'client_id' => $clientId,
                    'params' => $params,
                ]);
                return true;
            }

            Log::error('GA4 tracking failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'event' => $eventName,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('GA4 tracking exception', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Track invoice creation event
     */
    public function trackInvoiceCreated($invoice, string $context = 'unknown'): bool
    {
        $user = $invoice->user;

        return $this->track('invoice_created', [
            'context' => $context,
            'invoice_id_hash' => hash('sha256', $invoice->id),
            'invoice_number' => $invoice->invoice_number,
            'invoice_total' => $invoice->total_amount,
            'currency' => $invoice->currency ?? 'NGN',
            'has_business_profile' => $invoice->business_profile_id ? true : false,
            'simple_mode' => $invoice->simple_mode ?? false,
            'time_since_signup_sec' => $user ? now()->diffInSeconds($user->created_at) : null,
            'is_first_invoice' => $user ? ($user->invoices()->count() === 1) : false,
        ], null, $user?->id);
    }

    /**
     * Track public invoice creation by authenticated user (deflection)
     */
    public function trackAuthUserDeflection($userId, string $publicInvoiceId): bool
    {
        return $this->track('auth_user_deflection', [
            'user_id_hash' => hash('sha256', $userId),
            'public_invoice_id' => $publicInvoiceId,
            'deflection_type' => 'used_public_invoice',
            'user_has_invoices' => \App\Models\User::find($userId)?->invoices()->count() > 0,
        ], null, $userId);
    }

    /**
     * Track user registration
     */
    public function trackUserRegistration($user, string $method = 'email'): bool
    {
        return $this->track('user_registration', [
            'user_id_hash' => hash('sha256', $user->id),
            'registration_method' => $method,
            'user_email_domain' => $this->extractDomain($user->email),
        ], null, $user->id);
    }

    /**
     * Track time to first invoice (called when first invoice is created)
     */
    public function trackTimeToFirstInvoice($user, $invoice): bool
    {
        $timeToFirstInvoice = now()->diffInSeconds($user->created_at);

        return $this->track('time_to_first_invoice', [
            'user_id_hash' => hash('sha256', $user->id),
            'time_seconds' => $timeToFirstInvoice,
            'time_minutes' => round($timeToFirstInvoice / 60, 2),
            'time_hours' => round($timeToFirstInvoice / 3600, 2),
            'invoice_type' => $invoice->simple_mode ? 'simple' : 'advanced',
        ], null, $user->id);
    }

    /**
     * Track A/B test variant view
     */
    public function trackAbTestVariant(string $testName, string $variant, ?int $userId = null): bool
    {
        return $this->track('ab_test_viewed', [
            'test_name' => $testName,
            'variant' => $variant,
            'user_id_hash' => $userId ? hash('sha256', $userId) : null,
        ], null, $userId);
    }

    /**
     * Track A/B test conversion
     */
    public function trackAbTestConversion(string $testName, string $variant, string $conversionType, ?int $userId = null): bool
    {
        return $this->track('ab_test_converted', [
            'test_name' => $testName,
            'variant' => $variant,
            'conversion_type' => $conversionType,
            'user_id_hash' => $userId ? hash('sha256', $userId) : null,
        ], null, $userId);
    }

    /**
     * Sanitize parameters to ensure they're GA4-compatible
     */
    protected function sanitizeParams(array $params): array
    {
        $sanitized = [];

        foreach ($params as $key => $value) {
            // Skip null values
            if ($value === null) {
                continue;
            }

            // Convert booleans to strings
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            // Convert arrays/objects to JSON strings
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Generate a unique client ID
     */
    protected function generateClientId(): string
    {
        return sprintf('%s.%s', time(), rand(100000000, 999999999));
    }

    /**
     * Extract domain from email address
     */
    protected function extractDomain(string $email): string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? 'unknown';
    }

    /**
     * Check if GA4 is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->measurementId) && !empty($this->apiSecret);
    }
}
