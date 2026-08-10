<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\Event;
use Illuminate\Support\Facades\Log;

class EventReconciliationService
{
    /**
     * Reconcile an incoming event payload against existing records.
     * Enforces UID + SEQUENCE idempotency rules, METHOD:CANCEL, and METHOD:REPLY.
     *
     * @param int $tenantId
     * @param int $calendarId
     * @param array $eventData
     * @param string $sourceType
     * @return Event|null
     */
    public function reconcile(int $tenantId, int $calendarId, array $eventData, string $sourceType): ?Event
    {
        $uid = $eventData['uid'];
        $sequence = $eventData['sequence'];
        $recurrenceId = $eventData['recurrence_id'];
        $methodOrigin = strtoupper($eventData['method_origin'] ?? 'REQUEST');

        // Check for existing event matching UID & RecurrenceID within tenant + calendar
        $existing = Event::where('tenant_id', $tenantId)
            ->where('calendar_id', $calendarId)
            ->where('uid', $uid)
            ->where('recurrence_id', $recurrenceId)
            ->first();

        // 1. Handle METHOD:CANCEL
        if ($methodOrigin === 'CANCEL') {
            if ($existing) {
                $existing->update(['status' => 'cancelled']);
                return $existing;
            }
            // Event not found locally, store cancelled placeholder
            return Event::create($this->buildModelPayload($tenantId, $calendarId, $eventData, $sourceType, 'cancelled'));
        }

        // 2. Handle METHOD:REPLY
        if ($methodOrigin === 'REPLY') {
            if ($existing) {
                // Update attendee response status without mutating main event metadata
                $attendees = $existing->attendees_json ?? [];
                $incomingAttendees = $eventData['attendees'] ?? [];
                
                foreach ($incomingAttendees as $inc) {
                    foreach ($attendees as &$att) {
                        if (strtolower($att['email']) === strtolower($inc['email'])) {
                            $att['partstat'] = $inc['partstat'];
                        }
                    }
                }
                
                $existing->update(['attendees_json' => $attendees]);
                return $existing;
            }
            return null; // Cannot reply to un-ingested event
        }

        // 3. Idempotency Check (SEQUENCE comparison)
        if ($existing) {
            if ($sequence <= $existing->sequence) {
                Log::info("Ignoring event update for UID {$uid}: incoming sequence {$sequence} <= existing sequence {$existing->sequence}");
                return $existing; // Silently ignore stale or duplicate sequence
            }

            // Update existing event with higher sequence
            $existing->update($this->buildModelPayload($tenantId, $calendarId, $eventData, $sourceType, $eventData['status']));
            return $existing;
        }

        // 4. Create new Event
        return Event::create($this->buildModelPayload($tenantId, $calendarId, $eventData, $sourceType, $eventData['status']));
    }

    private function buildModelPayload(int $tenantId, int $calendarId, array $d, string $sourceType, string $status): array
    {
        return [
            'tenant_id' => $tenantId,
            'calendar_id' => $calendarId,
            'uid' => $d['uid'],
            'sequence' => $d['sequence'],
            'recurrence_id' => $d['recurrence_id'],
            'method_origin' => $d['method_origin'] ?? 'REQUEST',
            'organizer_json' => $d['organizer'] ?? null,
            'attendees_json' => $d['attendees'] ?? [],
            'summary' => $d['summary'],
            'description' => $d['description'] ?? null,
            'location' => $d['location'] ?? null,
            'dtstart_utc' => $d['dtstart_utc'],
            'dtend_utc' => $d['dtend_utc'],
            'tzid_original' => $d['tzid_original'] ?? 'UTC',
            'rrule' => $d['rrule'] ?? null,
            'exdates_json' => $d['exdates'] ?? [],
            'status' => $status,
            'source_type' => $sourceType,
            'raw_vevent_hash' => $d['raw_vevent_hash'] ?? null,
        ];
    }
}
