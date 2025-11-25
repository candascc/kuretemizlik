<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * CSRF Middleware Test Suite
 * Tests for CSRF middleware functionality
 */
class CsrfMiddlewareTest extends TestCase
{
    /**
     * Test that CsrfMiddleware exists and has required methods
     */
    public function testCsrfMiddlewareExists(): void
    {
        $this->assertTrue(class_exists('CsrfMiddleware'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('CsrfMiddleware', 'require'));
        $this->assertTrue(method_exists('CsrfMiddleware', 'check'));
        $this->assertTrue(method_exists('CsrfMiddleware', 'reject'));
        $this->assertTrue(method_exists('CsrfMiddleware', 'token'));
        $this->assertTrue(method_exists('CsrfMiddleware', 'field'));
    }
    
    /**
     * Test that CSRF class exists and has required methods
     */
    public function testCsrfClassExists(): void
    {
        $this->assertTrue(class_exists('CSRF'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('CSRF', 'generate'));
        $this->assertTrue(method_exists('CSRF', 'get'));
        $this->assertTrue(method_exists('CSRF', 'verify'));
        $this->assertTrue(method_exists('CSRF', 'verifyRequest'));
        $this->assertTrue(method_exists('CSRF', 'field'));
    }
    
    /**
     * Test that CSRF token generation works
     */
    public function testCsrfTokenGeneration(): void
    {
        // Start session for CSRF
        SessionHelper::ensureStarted();
        
        $token1 = CSRF::generate();
        $token2 = CSRF::generate();
        
        // Tokens should be different
        $this->assertNotEquals($token1, $token2);
        
        // Tokens should be strings
        $this->assertIsString($token1);
        $this->assertIsString($token2);
        
        // Tokens should be 64 characters (32 bytes hex)
        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));
    }
    
    /**
     * Test that CSRF token verification works
     */
    public function testCsrfTokenVerification(): void
    {
        // Start session for CSRF
        SessionHelper::ensureStarted();
        
        $token = CSRF::generate();
        
        // Valid token should verify
        $this->assertTrue(CSRF::verify($token));
        
        // Invalid token should not verify
        $this->assertFalse(CSRF::verify('invalid_token'));
        
        // Used token should not verify again (one-time use)
        $this->assertFalse(CSRF::verify($token));
    }
    
    /**
     * Test that CSRF verifyRequest works with POST data
     */
    public function testCsrfVerifyRequestWithPost(): void
    {
        // Start session for CSRF
        SessionHelper::ensureStarted();
        
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CSRF::get();
        $_POST['csrf_token'] = $token;
        
        // Should verify successfully
        $this->assertTrue(CSRF::verifyRequest());
        
        // Cleanup
        unset($_POST['csrf_token']);
        unset($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * Test that CSRF verifyRequest fails with invalid token
     */
    public function testCsrfVerifyRequestFailsWithInvalidToken(): void
    {
        // Start session for CSRF
        SessionHelper::ensureStarted();
        
        // Simulate POST request with invalid token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'invalid_token_12345';
        
        // Should fail verification
        $this->assertFalse(CSRF::verifyRequest());
        
        // Cleanup
        unset($_POST['csrf_token']);
        unset($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * Test that CSRF field() generates HTML input
     */
    public function testCsrfFieldGeneratesHtml(): void
    {
        // Start session for CSRF
        SessionHelper::ensureStarted();
        
        $field = CSRF::field();
        
        // Should contain input tag
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }
}

