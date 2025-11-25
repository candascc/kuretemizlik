<?php
/**
 * Crawl Debug Test Script
 * 
 * Tests crawl execution step by step to identify exact failure point
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CRAWL DEBUG TEST ===\n\n";

// Step 1: Check if required files exist
echo "Step 1: Checking required files...\n";
$requiredFiles = [
    __DIR__ . '/CrawlClient.php',
    __DIR__ . '/AdminCrawlRunner.php',
    __DIR__ . '/SysadminCrawlRunner.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "  ✓ " . basename($file) . " exists\n";
    } else {
        echo "  ✗ " . basename($file) . " NOT FOUND: {$file}\n";
        exit(1);
    }
}

// Step 2: Test CrawlClient loading
echo "\nStep 2: Testing CrawlClient loading...\n";
try {
    require_once __DIR__ . '/CrawlClient.php';
    if (class_exists('CrawlClient')) {
        echo "  ✓ CrawlClient class loaded\n";
    } else {
        echo "  ✗ CrawlClient class not found after require\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo "  ✗ Error loading CrawlClient: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// Step 3: Test AdminCrawlRunner loading
echo "\nStep 3: Testing AdminCrawlRunner loading...\n";
try {
    require_once __DIR__ . '/AdminCrawlRunner.php';
    if (class_exists('AdminCrawlRunner')) {
        echo "  ✓ AdminCrawlRunner class loaded\n";
    } else {
        echo "  ✗ AdminCrawlRunner class not found after require\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo "  ✗ Error loading AdminCrawlRunner: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// Step 4: Test instantiation
echo "\nStep 4: Testing instantiation...\n";
try {
    $baseUrl = 'https://kuretemizlik.local/app';
    $logFile = sys_get_temp_dir() . '/test_crawl_' . date('Y-m-d_H-i-s') . '.log';
    $client = new CrawlClient($baseUrl, $logFile);
    echo "  ✓ CrawlClient instantiated\n";
    
    $runner = new AdminCrawlRunner();
    echo "  ✓ AdminCrawlRunner instantiated\n";
} catch (Throwable $e) {
    echo "  ✗ Error instantiating: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 5: Test login (without actual HTTP call)
echo "\nStep 5: Testing login method exists...\n";
if (method_exists($client, 'login')) {
    echo "  ✓ CrawlClient::login() method exists\n";
} else {
    echo "  ✗ CrawlClient::login() method not found\n";
    exit(1);
}

// Step 6: Test run method exists
echo "\nStep 6: Testing run method exists...\n";
if (method_exists($runner, 'run')) {
    echo "  ✓ AdminCrawlRunner::run() method exists\n";
} else {
    echo "  ✗ AdminCrawlRunner::run() method not found\n";
    exit(1);
}

// Step 7: Test actual login (if credentials provided)
if (isset($argv[1]) && isset($argv[2])) {
    $username = $argv[1];
    $password = $argv[2];
    
    echo "\nStep 7: Testing actual login...\n";
    echo "  Username: {$username}\n";
    echo "  Base URL: {$baseUrl}\n";
    
    try {
        $loginResult = $client->login($username, $password);
        if ($loginResult) {
            echo "  ✓ Login successful\n";
        } else {
            echo "  ✗ Login failed\n";
            echo "  Check log file: {$logFile}\n";
        }
    } catch (Throwable $e) {
        echo "  ✗ Login exception: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  Trace: " . $e->getTraceAsString() . "\n";
    }
    
    // Step 8: Test crawl execution (limited)
    if ($loginResult) {
        echo "\nStep 8: Testing crawl execution (first URL only)...\n";
        try {
            // Get first URL result
            $result = $client->get('/app/');
            echo "  Status: " . ($result['status'] ?? 'unknown') . "\n";
            echo "  Has Error: " . ($result['error_flag'] ?? 'unknown' ? 'yes' : 'no') . "\n";
            echo "  Body Length: " . ($result['body_length'] ?? 0) . " bytes\n";
            
            if (($result['error_flag'] ?? false)) {
                echo "  ✗ First URL request failed\n";
                if (isset($result['error'])) {
                    echo "  Error: " . $result['error'] . "\n";
                }
            } else {
                echo "  ✓ First URL request successful\n";
            }
        } catch (Throwable $e) {
            echo "  ✗ Crawl execution exception: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "  Trace: " . $e->getTraceAsString() . "\n";
        }
    }
} else {
    echo "\nStep 7: Skipped (no credentials provided)\n";
    echo "  Usage: php test_crawl_debug.php <username> <password>\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Log file: {$logFile}\n";

