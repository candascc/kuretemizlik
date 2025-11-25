<?php
/**
 * Manual Crawl Starter
 *
 * Allows triggering InternalCrawlService without hitting the web UI.
 * Usage:
 *   php scripts/manual_crawl_start.php [ROLE]
 *
 * Example:
 *   php scripts/manual_crawl_start.php ADMIN
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';
require_once __DIR__ . '/../src/Helpers/RouterHelper.php';
require_once __DIR__ . '/../src/Services/InternalCrawlService.php';
require_once __DIR__ . '/../src/Services/CrawlStatusManager.php';
require_once __DIR__ . '/../src/Services/CrawlProgressTracker.php';

if (!isset($GLOBALS['router'])) {
    define('KUREAPP_SKIP_ROUTER_RUN', true);
    require_once __DIR__ . '/../index.php';
}

$roleMap = [
    'SUPERADMIN'   => 'test_superadmin',
    'ADMIN'        => 'test_admin',
    'OPERATOR'     => 'test_operator',
    'SITE_MANAGER' => 'test_site_manager',
    'FINANCE'      => 'test_finance',
    'SUPPORT'      => 'test_support',
];

$requestedRole = strtoupper($argv[1] ?? 'ADMIN');
if (!isset($roleMap[$requestedRole])) {
    fwrite(STDERR, "Invalid role '{$requestedRole}'. Allowed: " . implode(', ', array_keys($roleMap)) . PHP_EOL);
    exit(1);
}

$password = getenv('CRAWL_TEST_PASSWORD');
if (empty($password)) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $password = '12dream21';
    } else {
        fwrite(STDERR, "CRAWL_TEST_PASSWORD is not set. Aborting for safety." . PHP_EOL);
        exit(1);
    }
}

$username = $roleMap[$requestedRole];
$db = Database::getInstance();
$user = $db->fetch('SELECT id FROM users WHERE username = ?', [$username]);
if (!$user) {
    fwrite(STDERR, "Test user '{$username}' not found in database." . PHP_EOL);
    exit(1);
}

$statusManager = new CrawlStatusManager();
if ($statusManager->isLocked()) {
    $lock = $statusManager->getCurrentLock();
    fwrite(STDERR, "Another crawl is running (testId={$lock['testId']}). Aborting." . PHP_EOL);
    exit(1);
}

$testId = $statusManager->generateTestId((int)($user['id'] ?? 0));
if (!$statusManager->createLock($testId, $requestedRole, (int)$user['id'])) {
    fwrite(STDERR, "Failed to create crawl lock." . PHP_EOL);
    exit(1);
}

$progressDir = sys_get_temp_dir() . '/crawl_progress';
if (!is_dir($progressDir)) {
    @mkdir($progressDir, 0755, true);
}
$progressFile = $progressDir . '/' . $testId . '.json';
$initialProgress = [
    'status' => 'running',
    'current' => 0,
    'total' => 0,
    'percentage' => 0,
    'current_url' => 'Manual crawl initializing...',
    'success_count' => 0,
    'error_count' => 0,
    'timestamp' => time(),
];
@file_put_contents($progressFile, json_encode($initialProgress, JSON_UNESCAPED_UNICODE), LOCK_EX);

define('CRAWL_BACKGROUND_PROCESS', true);

echo "Manual crawl starting...\n";
echo "  Role: {$requestedRole}\n";
echo "  Username: {$username}\n";
echo "  Test ID: {$testId}\n\n";

$router = RouterHelper::getOrCreateRouter();
$service = new InternalCrawlService($router, null, $testId);

try {
    $result = $service->run($username, $password);
    $finalData = [
        'status' => 'completed',
        'result' => $result,
        'completedAt' => time(),
    ];
    $statusManager->updateStatus($testId, 'completed');
    @file_put_contents($progressFile, json_encode($finalData, JSON_UNESCAPED_UNICODE), LOCK_EX);

    echo "Crawl completed.\n";
    echo "  Total URLs: {$result['total_count']}\n";
    echo "  Success: {$result['success_count']}\n";
    echo "  Errors: {$result['error_count']}\n";
    echo "Progress file: {$progressFile}\n";
    echo "View via: /app/sysadmin/crawl/progress?testId={$testId}\n";

    exit($result['error_count'] > 0 ? 2 : 0);
} catch (Throwable $e) {
    $statusManager->updateStatus($testId, 'failed');
    $errorData = [
        'status' => 'failed',
        'error' => $e->getMessage(),
        'failedAt' => time(),
    ];
    @file_put_contents($progressFile, json_encode($errorData, JSON_UNESCAPED_UNICODE), LOCK_EX);
    fwrite(STDERR, "Crawl failed: {$e->getMessage()}\n");
    exit(2);
}

