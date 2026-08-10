<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Domain\IdentityProvider\Services\GrandpaSsonService;
use Illuminate\Support\Facades\Http;

class GrandpaSsonServiceTest extends TestCase
{
    /** @test */
    public function it_handles_disabled_grandpasson_seam_gracefully()
    {
        config(['services.grandpasson.enabled' => false]);
        $service = new GrandpaSsonService();

        $this->assertFalse($service->isEnabled());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GrandpaSSOn integration seam is disabled.');
        $service->exchangeCode('dummy-code');
    }

    /** @test */
    public function it_exchanges_code_for_tenant_and_user_claims_when_enabled()
    {
        config([
            'services.grandpasson.enabled' => true,
            'services.grandpasson.url' => 'http://grandpasson.local'
        ]);

        Http::fake([
            'http://grandpasson.local/session/exchange' => Http::response([
                'access_token' => 'token_xyz_123',
                'tenant' => [
                    'public_id' => 't-uuid-999',
                    'name' => 'Acme Shared Tenant'
                ],
                'user' => [
                    'email' => 'user@acme.com',
                    'name' => 'John Doe'
                ],
                'locale' => 'pt-BR',
                'groups' => ['calendar_admins']
            ], 200)
        ]);

        $service = new GrandpaSsonService();
        $claims = $service->exchangeCode('valid-auth-code', 'verifier-123');

        $this->assertEquals('token_xyz_123', $claims['access_token']);
        $this->assertEquals('t-uuid-999', $claims['tenant_public_id']);
        $this->assertEquals('user@acme.com', $claims['user']['email']);
        $this->assertEquals('pt-BR', $claims['locale']);
    }
}
