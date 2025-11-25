<?php

class ResidentPortalMetricsService
{
    private const CACHE_TTL = 120; // seconds

    private Database $db;
    private ResidentMetricsCacheInterface $cache;

    public function __construct(?ResidentMetricsCacheInterface $cacheDriver = null)
    {
        $this->db = Database::getInstance();
        if ($cacheDriver !== null) {
            $this->cache = $cacheDriver;
            ResidentPortalMetrics::setCacheDriver($cacheDriver);
        } else {
            $this->cache = ResidentPortalMetrics::cacheDriver();
        }
    }

    public function getDashboardMetrics(int $unitId, int $buildingId): array
    {
        if ($unitId <= 0 || $buildingId <= 0) {
            return $this->defaultMetrics();
        }

        $cacheKey = $this->cacheKey($unitId, $buildingId);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->fetch(
            "SELECT
                (SELECT COUNT(*) FROM management_fees 
                    WHERE unit_id = :unit_id AND status IN ('pending','partial','overdue')) AS fee_count,
                (SELECT COALESCE(SUM(CASE WHEN total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0)
                    FROM management_fees 
                    WHERE unit_id = :unit_id AND status IN ('pending','partial','overdue')) AS outstanding_amount,
                (SELECT COUNT(*) FROM resident_requests 
                    WHERE unit_id = :unit_id AND status IN ('open','in_progress')) AS open_requests,
                (SELECT COUNT(*) FROM building_announcements 
                    WHERE building_id = :building_id 
                      AND (expire_date IS NULL OR expire_date >= date('now'))) AS active_announcements,
                (SELECT COUNT(*) FROM building_meetings 
                    WHERE building_id = :building_id 
                      AND meeting_date >= date('now') 
                      AND status = 'scheduled') AS upcoming_meetings",
            [
                ':unit_id' => $unitId,
                ':building_id' => $buildingId,
            ]
        );

        $metrics = [
            'pendingFees' => [
                'count' => (int)($row['fee_count'] ?? 0),
                'outstanding' => (float)($row['outstanding_amount'] ?? 0.0),
            ],
            'openRequests' => (int)($row['open_requests'] ?? 0),
            'announcements' => (int)($row['active_announcements'] ?? 0),
            'meetings' => (int)($row['upcoming_meetings'] ?? 0),
        ];

        $this->cache->set($cacheKey, $metrics, self::CACHE_TTL);

        return $metrics;
    }

    public function clearCache(int $unitId, int $buildingId): void
    {
        $this->cache->clear($this->cacheKey($unitId, $buildingId));
    }

    private function cacheKey(int $unitId, int $buildingId): string
    {
        return $unitId . '_' . $buildingId;
    }

    private function defaultMetrics(): array
    {
        return [
            'pendingFees' => ['count' => 0, 'outstanding' => 0.0],
            'openRequests' => 0,
            'announcements' => 0,
            'meetings' => 0,
        ];
    }
}

