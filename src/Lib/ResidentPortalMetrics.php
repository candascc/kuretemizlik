<?php

class ResidentPortalMetrics
{
    private static ?ResidentMetricsCacheInterface $cacheDriver = null;
    private static bool $cacheResolved = false;

    private static function cache(): ResidentMetricsCacheInterface
    {
        if (!self::$cacheResolved || self::$cacheDriver === null) {
            self::$cacheDriver = self::resolveCacheDriver();
            self::$cacheResolved = true;
        }
        return self::$cacheDriver;
    }

    public static function cacheDriver(): ResidentMetricsCacheInterface
    {
        return self::cache();
    }

    public static function setCacheDriver(?ResidentMetricsCacheInterface $driver): void
    {
        self::$cacheDriver = $driver;
        self::$cacheResolved = true;
    }

    public static function resetCacheDriver(): void
    {
        self::$cacheDriver = null;
        self::$cacheResolved = false;
    }

    private static function resolveCacheDriver(): ResidentMetricsCacheInterface
    {
        $preferredDriver = strtolower((string)env('RESIDENT_METRICS_CACHE_DRIVER', 'auto'));

        if ($preferredDriver === 'array') {
            if (class_exists('Logger')) {
                Logger::info('[ResidentPortalMetrics] Using array cache driver (configured)');
            }
            return new ResidentMetricsArrayCache();
        }

        if ($preferredDriver === 'redis' || $preferredDriver === 'auto') {
            if (class_exists('Redis')) {
                try {
                    $redisOptions = [
                        'host' => env('REDIS_HOST', '127.0.0.1'),
                        'port' => env('REDIS_PORT', 6379),
                        'password' => env('REDIS_PASSWORD'),
                        'database' => env('REDIS_DB', 0),
                        'timeout' => env('REDIS_TIMEOUT', 1.5),
                        'namespace' => env('RESIDENT_METRICS_CACHE_NAMESPACE', 'resident_metrics'),
                    ];
                    if (class_exists('Logger')) {
                        Logger::info('[ResidentPortalMetrics] Using Redis cache driver', [
                            'host' => $redisOptions['host'],
                            'port' => $redisOptions['port'],
                            'namespace' => $redisOptions['namespace'],
                        ]);
                    }
                    return new ResidentMetricsRedisCache(null, $redisOptions);
                } catch (Throwable $e) {
                    $message = '[ResidentPortalMetrics] Redis cache unavailable: ' . $e->getMessage();
                    if (class_exists('Logger')) {
                        Logger::warning($message);
                    } else {
                        error_log($message);
                    }
                    if ($preferredDriver === 'redis') {
                        throw $e;
                    }
                }
            } elseif ($preferredDriver === 'redis') {
                throw new RuntimeException('Redis extension is not available for ResidentPortalMetrics cache.');
            }
        }

        if (class_exists('Logger')) {
            Logger::info('[ResidentPortalMetrics] Falling back to array cache driver (auto)');
        }
        return new ResidentMetricsArrayCache();
    }

    /**
     * Retrieve aggregated resident portal statistics.
     *
     * Supported options:
     * - login_since (string|DateTimeInterface): only count logins after given datetime.
     * - cache_ttl (int): cache duration in seconds (0 disables caching). Default 300s.
     */
    public static function getStats(Database $db, array $options = []): array
    {
        $ttl = max(0, (int)($options['cache_ttl'] ?? 300));
        $cacheKey = md5(json_encode($options));
        $now = time();

        if ($ttl !== 0) {
            $cached = self::cache()->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $loginSince = $options['login_since'] ?? null;
        if ($loginSince instanceof DateTimeInterface) {
            $loginSince = $loginSince->format('Y-m-d H:i:s');
        }

        $params = [];
        $loggedCondition = 'last_login_at IS NOT NULL';
        if (!empty($loginSince)) {
            $loggedCondition .= ' AND last_login_at >= ?';
            $params[] = $loginSince;
        }

        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive,
                SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) AS verified,
                SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) AS unverified,
                SUM(CASE WHEN {$loggedCondition} THEN 1 ELSE 0 END) AS logged_in
            FROM resident_users
        ";

        $data = $db->fetch($sql, $params) ?: [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'verified' => 0,
            'unverified' => 0,
            'logged_in' => 0,
        ];

        foreach (['total', 'active', 'inactive', 'verified', 'unverified', 'logged_in'] as $key) {
            $data[$key] = (int)($data[$key] ?? 0);
        }

        if ($ttl !== 0) {
            self::cache()->set($cacheKey, $data, $ttl);
        }

        return $data;
    }

    public static function clearCache(?string $pattern = null): void
    {
        self::cache()->clear($pattern);
    }
}

