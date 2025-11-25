<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Models/ResidentUser.php';

final class ResidentUserLookupTest extends TestCase
{
    private Database $db;
    private ResidentUser $residentModel;
    private int $residentId;
    private string $phoneDigits = '905312220033';

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->residentModel = new ResidentUser();

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        $this->ensureResidentUserSchema();
        $this->seedResident();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    public function testFindByPhoneMatchesCommonFormats(): void
    {
        $inputs = [
            '0531 222 00 33',
            '+90 (531) 222 00 33',
            '905312220033',
            '+905312220033',
        ];

        foreach ($inputs as $input) {
            $result = $this->residentModel->findByPhone($input);
            $this->assertNotNull($result, "Phone lookup should succeed for {$input}");
            $this->assertSame($this->residentId, (int)($result['id'] ?? 0));
        }
    }

    public function testFindByEmailOrPhoneUsesPhoneWhenEmailMissing(): void
    {
        $result = $this->residentModel->findByEmailOrPhone(null, '0531 222 00 33');

        $this->assertNotNull($result);
        $this->assertSame($this->residentId, (int)($result['id'] ?? 0));
    }

    private function seedResident(): void
    {
        $now = date('Y-m-d H:i:s');
        $buildingId = $this->db->insert('buildings', [
            'name' => 'Lookup Tower ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Cad. 789',
            'city' => 'İstanbul',
            'total_units' => 12,
            'status' => 'active',
        ]);

        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'L' . rand(100, 999),
            'owner_type' => 'owner',
            'owner_name' => 'Lookup Owner',
            'monthly_fee' => 300,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->residentId = $this->residentModel->create([
            'unit_id' => $unitId,
            'name' => 'Lookup User',
            'email' => 'lookup.user.' . uniqid() . '@example.com', // Email is required in database
            'phone' => '+'.$this->phoneDigits,
            'password_hash' => '', // Empty string instead of null for NOT NULL constraint
            'is_owner' => 1,
            'is_active' => 1,
        ]);
    }

    private function ensureResidentUserSchema(): void
    {
        $columns = $this->db->fetchAll("PRAGMA table_info(resident_users)");
        $names = array_map(static fn ($col) => $col['name'] ?? '', $columns);

        $required = [
            'password_hash' => "ALTER TABLE resident_users ADD COLUMN password_hash TEXT",
            'password_set_at' => "ALTER TABLE resident_users ADD COLUMN password_set_at TEXT",
            'last_otp_sent_at' => "ALTER TABLE resident_users ADD COLUMN last_otp_sent_at TEXT",
            'otp_attempts' => "ALTER TABLE resident_users ADD COLUMN otp_attempts INTEGER NOT NULL DEFAULT 0",
            'otp_context' => "ALTER TABLE resident_users ADD COLUMN otp_context TEXT",
        ];

        foreach ($required as $column => $sql) {
            if (!in_array($column, $names, true)) {
                $this->db->query($sql);
            }
        }
    }
}


