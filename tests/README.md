# Test Suite Documentation

## Overview

This test suite provides comprehensive testing for the application, including unit tests, integration tests, functional tests, stress tests, and load tests.

## Test Structure

```
tests/
├── unit/              # Unit tests for individual components
├── integration/       # Integration tests for component interactions
├── functional/        # End-to-end functional tests
├── security/          # Security-focused tests
├── performance/       # Performance tests
├── stress/            # Stress tests with large datasets
├── load/              # Load tests for concurrent operations
├── Support/           # Test support utilities
│   ├── Factories/    # Test data factories
│   └── Seeders/       # Database seeders
└── bootstrap.php      # Test bootstrap file
```

## Test Data Factories

### Using Factories

Factories provide a convenient way to create test data:

```php
require_once __DIR__ . '/../Support/FactoryRegistry.php';

// Create a user
$userId = FactoryRegistry::user()->create(['role' => 'ADMIN']);

// Create a customer
$customerId = FactoryRegistry::customer()->create(['company_id' => 1]);

// Create multiple records
$customerIds = FactoryRegistry::customer()->createMany(10, ['company_id' => 1]);
```

### Available Factories

- `FactoryRegistry::user()` - UserFactory
- `FactoryRegistry::customer()` - CustomerFactory
- `FactoryRegistry::job()` - JobFactory
- `FactoryRegistry::building()` - BuildingFactory
- `FactoryRegistry::unit()` - UnitFactory
- `FactoryRegistry::residentUser()` - ResidentUserFactory
- `FactoryRegistry::company()` - CompanyFactory
- `FactoryRegistry::payment()` - PaymentFactory
- `FactoryRegistry::contract()` - ContractFactory
- `FactoryRegistry::service()` - ServiceFactory
- `FactoryRegistry::address()` - AddressFactory

## Database Seeders

### Using Seeders

Seeders help create large datasets for testing:

```php
require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';

$seeder = new \Tests\Support\Seeders\LargeDatasetSeeder();
$seeder->seed();
```

### Available Seeders

- `LargeDatasetSeeder` - Seeds 10000+ records for large dataset testing
- `StressTestSeeder` - Seeds data specifically for stress tests
- `ProductionLikeSeeder` - Seeds realistic production-like data

## Running Tests

### Run All Tests

```bash
php tests/run_all_tests_one_by_one.php
```

### Run Specific Test Suites

```bash
# Fast tests (unit tests only)
php tests/run_all_tests_one_by_one.php --fast

# Stress tests
php tests/run_all_tests_one_by_one.php --stress

# Load tests
php tests/run_all_tests_one_by_one.php --load

# Specific suite
php tests/run_all_tests_one_by_one.php --suite=unit
```

### Run with Coverage

```bash
php tests/run_coverage.php
```

### Run in Parallel

```bash
php tests/run_parallel.php
```

### Run Individual Test File

```bash
php vendor/bin/phpunit tests/unit/ControllerTraitTest.php
```

## Test Suites

### Fast Suite
Unit tests that run quickly:
```bash
php vendor/bin/phpunit --testsuite=Fast
```

### Slow Suite
Integration and functional tests:
```bash
php vendor/bin/phpunit --testsuite=Slow
```

### Stress Suite
Stress tests with large datasets:
```bash
php vendor/bin/phpunit --testsuite=Stress
```

### Load Suite
Load tests for concurrent operations:
```bash
php vendor/bin/phpunit --testsuite=Load
```

## Coverage Reports

### Generate Coverage Report

```bash
php tests/run_coverage.php
```

Coverage reports are generated in:
- HTML: `tests/coverage/index.html`
- JSON: `tests/coverage/coverage.json`

### Generate Coverage Badge

```bash
php tests/generate_coverage_badge.php
```

Badge is generated at: `tests/coverage/badge.svg`

## Performance Monitoring

The `TestPerformanceMonitor` class tracks test execution time and memory usage:

```php
use Tests\Support\TestPerformanceMonitor;

TestPerformanceMonitor::start('testName');
// ... test code ...
$metrics = TestPerformanceMonitor::stop('testName');

// Generate report
$report = TestPerformanceMonitor::generateReport();
TestPerformanceMonitor::saveReport('performance_report.txt');
```

## Best Practices

1. **Use Factories**: Always use factories to create test data instead of manual database inserts
2. **Clean Up**: Use transactions in tests to ensure clean state
3. **Isolation**: Each test should be independent and not rely on other tests
4. **Naming**: Use descriptive test method names that explain what is being tested
5. **Assertions**: Use specific assertions rather than generic ones
6. **Data**: Use realistic test data that matches production scenarios

## Writing New Tests

### Unit Test Example

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class MyComponentTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    public function testMyComponent(): void
    {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

### Functional Test Example

```php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';

use PHPUnit\Framework\TestCase;

class MyFunctionalTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    public function testEndToEndFlow(): void
    {
        // Create test data using factories
        $customerId = FactoryRegistry::customer()->create();
        $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId]);

        // Test the flow
        $job = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
        $this->assertNotNull($job);
        $this->assertEquals($customerId, $job['customer_id']);
    }
}
```

## Troubleshooting

### Tests Failing Due to Missing Data

Ensure you're using factories or seeders to create required test data.

### Session Issues

If you encounter session-related errors, ensure `SessionHelper::ensureStarted()` is called in `setUp()`.

### Database Transaction Issues

Always wrap test database operations in transactions and rollback in `tearDown()`.

### Memory Issues

For large dataset tests, consider using `--stress` flag or running tests individually.

## CI/CD Integration

Tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: php tests/run_all_tests_one_by_one.php

- name: Generate Coverage
  run: php tests/run_coverage.php
```

## Additional Resources

- PHPUnit Documentation: https://phpunit.de/documentation.html
- Faker Documentation: https://fakerphp.github.io/
- Test Coverage Best Practices: https://phpunit.de/manual/current/en/code-coverage-analysis.html
