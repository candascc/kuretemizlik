<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/ResidentPortalMetrics.php';
require_once __DIR__ . '/../../src/Cache/ResidentMetricsArrayCache.php';
require_once __DIR__ . '/../../src/Contracts/ResidentMetricsCacheInterface.php';

final class ResidentPortalMetricsCacheTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        ResidentPortalMetrics::resetCacheDriver();
        ResidentPortalMetrics::clearCache();
    }

    protected function tearDown(): void
    {
        ResidentPortalMetrics::resetCacheDriver();
        ResidentPortalMetrics::clearCache();
    }

    public function testCustomDriverIsUsedForCaching(): void
    {
        $driver = new class implements ResidentMetricsCacheInterface {
            public int $getCalls = 0;
            public int $setCalls = 0;
            private array $store = [];

            public function get(string $key): ?array
            {
                $this->getCalls++;
                return $this->store[$key] ?? null;
            }

            public function set(string $key, array $value, int $ttl): void
            {
                $this->setCalls++;
                $this->store[$key] = $value;
            }

            public function clear(?string $pattern = null): void
            {
                $this->store = [];
            }
        };

        ResidentPortalMetrics::setCacheDriver($driver);

        // First call hits DB and stores cache
        ResidentPortalMetrics::getStats($this->db, ['cache_ttl' => 60]);
        $this->assertSame(1, $driver->setCalls, 'Cache driver should record set call on first query.');

        // Second call uses cached result
        ResidentPortalMetrics::getStats($this->db, ['cache_ttl' => 60]);
        $this->assertSame(2, $driver->getCalls, 'Cache driver should receive get calls for subsequent queries.');
        $this->assertSame(1, $driver->setCalls, 'Cache store should not be rewritten while TTL valid.');
    }

    public function testClearCacheDelegatesToDriver(): void
    {
        $driver = new class implements ResidentMetricsCacheInterface {
            public int $clearCalls = 0;

            public function get(string $key): ?array
            {
                return null;
            }

            public function set(string $key, array $value, int $ttl): void
            {
            }

            public function clear(?string $pattern = null): void
            {
                $this->clearCalls++;
            }
        };

        ResidentPortalMetrics::setCacheDriver($driver);
        ResidentPortalMetrics::clearCache();
        $this->assertSame(1, $driver->clearCalls);
    }
}

