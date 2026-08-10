<?php

namespace App\Domain\TaskConnect\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class TaskConnectService
{
    private bool $enabled;
    private string $secret;

    public function __construct()
    {
        $this->enabled = (bool) config('services.taskconnect.enabled', false);
        $this->secret = (string) config('services.taskconnect.secret', 'default_secret_key');
    }

    /**
     * Check if TaskConnect auxiliary scheduler seam is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Verify incoming HMAC signature from TaskConnect HTTP task dispatch.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expectedSignature, $signature);
    }
}
