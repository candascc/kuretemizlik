<?php
/**
 * Cache Configuration
 */

return [
    'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'drivers' => ['file', 'redis'],
    
    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['REDIS_PORT'] ?? 6379,
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => $_ENV['REDIS_DATABASE'] ?? 0,
    ],
    
    'file' => [
        'path' => __DIR__ . '/../cache',
    ],
    
    'default_ttl' => 3600,
    'prefix' => 'app:',
    'compression' => true,
    'serialization' => 'json',
];
