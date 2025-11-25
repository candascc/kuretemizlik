<?php
/**
 * Functional Test: Header Security Hardening
 *
 * Validates canonical/OpenGraph URL sanitization helper used in layout header.
 * Converted to PHPUnit from standalone test
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

final class HeaderSecurityTest extends TestCase
{
    /**
     * Test: Valid canonical URL preserved
     */
    public function testCanonicalUrlPreservesValidInput(): void
    {
        $raw = 'https://tenant.example.com/app/dashboard?filter=active';
        $sanitized = \Utils::sanitizeUrl($raw, 'http://localhost/app/');

        $this->assertEquals(
            'https://tenant.example.com/app/dashboard?filter=active',
            $sanitized,
            'Valid canonical URL should be preserved'
        );
    }

    /**
     * Test: Injected host rejected
     */
    public function testCanonicalUrlRejectsInjectedHost(): void
    {
        $raw = "https://tenant.example.com\"/><script>alert(1)</script>";
        $fallback = 'http://localhost/app/';
        $sanitized = Utils::sanitizeUrl($raw, $fallback);

        $this->assertEquals(
            $fallback,
            $sanitized,
            'Malicious host should be replaced with fallback'
        );
    }

    /**
     * Test: Malicious path cleaned
     */
    public function testCanonicalUrlCleansMaliciousPath(): void
    {
        $raw = "https://tenant.example.com/app/%0d%0a<script>alert(1)</script>?foo=bar";
        $sanitized = \Utils::sanitizeUrl($raw, 'http://localhost/app/');

        $this->assertStringNotContainsString('%', $sanitized, 'URL should not contain encoded newlines');
        $this->assertStringNotContainsString("\n", $sanitized, 'URL should not contain newlines');
        $this->assertStringStartsWith('https://tenant.example.com/app/', $sanitized, 'URL should start with valid domain');
    }
}
