<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Views/helpers/escape.php';

/**
 * Security Tests: XSS Prevention
 * 
 * Tests HTML escaping and XSS prevention mechanisms
 */
final class XssPreventionTest extends TestCase
{
    /**
     * Test e() helper function with script tag
     */
    public function testEscapeHelperWithScriptTag(): void
    {
        $input = '<script>alert("XSS")</script>';
        $result = e($input);
        
        $this->assertStringNotContainsString('<script>', $result, 'Script tags should be escaped');
        $this->assertStringContainsString('&lt;script&gt;', $result, 'Script tags should be HTML encoded');
    }

    /**
     * Test e() helper function with HTML entities
     */
    public function testEscapeHelperWithHtmlEntities(): void
    {
        $input = '<div>Test & "Quote"</div>';
        $result = e($input);
        
        $this->assertStringContainsString('&lt;div&gt;', $result, 'HTML tags should be escaped');
        $this->assertStringContainsString('&amp;', $result, 'Ampersands should be escaped');
        $this->assertStringContainsString('&quot;', $result, 'Quotes should be escaped');
    }

    /**
     * Test e() helper function with safe content
     */
    public function testEscapeHelperWithSafeContent(): void
    {
        $input = 'Safe text content';
        $result = e($input);
        
        $this->assertEquals('Safe text content', $result, 'Safe content should remain unchanged');
    }

    /**
     * Test e() helper function with null
     */
    public function testEscapeHelperWithNull(): void
    {
        $result = e(null);
        
        $this->assertEquals('', $result, 'Null should return empty string');
    }

    /**
     * Test e() helper function with empty string
     */
    public function testEscapeHelperWithEmptyString(): void
    {
        $result = e('');
        
        $this->assertEquals('', $result, 'Empty string should return empty string');
    }

    /**
     * Test e() helper function with JavaScript event handlers
     */
    public function testEscapeHelperWithJavaScriptEvents(): void
    {
        $input = '<img src="x" onerror="alert(1)">';
        $result = e($input);
        
        // The e() function escapes HTML, so onerror will be in the escaped string
        // The important thing is that it's escaped and won't execute
        $this->assertStringContainsString('&lt;img', $result, 'HTML tags should be escaped');
        $this->assertStringContainsString('onerror', $result, 'Event handlers should be in escaped string');
        $this->assertStringNotContainsString('<img', $result, 'Raw HTML tags should not be present');
        // Check that the entire string is escaped (no raw HTML)
        $this->assertStringNotContainsString('onerror="', $result, 'Event handler attributes should be escaped');
    }
}

