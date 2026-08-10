<?php

namespace App\Domain\Calendar\Services;

use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Exception;

class IcalendarParserService
{
    /**
     * Parse raw iCalendar string content into normalized DTO array.
     *
     * @param string $icsContent
     * @return array
     * @throws Exception
     */
    public function parse(string $icsContent): array
    {
        try {
            $vcalendar = Reader::read($icsContent);
        } catch (Exception $e) {
            throw new Exception("Failed to parse iCalendar data: " . $e->getMessage());
        }

        if (!$vcalendar instanceof VCalendar) {
            throw new Exception("Invalid iCalendar content: VCALENDAR root missing.");
        }

        $method = isset($vcalendar->METHOD) ? (string) $vcalendar->METHOD : 'REQUEST';
        $events = [];

        foreach ($vcalendar->select('VEVENT') as $vevent) {
            if (!$vevent instanceof VEvent) {
                continue;
            }

            $events[] = $this->extractEventData($vevent, $method);
        }

        return [
            'method' => strtoupper($method),
            'events' => $events,
        ];
    }

    private function extractEventData(VEvent $vevent, string $topMethod): array
    {
        $uid = isset($vevent->UID) ? (string) $vevent->UID : throw new Exception("VEVENT missing mandatory UID property.");
        $sequence = isset($vevent->SEQUENCE) ? (int) (string) $vevent->SEQUENCE : 0;
        $recurrenceId = isset($vevent->{'RECURRENCE-ID'}) ? (string) $vevent->{'RECURRENCE-ID'} : null;
        $summary = isset($vevent->SUMMARY) ? (string) $vevent->SUMMARY : '(No Title)';
        $description = isset($vevent->DESCRIPTION) ? (string) $vevent->DESCRIPTION : null;
        $location = isset($vevent->LOCATION) ? (string) $vevent->LOCATION : null;

        // Dates & Timezone
        $dtstart = $vevent->DTSTART->getDateTime();
        $dtend = isset($vevent->DTEND) ? $vevent->DTEND->getDateTime() : (clone $dtstart);
        
        $tzidOriginal = 'UTC';
        if (isset($vevent->DTSTART['TZID'])) {
            $tzidOriginal = (string) $vevent->DTSTART['TZID'];
        }

        // Convert to UTC ISO string
        $dtstartUtc = (clone $dtstart)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $dtendUtc = (clone $dtend)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        // RRULE & EXDATE
        $rrule = isset($vevent->RRULE) ? (string) $vevent->RRULE : null;
        $exdates = [];
        if (isset($vevent->EXDATE)) {
            foreach ($vevent->select('EXDATE') as $exdate) {
                $exdates[] = (string) $exdate;
            }
        }

        // Organizer & Attendees
        $organizer = null;
        if (isset($vevent->ORGANIZER)) {
            $organizer = [
                'email' => str_replace('mailto:', '', strtolower((string) $vevent->ORGANIZER)),
                'name' => isset($vevent->ORGANIZER['CN']) ? (string) $vevent->ORGANIZER['CN'] : null,
            ];
        }

        $attendees = [];
        if (isset($vevent->ATTENDEE)) {
            foreach ($vevent->select('ATTENDEE') as $att) {
                $attendees[] = [
                    'email' => str_replace('mailto:', '', strtolower((string) $att)),
                    'name' => isset($att['CN']) ? (string) $att['CN'] : null,
                    'partstat' => isset($att['PARTSTAT']) ? (string) $att['PARTSTAT'] : 'NEEDS-ACTION',
                    'role' => isset($att['ROLE']) ? (string) $att['ROLE'] : 'REQ-PARTICIPANT',
                ];
            }
        }

        // Status
        $status = 'confirmed';
        if (isset($vevent->STATUS)) {
            $st = strtoupper((string) $vevent->STATUS);
            if ($st === 'CANCELLED') {
                $status = 'cancelled';
            } elseif ($st === 'TENTATIVE') {
                $status = 'tentative';
            }
        }

        return [
            'uid' => $uid,
            'sequence' => $sequence,
            'recurrence_id' => $recurrenceId,
            'method_origin' => $topMethod,
            'summary' => $summary,
            'description' => $description,
            'location' => $location,
            'dtstart_utc' => $dtstartUtc,
            'dtend_utc' => $dtendUtc,
            'tzid_original' => $tzidOriginal,
            'rrule' => $rrule,
            'exdates' => $exdates,
            'organizer' => $organizer,
            'attendees' => $attendees,
            'status' => $status,
            'raw_vevent_hash' => md5($vevent->serialize()),
        ];
    }
}
