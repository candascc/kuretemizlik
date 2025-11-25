<?php
/**
 * Functional Test: Management Residents & Portal UI
 *
 * Verifies resident portal management filters, pagination and failure alerts.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/Auth.php';
require_once __DIR__ . '/../../src/Controllers/ManagementResidentsController.php';
require_once __DIR__ . '/../../src/Lib/ResidentPortalMetrics.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';

use Tests\Support\FactoryRegistry;

class ManagementResidentsTest
{
    private Database $db;
    private array $created = [
        'users' => [],
        'buildings' => [],
        'units' => [],
        'residents' => [],
    ];
    private array $results = [];
    private bool $transactionActive = false;
    private array $additionalResidentNames = [];
    private int $seededAdminId;
    private int $primaryBuildingId;
    private string $primaryResidentName;
    private string $secondaryResidentName;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $this->transactionActive = true;
            }
        } catch (Throwable $e) {
            $this->transactionActive = false;
        }
    }

    public function runAll(): bool
    {
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 4: Management Residents & Portal           │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";

        try {
            $this->seedData();
            $this->testBuildingFilter();
            $this->testSearchFilter();
            $this->testPaginationCoverage();
            $this->testAlertOnDataFailure();
        } finally {
            $this->cleanup();
        }

        $this->printSummary();
        return $this->allPassed();
    }

    private function seedData(): void
    {
        // Seed admin user for session simulation
        $adminId = FactoryRegistry::user()->create([
            'role' => 'ADMIN',
            'email' => 'test-admin+' . uniqid() . '@example.com',
        ]);
        $this->created['users'][] = $adminId;

        // Seed buildings
        $buildingA = FactoryRegistry::building()->create([
            'name' => 'Portal Tower ' . uniqid(),
            'city' => 'İstanbul',
            'total_units' => 10,
        ]);
        $buildingB = FactoryRegistry::building()->create([
            'name' => 'Yedek Plaza ' . uniqid(),
            'city' => 'İstanbul',
            'total_units' => 8,
        ]);
        $this->created['buildings'][] = $buildingA;
        $this->created['buildings'][] = $buildingB;

        // Seed units
        $unitA = FactoryRegistry::unit()->create([
            'building_id' => $buildingA,
            'unit_number' => 'A1',
            'owner_name' => 'Test Owner A',
            'monthly_fee' => 0,
        ]);
        $unitB = FactoryRegistry::unit()->create([
            'building_id' => $buildingB,
            'unit_number' => 'B1',
            'owner_name' => 'Test Owner B',
            'monthly_fee' => 0,
        ]);
        $this->created['units'][] = $unitA;
        $this->created['units'][] = $unitB;

        // Seed residents
        $residentPrimary = FactoryRegistry::residentUser()->create([
            'unit_id' => $unitA,
            'name' => 'Portal Test Sakin',
            'email' => 'resident+' . uniqid() . '@example.com',
            'phone' => '5550000000',
            'email_verified' => 1,
        ]);
        $residentOther = FactoryRegistry::residentUser()->create([
            'unit_id' => $unitB,
            'name' => 'Diğer Sakin',
            'email' => 'other+' . uniqid() . '@example.com',
            'phone' => '5551111111',
        ]);
        $this->created['residents'][] = $residentPrimary;
        $this->created['residents'][] = $residentOther;
        
        $now = time();
        for ($i = 1; $i <= 8; $i++) {
            $name = "Portal Extra {$i}";
            $createdAt = date('Y-m-d H:i:s', $now - (60 + $i * 30));
            $residentExtra = FactoryRegistry::residentUser()->create([
                'unit_id' => $unitA,
                'name' => $name,
                'email' => 'resident-extra+' . uniqid() . '@example.com',
                'phone' => '555' . str_pad((string)mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $this->created['residents'][] = $residentExtra;
            $this->additionalResidentNames[] = $name;
        }

        $this->seededAdminId = $adminId;
        $this->primaryBuildingId = $buildingA;
        $this->secondaryResidentName = 'Diğer Sakin';
        $this->primaryResidentName = 'Portal Test Sakin';
    }

    private function cleanup(): void
    {
        if ($this->transactionActive && $this->db->inTransaction()) {
            $this->db->rollback();
            $this->transactionActive = false;
        } else {
            foreach (array_reverse($this->created['residents']) as $id) {
                $this->db->delete('resident_users', 'id = ?', [$id]);
            }
            foreach (array_reverse($this->created['units']) as $id) {
                $this->db->delete('units', 'id = ?', [$id]);
            }
            foreach (array_reverse($this->created['buildings']) as $id) {
                $this->db->delete('buildings', 'id = ?', [$id]);
            }
            foreach (array_reverse($this->created['users']) as $id) {
                $this->db->delete('users', 'id = ?', [$id]);
            }
        }

        $_GET = [];
        $_SESSION = [];
        Auth::refresh();
    }

    private function testBuildingFilter(): void
    {
        echo "• Test: Bina filtresi yalnızca ilgili sakinleri döndürür... ";
        $_GET = [
            'building_id' => $this->primaryBuildingId,
        ];
        $output = $this->renderResidentsPage();
        $table = $this->extractResidentTable($output);

        $pass = ($table !== null)
            && (strpos($table, $this->primaryResidentName) !== false)
            && (strpos($table, $this->secondaryResidentName) === false);

        $this->addResult('Building filter isolates residents', $pass, $pass ? 'Başarılı' : 'Filtre diğer sakinleri dışlamadı');
        echo $pass ? "✅\n" : "❌\n";
        $_GET = [];
    }

    private function testSearchFilter(): void
    {
        echo "• Test: Arama kutusu isim/email eşleşmelerini bulur... ";
        $_GET = [
            'search' => 'Portal Test',
        ];
        $output = $this->renderResidentsPage();
        $table = $this->extractResidentTable($output);

        $pass = (strpos($output, 'name="search"') !== false)
            && (strpos($output, 'value="Portal Test"') !== false)
            && ($table !== null)
            && (strpos($table, $this->primaryResidentName) !== false);

        $this->addResult('Search filter locates residents', $pass, $pass ? 'Başarılı' : 'Arama sonuçları beklenmiyor');
        echo $pass ? "✅\n" : "❌\n";
        $_GET = [];
    }

    private function testPaginationCoverage(): void
    {
        echo "• Test: Sayfalama ve kombin filtreler doğru çalışıyor... ";

        $pageTwoOutput = $this->renderResidentsPageWithGet([
            'building_id' => $this->primaryBuildingId,
            'per_page' => 5,
            'page' => 2,
        ], 'management-residents-page-2');
        $pageTwoTable = $this->extractResidentTable($pageTwoOutput);
        $pageTwoPass = ($pageTwoTable !== null)
            && strpos($pageTwoOutput, 'Sayfa 2 / 2') !== false
            && strpos($pageTwoTable, 'Portal Extra') !== false
            && strpos($pageTwoTable, $this->primaryResidentName) === false
            && strpos($pageTwoTable, $this->secondaryResidentName) === false;

        $emptyOutput = $this->renderResidentsPageWithGet([
            'search' => 'NoMatch' . uniqid(),
            'per_page' => 5,
        ], 'management-residents-empty');

        $emptyStatePass = (strpos($emptyOutput, 'Tanımlı sakin bulunmuyor') !== false);

        $pass = $pageTwoPass && $emptyStatePass;
        if (!$pass) {
            file_put_contents(__DIR__ . '/../../tmp_management_residents_pagination_page.html', $pageTwoOutput);
            file_put_contents(__DIR__ . '/../../tmp_management_residents_pagination_empty.html', $emptyOutput);
        }
        $this->addResult('Pagination and combined filters', $pass, $pass ? 'Başarılı' : 'Sayfalama doğrulanamadı');
        echo $pass ? "✅\n" : "❌\n";
    }

    private function testAlertOnDataFailure(): void
    {
        echo "• Test: Veri sorgusu hata verdiğinde uyarı gösterilir... ";
        $_GET = []; // default filters
        $output = '';

        try {
            $this->db->execute('SAVEPOINT resident_portal_failure');
            $this->db->execute('ALTER TABLE resident_requests RENAME TO resident_requests__bak');
            $output = $this->renderResidentsPage();
            $this->db->execute('ROLLBACK TO resident_portal_failure');
            $this->db->execute('RELEASE resident_portal_failure');
        } catch (Throwable $e) {
            try {
                $this->db->execute('ROLLBACK TO resident_portal_failure');
                $this->db->execute('RELEASE resident_portal_failure');
            } catch (Throwable $inner) {
                // ignore
            }
            $this->addResult('Alerts on failure', false, 'Hata simülasyonu başarısız: ' . $e->getMessage());
            echo "❌\n";
            return;
        } finally {
            $_GET = [];
        }

        $pass = (strpos($output, 'Sakin talepleri listesi şu anda görüntülenemiyor.') !== false)
            && (strpos($output, 'Ref:') !== false);
        $this->addResult('Alerts on failure', $pass, $pass ? 'Başarılı' : 'Uyarı mesajı bulunamadı');
        echo $pass ? "✅\n" : "❌\n";
    }

    private function extractResidentTable(string $output): ?string
    {
        if (preg_match('#Sakin Listesi.*?<tbody[^>]*>(.*?)</tbody>#su', $output, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function renderResidentsPageWithGet(array $params, ?string $snapshotName = null): string
    {
        $_GET = $params;
        $output = $this->renderResidentsPage();
        $_GET = [];
        $this->maybeSaveSnapshot($output, $snapshotName);
        return $output;
    }

    private function maybeSaveSnapshot(string $html, ?string $name): void
    {
        if (!$name) {
            return;
        }
        $dir = getenv('VISUAL_SNAPSHOT_DIR');
        if (!$dir) {
            return;
        }
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.html', $html);
        }
    }

    private function renderResidentsPage(): string
    {
        $this->simulateAdminSession();
        $_SERVER['REQUEST_URI'] = '/management/residents';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        try {
            $controller = new ManagementResidentsController();
            $controller->index();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_clean();
        Auth::refresh();
        return $output;
    }

    private function simulateAdminSession(): void
    {
        $_SESSION['user_id'] = $this->seededAdminId;
        $_SESSION['role'] = 'ADMIN';
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }

    private function addResult(string $label, bool $passed, string $message = ''): void
    {
        $this->results[] = [
            'label' => $label,
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function printSummary(): void
    {
        echo "\nTest Sonuçları:\n";
        foreach ($this->results as $result) {
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            echo sprintf(" - [%s] %s", $status, $result['label']);
            if ($result['message']) {
                echo " :: " . $result['message'];
            }
            echo "\n";
        }
        echo "\n";
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

