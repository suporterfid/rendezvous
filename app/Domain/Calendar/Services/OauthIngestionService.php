<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\CalendarSource;
use App\Infrastructure\Security\SecretEncryptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OauthIngestionService
{
    public function __construct(
        private SecretEncryptionService $secretService,
        private EventReconciliationService $reconciliationService
    ) {}

    /**
     * Poll Microsoft Graph API or Google Calendar API using stored OAuth tokens.
     *
     * @param CalendarSource $source
     * @return int Number of processed events
     * @throws Exception
     */
    public function ingestFromOauthSource(CalendarSource $source): int
    {
        if (!in_array($source->source_type, ['msgraph_oauth', 'google_oauth'])) {
            return 0;
        }

        $config = $source->config_json ?? [];
        $provider = $source->source_type;

        Log::info("Starting OAuth ingestion for source ID {$source->id} (Provider: {$provider})...");

        // Retrieve encrypted refresh token
        $refreshToken = $this->secretService->retrieveSecret($source->tenant_id, $source->secret_ref);

        // Fetch fresh access token using refresh token
        $accessToken = $this->refreshAccessToken($provider, $config, $refreshToken);

        // Fetch incremental events via API
        $events = $this->fetchIncrementalEvents($provider, $config, $accessToken);
        $syncedCount = 0;

        foreach ($events as $eventData) {
            $event = $this->reconciliationService->reconcile(
                $source->tenant_id,
                $source->calendar_id,
                $eventData,
                $provider
            );

            if ($event) {
                $syncedCount++;
            }
        }

        $source->update([
            'last_synced_at' => now(),
            'last_sync_state' => 'success',
        ]);

        return $syncedCount;
    }

    private function refreshAccessToken(string $provider, array $config, string $refreshToken): string
    {
        $tokenUrl = $provider === 'msgraph_oauth' 
            ? 'https://login.microsoftonline.com/common/oauth2/v2.0/token'
            : 'https://oauth2.googleapis.com/token';

        $response = Http::asForm()->post($tokenUrl, [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new Exception("OAuth token refresh failed for {$provider}: " . $response->status());
        }

        return $response->json()['access_token'];
    }

    private function fetchIncrementalEvents(string $provider, array $config, string $accessToken): array
    {
        // Mock event data structure returned from Graph / Google API for domain normalization
        return [];
    }
}
