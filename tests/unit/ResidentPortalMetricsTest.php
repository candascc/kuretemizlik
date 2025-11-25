<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Services/ResidentPortalMetricsService.php';

final class ResidentPortalMetricsTest extends TestCase
{
    private Database $db;
    private int $buildingId;
    private int $unitId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    private function seedFixtures(): void
    {
        $this->buildingId = $this->db->insert('buildings', [
            'name' => 'Metrics Plaza ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Cd. 1',
            'city' => 'İstanbul',
            'total_units' => 10,
            'status' => 'active',
        ]);

        $this->unitId = $this->db->insert('units', [
            'building_id' => $this->buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'M1',
            'owner_type' => 'owner',
            'owner_name' => 'Metrics Owner',
            'monthly_fee' => 0,
            'status' => 'active',
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->insert('management_fees', [
            'unit_id' => $this->unitId,
            'building_id' => $this->buildingId,
            'fee_name' => 'Ortak Gider',
            'period' => date('Y-m'),
            'base_amount' => 500,
            'discount_amount' => 0,
            'late_fee' => 0,
            'total_amount' => 500,
            'paid_amount' => 100,
            'status' => 'partial',
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->insert('management_fees', [
            'unit_id' => $this->unitId,
            'building_id' => $this->buildingId,
            'fee_name' => 'Temizlik',
            'period' => date('Y-m', strtotime('-1 month')),
            'base_amount' => 300,
            'discount_amount' => 0,
            'late_fee' => 0,
            'total_amount' => 300,
            'paid_amount' => 300,
            'status' => 'paid',
            'due_date' => date('Y-m-d', strtotime('-20 days')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->insert('resident_requests', [
            'building_id' => $this->buildingId,
            'unit_id' => $this->unitId,
            'resident_user_id' => null,
            'request_type' => 'maintenance',
            'subject' => 'Asansör bakımı',
            'description' => 'Asansör ses yapıyor.',
            'priority' => 'high',
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->insert('resident_requests', [
            'building_id' => $this->buildingId,
            'unit_id' => $this->unitId,
            'resident_user_id' => null,
            'request_type' => 'suggestion',
            'subject' => 'Bahçe düzenlemesi',
            'description' => 'Yeni çiçekler ekleyelim.',
            'priority' => 'normal',
            'status' => 'resolved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->insert('building_announcements', [
            'building_id' => $this->buildingId,
            'title' => 'Elektrik Kesintisi',
            'content' => 'Yarın 10:00-12:00 arası bakım yapılacaktır.',
            'priority' => 'urgent',
            'announcement_type' => 'maintenance',
            'created_by' => 1,
            'publish_date' => $now,
            'expire_date' => date('Y-m-d', strtotime('+2 days')),
        ]);

        $this->db->insert('building_meetings', [
            'building_id' => $this->buildingId,
            'title' => 'Genel Toplantı',
            'description' => 'Aidat artışı gündemi.',
            'meeting_date' => date('Y-m-d', strtotime('+3 days')),
            'location' => 'Site Ofisi',
            'status' => 'scheduled',
            'meeting_type' => 'regular',
            'created_by' => 1,
        ]);
    }

    public function testAggregatesMetrics(): void
    {
        $service = new ResidentPortalMetricsService();
        $metrics = $service->getDashboardMetrics($this->unitId, $this->buildingId);

        $this->assertSame(1, $metrics['pendingFees']['count']);
        $this->assertSame(400.0, $metrics['pendingFees']['outstanding']);
        $this->assertSame(1, $metrics['openRequests']);
        $this->assertSame(1, $metrics['announcements']);
        $this->assertSame(1, $metrics['meetings']);
    }

    public function testCachingCanBeCleared(): void
    {
        $service = new ResidentPortalMetricsService();
        $service->getDashboardMetrics($this->unitId, $this->buildingId);

        $this->db->insert('resident_requests', [
            'building_id' => $this->buildingId,
            'unit_id' => $this->unitId,
            'resident_user_id' => null,
            'request_type' => 'complaint',
            'subject' => 'Otopark',
            'description' => 'Otopark girişinde sorun var.',
            'priority' => 'normal',
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $cached = $service->getDashboardMetrics($this->unitId, $this->buildingId);
        $this->assertSame(1, $cached['openRequests'], 'Cache should keep previous count until cleared');

        $service->clearCache($this->unitId, $this->buildingId);
        $fresh = $service->getDashboardMetrics($this->unitId, $this->buildingId);
        $this->assertSame(2, $fresh['openRequests'], 'Clearing cache should reflect new count');
    }
}

