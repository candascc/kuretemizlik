# API Documentation

**Last Updated:** 2025-01-XX  
**Version:** 1.0.0

---

## Overview

This document provides comprehensive API documentation for the Temizlik İş Takip Uygulaması (Cleaning Company Management SaaS).

---

## Table of Contents

1. [Authentication](#authentication)
2. [API Endpoints](#api-endpoints)
3. [Request/Response Format](#requestresponse-format)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Security](#security)

---

## Authentication

### Overview

All API endpoints require authentication. The API uses session-based authentication.

### Authentication Methods

1. **Session Authentication**
   - Login via `/api/v2/auth/login`
   - Session cookie is set automatically
   - Use session cookie for subsequent requests

2. **API Key Authentication** (Future)
   - API keys for programmatic access
   - Pass via `X-API-Key` header

### Login Endpoint

**POST** `/api/v2/auth/login`

**Request:**
```json
{
  "username": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "user@example.com",
    "role": "ADMIN"
  }
}
```

**Rate Limiting:** 5 attempts per 5 minutes per IP

---

## API Endpoints

### Jobs API

#### List Jobs

**GET** `/api/v2/jobs`

**Query Parameters:**
- `page` (int, optional): Page number (default: 1, min: 1, max: 1000)
- `per_page` (int, optional): Items per page (default: 20, min: 1, max: 100)
- `customer_id` (int, optional): Filter by customer ID (min: 1)
- `service_id` (int, optional): Filter by service ID (min: 1)
- `status` (string, optional): Filter by status (SCHEDULED, DONE, CANCELLED)
- `date_from` (date, optional): Filter from date (YYYY-MM-DD)
- `date_to` (date, optional): Filter to date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "service_id": 1,
      "start_at": "2025-01-15 10:00:00",
      "end_at": "2025-01-15 12:00:00",
      "status": "SCHEDULED",
      "total_amount": 150.00
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

#### Get Job

**GET** `/api/v2/jobs/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "customer_id": 1,
    "service_id": 1,
    "start_at": "2025-01-15 10:00:00",
    "end_at": "2025-01-15 12:00:00",
    "status": "SCHEDULED",
    "total_amount": 150.00,
    "customer": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

#### Create Job

**POST** `/api/v2/jobs`

**Request:**
```json
{
  "customer_id": 1,
  "service_id": 1,
  "start_at": "2025-01-15 10:00:00",
  "end_at": "2025-01-15 12:00:00",
  "total_amount": 150.00,
  "note": "Regular cleaning"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "customer_id": 1,
    "service_id": 1,
    "start_at": "2025-01-15 10:00:00",
    "end_at": "2025-01-15 12:00:00",
    "status": "SCHEDULED",
    "total_amount": 150.00
  }
}
```

**Required Capability:** `jobs.create`

#### Update Job

**PUT** `/api/v2/jobs/{id}`

**Request:**
```json
{
  "status": "DONE",
  "total_amount": 150.00
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "DONE",
    "total_amount": 150.00
  }
}
```

**Required Capability:** `jobs.edit`

#### Delete Job

**DELETE** `/api/v2/jobs/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Job deleted successfully"
}
```

**Required Capability:** `jobs.delete`

---

### Customers API

#### List Customers

**GET** `/api/v2/customers`

**Query Parameters:**
- `page` (int, optional): Page number (default: 1)
- `per_page` (int, optional): Items per page (default: 20)
- `search` (string, optional): Search by name, email, or phone

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+90 531 300 40 50"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "total_pages": 3
  }
}
```

#### Get Customer

**GET** `/api/v2/customers/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+90 531 300 40 50",
    "addresses": [
      {
        "id": 1,
        "label": "Home",
        "line": "123 Main St",
        "city": "Istanbul"
      }
    ]
  }
}
```

---

## Request/Response Format

### Request Format

- **Content-Type:** `application/json`
- **Method:** GET, POST, PUT, DELETE
- **Headers:**
  - `Content-Type: application/json`
  - `X-Requested-With: XMLHttpRequest` (optional)

### Response Format

All responses follow this format:

```json
{
  "success": true|false,
  "data": {},
  "message": "Optional message",
  "errors": []
}
```

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
  "message": "Error message",
  "errors": [
    {
      "field": "email",
      "message": "Invalid email format"
    }
  ]
}
```

---

## Error Handling

### HTTP Status Codes

- `200 OK` - Success
- `201 Created` - Resource created
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### Error Response Format

```json
{
  "success": false,
  "message": "Error message",
  "errors": [
    {
      "field": "field_name",
      "message": "Error message for this field"
    }
  ],
  "code": "ERROR_CODE"
}
```

---

## Rate Limiting

### Overview

API endpoints are rate-limited to prevent abuse.

### Rate Limits

- **Default:** 100 requests per minute per IP
- **Login Endpoint:** 5 attempts per 5 minutes per IP
- **API Endpoints:** Configurable per endpoint

### Rate Limit Headers

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

### Rate Limit Exceeded

When rate limit is exceeded:

**Status Code:** `429 Too Many Requests`

**Response:**
```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 60
}
```

---

## Security

### CSRF Protection

- All POST/PUT/DELETE requests require CSRF token
- Include CSRF token in request header: `X-CSRF-Token`
- Or include in request body: `csrf_token`

### Input Validation

- All inputs are validated and sanitized
- Use InputSanitizer for all user inputs
- Min/max validation for numeric inputs
- Type validation for all inputs

### SQL Injection Prevention

- All queries use parameterized statements
- Table and column names are validated
- InputSanitizer prevents SQL injection

### XSS Prevention

- All output is escaped using `e()` helper function
- JSON responses are automatically safe
- HTML content is sanitized

---

## Examples

### cURL Examples

#### Login

```bash
curl -X POST https://example.com/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "password123"
  }'
```

#### List Jobs

```bash
curl -X GET "https://example.com/api/v2/jobs?page=1&per_page=20" \
  -H "Cookie: PHPSESSID=session_id"
```

#### Create Job

```bash
curl -X POST https://example.com/api/v2/jobs \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=session_id" \
  -H "X-CSRF-Token: csrf_token" \
  -d '{
    "customer_id": 1,
    "service_id": 1,
    "start_at": "2025-01-15 10:00:00",
    "end_at": "2025-01-15 12:00:00",
    "total_amount": 150.00
  }'
```

### JavaScript Examples

#### Login

```javascript
fetch('/api/v2/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    username: 'user@example.com',
    password: 'password123'
  })
})
.then(response => response.json())
.then(data => {
  console.log(data);
});
```

#### List Jobs

```javascript
fetch('/api/v2/jobs?page=1&per_page=20', {
  method: 'GET',
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log(data);
});
```

---

## Changelog

### Version 1.0.0 (2025-01-XX)
- Initial API documentation
- Authentication endpoints
- Jobs API
- Customers API
- Rate limiting
- Security guidelines

---

**Last Updated:** 2025-01-XX  
**Maintained By:** Development Team

