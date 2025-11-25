<?php
/**
 * Security Configuration
 * 
 * ROUND 3: Security & Production Hardening
 * 
 * This file contains security-related configuration settings including:
 * - Security Analytics settings
 * - Alerting configuration
 * - Audit log retention
 * - Advanced auth features (2FA/MFA, IP allowlist/blocklist)
 */

return [
    // Security Analytics Configuration
    'analytics' => [
        'enabled' => env('SECURITY_ANALYTICS_ENABLED', true),
        'rules' => [
            'brute_force' => env('SECURITY_ANALYTICS_BRUTE_FORCE', true),
            'multi_tenant_enumeration' => env('SECURITY_ANALYTICS_MULTI_TENANT_ENUM', true),
            'rate_limit_abuse' => env('SECURITY_ANALYTICS_RATE_LIMIT_ABUSE', true),
        ],
    ],
    
    // Alerting Configuration
    'alerts' => [
        'enabled' => env('SECURITY_ALERTS_ENABLED', false), // Default: disabled (only log)
        'channels' => explode(',', env('SECURITY_ALERTS_CHANNELS', 'log')), // log,email,webhook
        'email' => [
            'to' => env('SECURITY_ALERTS_EMAIL_TO', ''),
            'from' => env('SECURITY_ALERTS_EMAIL_FROM', 'security@kuretemizlik.com'),
        ],
        'webhook' => [
            'url' => env('SECURITY_ALERTS_WEBHOOK_URL', ''),
            'secret' => env('SECURITY_ALERTS_WEBHOOK_SECRET', ''),
            'timeout' => (int)env('SECURITY_ALERTS_WEBHOOK_TIMEOUT', 5), // seconds
        ],
        'throttle' => [
            'max_per_minute' => (int)env('SECURITY_ALERTS_THROTTLE_MAX_PER_MINUTE', 10), // Max alerts per minute per event type
            'memory_backend' => env('SECURITY_ALERTS_THROTTLE_BACKEND', 'file'), // file or sqlite
        ],
    ],
    
    // Audit Log Retention
    'audit' => [
        'retention_days' => (int)env('SECURITY_AUDIT_RETENTION_DAYS', 2555), // 7 years default
        'enable_retention_cleanup' => env('SECURITY_AUDIT_ENABLE_CLEANUP', false), // Default: disabled
    ],
    
    // Multi-Factor Authentication (MFA)
    'mfa' => [
        'enabled' => env('SECURITY_MFA_ENABLED', false), // Default: disabled
        'methods' => explode(',', env('SECURITY_MFA_METHODS', 'otp_sms,totp')), // Available methods
        'required_for_roles' => explode(',', env('SECURITY_MFA_REQUIRED_ROLES', 'SUPERADMIN')), // Roles that require MFA
    ],
    
    // IP Access Control
    'ip_allowlist' => [
        'enabled' => env('SECURITY_IP_ALLOWLIST_ENABLED', false), // Default: disabled
        'list' => array_filter(explode(',', env('SECURITY_IP_ALLOWLIST', ''))), // Comma-separated IPs or CIDR
    ],
    
    'ip_blocklist' => [
        'enabled' => env('SECURITY_IP_BLOCKLIST_ENABLED', false), // Default: disabled
        'list' => array_filter(explode(',', env('SECURITY_IP_BLOCKLIST', ''))), // Comma-separated IPs or CIDR
    ],
    
    // External Logging & Monitoring (Sentry/ELK/CloudWatch)
    'logging' => [
        'external' => [
            'enabled' => env('EXTERNAL_LOGGING_ENABLED', false), // Default: disabled
            'provider' => env('EXTERNAL_LOGGING_PROVIDER', 'sentry'), // sentry, elk, cloudwatch, custom
            'dsn' => env('EXTERNAL_LOGGING_DSN', ''), // Provider-specific DSN/endpoint
            'timeout' => (int)env('EXTERNAL_LOGGING_TIMEOUT', 2), // seconds
            'secret' => env('EXTERNAL_LOGGING_SECRET', ''), // For webhook signature
        ],
    ],
    
    // Database Migrations - Web Runner
    // ROUND 7: Web tabanlı migration runner için config
    // SSH erişimi olmayan hosting'lerde tarayıcı üzerinden migration tetiklemek için kullanılır.
    // Sadece SUPERADMIN + gizli token ile erişilebilir olacak.
    'db_migrations' => [
        // Web tabanlı migration runner'ı aç/kapa.
        // Production'da default: false. Sadece ihtiyaç olduğunda kısa süreliğine açılacak.
        'web_runner_enabled' => env('DB_WEB_MIGRATION_ENABLED', false),
        
        // Opsiyonel ekstra güvenlik: URL parametresi ile gönderilecek gizli token.
        // Örn: https://www.kuretemizlik.com/app/tools/db/migrate?token=...
        'token' => env('DB_WEB_MIGRATION_TOKEN', ''),
    ],
];
