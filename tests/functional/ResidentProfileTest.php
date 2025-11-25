<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';

use Tests\Support\FactoryRegistry;
require_once __DIR__ . '/../../src/Models/ResidentUser.php';
require_once __DIR__ . '/../../src/Models/Unit.php';
require_once __DIR__ . '/../../src/Models/Building.php';
require_once __DIR__ . '/../../src/Controllers/ResidentController.php';
require_once __DIR__ . '/../../src/Services/ResidentContactVerificationService.php';
require_once __DIR__ . '/../../src/Services/ResidentNotificationPreferenceService.php';
require_once __DIR__ . '/../../src/Services/EmailQueue.php';
require_once __DIR__ . '/../../src/Services/SMSQueue.php';
require_once __DIR__ . '/../../src/Lib/ActivityLogger.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';
require_once __DIR__ . '/../../src/Lib/ResidentPortalMetrics.php';
require_once __DIR__ . '/../../src/Cache/ResidentMetricsArrayCache.php';
require_once __DIR__ . '/../../src/Contracts/ResidentMetricsCacheInterface.php';

if (!class_exists('RedirectException')) {
    class RedirectException extends Exception
    {
        public string $target;
        public int $status;

        public function __construct(string $target, int $status)
        {
            parent::__construct("Redirected to {$target}", $status);
            $this->target = $target;
            $this->status = $status;
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        throw new RedirectException($url, $status);
    }
}

class ResidentProfileTest
{
    private Database $db;
    private ResidentUser $residentModel;
    private array $results = [];
    private ?int $buildingId = null;
    private ?int $unitId = null;
    private ?int $residentId = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->residentModel = new ResidentUser();
    }

    public function runAll(): bool
    {
        echo "=== Resident Profile Tests ===\n\n";

        $this->testContactUpdateNormalizesInput();
        $this->testSecondaryPhoneMustDiffer();
        $this->testPasswordUpdateFlow();

        $this->printResults();
        return $this->allPassed();
    }

    private function seedResident(string $password = 'OldPass123!'): array
    {
        $this->buildingId = FactoryRegistry::building()->create([
            'name' => 'Resident Test Tower ' . uniqid(),
            'city' => 'İstanbul',
            'total_units' => 10,
        ]);

        $this->unitId = FactoryRegistry::unit()->create([
            'building_id' => $this->buildingId,
            'unit_number' => 'D' . rand(1, 99),
            'owner_name' => 'Test Owner',
            'monthly_fee' => 450,
        ]);

        $this->residentId = FactoryRegistry::residentUser()->create([
            'unit_id' => $this->unitId,
            'name' => 'Profil Test',
            'email' => 'profil+' . uniqid() . '@example.com',
            'phone' => '+905551234567',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'email_verified' => 1,
        ]);

        return [
            'building_id' => $this->buildingId,
            'unit_id' => $this->unitId,
            'resident_id' => $this->residentId,
        ];
    }

    private function testContactUpdateNormalizesInput(): void
    {
        echo "Test: Contact form normalizes phone & toggles notifications\n";
        echo "-----------------------------------------------------------------\n";

        $this->db->beginTransaction();
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            $_POST = [];

            $seed = $this->seedResident();
            $_SESSION['resident_user_id'] = $seed['resident_id'];
            $_SESSION['resident_unit_id'] = $seed['unit_id'];
            $_SESSION['resident_name'] = 'Profil Test';
            $_SESSION['resident_email'] = 'profil@example.com';

            $cacheSpy = new class implements ResidentMetricsCacheInterface {
                public bool $cleared = false;
                public function get(string $key): ?array { return null; }
                public function set(string $key, array $value, int $ttl): void {}
                public function clear(?string $pattern = null): void { $this->cleared = true; }
            };
            ResidentPortalMetrics::setCacheDriver($cacheSpy);

            $_POST = [
                'form_context' => 'contact',
                'name' => 'Profil Test',
                'email' => 'profil@example.com',
                'phone' => '0 (555) 123 45 67',
                'secondary_email' => 'ikinci@example.com',
                'secondary_phone' => '+90 555 765 43 21',
                'notify_email' => '1',
                'notify_sms' => '1',
                'pref_email_fees' => '1',
                'pref_sms_fees' => '1',
                'pref_email_meetings' => '1',
                'pref_email_announcements' => '1',
                'pref_email_alerts' => '1',
                'pref_sms_alerts' => '1',
            ];

            $resident = $this->residentModel->find($seed['resident_id']);
            $controller = new ResidentController();
            $method = new ReflectionMethod(ResidentController::class, 'updateProfile');
            $method->setAccessible(true);
            $verificationService = new ResidentContactVerificationService();
            $preferenceService = new ResidentNotificationPreferenceService();
            $preferenceService = new ResidentNotificationPreferenceService();
            $preferenceService = new ResidentNotificationPreferenceService();
            $preferenceService = new ResidentNotificationPreferenceService();

            try {
                $method->invoke($controller, $seed['resident_id'], $resident, $verificationService, $preferenceService);
            } catch (RedirectException $redirect) {
                // Expected redirect back to profile page
            }

            $updated = $this->residentModel->find($seed['resident_id']);

            $this->assertTrue(str_starts_with($updated['phone'], '+90'), 'Primary phone normalized with +90 prefix');
            $this->assertSame('+905551234567', Utils::normalizePhone($updated['phone']), 'Primary phone exact normalized value');
            $this->assertSame('+905557654321', Utils::normalizePhone($updated['secondary_phone']), 'Secondary phone normalized');
            $this->assertSame(1, (int)$updated['notify_email'], 'Email notifications enabled');
            $this->assertSame(1, (int)$updated['notify_sms'], 'SMS notifications enabled');
            $this->assertSame('ikinci@example.com', $updated['secondary_email'], 'Secondary email saved');

            $pendingVerification = $this->db->fetch(
                "SELECT * FROM resident_contact_verifications WHERE resident_user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1",
                [$seed['resident_id']]
            );
            $this->assertTrue($pendingVerification !== false, 'Email change requires verification token');
            $this->assertSame('email', $pendingVerification['verification_type'], 'Verification type marked as email');

            // Simulate verification by injecting known code
            $this->db->update('resident_contact_verifications', [
                'code_hash' => password_hash('123456', PASSWORD_DEFAULT),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
            ], 'id = ?', [$pendingVerification['id']]);

            $_POST = [
                'verification_id' => $pendingVerification['id'],
                'code' => '123456',
            ];
            $_SERVER['REQUEST_METHOD'] = 'POST';

            try {
                $controller->verifyContact();
            } catch (RedirectException $redirect) {
                $this->assertSame(base_url('/resident/profile'), $redirect->target, 'Redirected back to profile after verification');
            }

            $_SERVER['REQUEST_METHOD'] = 'GET';

            $verified = $this->db->fetch(
                "SELECT status FROM resident_contact_verifications WHERE id = ?",
                [$pendingVerification['id']]
            );
            $this->assertSame('verified', $verified['status'], 'Verification marked as verified');

            $updatedAfterVerify = $this->residentModel->find($seed['resident_id']);
            $this->assertSame('profil@example.com', $updatedAfterVerify['email'], 'Email updated after verification');
            $this->assertSame('profil@example.com', $_SESSION['resident_email'], 'Session email refreshed after verification');

            $prefs = $preferenceService->getResidentPreferences($seed['resident_id']);
            $this->assertTrue($prefs['fees']['email'], 'Aidat e-postası açık');
            $this->assertTrue($prefs['fees']['sms'], 'Aidat SMS’i açık');
            $this->assertTrue($prefs['alerts']['sms'], 'Acil durum SMS’i açık');
            $this->assertTrue($prefs['alerts']['email'], 'Acil durum e-postası açık');
            $this->assertTrue($cacheSpy->cleared, 'Portal metrik önbelleği güncelleme sonrasında temizlenmelidir.');
            ResidentPortalMetrics::resetCacheDriver();
            ResidentPortalMetrics::clearCache();

            $this->addResult('contact_update', true, 'Contact info normalized and saved');
            echo "✅ PASS: Contact info normalized correctly\n\n";
        } catch (Exception $e) {
            $this->addResult('contact_update', false, $e->getMessage());
            echo "❌ ERROR: {$e->getMessage()}\n\n";
        } finally {
            ResidentPortalMetrics::resetCacheDriver();
            ResidentPortalMetrics::clearCache();
            $this->db->rollback();
        }
    }

    private function testSecondaryPhoneMustDiffer(): void
    {
        echo "Test: Secondary phone cannot match primary\n";
        echo "-----------------------------------------------------------------\n";

        $this->db->beginTransaction();
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            $_POST = [];

            $seed = $this->seedResident();
            $_SESSION['resident_user_id'] = $seed['resident_id'];
            $_SESSION['resident_unit_id'] = $seed['unit_id'];

            $resident = $this->residentModel->find($seed['resident_id']);
            $controller = new ResidentController();
            $method = new ReflectionMethod(ResidentController::class, 'updateProfile');
            $method->setAccessible(true);
            $verificationService = new ResidentContactVerificationService();
            $preferenceService = new ResidentNotificationPreferenceService();

            $_POST = [
                'form_context' => 'contact',
                'name' => 'Profil Test',
                'email' => $resident['email'],
                'phone' => '+90 555 111 00 00',
                'secondary_phone' => '+90 555 111 00 00',
            ];

            try {
                $method->invoke($controller, $seed['resident_id'], $resident, $verificationService, $preferenceService);
            } catch (RedirectException $redirect) {
                $this->assertSame(base_url('/resident/profile'), $redirect->target, 'Redirected back to profile on validation error');
            }

            $this->assertSame(
                ResidentContactVerificationService::ERROR_PHONE_DUPLICATE,
                $_SESSION['flash']['error'] ?? null,
                'Flash message should explain duplicated phone restriction'
            );

            $this->addResult('secondary_phone_validation', true, 'Secondary phone validation enforced');
            echo "✅ PASS: Secondary phone validation enforced\n\n";
        } catch (Exception $e) {
            $this->addResult('secondary_phone_validation', false, $e->getMessage());
            echo "❌ ERROR: {$e->getMessage()}\n\n";
        } finally {
            $this->db->rollback();
        }
    }

    private function testPasswordUpdateFlow(): void
    {
        echo "Test: Password update enforces validation rules\n";
        echo "-------------------------------------------------\n";

        $this->db->beginTransaction();
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            $_POST = [];

            $seed = $this->seedResident('OldPass123!');
            $_SESSION['resident_user_id'] = $seed['resident_id'];
            $_SESSION['resident_unit_id'] = $seed['unit_id'];

            $resident = $this->residentModel->find($seed['resident_id']);
            $controller = new ResidentController();
            $method = new ReflectionMethod(ResidentController::class, 'updateProfile');
            $method->setAccessible(true);
            $verificationService = new ResidentContactVerificationService();
            $preferenceService = new ResidentNotificationPreferenceService();

            // Attempt with wrong current password
            $_POST = [
                'form_context' => 'password',
                'current_password' => 'WrongPass',
                'new_password' => 'NewSecure123!',
                'confirm_password' => 'NewSecure123!',
            ];
            $_SERVER['REQUEST_METHOD'] = 'POST';

            try {
                $method->invoke($controller, $seed['resident_id'], $resident, $verificationService, $preferenceService);
            } catch (RedirectException $redirect) {
                $this->assertSame(base_url('/resident/profile'), $redirect->target, 'Redirected back to profile on failure');
            }
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $unchanged = $this->residentModel->find($seed['resident_id']);
            $this->assertTrue(password_verify('OldPass123!', $unchanged['password_hash']), 'Password unchanged on failure');

            // Successful change
            $_POST = [
                'form_context' => 'password',
                'current_password' => 'OldPass123!',
                'new_password' => 'NewSecure123!',
                'confirm_password' => 'NewSecure123!',
            ];

            $resident = $this->residentModel->find($seed['resident_id']);
            $_SERVER['REQUEST_METHOD'] = 'POST';
            try {
                $method->invoke($controller, $seed['resident_id'], $resident, $verificationService, $preferenceService);
            } catch (RedirectException $redirect) {
                // expected redirect
            }
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $updated = $this->residentModel->find($seed['resident_id']);
            $this->assertTrue(password_verify('NewSecure123!', $updated['password_hash']), 'Password updated successfully');

            $this->addResult('password_update', true, 'Password validation enforced correctly');
            echo "✅ PASS: Password update flow validated\n\n";
        } catch (Exception $e) {
            $this->addResult('password_update', false, $e->getMessage());
            echo "❌ ERROR: {$e->getMessage()}\n\n";
        } finally {
            $this->db->rollback();
        }
    }

    private function addResult(string $key, bool $passed, string $message): void
    {
        $this->results[$key] = [
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertSame($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . " (Beklenen: " . var_export($expected, true) . ", Gelen: " . var_export($actual, true) . ")");
        }
    }

    private function printResults(): void
    {
        echo "-----------------------------------------------------------------\n";
        foreach ($this->results as $key => $result) {
            $label = strtoupper(str_replace('_', ' ', $key));
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            echo sprintf("[%s] %s: %s\n", $status, $label, $result['message']);
        }
        echo "-----------------------------------------------------------------\n\n";
    }

    private function allPassed(): bool
    {
        foreach ($this->results as $result) {
            if (!$result['passed']) {
                return false;
            }
        }
        return true;
    }
}

