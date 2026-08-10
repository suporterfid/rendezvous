<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;

class FeedExportService
{
    /**
     * Generate iCalendar export feed compliant with Outlook 365 and Google Workspace.
     *
     * Rules:
     * - Exactly one VCALENDAR root component
     * - No top-level METHOD property (avoids Outlook single-invite interpretation)
     * - Includes X-WR-CALNAME and X-WR-TIMEZONE
     * - Valid VEVENT items with UID, DTSTAMP, DTSTART, DTEND
     *
     * @param Calendar $calendar
     * @return string
     */
    public function exportCalendarFeed(Calendar $calendar): string
    {
        $events = Event::where('tenant_id', $calendar->tenant_id)
            ->where('calendar_id', $calendar->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('dtstart_utc', 'asc')
            ->get();

        $calName = $this->escapeIcsText($calendar->name);
        $timezone = 'UTC';

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Rendezvous//Calendar Mash-up Platform//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = "X-WR-CALNAME:{$calName}";
        $lines[] = "X-WR-TIMEZONE:{$timezone}";
        $lines[] = 'X-PUBLISHED-TTL:PT1H';
        $lines[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT1H';

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $event->uid;
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z', strtotime($event->created_at));
            $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', strtotime($event->dtstart_utc));
            $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', strtotime($event->dtend_utc));
            $lines[] = 'SUMMARY:' . $this->escapeIcsText($event->summary);
            
            if ($event->description) {
                $lines[] = 'DESCRIPTION:' . $this->escapeIcsText($event->description);
            }
            if ($event->location) {
                $lines[] = 'LOCATION:' . $this->escapeIcsText($event->location);
            }
            if ($event->rrule) {
                $lines[] = 'RRULE:' . $event->rrule;
            }
            if ($event->status === 'tentative') {
                $lines[] = 'STATUS:TENTATIVE';
            } else {
                $lines[] = 'STATUS:CONFIRMED';
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escapeIcsText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace("\n", '\n', $text);
        return str_replace("\r", '', $text);
    }
}
