<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/ControllerHelper.php';
require_once __DIR__ . '/../../src/Lib/CSRF.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';

// Mock redirect function for testing
if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        throw new Exception("Redirect to: {$url}");
    }
}

// Mock base_url function for testing
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return '/app' . $path;
    }
}

/**
 * Unit Tests for ControllerHelper
 * 
 * Tests the centralized controller helper methods
 */
final class ControllerHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    /**
     * Test CSRF verification with valid token
     */
    public function testVerifyCsrfOrRedirectWithValidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CSRF::generate();
        $_POST['csrf_token'] = $token;

        $result = ControllerHelper::verifyCsrfOrRedirect('/test');
        
        $this->assertTrue($result, 'CSRF verification should pass with valid token');
    }

    /**
     * Test CSRF verification with invalid token
     */
    public function testVerifyCsrfOrRedirectWithInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'invalid_token';

        // Since redirect() throws exception, we expect it
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Redirect to/');
        
        ControllerHelper::verifyCsrfOrRedirect('/test');
    }

    /**
     * Test POST method requirement
     */
    public function testRequirePostOrRedirectWithPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $result = ControllerHelper::requirePostOrRedirect('/test');
        
        $this->assertTrue($result, 'Should return true for POST requests');
    }

    /**
     * Test POST method requirement with GET
     */
    public function testRequirePostOrRedirectWithGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Since redirect() throws exception, we expect it
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Redirect to/');
        
        ControllerHelper::requirePostOrRedirect('/test');
    }

    /**
     * Test ID validation with valid ID
     */
    public function testValidateIdWithValidId(): void
    {
        $result = ControllerHelper::validateId(123);
        
        $this->assertEquals(123, $result, 'Should return valid integer ID');
    }

    /**
     * Test ID validation with invalid ID
     */
    public function testValidateIdWithInvalidId(): void
    {
        $result = ControllerHelper::validateId('invalid');
        
        $this->assertNull($result, 'Should return null for invalid ID');
    }

    /**
     * Test ID validation with zero
     */
    public function testValidateIdWithZero(): void
    {
        $result = ControllerHelper::validateId(0);
        
        $this->assertNull($result, 'Should return null for zero ID');
    }

    /**
     * Test pagination validation
     */
    public function testValidatePagination(): void
    {
        $params = ['page' => 2, 'limit' => 20];
        
        $result = ControllerHelper::validatePagination($params);
        
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(20, $result['limit']);
        $this->assertEquals(20, $result['offset']); // (2-1) * 20
    }

    /**
     * Test pagination validation with defaults
     */
    public function testValidatePaginationWithDefaults(): void
    {
        $params = [];
        
        $result = ControllerHelper::validatePagination($params);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
        $this->assertEquals(0, $result['offset']);
    }

    /**
     * Test date range validation
     */
    public function testValidateDateRange(): void
    {
        $params = [
            'date_from' => '2025-01-01',
            'date_to' => '2025-01-31'
        ];
        
        $result = ControllerHelper::validateDateRange($params);
        
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
        $this->assertEquals('2025-01-01', $result['date_from']);
        $this->assertEquals('2025-01-31', $result['date_to']);
    }

    /**
     * Test WHERE clause building
     */
    public function testBuildWhereClause(): void
    {
        $filters = [
            'status' => 'ACTIVE',
            'type' => 'JOB'
        ];
        $allowedFields = ['status', 'type'];
        
        $result = ControllerHelper::buildWhereClause($filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('status', $result['where']);
        $this->assertStringContainsString('type', $result['where']);
        $this->assertCount(2, $result['params']);
    }

    /**
     * Test WHERE clause building with array values
     */
    public function testBuildWhereClauseWithArrayValues(): void
    {
        $filters = [
            'status' => ['ACTIVE', 'PENDING']
        ];
        $allowedFields = ['status'];
        
        $result = ControllerHelper::buildWhereClause($filters, $allowedFields);
        
        $this->assertStringContainsString('IN', $result['where']);
        $this->assertCount(2, $result['params']);
    }
}

