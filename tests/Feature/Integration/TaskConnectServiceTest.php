<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Domain\TaskConnect\Services\TaskConnectService;

class TaskConnectServiceTest extends TestCase
{
    /** @test */
    public function it_verifies_valid_hmac_signatures()
    {
        config([
            'services.taskconnect.enabled' => true,
            'services.taskconnect.secret' => 'my_secret_key_123'
        ]);

        $service = new TaskConnectService();
        $payload = json_encode(['task_id' => 'tc-job-100']);
        $validSignature = hash_hmac('sha256', $payload, 'my_secret_key_123');

        $this->assertTrue($service->verifySignature($payload, $validSignature));
        $this->assertFalse($service->verifySignature($payload, 'invalid_sig'));
    }

    /** @test */
    public function it_rejects_webhooks_when_taskconnect_seam_is_disabled()
    {
        config(['services.taskconnect.enabled' => false]);
        $service = new TaskConnectService();

        $this->assertFalse($service->verifySignature('payload', 'sig'));

        $response = $this->postJson('/api/v1/taskconnect/ingest-mailboxes', []);
        $response->assertStatus(403);
    }
}
