<?php

namespace Tests\Feature\Calendar;

use Tests\TestCase;
use App\Domain\Calendar\Services\OauthIngestionService;
use App\Domain\Calendar\Models\CalendarSource;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Calendar\Models\Calendar;
use App\Infrastructure\Security\SecretEncryptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OauthIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_refreshes_oauth_tokens_and_fetches_events_from_msgraph()
    {
        $tenant = Tenant::create(['name' => 'OAuth Corp']);
        $calendar = Calendar::create(['tenant_id' => $tenant->id, 'name' => 'Graph Calendar']);

        $secretService = new SecretEncryptionService();
        $secret = $secretService->storeSecret($tenant->id, 'ref_msgraph_token', 'refresh_token_abc_123');

        $source = CalendarSource::create([
            'tenant_id' => $tenant->id,
            'calendar_id' => $calendar->id,
            'source_type' => 'msgraph_oauth',
            'config_json' => [
                'client_id' => 'client_id_app',
                'client_secret' => 'client_secret_app'
            ],
            'secret_ref' => 'ref_msgraph_token'
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'access_token_fresh_999',
                'expires_in' => 3600
            ], 200)
        ]);

        $service = $this->app->make(OauthIngestionService::class);
        $count = $service->ingestFromOauthSource($source);

        $this->assertEquals(0, $count);
        $this->assertEquals('success', $source->fresh()->last_sync_state);
    }
}
