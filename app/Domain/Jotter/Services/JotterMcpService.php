<?php

namespace App\Domain\Jotter\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class JotterMcpService
{
    private bool $enabled;
    private string $mcpEndpoint;

    public function __construct()
    {
        $this->enabled = (bool) config('services.jotter.enabled', false);
        $this->mcpEndpoint = (string) config('services.jotter.mcp_url', 'http://localhost:8001/mcp');
    }

    /**
     * Check if Jotter MCP integration seam is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Search notes in Jotter knowledge base by query keyword.
     *
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function searchNotes(string $query): array
    {
        if (!$this->enabled) {
            return [];
        }

        Log::info("Executing Jotter MCP search_notes query '{$query}'...");

        $response = Http::timeout(5)->post($this->mcpEndpoint, [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'search_notes',
                'arguments' => ['query' => $query]
            ],
            'id' => 1
        ]);

        if (!$response->successful()) {
            Log::warning("Jotter MCP search_notes call failed with status " . $response->status());
            return [];
        }

        return $response->json()['result']['content'] ?? [];
    }

    /**
     * Format a wikilink string for cross-referencing in Rendezvous event description.
     *
     * @param string $noteTitle
     * @return string
     */
    public function formatWikilink(string $noteTitle): string
    {
        return "[[" . trim($noteTitle) . "]]";
    }
}
