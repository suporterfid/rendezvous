<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Calendar\Models\MailboxIngestConfig;
use App\Infrastructure\Imap\MailboxIngestionService;
use Illuminate\Support\Facades\Log;

class IngestMailboxesCommand extends Command
{
    protected $signature = 'calendar:ingest-mailboxes';
    protected $description = 'Poll configured tenant IMAP mailboxes for forwarded meeting invites (.ics / text/calendar)';

    public function handle(MailboxIngestionService $ingestionService): int
    {
        $this->info("Starting IMAP mailbox ingestion cycle...");

        // Retrieve all configured mailboxes
        $configs = MailboxIngestConfig::all();
        $totalProcessed = 0;

        foreach ($configs as $config) {
            $count = $ingestionService->processMailbox($config);
            $totalProcessed += $count;
        }

        $this->info("Completed ingestion cycle. Total events processed: {$totalProcessed}");
        return Command::SUCCESS;
    }
}
