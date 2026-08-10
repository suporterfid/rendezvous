<?php

namespace Tests\Feature\Notifications;

use Tests\TestCase;
use App\Domain\Notifications\Services\WebhookDispatchService;
use App\Domain\Calendar\Models\Event;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Models\Calendar;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_outbound_webhook_with_valid_hmac_signature()
    {
        config(['services.webhooks.enabled' => true]);

        $tenant = Tenant::create(['name' => 'Acme']);
        $calendar = Calendar::create(['tenant_id' => $tenant->id, 'name' => 'Work']);
        $event = Event::create([
            'tenant_id' => $tenant->id,
            'calendar_id' => $calendar->id,
            'uid' => 'hook-evt-100@acme.com',
            'summary' => 'Urgent Client Review',
            'dtstart_utc' => now()->addHour()->format('Y-m-d H:i:s'),
            'dtend_utc' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'source_type' => 'email_invite'
        ]);

        Http::fake([
            'https://webhook.site/test-target' => Http::response(['status' => 'received'], 200)
        ]);

        $service = new WebhookDispatchService();
        $secret = 'secret_webhook_key_99';
        
        $success = $service->dispatch($tenant->id, 'https://webhook.site/test-target', $secret, 'event.created', $event);

        $this->assertTrue($success);

        Http::assertSent(function ($request) use ($secret) {
            $payload = $request->body();
            $signature = $request->header('X-Rendezvous-Signature')[0] ?? '';
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            return $request->hasHeader('X-Rendezvous-Signature') &&
                   $request->header('X-Rendezvous-Event')[0] === 'event.created' &&
                   hash_equals($expectedSignature, $signature);
        });
    }
}
