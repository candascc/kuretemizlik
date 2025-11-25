<?php
// config/backup.php

return [
    'enabled' => true,
    'driver' => 'local', // 'local', 's3', 'ftp'
    
    'local' => [
        'path' => __DIR__ . '/../backups',
        'retention_days' => 30,
    ],
    
    's3' => [
        'bucket' => $_ENV['BACKUP_S3_BUCKET'] ?? 'temizlik-backups',
        'region' => $_ENV['BACKUP_S3_REGION'] ?? 'us-east-1',
        'access_key' => $_ENV['BACKUP_S3_ACCESS_KEY'] ?? '',
        'secret_key' => $_ENV['BACKUP_S3_SECRET_KEY'] ?? '',
    ],
    
    'ftp' => [
        'host' => $_ENV['BACKUP_FTP_HOST'] ?? '',
        'username' => $_ENV['BACKUP_FTP_USERNAME'] ?? '',
        'password' => $_ENV['BACKUP_FTP_PASSWORD'] ?? '',
        'port' => $_ENV['BACKUP_FTP_PORT'] ?? 21,
        'path' => $_ENV['BACKUP_FTP_PATH'] ?? '/backups',
    ],
    
    'schedule' => [
        'daily' => true,
        'time' => '02:00', // 2 AM
        'timezone' => 'Europe/Istanbul',
    ],
    
    'compression' => [
        'enabled' => true,
        'method' => 'gzip', // 'gzip', 'zip'
        'level' => 6,
    ],
    
    'encryption' => [
        'enabled' => false,
        'method' => 'aes-256-cbc',
        'key' => $_ENV['BACKUP_ENCRYPTION_KEY'] ?? '',
    ],
    
    'notifications' => [
        'enabled' => true,
        'email' => $_ENV['BACKUP_NOTIFICATION_EMAIL'] ?? '',
        'on_success' => false,
        'on_failure' => true,
    ],
    
    'exclude' => [
        'directories' => [
            'vendor',
            'node_modules',
            'cache',
            'logs',
            'backups',
        ],
        'files' => [
            '*.log',
            '*.tmp',
            '.env',
            '.git',
        ],
    ],
    
    'include' => [
        'directories' => [
            'src',
            'config',
            'migrations',
            'public',
        ],
        'files' => [
            'composer.json',
            'composer.lock',
            'index.php',
            '.htaccess',
        ],
    ],
];
