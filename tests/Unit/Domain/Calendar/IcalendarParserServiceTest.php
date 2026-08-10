<?php

namespace Tests\Unit\Domain\Calendar;

use Tests\TestCase;
use App\Domain\Calendar\Services\IcalendarParserService;

class IcalendarParserServiceTest extends TestCase
{
    private IcalendarParserService $parserService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parserService = new IcalendarParserService();
    }

    /** @test */
    public function it_parses_valid_vevent_ics_content_correctly()
    {
        $icsData = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Example Corp//NONSGML v1.0//EN
METHOD:REQUEST
BEGIN:VEVENT
UID:test-uid-12345@corp.example.com
SEQUENCE:2
SUMMARY:Weekly Architecture Sync
DESCRIPTION:Discussion on Rendezvous platform
LOCATION:Room 402 / Teams
DTSTART:20260815T140000Z
DTEND:20260815T150000Z
ORGANIZER;CN=Alice Organizer:mailto:alice@corp.example.com
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=Bob:mailto:bob@corp.example.com
END:VEVENT
END:VCALENDAR
ICS;

        $parsed = $this->parserService->parse($icsData);

        $this->assertEquals('REQUEST', $parsed['method']);
        $this->assertCount(1, $parsed['events']);

        $event = $parsed['events'][0];
        $this->assertEquals('test-uid-12345@corp.example.com', $event['uid']);
        $this->assertEquals(2, $event['sequence']);
        $this->assertEquals('Weekly Architecture Sync', $event['summary']);
        $this->assertEquals('alice@corp.example.com', $event['organizer']['email']);
        $this->assertEquals('bob@corp.example.com', $event['attendees'][0]['email']);
    }
}
