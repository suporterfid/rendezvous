<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Domain\Jotter\Services\JotterMcpService;
use Illuminate\Support\Facades\Http;

class JotterMcpServiceTest extends TestCase
{
    /** @test */
    public function it_returns_empty_results_when_jotter_mcp_is_disabled()
    {
        config(['services.jotter.enabled' => false]);
        $service = new JotterMcpService();

        $this->assertFalse($service->isEnabled());
        $this->assertEquals([], $service->searchNotes('Sprint Planning'));
    }

    /** @test */
    public function it_calls_jotter_mcp_search_notes_tool_when_enabled()
    {
        config([
            'services.jotter.enabled' => true,
            'services.jotter.mcp_url' => 'http://jotter.local/mcp'
        ]);

        Http::fake([
            'http://jotter.local/mcp' => Http::response([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        ['title' => 'Sprint Kickoff Prep Notes', 'path' => '/notes/prep.md']
                    ]
                ],
                'id' => 1
            ], 200)
        ]);

        $service = new JotterMcpService();
        $notes = $service->searchNotes('Sprint');

        $this->assertCount(1, $notes);
        $this->assertEquals('Sprint Kickoff Prep Notes', $notes[0]['title']);
        $this->assertEquals('[[Sprint Kickoff Prep Notes]]', $service->formatWikilink($notes[0]['title']));
    }
}
