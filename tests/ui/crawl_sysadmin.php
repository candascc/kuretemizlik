<?php
/**
 * System Admin (candas) Crawl Script
 * 
 * PATH_CRAWL_SYSADMIN_V1: Crawls all system admin accessible pages
 * 
 * Usage:
 *   php tests/ui/crawl_sysadmin.php [base_url] [username] [password]
 * 
 * Environment Variables:
 *   KUREAPP_SYSADMIN_USER (default: candas)
 *   KUREAPP_SYSADMIN_PASS (default: 12dream21)
 *   KUREAPP_BASE_URL (default: https://www.kuretemizlik.com/app)
 */

require_once __DIR__ . '/SysadminCrawlRunner.php';

// Get base URL from args or env
$baseUrl = $argv[1] ?? getenv('KUREAPP_BASE_URL') ?: 'https://www.kuretemizlik.com/app';
$baseUrl = rtrim($baseUrl, '/');

// Get credentials from args or env
$username = $argv[2] ?? getenv('KUREAPP_SYSADMIN_USER') ?: 'candas';
$password = $argv[3] ?? getenv('KUREAPP_SYSADMIN_PASS') ?: '12dream21';

// Log file
$logFile = __DIR__ . '/../../logs/crawl_sysadmin_' . date('Y-m-d_H-i-s') . '.log';

echo "=== PATH_CRAWL_SYSADMIN_V1: System Admin Crawl Test ===\n";
echo "Base URL: {$baseUrl}\n";
echo "Username: {$username}\n";
echo "Log File: {$logFile}\n\n";

// PATH_CRAWL_SYSADMIN_WEB_V1: Use SysadminCrawlRunner for both CLI and web
$runner = new SysadminCrawlRunner();
$crawlResult = $runner->run($baseUrl, $username, $password, $logFile);

// Check for login error
if (isset($crawlResult['error'])) {
    fwrite(STDERR, "Error: {$crawlResult['error']}\n");
    exit(1);
}

// Display results
echo "Crawling " . $crawlResult['total_count'] . " URLs (recursive link-based crawl)...\n\n";

foreach ($crawlResult['items'] as $item) {
    $url = $item['url'];
    $status = $item['status'];
    $hasError = $item['has_error'];
    $hasMarker = $item['has_marker'] ? 'YES' : 'NO';
    $depth = $item['depth'] ?? 0;
    $note = $item['note'] ?? '';
    
    $depthStr = $depth > 0 ? " [depth={$depth}]" : " [seed]";
    
    if ($hasError) {
        echo "GET {$url}{$depthStr}... ERROR (status={$status})";
        if ($note) {
            echo " - {$note}";
        }
        echo "\n";
    } else {
        echo "GET {$url}{$depthStr}... OK (status={$status}, marker={$hasMarker})\n";
    }
}

// Summary
echo "\n=== CRAWL SUMMARY ===\n";
echo "Total URLs: {$crawlResult['total_count']}\n";
echo "Success: {$crawlResult['success_count']}\n";
echo "Errors: {$crawlResult['error_count']}\n";
echo "Log File: {$logFile}\n\n";

// Error details
if ($crawlResult['error_count'] > 0) {
    echo "=== ERROR DETAILS ===\n";
    foreach ($crawlResult['items'] as $item) {
        if ($item['has_error']) {
            echo "  - {$item['url']}: status={$item['status']}";
            if (!empty($item['note'])) {
                echo " - {$item['note']}";
            }
            echo "\n";
        }
    }
    echo "\n";
}

// Exit code
exit($crawlResult['error_count'] > 0 ? 1 : 0);

