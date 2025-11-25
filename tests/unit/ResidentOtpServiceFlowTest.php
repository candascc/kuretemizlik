<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';
require_once __DIR__ . '/../../src/Models/ResidentUser.php';
require_once __DIR__ . '/../../src/Services/ResidentOtpService.php';
require_once __DIR__ . '/../../src/Services/EmailQueue.php';
require_once __DIR__ . '/../../src/Services/SMSQueue.php';

final class ResidentOtpServiceFlowTest extends TestCase
{
    private Database $db;
    private ResidentUser $residentModel;
    private int $residentId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->residentModel = new ResidentUser();

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_ENV['SMS_ENABLED'] = $_ENV['SMS_ENABLED'] ?? 'false';

        $this->ensureResidentUserSchema();
        $this->seedResident();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    public function testRequestTokenUpdatesResidentOtpState(): void
    {
        $service = new ResidentOtpService();
        $resident = $this->residentModel->find($this->residentId);

        $result = $service->requestToken($resident, 'sms', '127.0.0.1', 'set_password');

        $this->assertArrayHasKey('token_id', $result);
        $this->assertNotEmpty($result['masked_contact']);

        $token = $this->db->fetch(
            "SELECT * FROM resident_login_tokens WHERE id = ?",
            [$result['token_id']]
        );
        $this->assertNotFalse($token);
        $meta = json_decode($token['meta'], true) ?? [];
        $this->assertSame('set_password', $meta['context'] ?? null);
    }

    public function testRequestTokenRespectsCooldown(): void
    {
        $resident = $this->residentModel->find($this->residentId);
        $now = date('Y-m-d H:i:s');

        $this->residentModel->update($this->residentId, [
            'otp_context' => 'login',
            'otp_attempts' => 1,
            'last_otp_sent_at' => $now,
        ]);

        $service = new ResidentOtpService();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Lütfen yeni kod istemeden önce biraz bekleyin.');

        $service->requestToken(
            $this->residentModel->find($this->residentId),
            'sms',
            '127.0.0.1',
            'login'
        );
    }

    private function seedResident(): void
    {
        $now = date('Y-m-d H:i:s');
        $buildingId = $this->db->insert('buildings', [
            'name' => 'OTP Tower ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Cad. 456',
            'city' => 'İstanbul',
            'total_units' => 10,
            'status' => 'active',
        ]);

        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'O' . rand(100, 999),
            'owner_type' => 'owner',
            'owner_name' => 'OTP Owner',
            'monthly_fee' => 250,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->residentId = $this->residentModel->create([
            'unit_id' => $unitId,
            'name' => 'OTP User',
            'email' => 'otp-flow@example.com',
            'phone' => '+905552220000',
            'password_hash' => password_hash('SetPassword123', PASSWORD_DEFAULT),
            'is_owner' => 1,
            'is_active' => 1,
        ]);
    }

    private function ensureResidentUserSchema(): void
    {
        $columns = $this->db->fetchAll("PRAGMA table_info(resident_users)");
        $names = array_map(fn ($col) => $col['name'] ?? '', $columns);

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


