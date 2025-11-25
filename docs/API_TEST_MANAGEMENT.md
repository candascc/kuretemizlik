# API Test Management Documentation

## Overview

The Test Management API provides programmatic access to test execution and results. All endpoints require SUPERADMIN authentication.

## Base URL

```
/app/sysadmin/tests
```

## Authentication

All endpoints require:
- Valid session with SUPERADMIN role
- CSRF token for POST requests

## Endpoints

### Run Tests

**POST** `/run`

Execute tests with specified parameters.

**Request Body**:
```json
{
  "suite": "Fast",
  "test_file": "tests/unit/ControllerTraitTest.php"
}
```

**Parameters**:
- `suite` (string, required): Test suite name (All, Fast, Slow, Stress, Load)
- `test_file` (string, optional): Specific test file to run

**Response**:
```json
{
  "run_id": "abc123def456",
  "status": "running",
  "message": "Tests started successfully",
  "started_at": "2025-11-25 10:00:00"
}
```

**Status Codes**:
- `200`: Tests started successfully
- `400`: Invalid parameters
- `403`: Insufficient permissions
- `500`: Server error

### Get Test Status

**GET** `/status/{runId}`

Get current status of a test run.

**Path Parameters**:
- `runId` (string, required): Test run identifier

**Response**:
```json
{
  "run_id": "abc123def456",
  "status": "completed",
  "started_at": "2025-11-25 10:00:00",
  "completed_at": "2025-11-25 10:05:00",
  "duration": 300
}
```

**Status Values**:
- `running`: Tests are currently executing
- `completed`: Tests finished (success or failure)
- `failed`: Test execution failed
- `not_found`: Run ID not found

**Status Codes**:
- `200`: Status retrieved successfully
- `404`: Run ID not found
- `500`: Server error

### Get Test Results

**GET** `/results/{runId}`

Get detailed test results for a completed run.

**Path Parameters**:
- `runId` (string, required): Test run identifier

**Response**: HTML page with:
- Test summary (total tests, assertions, failures, errors)
- Individual test outcomes
- Raw log output

**Status Codes**:
- `200`: Results retrieved successfully
- `404`: Run ID not found or tests still running
- `500`: Server error

## Test Suites

### Fast

Unit tests only. Quick feedback on code changes.

**Example**:
```json
{
  "suite": "Fast"
}
```

### Slow

Integration and functional tests. More comprehensive but slower.

**Example**:
```json
{
  "suite": "Slow"
}
```

### Stress

Stress tests. Tests system under load.

**Example**:
```json
{
  "suite": "Stress"
}
```

### Load

Load tests. Tests concurrent operations.

**Example**:
```json
{
  "suite": "Load"
}
```

### All

All test suites combined.

**Example**:
```json
{
  "suite": "All"
}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {}
  }
}
```

## Error Codes

- `INVALID_SUITE`: Invalid test suite name
- `INVALID_TEST_FILE`: Test file not found
- `PERMISSION_DENIED`: Insufficient permissions
- `TEST_EXECUTION_FAILED`: Test execution error
- `RUN_NOT_FOUND`: Test run ID not found

## Rate Limiting

- Maximum 1 test run per minute per user
- Maximum 5 concurrent test runs

## Examples

### Run Fast Suite

```bash
curl -X POST https://example.com/app/sysadmin/tests/run \
  -H "Content-Type: application/json" \
  -H "Cookie: session=..." \
  -d '{
    "suite": "Fast"
  }'
```

### Check Test Status

```bash
curl https://example.com/app/sysadmin/tests/status/abc123def456 \
  -H "Cookie: session=..."
```

### Get Test Results

```bash
curl https://example.com/app/sysadmin/tests/results/abc123def456 \
  -H "Cookie: session=..."
```

## Webhooks (Future)

Webhook support for test completion notifications is planned.

## Related Documentation

- [Test Management Panel](TEST_MANAGEMENT_PANEL.md)
- [Developer Onboarding](DEVELOPER_ONBOARDING.md)




