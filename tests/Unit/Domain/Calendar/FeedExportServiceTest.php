<?php

namespace Tests\Unit\Domain\Calendar;

use Tests\TestCase;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Services\FeedExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeedExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeedExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new FeedExportService();
    }

    /** @test */
    public function it_exports_calendar_feed_with_exactly_one_vcalendar_block()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $calendar = Calendar::create([
            'tenant_id' => $tenant->id,
            'name' => 'Corporate Calendar',
            'color' => '#6366f1'
        ]);

        Event::create([
            'tenant_id' => $tenant->id,
            'calendar_id' => $calendar->id,
            'uid' => 'evt-101@example.com',
            'sequence' => 0,
            'summary' => 'Project Kickoff Meeting',
            'dtstart_utc' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'dtend_utc' => now()->addDays(1)->addHour()->format('Y-m-d H:i:s'),
            'source_type' => 'email_invite',
            'status' => 'confirmed'
        ]);

        $icsOutput = $this->exportService->exportCalendarFeed($calendar);

        // Rule 1: Exactly one VCALENDAR block
        $this->assertEquals(1, substr_count($icsOutput, 'BEGIN:VCALENDAR'));
        $this->assertEquals(1, substr_count($icsOutput, 'END:VCALENDAR'));

        // Rule 2: Omit top-level METHOD property
        $this->assertStringNotContainsString('METHOD:PUBLISH', $icsOutput);
        $this->assertStringNotContainsString('METHOD:REQUEST', $icsOutput);

        // Rule 3: Include X-WR-CALNAME and X-WR-TIMEZONE
        $this->assertStringContainsString('X-WR-CALNAME:Corporate Calendar', $icsOutput);
        $this->assertStringContainsString('X-WR-TIMEZONE:UTC', $icsOutput);

        // Rule 4: Include VEVENT attributes
        $this->assertStringContainsString('UID:evt-101@example.com', $icsOutput);
        $this->assertStringContainsString('SUMMARY:Project Kickoff Meeting', $icsOutput);
    }
}
