<?php
/**
 * Logging Configuration
 * Configure the advanced logging system
 */

return [
    // Enable/disable logging
    'enabled' => true,

    // Minimum log level to record
    // Options: DEBUG, INFO, WARNING, ERROR, CRITICAL
    'min_level' => $_ENV['LOG_LEVEL'] ?? LogLevel::INFO,

    // Log format
    // Options: json, text
    'format' => 'json',

    // Log directory
    'log_dir' => __DIR__ . '/../logs',

    // Maximum log file size before rotation (10MB)
    'max_file_size' => 10 * 1024 * 1024,

    // Number of days to keep logs
    'retention_days' => 30,

    // Log channels configuration
    'channels' => [
        'app' => [
            'enabled' => true,
            'min_level' => LogLevel::INFO
        ],
        'security' => [
            'enabled' => true,
            'min_level' => LogLevel::WARNING
        ],
        'performance' => [
            'enabled' => true,
            'min_level' => LogLevel::DEBUG
        ],
        'api' => [
            'enabled' => true,
            'min_level' => LogLevel::INFO
        ],
        'database' => [
            'enabled' => $_ENV['LOG_QUERIES'] ?? false,
            'min_level' => LogLevel::DEBUG
        ]
    ],

    // Error reporting configuration
    'error_reporting' => [
        'enabled' => true,
        'email_on_critical' => $_ENV['ERROR_EMAIL'] ?? null,
        'slack_webhook' => $_ENV['SLACK_WEBHOOK'] ?? null
    ],

    // Performance monitoring
    'performance' => [
        'slow_query_threshold' => 100, // milliseconds
        'slow_request_threshold' => 1000, // milliseconds
        'memory_limit_warning' => 0.8 // 80% of memory limit
    ]
];

