# Security Guidelines

**Last Updated:** 2025-01-XX  
**Version:** 1.0.0

---

## Overview

This document outlines security best practices and guidelines for the Temizlik İş Takip Uygulaması (Cleaning Company Management SaaS).

---

## Table of Contents

1. [Authentication & Authorization](#authentication--authorization)
2. [Input Validation & Sanitization](#input-validation--sanitization)
3. [SQL Injection Prevention](#sql-injection-prevention)
4. [XSS Prevention](#xss-prevention)
5. [CSRF Protection](#csrf-protection)
6. [Session Security](#session-security)
7. [Password Security](#password-security)
8. [File Upload Security](#file-upload-security)
9. [API Security](#api-security)
10. [Error Handling & Information Disclosure](#error-handling--information-disclosure)
11. [Rate Limiting](#rate-limiting)
12. [Security Headers](#security-headers)
13. [Audit Logging](#audit-logging)

---

## Authentication & Authorization

### Best Practices

1. **Always use `Auth::require()` or `Auth::requireCapability()`**
   ```php
   // ✅ Good
   Auth::requireCapability('jobs.create');
   
   // ❌ Bad
   if (!isset($_SESSION['user_id'])) {
       redirect('/login');
   }
   ```

2. **Use RBAC (Role-Based Access Control)**
   - Check capabilities, not just roles
   - Use `Auth::requireCapability()` for fine-grained control

3. **Session Management**
   - Sessions are automatically managed by `Auth` class
   - Session regeneration on login (ERR-008 fix)
   - Session locking to prevent race conditions (ERR-020 fix)

---

## Input Validation & Sanitization

### Always Use InputSanitizer

1. **Never use raw `$_GET` or `$_POST`**
   ```php
   // ✅ Good
   $id = InputSanitizer::int($_GET['id'] ?? null, 1);
   $name = InputSanitizer::string($_POST['name'] ?? '', 100);
   $email = InputSanitizer::email($_POST['email'] ?? '');
   
   // ❌ Bad
   $id = $_GET['id'];
   $name = $_POST['name'];
   ```

2. **Use Min/Max Validation**
   ```php
   // ✅ Good
   $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
   
   // ❌ Bad
   $page = (int)$_GET['page'];
   ```

3. **Validate Data Types**
   - Use `InputSanitizer::int()` for integers
   - Use `InputSanitizer::float()` for floats
   - Use `InputSanitizer::email()` for emails
   - Use `InputSanitizer::date()` for dates
   - Use `InputSanitizer::phone()` for phone numbers

---

## SQL Injection Prevention

### Always Use Parameterized Queries

1. **Use Database::fetch() with parameters**
   ```php
   // ✅ Good
   $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
   
   // ❌ Bad
   $user = $db->fetch("SELECT * FROM users WHERE id = " . $userId);
   ```

2. **Validate Table Names**
   - Database class validates table names (ERR-016 fix)
   - Never concatenate table names from user input

3. **Validate Column Names**
   - Use whitelist for column names
   - Never use user input directly in column names

---

## XSS Prevention

### Always Escape Output

1. **Use `e()` helper function in views**
   ```php
   // ✅ Good
   <div><?= e($user['name']) ?></div>
   
   // ❌ Bad
   <div><?= $user['name'] ?></div>
   ```

2. **653 htmlspecialchars() replaced with e()** (ERR-009 fix)
   - All view files use `e()` helper function
   - Centralized escaping in `src/Views/helpers/escape.php`

3. **JavaScript innerHTML**
   - Use `textContent` when possible
   - Use DOMPurify for HTML content
   - Add security notes for legitimate uses

---

## CSRF Protection

### Always Verify CSRF Tokens

1. **Use ControllerHelper::verifyCsrfOrRedirect()**
   ```php
   // ✅ Good
   if (!ControllerHelper::verifyCsrfOrRedirect('/jobs')) {
       return;
   }
   
   // ❌ Bad
   if (!CSRF::verifyRequest()) {
       // Manual redirect...
   }
   ```

2. **Include CSRF Token in Forms**
   ```php
   // ✅ Good
   <input type="hidden" name="csrf_token" value="<?= CSRF::get() ?>">
   ```

3. **One-Time Use Tokens**
   - CSRF tokens are one-time use (ERR-020 fix)
   - Session locking prevents race conditions

---

## Session Security

### Best Practices

1. **Session Regeneration**
   - Automatic on login (ERR-008 fix)
   - Use `Auth::regenerateSession()` for critical operations

2. **Session Locking**
   - CSRF token generation uses session locking (ERR-020 fix)
   - Prevents race conditions

3. **Session Timeout**
   - Configure appropriate session timeout
   - Clear session on logout

---

## Password Security

### Use PasswordHelper

1. **Always Use PasswordHelper::verifyPassword()**
   ```php
   // ✅ Good
   if (PasswordHelper::verifyPassword($password, $hash)) {
       // Login successful
   }
   
   // ❌ Bad
   if (password_verify($password, $hash)) {
       // Missing rehash check
   }
   ```

2. **Automatic Rehashing**
   - PasswordHelper automatically rehashes old passwords (ERR-014 fix)
   - Ensures passwords use latest algorithm

3. **Password Requirements**
   - Minimum 8 characters
   - Require uppercase, lowercase, and numbers
   - Use `Validator::password()` for validation

---

## File Upload Security

### Use FileUploadService

1. **Always Validate File Types**
   ```php
   // ✅ Good
   $uploadService = new FileUploadService();
   $result = $uploadService->upload($file, ['allowed_types' => ['image/jpeg', 'image/png']]);
   
   // ❌ Bad
   move_uploaded_file($_FILES['file']['tmp_name'], $destination);
   ```

2. **Security Checks** (ERR-013 fix)
   - MIME type validation
   - File size limits
   - Dangerous extension checks
   - Malicious content detection
   - Magic bytes validation

---

## API Security

### Best Practices

1. **Always Authenticate API Requests**
   ```php
   // ✅ Good
   Auth::require();
   Auth::requireCapability('api.access');
   
   // ❌ Bad
   // No authentication check
   ```

2. **Use API Rate Limiting**
   ```php
   // ✅ Good
   $rateLimiter = new ApiRateLimiter();
   if (!$rateLimiter->check('api_endpoint_' . Auth::id())) {
       http_response_code(429);
       exit;
   }
   ```

3. **Validate API Input**
   - Use InputSanitizer for all API inputs
   - Validate min/max values
   - Use type validation

---

## Error Handling & Information Disclosure

### Never Expose Sensitive Information

1. **Use APP_DEBUG Flag**
   ```php
   // ✅ Good
   if (defined('APP_DEBUG') && APP_DEBUG) {
       echo $error->getMessage();
   } else {
       echo 'An error occurred. Please try again.';
   }
   
   // ❌ Bad
   echo $error->getMessage(); // Exposes sensitive info
   ```

2. **Sanitize Error Messages** (ERR-021 fix)
   - ErrorHandler sanitizes error messages
   - Only shows basenames in production
   - Filters sensitive information

---

## Rate Limiting

### Implement Rate Limiting

1. **Login Attempts** (ERR-012 fix)
   ```php
   // ✅ Good
   RateLimit::recordAttempt($key, 5, 300);
   if (RateLimit::isBlocked($key)) {
       // Block request
   }
   ```

2. **API Endpoints**
   - Use ApiRateLimiter for API endpoints
   - Configure appropriate limits

---

## Security Headers

### Use SecurityHeaders Class

1. **Always Set Security Headers**
   ```php
   // ✅ Good
   SecurityHeaders::set();
   SecurityHeaders::setCors($origin);
   
   // ❌ Bad
   // No security headers
   ```

2. **CORS Policy** (ERR-017 fix)
   - Origin validation
   - Whitelist support
   - Preflight handling

---

## Audit Logging

### Log Critical Operations

1. **Use AuditLogger**
   ```php
   // ✅ Good
   AuditLogger::getInstance()->logDataModification('JOB_CREATED', Auth::id(), [
       'job_id' => $jobId,
       'customer_id' => $customerId
   ]);
   ```

2. **Log Security Events** (ERR-018 fix)
   - Authentication events
   - Authorization failures
   - Data modifications
   - Security violations

---

## Code Quality & Security

### Best Practices

1. **Use Strict Types**
   ```php
   // ✅ Good
   <?php
   declare(strict_types=1);
   ```

2. **Use Type Hints**
   ```php
   // ✅ Good
   public function processLogin(string $phone): array
   
   // ❌ Bad
   public function processLogin($phone)
   ```

3. **Use Constants Instead of Magic Numbers**
   ```php
   // ✅ Good
   $limit = AppConstants::DEFAULT_PAGE_SIZE;
   
   // ❌ Bad
   $limit = 20;
   ```

4. **Use ControllerHelper**
   - Reduces code duplication
   - Centralizes security checks
   - Improves maintainability

---

## Testing

### Security Testing

1. **Run Security Tests**
   ```bash
   php tests/security/run_all.php
   vendor/bin/phpunit tests/security/
   ```

2. **Test Categories**
   - CSRF protection tests
   - XSS prevention tests
   - SQL injection tests
   - Authentication tests
   - Authorization tests

---

## Reporting Security Issues

If you discover a security vulnerability, please:

1. **Do NOT** create a public issue
2. Contact the security team directly
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be addressed before disclosure

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

---

**Last Updated:** 2025-01-XX  
**Maintained By:** Development Team

