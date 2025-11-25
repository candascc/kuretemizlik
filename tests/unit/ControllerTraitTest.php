<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * ControllerTrait Test Suite
 * Phase 4.4: Test Coverage - Tests for ControllerTrait methods
 */
class ControllerTraitTest extends TestCase
{
    private $testController;
    private $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure required classes are loaded
        if (!class_exists('ControllerHelper')) {
            require_once __DIR__ . '/../../src/Lib/ControllerHelper.php';
        }
        if (!trait_exists('ControllerTrait')) {
            require_once __DIR__ . '/../../src/Lib/ControllerTrait.php';
        }
        
        // Create a test controller that uses ControllerTrait
        $this->testController = new class {
            use ControllerTrait;
            
            // Mock model for testing
            public $mockModel;
            
            public function __construct()
            {
                $this->mockModel = new class {
                    private $data = [
                        1 => ['id' => 1, 'name' => 'Test Record'],
                        2 => ['id' => 2, 'name' => 'Another Record'],
                    ];
                    
                    public function find($id)
                    {
                        return $this->data[$id] ?? null;
                    }
                };
            }
        };
        
        // Create ReflectionClass for accessing protected methods
        $this->reflection = new ReflectionClass($this->testController);
    }

    /**
     * Test that findOrFail returns record when found
     */
    public function testFindOrFailReturnsRecordWhenFound(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            1,
            'Not found',
            null
        );
        
        $this->assertNotNull($record);
        $this->assertEquals(1, $record['id']);
        $this->assertEquals('Test Record', $record['name']);
    }

    /**
     * Test that findOrFail returns null when not found (no redirect)
     */
    public function testFindOrFailReturnsNullWhenNotFound(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            999,
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test that findOrFail validates ID
     */
    public function testFindOrFailValidatesId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            'invalid',
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test validatePagination with default values
     */
    public function testValidatePaginationWithDefaults(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, []);
        
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
        $this->assertEquals(0, $result['offset']);
    }

    /**
     * Test validatePagination with custom values
     */
    public function testValidatePaginationWithCustomValues(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => 3], 1, 10);
        
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(20, $result['offset']); // (3-1) * 10
    }

    /**
     * Test validateDateRange with valid dates
     */
    public function testValidateDateRangeWithValidDates(): void
    {
        $method = $this->reflection->getMethod('validateDateRange');
        $method->setAccessible(true);
        
        $params = [
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31'
        ];
        
        $result = $method->invoke($this->testController, $params);
        
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
        $this->assertNotNull($result['date_from']);
        $this->assertNotNull($result['date_to']);
    }

    /**
     * Test validateDateRange with missing dates
     */
    public function testValidateDateRangeWithMissingDates(): void
    {
        $method = $this->reflection->getMethod('validateDateRange');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, []);
        
        $this->assertNull($result['date_from']);
        $this->assertNull($result['date_to']);
    }

    /**
     * Test buildWhereClause with allowed fields
     */
    public function testBuildWhereClauseWithAllowedFields(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => 'active',
            'company_id' => 1,
            'invalid_field' => 'should_be_ignored'
        ];
        
        $allowedFields = ['status', 'company_id'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('status', $result['where']);
        $this->assertStringContainsString('company_id', $result['where']);
        $this->assertStringNotContainsString('invalid_field', $result['where']);
        $this->assertCount(2, $result['params']);
    }

    /**
     * Test buildWhereClause with empty filters
     */
    public function testBuildWhereClauseWithEmptyFilters(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, [], ['status']);
        
        $this->assertEquals('', $result['where']);
        $this->assertEmpty($result['params']);
    }

    /**
     * Test buildWhereClause with array values (IN clause)
     */
    public function testBuildWhereClauseWithArrayValues(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => ['active', 'pending']
        ];
        
        $allowedFields = ['status'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertStringContainsString('IN', $result['where']);
        $this->assertCount(2, $result['params']);
    }

    // ========== EXPANDED COVERAGE: Edge Cases, Boundary Tests, Negative Tests ==========

    /**
     * Test findOrFail with negative ID
     */
    public function testFindOrFailWithNegativeId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            -1,
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test findOrFail with zero ID
     */
    public function testFindOrFailWithZeroId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            0,
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test findOrFail with very large ID
     */
    public function testFindOrFailWithVeryLargeId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            PHP_INT_MAX,
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test findOrFail with null ID
     */
    public function testFindOrFailWithNullId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            null,
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test findOrFail with array ID (invalid type)
     */
    public function testFindOrFailWithArrayId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            [1, 2, 3],
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test findOrFail with object ID (invalid type)
     */
    public function testFindOrFailWithObjectId(): void
    {
        $method = $this->reflection->getMethod('findOrFail');
        $method->setAccessible(true);
        
        $record = $method->invoke(
            $this->testController,
            $this->testController->mockModel,
            (object)['id' => 1],
            'Not found',
            null
        );
        
        $this->assertNull($record);
    }

    /**
     * Test validatePagination with negative page
     */
    public function testValidatePaginationWithNegativePage(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => -5]);
        
        $this->assertGreaterThanOrEqual(1, $result['page'], 'Page should be at least 1');
    }

    /**
     * Test validatePagination with zero page
     */
    public function testValidatePaginationWithZeroPage(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => 0]);
        
        $this->assertGreaterThanOrEqual(1, $result['page'], 'Page should be at least 1');
    }

    /**
     * Test validatePagination with very large page
     */
    public function testValidatePaginationWithVeryLargePage(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => PHP_INT_MAX]);
        
        $this->assertIsInt($result['page']);
        $this->assertGreaterThan(0, $result['page']);
    }

    /**
     * Test validatePagination with negative limit
     */
    public function testValidatePaginationWithNegativeLimit(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['limit' => -10]);
        
        $this->assertGreaterThan(0, $result['limit'], 'Limit should be positive');
    }

    /**
     * Test validatePagination with zero limit
     */
    public function testValidatePaginationWithZeroLimit(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['limit' => 0]);
        
        $this->assertGreaterThan(0, $result['limit'], 'Limit should be positive');
    }

    /**
     * Test validatePagination with very large limit
     */
    public function testValidatePaginationWithVeryLargeLimit(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['limit' => 1000000]);
        
        $this->assertIsInt($result['limit']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    /**
     * Test validatePagination with string page (should be converted)
     */
    public function testValidatePaginationWithStringPage(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => '5']);
        
        $this->assertEquals(5, $result['page']);
    }

    /**
     * Test validatePagination with invalid string page
     */
    public function testValidatePaginationWithInvalidStringPage(): void
    {
        $method = $this->reflection->getMethod('validatePagination');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->testController, ['page' => 'invalid']);
        
        $this->assertGreaterThanOrEqual(1, $result['page'], 'Should default to valid page');
    }

    /**
     * Test validateDateRange with invalid date format
     */
    public function testValidateDateRangeWithInvalidFormat(): void
    {
        $method = $this->reflection->getMethod('validateDateRange');
        $method->setAccessible(true);
        
        $params = [
            'date_from' => 'invalid-date',
            'date_to' => '2024-13-45' // Invalid month and day
        ];
        
        $result = $method->invoke($this->testController, $params);
        
        // Should handle invalid dates gracefully
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
    }

    /**
     * Test validateDateRange with date_from after date_to
     */
    public function testValidateDateRangeWithReversedDates(): void
    {
        $method = $this->reflection->getMethod('validateDateRange');
        $method->setAccessible(true);
        
        $params = [
            'date_from' => '2024-12-31',
            'date_to' => '2024-01-01'
        ];
        
        $result = $method->invoke($this->testController, $params);
        
        // Should still return the dates (validation logic may be in controller)
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
    }

    /**
     * Test validateDateRange with empty string dates
     */
    public function testValidateDateRangeWithEmptyStrings(): void
    {
        $method = $this->reflection->getMethod('validateDateRange');
        $method->setAccessible(true);
        
        $params = [
            'date_from' => '',
            'date_to' => ''
        ];
        
        $result = $method->invoke($this->testController, $params);
        
        $this->assertNull($result['date_from']);
        $this->assertNull($result['date_to']);
    }

    /**
     * Test buildWhereClause with SQL injection attempt
     */
    public function testBuildWhereClauseWithSqlInjectionAttempt(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => "'; DROP TABLE users; --",
            'company_id' => "1 OR 1=1"
        ];
        
        $allowedFields = ['status', 'company_id'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        // Should use parameterized queries, so SQL injection should be safe
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        // The malicious string should be in params, not in WHERE clause directly
        $this->assertContains("'; DROP TABLE users; --", $result['params']);
    }

    /**
     * Test buildWhereClause with XSS attempt
     */
    public function testBuildWhereClauseWithXssAttempt(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => '<script>alert("XSS")</script>',
            'company_id' => '<img src=x onerror=alert(1)>'
        ];
        
        $allowedFields = ['status', 'company_id'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        // Should handle XSS attempts safely (parameterized queries)
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertContains('<script>alert("XSS")</script>', $result['params']);
    }

    /**
     * Test buildWhereClause with unicode characters
     */
    public function testBuildWhereClauseWithUnicode(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => '测试',
            'company_id' => '🚀'
        ];
        
        $allowedFields = ['status', 'company_id'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertContains('测试', $result['params']);
    }

    /**
     * Test buildWhereClause with very long string
     */
    public function testBuildWhereClauseWithVeryLongString(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $veryLongString = str_repeat('a', 10000);
        $filters = [
            'status' => $veryLongString
        ];
        
        $allowedFields = ['status'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertEquals($veryLongString, $result['params'][0]);
    }

    /**
     * Test buildWhereClause with null values
     */
    public function testBuildWhereClauseWithNullValues(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => null,
            'company_id' => null
        ];
        
        $allowedFields = ['status', 'company_id'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        // Null values should be handled (may be filtered out or included as IS NULL)
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
    }

    /**
     * Test buildWhereClause with numeric strings
     */
    public function testBuildWhereClauseWithNumericStrings(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'company_id' => '123',
            'status' => '456'
        ];
        
        $allowedFields = ['company_id', 'status'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertContains('123', $result['params']);
        $this->assertContains('456', $result['params']);
    }

    /**
     * Test buildWhereClause with boolean values
     */
    public function testBuildWhereClauseWithBooleanValues(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'is_active' => true,
            'is_deleted' => false
        ];
        
        $allowedFields = ['is_active', 'is_deleted'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
    }

    /**
     * Test buildWhereClause with empty array values
     */
    public function testBuildWhereClauseWithEmptyArrayValues(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => []
        ];
        
        $allowedFields = ['status'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        // Empty arrays should be handled gracefully
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
    }

    /**
     * Test buildWhereClause with large array values
     */
    public function testBuildWhereClauseWithLargeArrayValues(): void
    {
        $method = $this->reflection->getMethod('buildWhereClause');
        $method->setAccessible(true);
        
        $filters = [
            'status' => range(1, 1000) // Array with 1000 elements
        ];
        
        $allowedFields = ['status'];
        
        $result = $method->invoke($this->testController, $filters, $allowedFields);
        
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('IN', $result['where']);
        $this->assertCount(1000, $result['params']);
    }
}

