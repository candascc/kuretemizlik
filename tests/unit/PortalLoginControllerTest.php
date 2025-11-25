<?php

ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid', '0');
session_cache_limiter('');

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Controllers/PortalController.php';
require_once __DIR__ . '/../../src/Models/Customer.php';

// RedirectIntercept and redirect() are now in TestHelper.php
require_once __DIR__ . '/../TestHelper.php';

final class PortalLoginControllerTest extends TestCase
{
    private Database $db;
    private Customer $customerModel;
    private PortalController $controller;
    private int $customerId;

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
        $this->customerModel = new Customer();
        $this->controller = new PortalController();

        $this->ensureCustomerAuthColumns();

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        $this->customerId = $this->customerModel->create([
            'name' => 'Portal Test Customer',
            'phone' => '+905311112233',
            'email' => null,
            'notes' => null,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        $_SESSION = [];
        $_POST = [];
    }

    private function ensureCustomerAuthColumns(): void
    {
        $columns = $this->db->fetchAll("PRAGMA table_info(customers)");
        $names = array_map(static function ($column) {
            return $column['name'] ?? null;
        }, $columns);

        if (!in_array('password_hash', $names, true)) {
            $this->db->execute("ALTER TABLE customers ADD COLUMN password_hash TEXT");
        }
        if (!in_array('password_set_at', $names, true)) {
            $this->db->execute("ALTER TABLE customers ADD COLUMN password_set_at TEXT");
        }
        if (!in_array('last_otp_sent_at', $names, true)) {
            $this->db->execute("ALTER TABLE customers ADD COLUMN last_otp_sent_at TEXT");
        }
        if (!in_array('otp_attempts', $names, true)) {
            $this->db->execute("ALTER TABLE customers ADD COLUMN otp_attempts INTEGER NOT NULL DEFAULT 0");
        }
        if (!in_array('otp_context', $names, true)) {
            $this->db->execute("ALTER TABLE customers ADD COLUMN otp_context TEXT");
        }
    }

    public function testProcessLoginSetsOtpStepForFirstTimeCustomer(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['phone'] = '+90 531 111 22 33';

        try {
            $this->controller->processLogin();
            $this->fail('Redirect expected');
        } catch (RedirectIntercept $redirect) {
            $this->assertSame('/app/portal/login', $redirect->target);
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        $this->assertNotNull($flow, 'Flow state should be stored in session');
        $this->assertSame('otp', $flow['step'] ?? null);
        $this->assertSame($this->customerId, $flow['customer_id'] ?? null);
        $this->assertSame('set_password', $flow['context'] ?? null);
        $this->assertArrayHasKey('token_id', $flow);
    }

    public function testProcessLoginSwitchesToPasswordStepWhenPasswordExists(): void
    {
        $this->customerModel->updatePassword($this->customerId, 'GüçlüSifre123');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['phone'] = '0531 111 22 33';

        try {
            $this->controller->processLogin();
            $this->fail('Redirect expected');
        } catch (RedirectIntercept $redirect) {
            $this->assertSame('/app/portal/login', $redirect->target);
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        $this->assertNotNull($flow, 'Flow state should be stored in session');
        $this->assertSame('password', $flow['step'] ?? null);
        $this->assertSame($this->customerId, $flow['customer_id'] ?? null);
    }
}


