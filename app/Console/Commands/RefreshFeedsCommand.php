<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Calendar\Models\CalendarSource;
use App\Infrastructure\Http\IcsFeedRefreshService;

class RefreshFeedsCommand extends Command
{
    protected $signature = 'calendar:refresh-feeds';
    protected $description = 'Fetch and refresh subscribed external ICS calendar feeds';

    public function handle(IcsFeedRefreshService $refreshService): int
    {
        $this->info("Starting external ICS feeds refresh cycle...");

        $sources = CalendarSource::where('source_type', 'ics_feed')->get();
        $totalSynced = 0;

        foreach ($sources as $source) {
            $count = $refreshService->refreshSource($source);
            $totalSynced += $count;
        }

        $this->info("Completed feed refresh cycle. Total events synced: {$totalSynced}");
        return Command::SUCCESS;
    }
}
