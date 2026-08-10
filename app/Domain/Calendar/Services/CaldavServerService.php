<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;
use Illuminate\Support\Facades\Log;
use Exception;

class CaldavServerService
{
    public function __construct(
        private IcalendarParserService $parserService,
        private EventReconciliationService $reconciliationService
    ) {}

    /**
     * Handle incoming CalDAV PUT request (creating or updating an event via CalDAV client).
     *
     * @param Calendar $calendar
     * @param string $eventUid
     * @param string $icsBody
     * @return Event
     * @throws Exception
     */
    public function handlePut(Calendar $calendar, string $eventUid, string $icsBody): Event
    {
        Log::info("CalDAV PUT request received for UID '{$eventUid}' on calendar ID {$calendar->id}...");

        $parsed = $this->parserService->parse($icsBody);

        if (empty($parsed['events'])) {
            throw new Exception("No valid VEVENT component found in CalDAV PUT body.");
        }

        $eventData = $parsed['events'][0];
        $eventData['uid'] = $eventUid; // Enforce URL UID parameter

        $event = $this->reconciliationService->reconcile(
            $calendar->tenant_id,
            $calendar->id,
            $eventData,
            'caldav_write'
        );

        if (!$event) {
            throw new Exception("Failed to reconcile CalDAV event UID '{$eventUid}'.");
        }

        return $event;
    }

    /**
     * Handle incoming CalDAV DELETE request (deleting or cancelling an event via CalDAV client).
     *
     * @param Calendar $calendar
     * @param string $eventUid
     * @return bool
     */
    public function handleDelete(Calendar $calendar, string $eventUid): bool
    {
        Log::info("CalDAV DELETE request received for UID '{$eventUid}' on calendar ID {$calendar->id}...");

        $event = Event::where('tenant_id', $calendar->tenant_id)
            ->where('calendar_id', $calendar->id)
            ->where('uid', $eventUid)
            ->first();

        if ($event) {
            $event->update(['status' => 'cancelled']);
            return true;
        }

        return false;
    }
}
