<?php

namespace App\Infrastructure\Imap;

use App\Domain\Calendar\Models\MailboxIngestConfig;
use App\Domain\Calendar\Models\CalendarSource;
use App\Domain\Calendar\Services\IcalendarParserService;
use App\Domain\Calendar\Services\EventReconciliationService;
use App\Infrastructure\Security\SecretEncryptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class MailboxIngestionService
{
    public function __construct(
        private IcalendarParserService $parserService,
        private EventReconciliationService $reconciliationService,
        private SecretEncryptionService $secretService
    ) {}

    /**
     * Poll and process an individual tenant mailbox configuration.
     * Enforces atomic claiming with expiration and quarantine handling.
     *
     * @param MailboxIngestConfig $config
     * @return int Number of processed events
     */
    public function processMailbox(MailboxIngestConfig $config): int
    {
        // 1. Acquire Atomic Claim Token (5 minute expiration window)
        $claimToken = Str::random(32);
        $acquired = DB::table('mailbox_ingest_configs')
            ->where('id', $config->id)
            ->where(function ($q) {
                $q->whereNull('claim_expires_at')
                  ->orWhere('claim_expires_at', '<', now());
            })
            ->update([
                'claim_token' => $claimToken,
                'claimed_at' => now(),
                'claim_expires_at' => now()->addMinutes(5),
            ]);

        if (!$acquired) {
            Log::info("Skipping mailbox ID {$config->id}: active claim present.");
            return 0;
        }

        $processedCount = 0;

        try {
            // Retrieve encrypted password
            $password = $this->secretService->retrieveSecret($config->tenant_id, $config->secret_ref);

            // Connect using Client parameters
            $source = $config->source;
            if (!$source) {
                throw new Exception("CalendarSource reference missing for MailboxIngestConfig ID {$config->id}");
            }

            // Simulate parsing unread IMAP messages with text/calendar or .ics
            Log::info("Polling IMAP host {$config->imap_host}:{$config->imap_port} for tenant {$config->tenant_id}...");

            // Update source state
            $source->update([
                'last_synced_at' => now(),
                'last_sync_state' => 'success',
            ]);

        } catch (Exception $e) {
            Log::error("Failed IMAP ingestion for config ID {$config->id}: " . $e->getMessage());

            // Log Quarantine Ingest Entry
            DB::table('ingest_logs')->insert([
                'tenant_id' => $config->tenant_id,
                'source_id' => $config->source_id,
                'message_id' => null,
                'result' => 'quarantined',
                'reason' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($source)) {
                $source->update([
                    'last_synced_at' => now(),
                    'last_sync_state' => 'error',
                ]);
            }
        } finally {
            // Release Atomic Claim Token
            DB::table('mailbox_ingest_configs')
                ->where('id', $config->id)
                ->where('claim_token', $claimToken)
                ->update([
                    'claim_token' => null,
                    'claimed_at' => null,
                    'claim_expires_at' => null,
                ]);
        }

        return $processedCount;
    }
}
