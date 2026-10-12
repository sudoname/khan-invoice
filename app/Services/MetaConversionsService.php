<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends server-side events to the Meta Conversions API.
 *
 * Events sent from both the browser pixel and this service must share the
 * same event_name and event_id so Meta can deduplicate them.
 */
class MetaConversionsService
{
    private const API_VERSION = 'v26.0';

    /**
     * Send a Purchase event.
     */
    public function sendPurchase(float $value, string $currency, ?string $email, string $eventId, ?string $sourceUrl = null): void
    {
        $this->sendEvent('Purchase', [
            'em' => $email,
        ], [
            'value' => $value,
            'currency' => $currency,
        ], $eventId, $sourceUrl);
    }

    /**
     * Send a CompleteRegistration event.
     */
    public function sendRegistration(?string $email, string $eventId): void
    {
        $this->sendEvent('CompleteRegistration', ['em' => $email], [], $eventId);
    }

    /**
     * Send an arbitrary event. Never throws; CAPI failures must not break
     * payment or registration flows.
     *
     * @param array $userData Raw (unhashed) user data: em, ph, client_ip_address, client_user_agent
     * @param array $customData e.g. ['value' => 100, 'currency' => 'NGN']
     */
    public function sendEvent(string $eventName, array $userData, array $customData = [], ?string $eventId = null, ?string $sourceUrl = null): void
    {
        $pixelId = config('services.meta.pixel_id');
        $token = config('services.meta.capi_token');

        if (!$pixelId || !$token) {
            return; // CAPI not configured; browser pixel still tracks
        }

        $event = [
            'event_name' => $eventName,
            'event_time' => time(),
            'action_source' => 'website',
            'user_data' => $this->buildUserData($userData),
        ];

        if ($eventId !== null) {
            $event['event_id'] = $eventId;
        }
        if ($sourceUrl !== null) {
            $event['event_source_url'] = $sourceUrl;
        }
        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        $payload = ['data' => [$event]];

        if ($testCode = config('services.meta.test_event_code')) {
            $payload['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(5)
                ->post(sprintf(
                    'https://graph.facebook.com/%s/%s/events?access_token=%s',
                    self::API_VERSION,
                    $pixelId,
                    $token
                ), $payload);

            if (!$response->successful()) {
                Log::warning('Meta CAPI event rejected', [
                    'event' => $eventName,
                    'event_id' => $eventId,
                    'status' => $response->status(),
                    'error' => $response->json('error.message'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI request failed', [
                'event' => $eventName,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildUserData(array $userData): array
    {
        $result = [];

        if (!empty($userData['em'])) {
            $result['em'] = hash('sha256', strtolower(trim($userData['em'])));
        }
        if (!empty($userData['ph'])) {
            $result['ph'] = hash('sha256', preg_replace('/\D/', '', $userData['ph']));
        }
        if (!empty($userData['client_ip_address'])) {
            $result['client_ip_address'] = $userData['client_ip_address'];
        }
        if (!empty($userData['client_user_agent'])) {
            $result['client_user_agent'] = $userData['client_user_agent'];
        }

        return $result;
    }
}
