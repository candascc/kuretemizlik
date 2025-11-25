# Crawl API Documentation

## Endpoints

### GET /sysadmin/crawl?role=SUPERADMIN

Runs crawl test for specified role.

**Parameters:**
- `role` (required): Role to test (SUPERADMIN, ADMIN, OPERATOR, SITE_MANAGER, FINANCE, SUPPORT)

**Response:** HTML page with crawl results

### POST /sysadmin/remote-crawl

API endpoint for remote crawl execution.

**Request Body:**
```json
{
  "role": "SUPERADMIN",
  "username": "optional",
  "password": "optional"
}
```

**Response:**
```json
{
  "success": true,
  "role": "SUPERADMIN",
  "username": "test_superadmin",
  "result": {
    "base_url": "/app",
    "username": "test_superadmin",
    "total_count": 25,
    "success_count": 24,
    "error_count": 1,
    "items": [...]
  }
}
```

## Error Codes

- `400`: Invalid role parameter
- `500`: Crawl execution error

