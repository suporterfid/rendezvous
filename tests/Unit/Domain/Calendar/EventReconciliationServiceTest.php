<?php

namespace Tests\Unit\Domain\Calendar;

use Tests\TestCase;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;
use App\Domain\Calendar\Services\EventReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private EventReconciliationService $reconciliationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reconciliationService = new EventReconciliationService();
    }

    /** @test */
    public function it_reconciles_events_idempotently_by_sequence()
    {
        $tenant = Tenant::create(['name' => 'Acme Corp']);
        $calendar = Calendar::create(['tenant_id' => $tenant->id, 'name' => 'Work']);

        $payloadV1 = [
            'uid' => 'sync-evt-99@acme.com',
            'sequence' => 1,
            'recurrence_id' => null,
            'method_origin' => 'REQUEST',
            'summary' => 'Initial Sprint Planning',
            'dtstart_utc' => '2026-08-20 10:00:00',
            'dtend_utc' => '2026-08-20 11:00:00',
            'status' => 'confirmed'
        ];

        // 1. First Ingestion (Sequence 1)
        $event1 = $this->reconciliationService->reconcile($tenant->id, $calendar->id, $payloadV1, 'email_invite');
        $this->assertEquals(1, $event1->sequence);
        $this->assertEquals('Initial Sprint Planning', $event1->summary);

        // 2. Duplicate Ingestion with same Sequence 1 (MUST be ignored idempotently)
        $eventDuplicate = $this->reconciliationService->reconcile($tenant->id, $calendar->id, $payloadV1, 'email_invite');
        $this->assertEquals(1, Event::count());

        // 3. Updated Ingestion with Sequence 2 (MUST update existing record)
        $payloadV2 = array_merge($payloadV1, [
            'sequence' => 2,
            'summary' => 'Updated Sprint Planning (Rescheduled)'
        ]);

        $event2 = $this->reconciliationService->reconcile($tenant->id, $calendar->id, $payloadV2, 'email_invite');
        $this->assertEquals(1, Event::count());
        $this->assertEquals('Updated Sprint Planning (Rescheduled)', $event2->fresh()->summary);

        // 4. Method CANCEL (MUST mark status as cancelled without deleting)
        $payloadCancel = array_merge($payloadV2, [
            'sequence' => 3,
            'method_origin' => 'CANCEL'
        ]);

        $eventCancelled = $this->reconciliationService->reconcile($tenant->id, $calendar->id, $payloadCancel, 'email_invite');
        $this->assertEquals('cancelled', $eventCancelled->fresh()->status);
        $this->assertEquals(1, Event::count());
    }
}
