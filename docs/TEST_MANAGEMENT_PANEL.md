# Test Management Panel Documentation

## Overview

The Test Management Panel provides a web-based interface for running tests, viewing results, and monitoring test coverage. It is accessible at `/app/sysadmin/tests` and requires SUPERADMIN role.

## Features

### Dashboard

The main dashboard (`/app/sysadmin/tests`) displays:
- Test statistics (total files, total tests, last run, success rate)
- Recent test runs with links to detailed results
- Test suite selection for running tests
- Coverage report link

### Running Tests

1. **Select Test Suite**: Choose from available test suites:
   - `All`: All tests
   - `Fast`: Unit tests only
   - `Slow`: Integration and functional tests
   - `Stress`: Stress tests
   - `Load`: Load tests

2. **Optional Test File**: Specify a single test file to run (optional)

3. **Click "Run Tests"**: Tests run in the background

### Viewing Results

1. **Test Status**: Check if tests are running or completed
2. **Test Results**: View detailed results including:
   - Summary (total tests, assertions, failures, errors)
   - Individual test outcomes
   - Raw log output
3. **Coverage Report**: View HTML coverage report (if available)

## API Endpoints

### POST `/app/sysadmin/tests/run`

Run tests with specified suite and optional test file.

**Request Body**:
```json
{
  "suite": "Fast",
  "test_file": "tests/unit/ControllerTraitTest.php"
}
```

**Response**:
```json
{
  "run_id": "abc123",
  "status": "running",
  "message": "Tests started"
}
```

### GET `/app/sysadmin/tests/status/{runId}`

Get status of a test run.

**Response**:
```json
{
  "run_id": "abc123",
  "status": "completed",
  "started_at": "2025-11-25 10:00:00",
  "completed_at": "2025-11-25 10:05:00"
}
```

### GET `/app/sysadmin/tests/results/{runId}`

Get detailed test results.

**Response**: HTML page with test results

## Permissions

- **SUPERADMIN**: Full access to all features
- Other roles: No access

## Troubleshooting

### Tests Not Running

1. Check file permissions on `tests/test_outputs/` directory
2. Verify PHPUnit is installed: `composer install`
3. Check server logs for errors

### Coverage Not Available

1. Install Xdebug or PCOV extension
2. Run tests with coverage: `composer test-coverage`
3. Check `tests/coverage/` directory exists

### Slow Test Execution

1. Use parallel execution: `composer test-parallel`
2. Run only Fast suite for quick feedback
3. Check `tests/PERFORMANCE_REPORT.json` for slow tests

## Best Practices

1. **Run Fast Suite First**: Get quick feedback on unit tests
2. **Run Full Suite Before Deployment**: Ensure all tests pass
3. **Check Coverage Regularly**: Aim for 90%+ on critical paths
4. **Review Slow Tests**: Optimize tests taking >5 seconds
5. **Use Parallel Execution**: Speed up test runs

## Related Documentation

- [API Test Management](API_TEST_MANAGEMENT.md)
- [Developer Onboarding](DEVELOPER_ONBOARDING.md)
- [Contributing Guidelines](../CONTRIBUTING.md)




