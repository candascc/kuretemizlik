<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';
require_once __DIR__ . '/../../src/Models/ResidentUser.php';
require_once __DIR__ . '/../../src/Services/ResidentNotificationPreferenceService.php';

final class ResidentNotificationPreferenceServiceTest extends TestCase
{
    private Database $db;
    private ResidentUser $residentModel;
    private ResidentNotificationPreferenceService $service;
    private int $residentId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        $this->residentModel = new ResidentUser();
        $this->service = new ResidentNotificationPreferenceService();

        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        $this->seedResident();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    public function testDefaultsAreCreatedAndResolved(): void
    {
        $prefs = $this->service->getResidentPreferences($this->residentId);
        $this->assertArrayHasKey('fees', $prefs);
        $this->assertTrue($prefs['fees']['email']);
        $this->assertTrue($prefs['alerts']['sms']);
    }

    public function testUpdateAndResolveChannelsRespectsGlobalFlags(): void
    {
        $this->service->updatePreferences($this->residentId, [
            'fees' => ['email' => 1, 'sms' => 0],
            'alerts' => ['email' => 1, 'sms' => 1],
        ]);

        $resident = $this->residentModel->find($this->residentId);

        $channels = $this->service->resolveChannels($resident, 'fees', ['email', 'sms']);
        $this->assertContains('email', $channels);
        $this->assertNotContains('sms', $channels);

        $channelsAlerts = $this->service->resolveChannels($resident, 'alerts', ['email', 'sms']);
        $this->assertContains('email', $channelsAlerts);
        $this->assertContains('sms', $channelsAlerts);

        // Disable global email and ensure resolved channels empty
        $this->residentModel->update($this->residentId, ['notify_email' => 0, 'notify_sms' => 0]);
        $resident = $this->residentModel->find($this->residentId);
        $channelsAfterGlobalOff = $this->service->resolveChannels($resident, 'alerts', ['email', 'sms']);
        $this->assertEmpty($channelsAfterGlobalOff);
    }

    public function testCategoryStatsReturnsAggregatedData(): void
    {
        $stats = $this->service->getCategoryStats();
        $this->assertArrayHasKey('fees', $stats);
        $this->assertArrayHasKey('email_enabled', $stats['fees']);
        $this->assertGreaterThanOrEqual(0, $stats['fees']['email_enabled']);
    }

    private function seedResident(): void
    {
        $buildingId = $this->db->insert('buildings', [
            'name' => 'Prefs Tower',
            'building_type' => 'apartman',
            'address_line' => 'Test Sok. 5',
            'city' => 'İstanbul',
            'total_units' => 10,
            'status' => 'active',
        ]);

        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'P1',
            'owner_type' => 'owner',
            'owner_name' => 'Prefs Owner',
            'monthly_fee' => 400,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->residentId = $this->residentModel->create([
            'unit_id' => $unitId,
            'name' => 'Preference Resident',
            'email' => 'pref@example.com',
            'phone' => '+905550000000',
            'password_hash' => password_hash('Secret123!', PASSWORD_DEFAULT),
            'notify_email' => 1,
            'notify_sms' => 1,
            'is_owner' => 1,
            'is_active' => 1,
        ]);
    }
}

