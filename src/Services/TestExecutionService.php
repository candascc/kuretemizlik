<?php
/**
 * Test Execution Service
 * 
 * Handles PHPUnit test execution, output parsing, and status tracking
 */

class TestExecutionService
{
    private string $appDir;
    private string $outputDir;
    
    public function __construct()
    {
        $this->appDir = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
        $this->outputDir = $this->appDir . '/tests/test_outputs';
        
        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    /**
     * Run tests and return execution info
     */
    public function runTests(string $suite, ?string $testFile = null): array
    {
        $runId = uniqid('run_');
        $jsonFile = $this->outputDir . '/' . $runId . '.json';
        $logFile = $this->outputDir . '/' . $runId . '.log';
        
        // Build PHPUnit command
        $command = "cd \"" . escapeshellarg($this->appDir) . "\" && ";
        $command .= "php vendor/bin/phpunit";
        
        if ($testFile) {
            $command .= " \"" . escapeshellarg($this->appDir . '/tests/' . $testFile) . "\"";
        } else {
            $command .= " --testsuite \"" . escapeshellarg($suite) . "\"";
        }
        
        $command .= " --configuration phpunit.xml";
        $command .= " --log-json \"" . escapeshellarg($jsonFile) . "\"";
        
        // Platform-specific command building
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            // Windows: Redirect output to log file, then execute in background
            $command .= " > \"" . escapeshellarg($logFile) . "\" 2>&1";
            // Use PowerShell for better background execution
            $psCommand = "Start-Process -NoNewWindow -FilePath 'php' -ArgumentList 'vendor/bin/phpunit";
            if ($testFile) {
                $psCommand .= " \"" . str_replace('\\', '/', $this->appDir . '/tests/' . $testFile) . "\"";
            } else {
                $psCommand .= " --testsuite \"" . $suite . "\"";
            }
            $psCommand .= " --configuration phpunit.xml";
            $psCommand .= " --log-json \"" . str_replace('\\', '/', $jsonFile) . "\"";
            $psCommand .= "' -RedirectStandardOutput \"" . str_replace('\\', '/', $logFile) . "\" -RedirectStandardError \"" . str_replace('\\', '/', $logFile) . "\"";
            
            // Fallback to simple background execution if PowerShell fails
            $command = "cd /d \"" . $this->appDir . "\" && start /B " . $command;
        } else {
            // Unix/Linux: Use tee for output, nohup for background
            $command .= " 2>&1 | tee \"" . escapeshellarg($logFile) . "\"";
            $command = "cd \"" . escapeshellarg($this->appDir) . "\" && nohup " . $command . " > /dev/null 2>&1 &";
        }
        
        // Execute command
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        // Save execution metadata
        $metaFile = $this->outputDir . '/' . $runId . '.meta.json';
        file_put_contents($metaFile, json_encode([
            'run_id' => $runId,
            'suite' => $suite,
            'test_file' => $testFile,
            'started_at' => date('Y-m-d H:i:s'),
            'command' => $command,
            'platform' => PHP_OS,
        ]));
        
        return [
            'run_id' => $runId,
            'status' => 'running',
            'message' => 'Test execution started',
            'json_file' => $jsonFile,
            'log_file' => $logFile,
        ];
    }
    
    /**
     * Get test run status
     */
    public function getTestRunStatus(string $runId): array
    {
        $jsonFile = $this->outputDir . '/' . $runId . '.json';
        $logFile = $this->outputDir . '/' . $runId . '.log';
        
        if (!file_exists($jsonFile)) {
            // Check if process is still running
            $isRunning = $this->isProcessRunning($runId);
            
            return [
                'status' => $isRunning ? 'running' : 'failed',
                'message' => $isRunning ? 'Test execution in progress...' : 'Test execution failed or not started',
            ];
        }
        
        // Parse JSON output
        $data = json_decode(file_get_contents($jsonFile), true);
        
        if (!$data) {
            return [
                'status' => 'error',
                'message' => 'Failed to parse test results',
            ];
        }
        
        return [
            'status' => 'completed',
            'data' => $this->parseTestResults($data),
        ];
    }
    
    /**
     * Parse PHPUnit JSON output
     * Supports both event-based and summary-based JSON formats
     */
    private function parseTestResults(array $data): array
    {
        $summary = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'errors' => 0,
            'skipped' => 0,
            'warnings' => 0,
            'duration' => 0,
            'success_rate' => 0,
            'tests' => [],
        ];
        
        // PHPUnit JSON format 1: Event-based (--log-json)
        if (isset($data['event']) && is_array($data['event'])) {
            foreach ($data['event'] as $event) {
                if (isset($event['event']) && $event['event'] === 'test') {
                    $summary['total_tests']++;
                    $testName = $event['test'] ?? 'Unknown';
                    $status = $event['status'] ?? 'unknown';
                    
                    if ($status === 'pass') {
                        $summary['passed']++;
                    } elseif ($status === 'fail') {
                        $summary['failed']++;
                    } elseif ($status === 'error') {
                        $summary['errors']++;
                    } elseif ($status === 'skip') {
                        $summary['skipped']++;
                    }
                    
                    $summary['tests'][] = [
                        'name' => $testName,
                        'status' => $status,
                        'time' => $event['time'] ?? 0,
                    ];
                }
            }
        }
        
        // PHPUnit JSON format 2: Summary-based (alternative format)
        if (isset($data['tests']) && is_numeric($data['tests'])) {
            $summary['total_tests'] = (int)$data['tests'];
        }
        if (isset($data['assertions']) && is_numeric($data['assertions'])) {
            // Assertions count available but not used in summary
        }
        if (isset($data['failures']) && is_numeric($data['failures'])) {
            $summary['failed'] = (int)$data['failures'];
        }
        if (isset($data['errors']) && is_numeric($data['errors'])) {
            $summary['errors'] = (int)$data['errors'];
        }
        if (isset($data['warnings']) && is_numeric($data['warnings'])) {
            $summary['warnings'] = (int)$data['warnings'];
        }
        if (isset($data['skipped']) && is_numeric($data['skipped'])) {
            $summary['skipped'] = (int)$data['skipped'];
        }
        
        // Calculate passed tests
        if ($summary['total_tests'] > 0 && !isset($data['event'])) {
            $summary['passed'] = $summary['total_tests'] - $summary['failed'] - $summary['errors'] - $summary['skipped'];
        }
        
        // Duration
        if (isset($data['duration'])) {
            $summary['duration'] = (float)$data['duration'];
        } elseif (isset($data['time'])) {
            $summary['duration'] = (float)$data['time'];
        }
        
        // Success rate
        if ($summary['total_tests'] > 0) {
            $summary['success_rate'] = round(($summary['passed'] / $summary['total_tests']) * 100, 2);
        }
        
        return $summary;
    }
    
    /**
     * Check if test process is still running
     */
    private function isProcessRunning(string $runId): bool
    {
        $jsonFile = $this->outputDir . '/' . $runId . '.json';
        $metaFile = $this->outputDir . '/' . $runId . '.meta.json';
        
        // If JSON file exists, test is completed
        if (file_exists($jsonFile)) {
            return false;
        }
        
        // Check metadata file for start time
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if ($meta && isset($meta['started_at'])) {
                $startTime = strtotime($meta['started_at']);
                $elapsed = time() - $startTime;
                
                // If started more than 30 minutes ago, assume failed
                if ($elapsed > 1800) {
                    return false;
                }
                
                // If started recently and JSON doesn't exist, assume running
                return true;
            }
        }
        
        // Default: if no JSON and no metadata, assume not started or failed
        return false;
    }
}

