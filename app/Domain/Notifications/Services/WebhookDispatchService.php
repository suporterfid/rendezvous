<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Calendar\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookDispatchService
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('services.webhooks.enabled', true);
    }

    /**
     * Dispatch an outbound webhook notification event to a tenant's registered target URL.
     *
     * @param int $tenantId
     * @param string $webhookUrl
     * @param string $webhookSecret
     * @param string $eventType ('event.created', 'event.updated', 'event.cancelled')
     * @param Event $event
     * @return bool
     */
    public function dispatch(int $tenantId, string $webhookUrl, string $webhookSecret, string $eventType, Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $payload = [
            'event_type' => $eventType,
            'tenant_id' => $tenantId,
            'event' => [
                'public_id' => $event->public_id,
                'uid' => $event->uid,
                'summary' => $event->summary,
                'status' => $event->status,
                'dtstart_utc' => $event->dtstart_utc ? $event->dtstart_utc->toIso8601String() : null,
                'dtend_utc' => $event->dtend_utc ? $event->dtend_utc->toIso8601String() : null,
            ],
            'dispatched_at' => now()->toIso8601String()
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);

        try {
            Log::info("Dispatching outbound webhook '{$eventType}' for event {$event->uid} to '{$webhookUrl}'...");

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Rendezvous-Signature' => $signature,
                'X-Rendezvous-Event' => $eventType,
                'User-Agent' => 'Rendezvous-WebhookDispatcher/1.0'
            ])->timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Webhook '{$eventType}' delivered successfully to '{$webhookUrl}'.");
                return true;
            }

            Log::warning("Webhook dispatch failed with HTTP status " . $response->status());
            return false;
        } catch (Exception $e) {
            Log::error("Webhook dispatch exception for URL '{$webhookUrl}': " . $e->getMessage());
            return false;
        }
    }
}
