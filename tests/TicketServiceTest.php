<?php

declare(strict_types=1);

namespace GlpiPlugin\Nessusglpi\Tests;

use GlpiPlugin\Nessusglpi\TicketService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for the pure (database-free) helpers of TicketService — mainly the HTML ticket
 * builders, which are the bulk of the class and the part most prone to silent regressions.
 *
 * Runnable two ways:
 *   - real PHPUnit:  vendor/bin/phpunit -c phpunit.xml.dist
 *   - no deps:       php tests/run.php   (uses a tiny TestCase polyfill)
 */
class TicketServiceTest extends TestCase
{
    /**
     * Invoke a private/protected TicketService method by name.
     *
     * @param array<int, mixed> $args
     */
    private function invoke(string $method, array $args)
    {
        $service = new TicketService();
        $ref = new ReflectionMethod(TicketService::class, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($service, $args);
    }

    public function testSeverityHtmlMetaCritical(): void
    {
        $meta = $this->invoke('severityHtmlMeta', [4, 'Critical']);

        $this->assertSame('💀', $meta['icon']);
        $this->assertStringContainsString('CRITICAL', $meta['label']);
    }

    public function testSeverityHtmlMetaInfoFallback(): void
    {
        $meta = $this->invoke('severityHtmlMeta', [0, '']);

        $this->assertSame('🔵', $meta['icon']);
    }

    public function testNormalizeSeverityLabelUsesSeverityWhenLabelMissing(): void
    {
        $this->assertSame('High', $this->invoke('normalizeSeverityLabel', ['', 3]));
        $this->assertSame('Critical', $this->invoke('normalizeSeverityLabel', ['', 4]));
    }

    public function testNormalizeSeverityLabelKeepsCustomLabel(): void
    {
        $this->assertSame('Elevated', $this->invoke('normalizeSeverityLabel', ['Elevated', 2]));
    }

    public function testJoinPortProtocol(): void
    {
        $this->assertSame('443/tcp', $this->invoke('joinPortProtocol', ['443', 'TCP']));
        $this->assertSame('', $this->invoke('joinPortProtocol', ['', '']));
        $this->assertSame('80', $this->invoke('joinPortProtocol', ['80', '']));
        $this->assertSame('udp', $this->invoke('joinPortProtocol', ['', 'UDP']));
    }

    public function testExtractCveListFiltersAndUppercases(): void
    {
        $list = $this->invoke('extractCveList', ['CVE-2021-1234, cve-2021-9999 not-a-cve']);

        $this->assertSame(['CVE-2021-1234', 'CVE-2021-9999'], $list);
    }

    public function testFirstNonEmpty(): void
    {
        $this->assertSame('x', $this->invoke('firstNonEmpty', [[null, '', 'x', 'y']]));
        $this->assertSame('', $this->invoke('firstNonEmpty', [[null, '']]));
    }

    public function testBuildHostLabelPrefersFqdnThenHostnameThenIp(): void
    {
        $this->assertSame('h.example.com', $this->invoke('buildHostLabel', [['fqdn' => 'h.example.com', 'hostname' => 'h', 'ip' => '1.2.3.4']]));
        $this->assertSame('h', $this->invoke('buildHostLabel', [['fqdn' => '', 'hostname' => 'h', 'ip' => '1.2.3.4']]));
        $this->assertSame('1.2.3.4', $this->invoke('buildHostLabel', [['fqdn' => '', 'hostname' => '', 'ip' => '1.2.3.4']]));
    }

    public function testBuildVulnerabilityTitleFormat(): void
    {
        $title = $this->invoke('buildVulnerabilityTitle', [
            ['severity' => 3, 'severity_label' => 'High', 'plugin_name' => 'OpenSSL flaw'],
            ['fqdn' => 'web01.local'],
        ]);

        $this->assertSame('[High] web01.local - OpenSSL flaw', $title);
    }

    public function testBuildVulnerabilityContentEscapesHtmlAndLinksCve(): void
    {
        $html = $this->invoke('buildVulnerabilityContent', [
            [
                'severity'         => 4,
                'severity_label'   => 'Critical',
                'plugin_name'      => '<script>alert(1)</script>',
                'cve'              => 'CVE-2020-0001',
                'plugin_id_nessus' => '12345',
            ],
            ['fqdn' => 'web01.local'],
            ['scan_id' => '42'],
            null,
        ]);

        // XSS in the plugin name must be escaped, never emitted raw.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        // CVE rendered as an NVD link.
        $this->assertStringContainsString('https://nvd.nist.gov/vuln/detail/CVE-2020-0001', $html);
        // Critical hero badge.
        $this->assertStringContainsString('CRITICAL', $html);
    }

    public function testBuildResolutionContentMentionsClearedVulnerability(): void
    {
        $html = $this->invoke('buildResolutionContent', []);

        $this->assertStringContainsString('no longer detected', $html);
    }
}
