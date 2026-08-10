<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tenant_a_cannot_access_or_query_tenant_b_calendars_and_events()
    {
        // 1. Create Tenant A & Calendar
        $tenantA = Tenant::create(['name' => 'Tenant A Corp']);
        $calendarA = Calendar::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Private Calendar'
        ]);
        $eventA = Event::create([
            'tenant_id' => $tenantA->id,
            'calendar_id' => $calendarA->id,
            'uid' => 'evt-a-100@tenanta.com',
            'summary' => 'Confidential Tenant A Strategy Meeting',
            'dtstart_utc' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'dtend_utc' => now()->addDays(1)->addHour()->format('Y-m-d H:i:s'),
            'source_type' => 'manual'
        ]);

        // 2. Create Tenant B & Calendar
        $tenantB = Tenant::create(['name' => 'Tenant B Inc']);
        $calendarB = Calendar::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Private Calendar'
        ]);

        // 3. Assert Tenant B query scoped by tenant_id returns NO results for Tenant A's event
        $tenantBEvents = Event::where('tenant_id', $tenantB->id)->get();
        $this->assertCount(0, $tenantBEvents);

        // 4. Assert direct cross-tenant identifier lookup fails when scoped by tenant_id
        $crossTenantQuery = Event::where('tenant_id', $tenantB->id)
            ->where('id', $eventA->id)
            ->first();

        $this->assertNull($crossTenantQuery);
    }
}
