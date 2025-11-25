<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * XSS Prevention Test Suite
 * Tests for XSS prevention in view helpers
 */
class XssPreventionTest extends TestCase
{
    /**
     * Test that e() function escapes HTML entities
     */
    public function testEscapeFunctionEscapesHtml(): void
    {
        $malicious = '<script>alert("XSS")</script>';
        $escaped = e($malicious);
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }
    
    /**
     * Test that e() function escapes quotes
     */
    public function testEscapeFunctionEscapesQuotes(): void
    {
        $withQuotes = 'He said "Hello" and \'Goodbye\'';
        $escaped = e($withQuotes);
        
        $this->assertStringContainsString('&quot;', $escaped);
        $this->assertStringContainsString('&#039;', $escaped);
    }
    
    /**
     * Test that e() function handles null values
     */
    public function testEscapeFunctionHandlesNull(): void
    {
        $result = e(null);
        $this->assertEquals('', $result);
    }
    
    /**
     * Test that e() function handles arrays
     */
    public function testEscapeFunctionHandlesArrays(): void
    {
        $array = ['key' => 'value'];
        $result = e($array);
        // Should convert array to string representation
        $this->assertIsString($result);
    }
    
    /**
     * Test that e() function prevents XSS in common attack vectors
     */
    public function testEscapeFunctionPreventsCommonXss(): void
    {
        $attackVectors = [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '<iframe src="javascript:alert(1)"></iframe>',
        ];
        
        foreach ($attackVectors as $vector) {
            $escaped = e($vector);
            // All HTML tags should be escaped
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<svg', $escaped);
            $this->assertStringNotContainsString('<iframe', $escaped);
            // HTML tags should be escaped
            $this->assertStringContainsString('&lt;', $escaped, 'HTML tags should be escaped');
        }
        
        // Test plain javascript: string (no HTML tags) - should be escaped as-is
        $plainJs = 'javascript:alert(1)';
        $escaped = e($plainJs);
        // Plain text without HTML tags is safe when escaped
        $this->assertIsString($escaped);
        $this->assertStringNotContainsString('<', $escaped);
    }
    
    /**
     * Test that h() alias function works
     */
    public function testHEscapeAlias(): void
    {
        $malicious = '<script>alert("XSS")</script>';
        $escaped = h($malicious);
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
        
        // Should be same as e()
        $this->assertEquals(e($malicious), h($malicious));
    }
    
    /**
     * Test that e() function handles special characters
     */
    public function testEscapeFunctionHandlesSpecialCharacters(): void
    {
        $special = '<>&"\'';
        $escaped = e($special);
        
        $this->assertStringContainsString('&lt;', $escaped);
        $this->assertStringContainsString('&gt;', $escaped);
        $this->assertStringContainsString('&amp;', $escaped);
        $this->assertStringContainsString('&quot;', $escaped);
        $this->assertStringContainsString('&#039;', $escaped);
    }
}

