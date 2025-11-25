<?php

declare(strict_types=1);

/**
 * Application Constants
 * 
 * Phase 4.2: Magic Numbers/Strings - Centralized constants
 * 
 * Centralized constants to replace magic numbers and strings throughout the application.
 * This improves maintainability, readability, and reduces the risk of errors from
 * inconsistent values. All application-wide constants should be defined here.
 * 
 * @package App\Constants
 * @since Phase 4.2
 */

class AppConstants
{
    // Pagination
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;
    const MIN_PAGE = 1;
    const MAX_PAGE = 10000;
    
    // Time intervals (in seconds)
    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2592000; // 30 days
    
    // Cache TTL (in seconds)
    const CACHE_TTL_SHORT = 300;      // 5 minutes
    const CACHE_TTL_MEDIUM = 3600;    // 1 hour
    const CACHE_TTL_LONG = 86400;     // 24 hours
    const CACHE_TTL_VERY_LONG = 604800; // 7 days
    
    // Rate limiting
    const RATE_LIMIT_LOGIN_ATTEMPTS = 5;
    const RATE_LIMIT_LOGIN_WINDOW = 300; // 5 minutes
    const RATE_LIMIT_API_REQUESTS = 100;
    const RATE_LIMIT_API_WINDOW = 3600; // 1 hour
    
    // HTTP Status Codes
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NO_CONTENT = 204;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    
    // String lengths
    const MAX_STRING_LENGTH_SHORT = 50;
    const MAX_STRING_LENGTH_MEDIUM = 200;
    const MAX_STRING_LENGTH_LONG = 500;
    const MAX_STRING_LENGTH_VERY_LONG = 2000;
    
    // File sizes (in bytes)
    const FILE_SIZE_MIN = 10;
    const FILE_SIZE_MAX_SMALL = 1024 * 1024;      // 1 MB
    const FILE_SIZE_MAX_MEDIUM = 5 * 1024 * 1024; // 5 MB
    const FILE_SIZE_MAX_LARGE = 10 * 1024 * 1024; // 10 MB
    
    // Password
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_MAX_LENGTH = 128;
    
    // Numeric limits
    const INT_MIN = 0;
    const INT_MAX = PHP_INT_MAX;
    const FLOAT_MIN = 0.0;
    const FLOAT_MAX = PHP_FLOAT_MAX;
    
    // Default values
    const DEFAULT_TIMEOUT = 30;
    const DEFAULT_RETRY_COUNT = 3;
    
    // Queue/Job limits
    const QUEUE_BATCH_SIZE = 10;
    const QUEUE_MAX_RETRIES = 3;
    const QUEUE_DEFAULT_PRIORITY = 5;
    
    // Export limits
    const EXPORT_MAX_RECORDS = 10000;
    const EXPORT_BATCH_SIZE = 1000;
    
    // Validation
    const VALIDATION_MIN_ID = 1;
    const VALIDATION_MAX_ID = PHP_INT_MAX;
    
    // Date/Time formats
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const TIME_FORMAT = 'H:i:s';
    
    // Status codes
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_PENDING = 2;
    const STATUS_DELETED = 99;
    
    // Job Status Strings
    const JOB_STATUS_SCHEDULED = 'SCHEDULED';
    const JOB_STATUS_DONE = 'DONE';
    const JOB_STATUS_CANCELLED = 'CANCELLED';
    const JOB_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const JOB_STATUS_PLANNED = 'PLANNED';
    
    // Dashboard/List Limits
    const DASHBOARD_RECENT_ITEMS = 5;
    const DASHBOARD_TOP_ITEMS = 5;
    const AUDIT_LOG_PAGE_SIZE = 50;
    
    // Search/Query Limits
    const SEARCH_MIN_LENGTH = 2;
    const PHONE_MIN_LENGTH = 11;
}

