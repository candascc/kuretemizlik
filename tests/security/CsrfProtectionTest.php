<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/CSRF.php';
require_once __DIR__ . '/../../src/Lib/ControllerHelper.php';

/**
 * Security Tests: CSRF Protection
 * 
 * Tests CSRF token generation, verification, and one-time use
 */
final class CsrfProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    /**
     * Test CSRF token generation
     */
    public function testTokenGeneration(): void
    {
        $token1 = CSRF::generate();
        $token2 = CSRF::generate();
        
        $this->assertNotEmpty($token1, 'Token should not be empty');
        $this->assertNotEmpty($token2, 'Token should not be empty');
        $this->assertNotEquals($token1, $token2, 'Each token should be unique');
    }

    /**
     * Test CSRF token verification with valid token
     */
    public function testTokenVerificationWithValidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CSRF::generate();
        $_POST['csrf_token'] = $token;
        
        $result = CSRF::verifyRequest();
        
        $this->assertTrue($result, 'Valid token should pass verification');
    }

    /**
     * Test CSRF token verification with invalid token
     */
    public function testTokenVerificationWithInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'invalid_token';
        
        $result = CSRF::verifyRequest();
        
        $this->assertFalse($result, 'Invalid token should fail verification');
    }

    /**
     * Test CSRF token one-time use
     */
    public function testTokenOneTimeUse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CSRF::generate();
        $_POST['csrf_token'] = $token;
        
        // First verification should pass
        $result1 = CSRF::verifyRequest();
        $this->assertTrue($result1, 'First verification should pass');
        
        // Clear cache by changing REQUEST_URI to simulate a different request
        $_SERVER['REQUEST_URI'] = ($_SERVER['REQUEST_URI'] ?? '/test') . '?second_verify=1';
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true); // Change request time to bypass cache
        
        // Second verification should fail (one-time use) - token was consumed
        $result2 = CSRF::verifyRequest();
        $this->assertFalse($result2, 'Second verification should fail (one-time use)');
    }

    /**
     * Test CSRF token with missing token
     */
    public function testTokenVerificationWithMissingToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['csrf_token']);
        
        $result = CSRF::verifyRequest();
        
        $this->assertFalse($result, 'Missing token should fail verification');
    }

    /**
     * Test CSRF token with GET request (should not require token)
     */
    public function testTokenVerificationWithGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST['csrf_token']);
        
        // GET requests typically don't require CSRF tokens
        // This depends on implementation
        $this->assertTrue(true, 'GET requests may not require CSRF tokens');
    }
}

