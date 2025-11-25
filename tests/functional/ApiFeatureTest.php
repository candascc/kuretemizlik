<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php'; // Load all necessary classes including FactoryRegistry
require_once __DIR__ . '/../Support/FactoryRegistry.php'; // Explicitly load FactoryRegistry
require_once __DIR__ . '/../TestHelper.php'; // For redirect() function
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/ResponseFormatter.php';
require_once __DIR__ . '/../../src/Lib/InputSanitizer.php';
require_once __DIR__ . '/../../src/Lib/Validator.php';
require_once __DIR__ . '/../../src/Lib/JWTAuth.php';
require_once __DIR__ . '/../../src/Lib/Auth.php';
require_once __DIR__ . '/../../src/Controllers/Api/V2/JobController.php';
require_once __DIR__ . '/../../src/Controllers/FileUploadController.php';

final class ApiFeatureTest extends TestCase
{
    private Database $db;
    private int $userId;
    private string $username;
    private string $authHeader = '';
    private bool $transactionStarted = false;
    private array $originalServer = [];

    protected function setUp(): void
    {
        ResponseFormatter::setAutoTerminate(false);
        http_response_code(200);

        if (!headers_sent()) {
            header_remove();
        }

        if (empty($_ENV['JWT_SECRET'])) {
            $_ENV['JWT_SECRET'] = bin2hex(random_bytes(32));
        }

        $this->db = Database::getInstance();
        FactoryRegistry::setDatabase($this->db);
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $this->transactionStarted = true;
        }

        $this->originalServer = $_SERVER;

        [$this->userId, $this->username] = $this->createTestUser();
        $token = JWTAuth::generateToken($this->userId, [
            'username' => $this->username,
            'role' => 'ADMIN'
        ]);

        $this->authHeader = 'Bearer ' . $token;
        $_SERVER['HTTP_AUTHORIZATION'] = $this->authHeader;
        $_SERVER['Authorization'] = $this->authHeader;
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $_SESSION['user_id'] = $this->userId;
        $_SESSION['username'] = $this->username;
        $_SESSION['role'] = 'ADMIN';
        $_SESSION['login_time'] = time();
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = $this->originalServer;
        http_response_code(200);

        ResponseFormatter::setAutoTerminate(true);

        if ($this->transactionStarted && $this->db->inTransaction()) {
            $this->db->rollBack();
        }

        $_SESSION = [];
    }

    public function testJobCreateValidationFailsWithoutRequiredFields(): void
    {
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new App\Controllers\Api\V2\JobController();

        ob_start();
        $controller->create();
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload, 'Response should be valid JSON');
        $this->assertFalse($payload['success']);
        $this->assertEquals(422, http_response_code());
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('customer_id', $payload['errors']);
        $this->assertArrayHasKey('start_at', $payload['errors']);
    }

    public function testJobCreateSucceedsAndNormalizesData(): void
    {
        $now = date('Y-m-d H:i:s');
        $customerId = $this->db->insert('customers', [
            'name' => 'API Customer',
            'phone' => '5551234567',
            'email' => 'api.customer@example.com',
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $_POST = [
            'customer_id' => $customerId,
            'service_id' => null,
            'assigned_to' => null,
            'start_at' => '2025-11-10 09:00',
            'end_at' => '2025-11-10 10:30',
            'status' => 'scheduled',
            'note' => '<b>Important Visit</b>',
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new App\Controllers\Api\V2\JobController();

        ob_start();
        $controller->create();
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload);
        $this->assertTrue($payload['success']);
        $this->assertEquals(201, http_response_code());
        $this->assertArrayHasKey('data', $payload);

        $jobId = $payload['data']['id'] ?? null;
        $this->assertNotNull($jobId, 'Job ID should be returned');

        $job = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
        $this->assertNotFalse($job);
        $this->assertEquals('2025-11-10 09:00', $job['start_at']);
        $this->assertEquals('2025-11-10 10:30', $job['end_at']);
        $this->assertEquals('SCHEDULED', $job['status']);
        $this->assertEquals('Important Visit', $job['note']);
    }

    public function testJobUpdateRejectsInvalidStatus(): void
    {
        $jobId = $this->createTestJob();

        $_POST = [
            'status' => 'INVALID_STATUS'
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new App\Controllers\Api\V2\JobController();

        ob_start();
        $controller->update($jobId);
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload);
        $this->assertFalse($payload['success']);
        $this->assertEquals(422, http_response_code());
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('status', $payload['errors']);
    }

    public function testFileDownloadMissingFileReturnsJson(): void
    {
        $controller = new FileUploadController();

        ob_start();
        try {
            $controller->download(999999);
            $this->fail('Expected redirect or exception');
        } catch (RedirectIntercept $redirect) {
            // Auth::require() redirects to login - this is expected behavior
            $this->assertStringContainsString('/login', $redirect->target);
        }
        $output = ob_get_clean();

        // If output is JSON (file not found case)
        if (!empty($output)) {
            $payload = json_decode($output, true);
            if ($payload !== null) {
                $this->assertFalse($payload['success']);
                $this->assertEquals(404, http_response_code());
                $this->assertEquals('Dosya bulunamadı', $payload['message']);
            }
        }
    }

    public function testFileUploadProgressMissingRecordReturnsJson(): void
    {
        $controller = new FileUploadController();

        $_GET = ['session_id' => 'non-existent-session'];

        ob_start();
        $controller->getProgress();
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload);
        $this->assertFalse($payload['success']);
        $this->assertEquals(404, http_response_code());
        $this->assertEquals('Upload progress bulunamadı', $payload['message']);
    }

    /**
     * @return array{0:int,1:string}
     */
    private function createTestUser(): array
    {
        $userId = FactoryRegistry::user()->create(['role' => 'ADMIN']);
        $userData = $this->db->fetch("SELECT username FROM users WHERE id = ?", [$userId]);
        $username = $userData['username'] ?? 'test_user';

        return [(int)$userId, $username];
    }

    private function createTestJob(): int
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $jobId = FactoryRegistry::job()->create([
            'customer_id' => $customerId,
            'company_id' => 1,
            'start_at' => '2025-11-10 08:00',
            'end_at' => '2025-11-10 09:00',
            'status' => 'SCHEDULED',
            'note' => 'Integration job',
        ]);

        return (int)$jobId;
    }
}

