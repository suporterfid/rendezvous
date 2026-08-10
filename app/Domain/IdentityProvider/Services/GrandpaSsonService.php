<?php

namespace App\Domain\IdentityProvider\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GrandpaSsonService
{
    private string $baseUrl;
    private bool $enabled;

    public function __construct()
    {
        $this->baseUrl = config('services.grandpasson.url', 'http://localhost:8000');
        $this->enabled = (bool) config('services.grandpasson.enabled', false);
    }

    /**
     * Check if GrandpaSSOn integration seam is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Exchange authorization code for session & tenant claims via POST /session/exchange.
     *
     * @param string $code
     * @param string $codeVerifier
     * @return array
     * @throws Exception
     */
    public function exchangeCode(string $code, string $codeVerifier = ''): array
    {
        if (!$this->enabled) {
            throw new Exception("GrandpaSSOn integration seam is disabled.");
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/session/exchange';
        Log::info("Exchanging authorization code with GrandpaSSOn broker at '{$endpoint}'...");

        $response = Http::timeout(10)->post($endpoint, [
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'scopes' => ['calendar:read', 'calendar:write', 'calendar:callback'],
        ]);

        if (!$response->successful()) {
            throw new Exception("GrandpaSSOn token exchange failed with status " . $response->status());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'] ?? null,
            'tenant_public_id' => $data['tenant']['public_id'] ?? null,
            'tenant_name' => $data['tenant']['name'] ?? null,
            'user' => [
                'email' => $data['user']['email'] ?? null,
                'name' => $data['user']['name'] ?? null,
            ],
            'locale' => $data['locale'] ?? 'en',
            'groups' => $data['groups'] ?? [],
        ];
    }
}
