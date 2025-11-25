# Developer Onboarding Guide

## Welcome!

This guide will help you set up your development environment and start contributing to the project.

## Prerequisites

- PHP 7.4 or higher
- Composer
- SQLite (for testing)
- Git

## Initial Setup

### 1. Clone Repository

```bash
git clone <repository-url>
cd kuretemizlik.com/app
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
# Edit .env with your settings
```

### 4. Setup Database

```bash
php src/Lib/MigrationRunner.php
```

### 5. Run Tests

```bash
composer test
```

## Development Workflow

### Code Style

We use PSR-12 coding standards. Check your code:

```bash
composer cs-check
```

Auto-fix issues:

```bash
composer cs-fix
```

### Static Analysis

Run PHPStan to check code quality:

```bash
composer stan
```

### Testing

#### Run All Tests

```bash
composer test
```

#### Run Specific Suite

```bash
php vendor/bin/phpunit --testsuite=Fast
php vendor/bin/phpunit --testsuite=Slow
```

#### Run Parallel (Faster)

```bash
composer test-parallel
```

#### Generate Coverage

```bash
composer test-coverage
```

View coverage report: `tests/coverage/index.html`

### Before Committing

Run the CI checks locally:

```bash
composer ci-check
```

This runs:
- Code style check
- PHPStan analysis
- All tests

## Project Structure

```
app/
├── config/          # Configuration files
├── src/            # Source code
│   ├── Controllers/  # Controllers
│   ├── Models/       # Models
│   ├── Services/     # Services
│   ├── Lib/          # Library classes
│   └── Views/        # View templates
├── tests/          # Tests
│   ├── unit/        # Unit tests
│   ├── integration/ # Integration tests
│   ├── functional/  # Functional tests
│   └── Support/     # Test helpers
└── docs/           # Documentation
```

## Writing Tests

### Unit Tests

Test individual methods in isolation:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase
{
    public function testSomething(): void
    {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

### Integration Tests

Test component interactions:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Database;

class MyIntegrationTest extends TestCase
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
        $this->db->rollBack();
        parent::tearDown();
    }

    public function testDatabaseOperation(): void
    {
        // Test with real database
    }
}
```

### Using Factories

Create test data using factories:

```php
use Tests\Support\FactoryRegistry;

$userId = FactoryRegistry::user()->create(['role' => 'ADMIN']);
$customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
```

## Code Quality Standards

### Type Hints

Always use type hints:

```php
public function processPayment(float $amount, string $method): bool
{
    // Implementation
}
```

### Return Types

Always specify return types:

```php
public function getUser(int $id): ?User
{
    // Implementation
}
```

### Error Handling

Use exceptions for errors:

```php
if (!$user) {
    throw new NotFoundException("User not found: {$id}");
}
```

### Documentation

Document public methods:

```php
/**
 * Process a payment
 *
 * @param float $amount Payment amount
 * @param string $method Payment method
 * @return bool True if successful
 * @throws PaymentException If payment fails
 */
public function processPayment(float $amount, string $method): bool
{
    // Implementation
}
```

## Common Tasks

### Adding a New Controller

1. Create controller in `src/Controllers/`
2. Add route in `index.php`
3. Create view in `src/Views/`
4. Write tests in `tests/unit/` or `tests/integration/`

### Adding a New Service

1. Create service in `src/Services/`
2. Write unit tests
3. Update documentation

### Adding a New Model

1. Create model in `src/Models/`
2. Create migration if needed
3. Write tests

## Getting Help

- Check [Troubleshooting Guide](TROUBLESHOOTING.md)
- Review [Contributing Guidelines](../CONTRIBUTING.md)
- Ask in team chat

## Next Steps

1. ✅ Complete initial setup
2. ✅ Run tests successfully
3. ✅ Read code quality standards
4. ✅ Pick your first task
5. ✅ Write tests for your changes
6. ✅ Submit pull request

Happy coding! 🚀




