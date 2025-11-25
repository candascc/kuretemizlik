<?php
/**
 * Phase 1 Simple Test Runner
 * Runs Phase 1 tests without PHPUnit dependency
 */

// Start output buffering to prevent headers_sent issues
if (ob_get_level() === 0) {
    ob_start();
}

// Define APP_BASE if not defined
if (!defined('APP_BASE')) {
    define('APP_BASE', '/app');
}

// Set session save path for CLI
if (PHP_SAPI === 'cli') {
    $tempPath = sys_get_temp_dir() . '/php_sessions_' . getmypid();
    if (!is_dir($tempPath)) {
        @mkdir($tempPath, 0700, true);
    }
    ini_set('session.save_path', $tempPath);
}

// Load required files
require_once __DIR__ . '/../src/Lib/SessionHelper.php';
require_once __DIR__ . '/../src/Lib/ExceptionHandler.php';

echo "=== Phase 1 Test Suite (Simple Runner) ===\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: SessionHelper::ensureStarted()
echo "Test 1: SessionHelper::ensureStarted()\n";
try {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $result = SessionHelper::ensureStarted();
    if ($result && session_status() === PHP_SESSION_ACTIVE) {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Session not started\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: SessionHelper::isActive()
echo "\nTest 2: SessionHelper::isActive()\n";
try {
    $isActive = SessionHelper::isActive();
    if ($isActive) {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Session not active\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: SessionHelper::getStatus()
echo "\nTest 3: SessionHelper::getStatus()\n";
try {
    $status = SessionHelper::getStatus();
    if ($status === PHP_SESSION_ACTIVE) {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Status is {$status}, expected " . PHP_SESSION_ACTIVE . "\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 4: SessionHelper idempotent
echo "\nTest 4: SessionHelper idempotent\n";
try {
    $sessionId1 = session_id();
    SessionHelper::ensureStarted();
    $sessionId2 = session_id();
    if ($sessionId1 === $sessionId2) {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Session ID changed\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: Session cookie parameters
echo "\nTest 5: Session cookie parameters\n";
try {
    $cookieParams = session_get_cookie_params();
    if ($cookieParams['httponly'] && $cookieParams['samesite'] === 'Lax') {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Cookie params incorrect\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 6: Array access safety
echo "\nTest 6: Array access safety\n";
try {
    $array = ['key1' => 'value1'];
    $value1 = $array['key1'] ?? 'default';
    $value2 = $array['key2'] ?? 'default';
    if ($value1 === 'value1' && $value2 === 'default') {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Array access not safe\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 7: ExceptionHandler::formatException()
echo "\nTest 7: ExceptionHandler::formatException()\n";
try {
    $exception = new Exception('Test exception', 100);
    $formatted = ExceptionHandler::formatException($exception);
    if (strpos($formatted, 'Exception') !== false && strpos($formatted, 'Test exception') !== false) {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Format incorrect\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 8: Extract with EXTR_SKIP
echo "\nTest 8: Extract with EXTR_SKIP\n";
try {
    $existingVar = 'original';
    $data = ['existingVar' => 'attempted_override', 'newVar' => 'new_value'];
    extract($data, EXTR_SKIP | EXTR_REFS);
    if ($existingVar === 'original' && isset($newVar) && $newVar === 'new_value') {
        echo "  ✅ PASS\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Extract not safe\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Results ===\n";
echo "Total: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed!\n";
    exit(1);
}

