# Troubleshooting Guide

Common issues and solutions for development and testing.

## Test Issues

### Tests Not Running

**Problem**: `phpunit` command not found

**Solution**:
```bash
composer install
php vendor/bin/phpunit
```

### Database Connection Errors

**Problem**: `SQLSTATE[HY000] [14] unable to open database file`

**Solution**:
1. Check database file permissions
2. Ensure database directory exists
3. Verify SQLite extension is enabled

### Session Errors

**Problem**: `session_start(): Session cannot be started after headers have already been sent`

**Solution**:
1. Ensure `bootstrap.php` is loaded first
2. Check for output before session_start()
3. Use `SessionHelper::ensureStarted()` in tests

### Test Isolation Issues

**Problem**: Tests affecting each other

**Solution**:
1. Use database transactions in setUp/tearDown
2. Clean up test data properly
3. Reset global state (SESSION, POST, etc.)

## Code Quality Issues

### PHP-CS-Fixer Errors

**Problem**: `Class "PhpCsFixer\RuleSet\Sets\desktop.ini" not found`

**Solution**:
1. Exclude desktop.ini files in `.php-cs-fixer.php`
2. Use `ignoreDotFiles(true)` in finder
3. Skip problematic directories

### PHPStan Errors

**Problem**: Too many errors

**Solution**:
1. Start with level 0, gradually increase
2. Use baseline for legacy code
3. Fix critical errors first

### Type Errors

**Problem**: Type mismatches

**Solution**:
1. Add proper type hints
2. Use null coalescing operators
3. Check return types

## CI/CD Issues

### GitHub Actions Failures

**Problem**: Tests failing in CI but passing locally

**Solution**:
1. Check PHP version matches
2. Verify dependencies installed
3. Check environment variables
4. Review CI logs

### Coverage Not Uploading

**Problem**: Codecov not receiving coverage

**Solution**:
1. Verify Xdebug/PCOV enabled
2. Check coverage file path
3. Verify Codecov token

## Performance Issues

### Slow Tests

**Problem**: Tests taking too long

**Solution**:
1. Use parallel execution: `composer test-parallel`
2. Run only Fast suite for quick feedback
3. Optimize database queries
4. Use test data factories

### Memory Issues

**Problem**: Out of memory errors

**Solution**:
1. Increase PHP memory limit
2. Clean up test data
3. Use generators for large datasets
4. Check for memory leaks

## Development Environment

### Composer Issues

**Problem**: Dependency conflicts

**Solution**:
```bash
composer update
composer install --no-interaction
```

### Autoload Issues

**Problem**: Class not found

**Solution**:
```bash
composer dump-autoload
```

### Path Issues

**Problem**: File not found errors

**Solution**:
1. Use `__DIR__` for relative paths
2. Check working directory
3. Verify file permissions

## Getting Help

1. Check error messages carefully
2. Review logs in `logs/` directory
3. Search existing issues
4. Ask in team chat
5. Create new issue with details

## Common Solutions

### Reset Test Database

```bash
rm tests/test.db
php src/Lib/MigrationRunner.php
```

### Clear Cache

```bash
rm -rf cache/*
rm -rf .phpunit.cache
```

### Reinstall Dependencies

```bash
rm -rf vendor
composer install
```

### Fix Permissions

```bash
chmod -R 755 tests/
chmod -R 755 cache/
```

## Still Having Issues?

1. Check [Developer Onboarding](DEVELOPER_ONBOARDING.md)
2. Review [Contributing Guidelines](../CONTRIBUTING.md)
3. Create detailed issue report
4. Include error messages and steps to reproduce




