<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';
require_once __DIR__ . '/../../src/Services/ResidentContactVerificationService.php';
require_once __DIR__ . '/../../src/Services/EmailQueue.php';
require_once __DIR__ . '/../../src/Services/SMSQueue.php';
require_once __DIR__ . '/../../src/Models/ResidentUser.php';

final class ResidentContactVerificationServiceTest extends TestCase
{
    private Database $db;
    private ResidentUser $residentModel;
    private int $residentId;
    private array $resident;

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

        $this->seedResident();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    public function testRequestCreatesPendingVerification(): void
    {
        $service = new ResidentContactVerificationService();

        $result = $service->requestVerification($this->resident, 'email', 'resident.new@example.com');
        $this->assertArrayHasKey('verification_id', $result);
        $this->assertArrayHasKey('masked_contact', $result);
        $this->assertStringContainsString('***', $result['masked_contact'], 'Masked email returned');

        $record = $this->db->fetch(
            "SELECT * FROM resident_contact_verifications WHERE id = ?",
            [$result['verification_id']]
        );

        $this->assertNotFalse($record);
        $this->assertSame('pending', $record['status']);
        $this->assertSame('email', $record['verification_type']);
        $this->assertSame('resident.new@example.com', $record['new_value']);
    }

    public function testVerifyCompletesAndReturnsNewValue(): void
    {
        $service = new ResidentContactVerificationService();
        $request = $service->requestVerification($this->resident, 'email', 'resident.verify@example.com');

        $this->db->update('resident_contact_verifications', [
            'code_hash' => password_hash('654321', PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
        ], 'id = ?', [$request['verification_id']]);

        $verified = $service->verify($this->residentId, $request['verification_id'], '654321');
        $this->assertSame('email', $verified['type']);
        $this->assertSame('resident.verify@example.com', $verified['new_value']);

        $stored = $this->db->fetch(
            "SELECT status FROM resident_contact_verifications WHERE id = ?",
            [$request['verification_id']]
        );
        $this->assertSame('verified', $stored['status']);
    }

    public function testResendUpdatesTimestamps(): void
    {
        $service = new ResidentContactVerificationService();
        $request = $service->requestVerification($this->resident, 'phone', '+90 555 999 88 77');

        // Adjust timestamps to allow resend immediately
        $this->db->update('resident_contact_verifications', [
            'sent_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
        ], 'id = ?', [$request['verification_id']]);

        $before = $this->db->fetch(
            "SELECT sent_at, expires_at FROM resident_contact_verifications WHERE id = ?",
            [$request['verification_id']]
        );

        $service->resend($this->residentId, $request['verification_id']);

        $after = $this->db->fetch(
            "SELECT sent_at, expires_at FROM resident_contact_verifications WHERE id = ?",
            [$request['verification_id']]
        );

        $this->assertTrue(strtotime($after['sent_at']) > strtotime($before['sent_at']), 'Sent timestamp updated');
        $this->assertTrue(strtotime($after['expires_at']) > strtotime($before['expires_at']), 'Expiry timestamp refreshed');
    }

    public function testDuplicateRequestWithinCooldownThrows(): void
    {
        $service = new ResidentContactVerificationService();
        $service->requestVerification($this->resident, 'phone', '+90 555 999 88 77');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Gönderilen kodu kullanın/');
        $service->requestVerification($this->resident, 'phone', '+90 555 999 88 77');
    }

    public function testRequestAllowedAfterExpiry(): void
    {
        $service = new ResidentContactVerificationService();
        $first = $service->requestVerification($this->resident, 'email', 'resident.expire@example.com');

        $this->db->update('resident_contact_verifications', [
            'sent_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
        ], 'id = ?', [$first['verification_id']]);

        $second = $service->requestVerification($this->resident, 'email', 'resident.expire@example.com');
        $this->assertArrayHasKey('verification_id', $second);
        $this->assertNotSame($first['verification_id'], $second['verification_id']);
    }

    private function seedResident(): void
    {
        $now = date('Y-m-d H:i:s');
        $buildingId = $this->db->insert('buildings', [
            'name' => 'Verification Tower ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Cad. 123',
            'city' => 'İstanbul',
            'total_units' => 20,
            'status' => 'active',
        ]);

        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'V' . rand(100, 999),
            'owner_type' => 'owner',
            'owner_name' => 'Verification Owner',
            'monthly_fee' => 350,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->residentId = $this->residentModel->create([
            'unit_id' => $unitId,
            'name' => 'Verification User',
            'email' => 'verification@example.com',
            'phone' => '+905551110000',
            'password_hash' => password_hash('Secure123!', PASSWORD_DEFAULT),
            'is_owner' => 1,
            'is_active' => 1,
        ]);

        $this->resident = $this->residentModel->find($this->residentId);
        $_SESSION['resident_user_id'] = $this->residentId;
        $_SESSION['resident_unit_id'] = $unitId;
    }
}

