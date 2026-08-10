<?php

namespace App\Infrastructure\Security;

use App\Domain\Security\Models\Secret;
use Illuminate\Support\Facades\Crypt;
use Exception;

class SecretEncryptionService
{
    /**
     * Store or update an encrypted secret for a tenant.
     *
     * @param int $tenantId
     * @param string $secretKey
     * @param string $plainTextValue
     * @return Secret
     */
    public function storeSecret(int $tenantId, string $secretKey, string $plainTextValue): Secret
    {
        $encrypted = Crypt::encryptString($plainTextValue);

        return Secret::updateOrCreate(
            ['tenant_id' => $tenantId, 'secret_key' => $secretKey],
            ['encrypted_value' => $encrypted]
        );
    }

    /**
     * Retrieve and decrypt a secret value by key.
     *
     * @param int $tenantId
     * @param string $secretKey
     * @return string
     * @throws Exception
     */
    public function retrieveSecret(int $tenantId, string $secretKey): string
    {
        $secret = Secret::where('tenant_id', $tenantId)
            ->where('secret_key', $secretKey)
            ->first();

        if (!$secret) {
            throw new Exception("Secret reference '{$secretKey}' not found for tenant ID {$tenantId}.");
        }

        return Crypt::decryptString($secret->encrypted_value);
    }

    /**
     * Redact sensitive fields in arrays/logs.
     *
     * @param array $data
     * @return array
     */
    public function redactSecrets(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'credential', 'api_key', 'authorization'];

        foreach ($data as $key => &$value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '[REDACTED]';
            } elseif (is_array($value)) {
                $value = $this->redactSecrets($value);
            }
        }

        return $data;
    }
}
