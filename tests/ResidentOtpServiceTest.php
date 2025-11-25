<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Services/ResidentOtpService.php';

final class ResidentOtpServiceTest extends TestCase
{
    private Database $db;
    private int $buildingId;
    private int $unitId;
    private int $residentId;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $this->db = Database::getInstance();
        $this->db->query('BEGIN');

        $this->ensureResidentUserSchema();

        $this->db->query("CREATE TABLE IF NOT EXISTS resident_login_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            resident_user_id INTEGER NOT NULL,
            token TEXT NOT NULL,
            channel TEXT NOT NULL CHECK(channel IN ('email','sms')),
            expires_at TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            max_attempts INTEGER NOT NULL DEFAULT 5,
            meta TEXT,
            consumed_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE CASCADE
        )");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_user ON resident_login_tokens(resident_user_id)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_token ON resident_login_tokens(token)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_expires ON resident_login_tokens(expires_at)");

        $now = date('Y-m-d H:i:s');
        $this->buildingId = $this->db->insert('buildings', [
            'name' => 'Test Blok',
            'building_type' => 'apartman',
            'address_line' => 'Test Mah. 1. Cad. No:1',
            'city' => 'İstanbul',
            'total_units' => 1,
            'monthly_maintenance_day' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->unitId = $this->db->insert('units', [
            'building_id' => $this->buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'A1',
            'owner_type' => 'owner',
            'owner_name' => 'Test Sahibi',
            'monthly_fee' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->residentId = $this->db->insert('resident_users', [
            'unit_id' => $this->unitId,
            'name' => 'Test Sakin',
            'email' => 'otp.test@example.com',
            'phone' => '+90 555 111 22 33',
            'password_hash' => password_hash('Dummy123!', PASSWORD_DEFAULT),
            'is_owner' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->query('ROLLBACK');
        $_SESSION = [];
    }

    public function testRequestTokenCreatesRowAndQueuesEmail(): void
    {
        $resident = (new ResidentUser())->find($this->residentId);
        $service = new ResidentOtpService();

        $result = $service->requestToken($resident, 'email', '127.0.0.1');

        $this->assertArrayHasKey('token_id', $result);
        $this->assertSame('email', $result['channel']);
        $this->assertNotEmpty($result['masked_contact']);

        $tokenRow = $this->db->fetch('SELECT * FROM resident_login_tokens WHERE id = ?', [$result['token_id']]);
        $this->assertNotEmpty($tokenRow);
        $this->assertSame($this->residentId, (int)$tokenRow['resident_user_id']);
        $this->assertSame('email', $tokenRow['channel']);
        $this->assertSame(0, (int)$tokenRow['attempts']);
        $this->assertNull($tokenRow['consumed_at']);

        $queuedEmail = $this->db->fetch('SELECT * FROM email_queue WHERE to_email = ? ORDER BY id DESC LIMIT 1', [$resident['email']]);
        $this->assertNotEmpty($queuedEmail);
        $this->assertSame('resident_login_otp', $queuedEmail['template']);
    }

    public function testVerifyTokenSucceedsAndMarksConsumed(): void
    {
        $resident = (new ResidentUser())->find($this->residentId);
        $service = new ResidentOtpService();
        $request = $service->requestToken($resident, 'email');

        $queuedEmail = $this->db->fetch('SELECT * FROM email_queue WHERE to_email = ? ORDER BY id DESC LIMIT 1', [$resident['email']]);
        $this->assertNotEmpty($queuedEmail, 'Doğrulama e-postası bulunamadı');

        preg_match('/(\d{6})/', $queuedEmail['message'], $matches);
        $this->assertNotEmpty($matches, 'E-posta içeriğinde doğrulama kodu bulunamadı');
        $code = $matches[1];

        $verify = $service->verifyToken((int)$request['token_id'], $code);
        $this->assertTrue($verify['success']);
        $this->assertSame($this->residentId, $verify['resident_user_id']);

        $tokenRow = $this->db->fetch('SELECT consumed_at FROM resident_login_tokens WHERE id = ?', [$request['token_id']]);
        $this->assertNotEmpty($tokenRow['consumed_at']);
    }

    public function testVerifyTokenFailsWithWrongCode(): void
    {
        $resident = (new ResidentUser())->find($this->residentId);
        $service = new ResidentOtpService();
        $request = $service->requestToken($resident, 'email');

        $verify = $service->verifyToken((int)$request['token_id'], '000000');
        $this->assertFalse($verify['success']);
        $this->assertSame('mismatch', $verify['reason']);
        $this->assertEquals(4, $verify['attempts_remaining']);
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

