<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Infrastructure\Security\SsrfGuardService;
use Exception;

class SsrfProtectionTest extends TestCase
{
    private SsrfGuardService $ssrfGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ssrfGuard = new SsrfGuardService();
    }

    /** @test */
    public function it_blocks_internal_loopback_and_private_ip_feed_urls()
    {
        $maliciousUrls = [
            'http://127.0.0.1/feed.ics',
            'http://localhost/secret',
            'http://10.0.0.1/internal',
            'http://192.168.1.1/router',
            'http://169.254.169.254/latest/meta-data/', // AWS IMDS Metadata IP
            'ftp://example.com/feed.ics', // Disallowed scheme
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $this->ssrfGuard->validateUrl($url);
                $this->fail("Expected SSRF protection exception for URL: {$url}");
            } catch (Exception $e) {
                $this->assertStringContainsString('SSRF Protection Block', $e->getMessage() . 'Disallowed URL scheme');
            }
        }
    }

    /** @test */
    public function it_allows_valid_public_https_feed_urls()
    {
        $validUrls = [
            'https://calendar.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics',
            'webcal://example.com/public-feed.ics',
        ];

        foreach ($validUrls as $url) {
            try {
                $this->ssrfGuard->validateUrl($url);
                $this->assertTrue(true);
            } catch (Exception $e) {
                // If DNS fails in offline mode, ensure exception is DNS related, not SSRF block
                $this->assertStringContainsString('Unable to resolve hostname', $e->getMessage());
            }
        }
    }
}
