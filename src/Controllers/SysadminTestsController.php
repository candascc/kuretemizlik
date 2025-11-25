<?php
/**
 * Sysadmin Tests Controller
 * 
 * Manages test execution, monitoring, and reporting via web UI
 * PATH: /app/sysadmin/tests
 */

require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/Utils.php';
require_once __DIR__ . '/../Services/TestExecutionService.php';

class SysadminTestsController
{
    private TestExecutionService $testService;
    
    public function __construct()
    {
        $this->testService = new TestExecutionService();
    }
    
    /**
     * Test dashboard - List all tests and show statistics
     * 
     * GET /app/sysadmin/tests
     */
    public function index(): void
    {
        Auth::require();
        Auth::requireRole(['SUPERADMIN']);
        
        $testStats = $this->getTestStatistics();
        $recentRuns = $this->getRecentTestRuns(10);
        $testSuites = $this->getTestSuites();
        
        $data = [
            'title' => 'Test Yönetimi',
            'stats' => $testStats,
            'recentRuns' => $recentRuns,
            'testSuites' => $testSuites,
        ];
        
        echo View::renderWithLayout('sysadmin/tests/dashboard', $data);
    }
    
    /**
     * Run tests
     * 
     * POST /app/sysadmin/tests/run
     */
    public function run(): void
    {
        Auth::require();
        Auth::requireRole(['SUPERADMIN']);
        
        try {
            // Input validation
            $suite = $_POST['suite'] ?? 'All';
            $testFile = $_POST['test_file'] ?? null;
            
            // Validate suite
            $validSuites = array_keys($this->getTestSuites());
            if (!in_array($suite, $validSuites, true)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid test suite',
                    'valid_suites' => $validSuites,
                ]);
                return;
            }
            
            // Validate test file (if provided)
            if ($testFile !== null) {
                // Security: Prevent path traversal
                $testFile = basename($testFile);
                if (!preg_match('/^[a-zA-Z0-9_\/\-]+Test\.php$/', $testFile)) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid test file name',
                    ]);
                    return;
                }
            }
            
            $result = $this->testService->runTests($suite, $testFile);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Test execution failed',
                'message' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Internal server error',
            ]);
        }
    }
    
    /**
     * Get test execution status
     * 
     * GET /app/sysadmin/tests/status/:runId
     */
    public function status($runId): void
    {
        Auth::require();
        Auth::requireRole(['SUPERADMIN']);
        
        try {
            // Input validation: Prevent path traversal
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $runId)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid run ID format',
                ]);
                return;
            }
            
            $status = $this->testService->getTestRunStatus($runId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $status,
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get test status',
                'message' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Internal server error',
            ]);
        }
    }
    
    /**
     * View test results
     * 
     * GET /app/sysadmin/tests/results/:runId
     */
    public function results($runId): void
    {
        Auth::require();
        Auth::requireRole(['SUPERADMIN']);
        
        try {
            // Input validation: Prevent path traversal
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $runId)) {
                set_flash('error', 'Geçersiz test run ID');
                redirect('/sysadmin/tests');
                return;
            }
            
            $status = $this->testService->getTestRunStatus($runId);
            $outputDir = $this->testService->getOutputDir();
            $jsonFile = $outputDir . '/' . $runId . '.json';
            $logFile = $outputDir . '/' . $runId . '.log';
            
            $results = [];
            if (file_exists($jsonFile)) {
                $jsonContent = file_get_contents($jsonFile);
                $results = json_decode($jsonContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Failed to parse JSON for run {$runId}: " . json_last_error_msg());
                }
            }
            
            $logContent = '';
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                // Limit log size to prevent memory issues (last 100KB)
                if (strlen($logContent) > 100000) {
                    $logContent = '... (log truncated, showing last 100KB) ...' . substr($logContent, -100000);
                }
            }
            
            $data = [
                'title' => 'Test Sonuçları',
                'results' => $results,
                'status' => $status,
                'logContent' => $logContent,
                'runId' => $runId,
            ];
            
            echo View::renderWithLayout('sysadmin/tests/results', $data);
            
        } catch (Exception $e) {
            error_log("Error in test results view: " . $e->getMessage());
            set_flash('error', 'Test sonuçları yüklenirken hata oluştu');
            redirect('/sysadmin/tests');
        }
    }
    
    /**
     * Get test statistics
     */
    private function getTestStatistics(): array
    {
        $testDir = __DIR__ . '/../../tests';
        $stats = [
            'total_files' => 0,
            'total_tests' => 0,
            'last_run' => null,
            'success_rate' => 0,
        ];
        
        // Count test files
        $testFiles = glob($testDir . '/**/*Test.php');
        $stats['total_files'] = count($testFiles);
        
        // Try to read last run results
        $lastRunFile = $testDir . '/test_outputs/comprehensive_results.json';
        if (file_exists($lastRunFile)) {
            $lastRun = json_decode(file_get_contents($lastRunFile), true);
            if ($lastRun) {
                $stats['last_run'] = $lastRun['timestamp'] ?? null;
                $stats['total_tests'] = $lastRun['summary']['total_tests'] ?? 0;
                $stats['success_rate'] = $lastRun['summary']['success_rate'] ?? 0;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get recent test runs
     */
    private function getRecentTestRuns(int $limit = 10): array
    {
        $runs = [];
        $outputDir = __DIR__ . '/../../tests/test_outputs';
        
        if (!is_dir($outputDir)) {
            return $runs;
        }
        
        $files = glob($outputDir . '/comprehensive_results_*.json');
        rsort($files);
        
        foreach (array_slice($files, 0, $limit) as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $runs[] = [
                    'timestamp' => $data['timestamp'] ?? null,
                    'total_tests' => $data['summary']['total_tests'] ?? 0,
                    'success_rate' => $data['summary']['success_rate'] ?? 0,
                    'file' => basename($file),
                ];
            }
        }
        
        return $runs;
    }
    
    /**
     * Get available test suites
     */
    private function getTestSuites(): array
    {
        return [
            'All' => 'Tüm Testler',
            'Fast' => 'Hızlı Testler',
            'Slow' => 'Yavaş Testler',
            'Unit' => 'Unit Testler',
            'Integration' => 'Integration Testler',
            'Functional' => 'Functional Testler',
            'Security' => 'Security Testler',
            'Performance' => 'Performance Testler',
            'Stress' => 'Stress Testler',
            'Load' => 'Load Testler',
        ];
    }
    
}

