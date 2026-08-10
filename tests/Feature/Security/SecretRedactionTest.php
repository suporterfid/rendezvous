<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Infrastructure\Security\SecretEncryptionService;

class SecretRedactionTest extends TestCase
{
    private SecretEncryptionService $secretService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->secretService = new SecretEncryptionService();
    }

    /** @test */
    public function it_redacts_sensitive_keys_in_logs_and_arrays()
    {
        $inputData = [
            'tenant_id' => 1,
            'imap_host' => 'imap.corp.com',
            'password' => 'SuperSecret123!',
            'api_key' => 'key_abc123xyz',
            'config' => [
                'token' => 'secret_bearer_token',
                'format' => 'json'
            ]
        ];

        $redacted = $this->secretService->redactSecrets($inputData);

        $this->assertEquals('[REDACTED]', $redacted['password']);
        $this->assertEquals('[REDACTED]', $redacted['api_key']);
        $this->assertEquals('[REDACTED]', $redacted['config']['token']);
        $this->assertEquals('imap.corp.com', $redacted['imap_host']);
    }
}
