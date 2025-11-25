<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * AppConstants Test Suite
 * Phase 4.4: Test Coverage - Tests for AppConstants values
 */
class AppConstantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure AppConstants is loaded
        if (!class_exists('AppConstants')) {
            require_once __DIR__ . '/../../src/Constants/AppConstants.php';
        }
    }

    /**
     * Test that all pagination constants are defined and valid
     */
    public function testPaginationConstants(): void
    {
        $this->assertIsInt(AppConstants::DEFAULT_PAGE_SIZE);
        $this->assertIsInt(AppConstants::MAX_PAGE_SIZE);
        $this->assertIsInt(AppConstants::MIN_PAGE);
        $this->assertIsInt(AppConstants::MAX_PAGE);
        
        $this->assertGreaterThan(0, AppConstants::DEFAULT_PAGE_SIZE);
        $this->assertGreaterThan(AppConstants::DEFAULT_PAGE_SIZE, AppConstants::MAX_PAGE_SIZE);
        $this->assertGreaterThan(0, AppConstants::MIN_PAGE);
        $this->assertGreaterThan(AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
    }

    /**
     * Test that time interval constants are correct
     */
    public function testTimeIntervalConstants(): void
    {
        $this->assertEquals(1, AppConstants::SECOND);
        $this->assertEquals(60, AppConstants::MINUTE);
        $this->assertEquals(3600, AppConstants::HOUR);
        $this->assertEquals(86400, AppConstants::DAY);
        $this->assertEquals(604800, AppConstants::WEEK);
        $this->assertEquals(2592000, AppConstants::MONTH);
        
        // Verify relationships
        $this->assertEquals(60 * AppConstants::SECOND, AppConstants::MINUTE);
        $this->assertEquals(60 * AppConstants::MINUTE, AppConstants::HOUR);
        $this->assertEquals(24 * AppConstants::HOUR, AppConstants::DAY);
        $this->assertEquals(7 * AppConstants::DAY, AppConstants::WEEK);
    }

    /**
     * Test that cache TTL constants are valid
     */
    public function testCacheTtlConstants(): void
    {
        $this->assertIsInt(AppConstants::CACHE_TTL_SHORT);
        $this->assertIsInt(AppConstants::CACHE_TTL_MEDIUM);
        $this->assertIsInt(AppConstants::CACHE_TTL_LONG);
        $this->assertIsInt(AppConstants::CACHE_TTL_VERY_LONG);
        
        $this->assertGreaterThan(0, AppConstants::CACHE_TTL_SHORT);
        $this->assertGreaterThan(AppConstants::CACHE_TTL_SHORT, AppConstants::CACHE_TTL_MEDIUM);
        $this->assertGreaterThan(AppConstants::CACHE_TTL_MEDIUM, AppConstants::CACHE_TTL_LONG);
        $this->assertGreaterThan(AppConstants::CACHE_TTL_LONG, AppConstants::CACHE_TTL_VERY_LONG);
    }

    /**
     * Test that rate limit constants are valid
     */
    public function testRateLimitConstants(): void
    {
        $this->assertIsInt(AppConstants::RATE_LIMIT_LOGIN_ATTEMPTS);
        $this->assertIsInt(AppConstants::RATE_LIMIT_LOGIN_WINDOW);
        $this->assertIsInt(AppConstants::RATE_LIMIT_API_REQUESTS);
        $this->assertIsInt(AppConstants::RATE_LIMIT_API_WINDOW);
        
        $this->assertGreaterThan(0, AppConstants::RATE_LIMIT_LOGIN_ATTEMPTS);
        $this->assertGreaterThan(0, AppConstants::RATE_LIMIT_LOGIN_WINDOW);
        $this->assertGreaterThan(0, AppConstants::RATE_LIMIT_API_REQUESTS);
        $this->assertGreaterThan(0, AppConstants::RATE_LIMIT_API_WINDOW);
    }

    /**
     * Test that HTTP status code constants are correct
     */
    public function testHttpStatusConstants(): void
    {
        $this->assertEquals(200, AppConstants::HTTP_OK);
        $this->assertEquals(201, AppConstants::HTTP_CREATED);
        $this->assertEquals(204, AppConstants::HTTP_NO_CONTENT);
        $this->assertEquals(400, AppConstants::HTTP_BAD_REQUEST);
        $this->assertEquals(401, AppConstants::HTTP_UNAUTHORIZED);
        $this->assertEquals(403, AppConstants::HTTP_FORBIDDEN);
        $this->assertEquals(404, AppConstants::HTTP_NOT_FOUND);
        $this->assertEquals(405, AppConstants::HTTP_METHOD_NOT_ALLOWED);
        $this->assertEquals(500, AppConstants::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test that string length constants are valid
     */
    public function testStringLengthConstants(): void
    {
        $this->assertIsInt(AppConstants::MAX_STRING_LENGTH_SHORT);
        $this->assertIsInt(AppConstants::MAX_STRING_LENGTH_MEDIUM);
        $this->assertIsInt(AppConstants::MAX_STRING_LENGTH_LONG);
        $this->assertIsInt(AppConstants::MAX_STRING_LENGTH_VERY_LONG);
        
        $this->assertGreaterThan(0, AppConstants::MAX_STRING_LENGTH_SHORT);
        $this->assertGreaterThan(AppConstants::MAX_STRING_LENGTH_SHORT, AppConstants::MAX_STRING_LENGTH_MEDIUM);
        $this->assertGreaterThan(AppConstants::MAX_STRING_LENGTH_MEDIUM, AppConstants::MAX_STRING_LENGTH_LONG);
        $this->assertGreaterThan(AppConstants::MAX_STRING_LENGTH_LONG, AppConstants::MAX_STRING_LENGTH_VERY_LONG);
    }

    /**
     * Test that password constants are valid
     */
    public function testPasswordConstants(): void
    {
        $this->assertIsInt(AppConstants::PASSWORD_MIN_LENGTH);
        $this->assertIsInt(AppConstants::PASSWORD_MAX_LENGTH);
        
        $this->assertGreaterThan(0, AppConstants::PASSWORD_MIN_LENGTH);
        $this->assertGreaterThan(AppConstants::PASSWORD_MIN_LENGTH, AppConstants::PASSWORD_MAX_LENGTH);
        $this->assertGreaterThanOrEqual(8, AppConstants::PASSWORD_MIN_LENGTH); // Security best practice
    }

    /**
     * Test that job status constants are defined
     */
    public function testJobStatusConstants(): void
    {
        $this->assertIsString(AppConstants::JOB_STATUS_SCHEDULED);
        $this->assertIsString(AppConstants::JOB_STATUS_DONE);
        $this->assertIsString(AppConstants::JOB_STATUS_CANCELLED);
        $this->assertIsString(AppConstants::JOB_STATUS_IN_PROGRESS);
        $this->assertIsString(AppConstants::JOB_STATUS_PLANNED);
        
        $this->assertEquals('SCHEDULED', AppConstants::JOB_STATUS_SCHEDULED);
        $this->assertEquals('DONE', AppConstants::JOB_STATUS_DONE);
        $this->assertEquals('CANCELLED', AppConstants::JOB_STATUS_CANCELLED);
    }

    /**
     * Test that dashboard limit constants are valid
     */
    public function testDashboardLimitConstants(): void
    {
        $this->assertIsInt(AppConstants::DASHBOARD_RECENT_ITEMS);
        $this->assertIsInt(AppConstants::DASHBOARD_TOP_ITEMS);
        $this->assertIsInt(AppConstants::AUDIT_LOG_PAGE_SIZE);
        
        $this->assertGreaterThan(0, AppConstants::DASHBOARD_RECENT_ITEMS);
        $this->assertGreaterThan(0, AppConstants::DASHBOARD_TOP_ITEMS);
        $this->assertGreaterThan(0, AppConstants::AUDIT_LOG_PAGE_SIZE);
    }

    /**
     * Test that search/query limit constants are valid
     */
    public function testSearchLimitConstants(): void
    {
        $this->assertIsInt(AppConstants::SEARCH_MIN_LENGTH);
        $this->assertIsInt(AppConstants::PHONE_MIN_LENGTH);
        
        $this->assertGreaterThan(0, AppConstants::SEARCH_MIN_LENGTH);
        $this->assertGreaterThan(0, AppConstants::PHONE_MIN_LENGTH);
        $this->assertGreaterThanOrEqual(2, AppConstants::SEARCH_MIN_LENGTH); // Minimum for meaningful search
    }

    /**
     * Test that date/time format constants are valid
     */
    public function testDateTimeFormatConstants(): void
    {
        $this->assertIsString(AppConstants::DATE_FORMAT);
        $this->assertIsString(AppConstants::DATETIME_FORMAT);
        $this->assertIsString(AppConstants::TIME_FORMAT);
        
        // Test that formats are valid by using them
        $date = date(AppConstants::DATE_FORMAT);
        $datetime = date(AppConstants::DATETIME_FORMAT);
        $time = date(AppConstants::TIME_FORMAT);
        
        $this->assertNotEmpty($date);
        $this->assertNotEmpty($datetime);
        $this->assertNotEmpty($time);
    }
}

