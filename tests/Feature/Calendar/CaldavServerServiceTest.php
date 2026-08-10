<?php

namespace Tests\Feature\Calendar;

use Tests\TestCase;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CaldavServerServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_caldav_put_and_delete_requests()
    {
        $tenant = Tenant::create(['name' => 'CalDAV Corp']);
        $calendar = Calendar::create(['tenant_id' => $tenant->id, 'name' => 'Writing Calendar']);

        $icsBody = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//CalDAV Client//EN
BEGIN:VEVENT
UID:caldav-put-123@corp.com
SEQUENCE:0
SUMMARY:CalDAV Native Client Meeting
DTSTART:20260901T100000Z
DTEND:20260901T110000Z
END:VEVENT
END:VCALENDAR
ICS;

        // 1. CalDAV PUT Request (Create event)
        $responsePut = $this->call('PUT', "/api/v1/caldav/calendars/{$calendar->public_id}/events/caldav-put-123@corp.com", [], [], [], [], $icsBody);
        $responsePut->assertStatus(201);
        $this->assertEquals(1, Event::count());

        // 2. CalDAV DELETE Request (Cancel event)
        $responseDelete = $this->call('DELETE', "/api/v1/caldav/calendars/{$calendar->public_id}/events/caldav-put-123@corp.com");
        $responseDelete->assertStatus(204);
        $this->assertEquals('cancelled', Event::first()->status);
    }
}
