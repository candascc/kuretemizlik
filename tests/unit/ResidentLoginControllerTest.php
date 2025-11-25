<?php

ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid', '0');
session_cache_limiter('');

use PHPUnit\Framework\TestCase;

// RedirectIntercept and redirect() are now in TestHelper.php
require_once __DIR__ . '/../TestHelper.php';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Controllers/ResidentController.php';
require_once __DIR__ . '/../../src/Models/ResidentUser.php';

final class ResidentLoginControllerTest extends TestCase
{
    private Database $db;
    private ResidentUser $residentModel;
    private ResidentController $controller;
    private int $residentId;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->db = Database::getInstance();
        $this->residentModel = new ResidentUser();
        $this->controller = new ResidentController();

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        $this->seedResidentWithoutPassword();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        $_SESSION = [];
        $_POST = [];
    }

    public function testProcessLoginSetsOtpStepForFirstTimeUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['phone'] = '+90 531 300 40 50';

        try {
            $this->controller->processLogin();
            $this->fail('Redirect expected');
        } catch (RedirectIntercept $redirect) {
            $this->assertSame('/app/resident/login', $redirect->target);
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        $this->assertNotNull($flow, 'Flow state should be stored in session');
        $this->assertSame('otp', $flow['step'] ?? null);
        $this->assertSame($this->residentId, $flow['resident_id'] ?? null);
        $this->assertSame('set_password', $flow['context'] ?? null);
        $this->assertArrayHasKey('token_id', $flow);
    }

    public function testProcessLoginSwitchesToPasswordStepWhenPasswordExists(): void
    {
        $this->residentModel->updatePassword($this->residentId, 'GüçlüSifre123');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['phone'] = '0531 300 40 50';

        try {
            $this->controller->processLogin();
            $this->fail('Redirect expected');
        } catch (RedirectIntercept $redirect) {
            $this->assertSame('/app/resident/login', $redirect->target);
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        $this->assertNotNull($flow, 'Flow state should be stored in session');
        $this->assertSame('password', $flow['step'] ?? null);
        $this->assertSame($this->residentId, $flow['resident_id'] ?? null);
    }

    private function seedResidentWithoutPassword(): void
    {
        $now = date('Y-m-d H:i:s');
        $buildingId = $this->db->insert('buildings', [
            'name' => 'Controller Tower ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Cad. 101',
            'city' => 'İstanbul',
            'total_units' => 20,
            'status' => 'active',
        ]);

        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'C' . rand(100, 999),
            'owner_type' => 'owner',
            'owner_name' => 'Controller Owner',
            'monthly_fee' => 275,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->residentId = $this->residentModel->create([
            'unit_id' => $unitId,
            'name' => 'Controller User',
            'email' => 'controller.user.' . uniqid() . '@example.com',
            'phone' => '+905313004050',
            'password_hash' => '', // Empty string instead of null - no password, should go to OTP step
            'password_set_at' => null,
            'is_owner' => 1,
            'is_active' => 1,
        ]);
    }
}


