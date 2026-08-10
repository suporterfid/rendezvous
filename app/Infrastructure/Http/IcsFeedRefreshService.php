<?php

namespace App\Infrastructure\Http;

use App\Domain\Calendar\Models\CalendarSource;
use App\Domain\Calendar\Services\IcalendarParserService;
use App\Domain\Calendar\Services\EventReconciliationService;
use App\Infrastructure\Security\SsrfGuardService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class IcsFeedRefreshService
{
    public function __construct(
        private SsrfGuardService $ssrfGuard,
        private IcalendarParserService $parserService,
        private EventReconciliationService $reconciliationService
    ) {}

    /**
     * Refresh a subscribed external ICS feed source.
     * Validates URL against SSRF and reconciles events.
     *
     * @param CalendarSource $source
     * @return int Number of updated/synced events
     */
    public function refreshSource(CalendarSource $source): int
    {
        if ($source->source_type !== 'ics_feed') {
            return 0;
        }

        $config = $source->config_json ?? [];
        $feedUrl = $config['url'] ?? null;

        if (!$feedUrl) {
            Log::warning("CalendarSource ID {$source->id} missing feed URL in config_json.");
            return 0;
        }

        try {
            // 1. SSRF Protection Check
            $this->ssrfGuard->validateUrl($feedUrl);

            // Rewrite webcal:// to https://
            $httpUrl = preg_replace('/^webcal:\/\//i', 'https://', $feedUrl);

            // 2. Fetch Feed with Timeout & ETag headers
            Log::info("Fetching external ICS feed from '{$httpUrl}' for source ID {$source->id}...");
            $headers = ['User-Agent' => 'Rendezvous-Calendar-Mashup/1.0'];

            if (!empty($config['etag'])) {
                $headers['If-None-Match'] = $config['etag'];
            }

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->get($httpUrl);

            if ($response->status() === 304) {
                Log::info("Feed for source ID {$source->id} unchanged (304 Not Modified).");
                $source->update([
                    'last_synced_at' => now(),
                    'last_sync_state' => 'success',
                ]);
                return 0;
            }

            if (!$response->successful()) {
                throw new Exception("HTTP request failed with status code " . $response->status());
            }

            $body = $response->body();

            // 3. Parse iCalendar Content
            $parsed = $this->parserService->parse($body);
            $syncedCount = 0;

            foreach ($parsed['events'] as $eventData) {
                $event = $this->reconciliationService->reconcile(
                    $source->tenant_id,
                    $source->calendar_id,
                    $eventData,
                    'ics_feed_subscription'
                );

                if ($event) {
                    $syncedCount++;
                }
            }

            // Update ETag & Last Synced State
            $etag = $response->header('ETag');
            $updatedConfig = array_merge($config, ['etag' => $etag]);

            $source->update([
                'config_json' => $updatedConfig,
                'last_synced_at' => now(),
                'last_sync_state' => 'success',
            ]);

            return $syncedCount;

        } catch (Exception $e) {
            Log::error("Failed to refresh ICS feed for source ID {$source->id}: " . $e->getMessage());

            $source->update([
                'last_synced_at' => now(),
                'last_sync_state' => 'error',
            ]);

            return 0;
        }
    }
}
