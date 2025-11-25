<?php
/**
 * Production Validation Script
 * 
 * Bu script production ortamında çalıştırılarak sistemin doğru yapılandırıldığını kontrol eder.
 * 
 * Kullanım: php scripts/validate_production.php
 */

// CLI'den çalıştırılıyor mu kontrol et
if (PHP_SAPI !== 'cli') {
    die("Bu script sadece command line'dan çalıştırılabilir.\n");
}

// Bootstrap
require_once __DIR__ . '/../config/config.php';

echo "=== Production Validation Script ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Environment Detection
echo "1. Environment Detection...\n";
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
$isProduction = (stripos($httpHost, 'kuretemizlik.com') !== false) || 
                (stripos($serverName, 'kuretemizlik.com') !== false) ||
                ($appEnv === 'production');

if ($isProduction) {
    $success[] = "Production environment detected correctly";
} else {
    $warnings[] = "Production environment not detected (HTTP_HOST: $httpHost, SERVER_NAME: $serverName)";
}

// 2. APP_DEBUG Check
echo "2. APP_DEBUG Check...\n";
if (defined('APP_DEBUG')) {
    if (APP_DEBUG === false) {
        $success[] = "APP_DEBUG is false (correct for production)";
    } else {
        $errors[] = "APP_DEBUG is true (should be false in production)";
    }
} else {
    $errors[] = "APP_DEBUG is not defined";
}

// 3. Path Resolution
echo "3. Path Resolution...\n";
if (defined('APP_ROOT')) {
    $success[] = "APP_ROOT is defined: " . APP_ROOT;
    
    // Check if paths are absolute
    if (defined('DB_PATH')) {
        if (strpos(DB_PATH, '/') === 0 || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:\\\\/', DB_PATH))) {
            $success[] = "DB_PATH is absolute: " . DB_PATH;
        } else {
            $warnings[] = "DB_PATH might not be absolute: " . DB_PATH;
        }
    }
} else {
    $errors[] = "APP_ROOT is not defined";
}

// 4. Database Check
echo "4. Database Check...\n";
if (defined('DB_PATH')) {
    $dbPath = DB_PATH;
    $dbDir = dirname($dbPath);
    
    // Check directory
    if (is_dir($dbDir)) {
        $success[] = "Database directory exists: $dbDir";
        
        if (is_writable($dbDir)) {
            $success[] = "Database directory is writable";
        } else {
            $errors[] = "Database directory is not writable: $dbDir (needs 775 or 777)";
        }
    } else {
        $warnings[] = "Database directory does not exist: $dbDir (will be created automatically)";
    }
    
    // Check database file
    if (file_exists($dbPath)) {
        $success[] = "Database file exists: $dbPath";
        
        if (is_writable($dbPath)) {
            $success[] = "Database file is writable";
        } else {
            $errors[] = "Database file is not writable: $dbPath (needs 664 or 666)";
        }
        
        // Check file permissions
        $perms = substr(sprintf('%o', fileperms($dbPath)), -4);
        if ($perms === '0664' || $perms === '0666') {
            $success[] = "Database file permissions are correct: $perms";
        } else {
            $warnings[] = "Database file permissions might be incorrect: $perms (recommended: 664 or 666)";
        }
    } else {
        $warnings[] = "Database file does not exist: $dbPath (will be created automatically)";
    }
    
    // Try to connect
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SELECT 1");
        $success[] = "Database connection successful";
    } catch (Exception $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
    }
} else {
    $errors[] = "DB_PATH is not defined";
}

// 5. Log Directory Check
echo "5. Log Directory Check...\n";
$logDir = APP_ROOT . '/logs';
if (is_dir($logDir)) {
    $success[] = "Log directory exists: $logDir";
    
    if (is_writable($logDir)) {
        $success[] = "Log directory is writable";
    } else {
        $errors[] = "Log directory is not writable: $logDir (needs 775 or 777)";
    }
} else {
    $warnings[] = "Log directory does not exist: $logDir (will be created automatically)";
}

// 6. Upload Directory Check
echo "6. Upload Directory Check...\n";
$uploadDirs = [
    APP_ROOT . '/uploads',
    APP_ROOT . '/storage',
];
foreach ($uploadDirs as $uploadDir) {
    if (is_dir($uploadDir)) {
        $success[] = "Upload directory exists: $uploadDir";
        
        if (is_writable($uploadDir)) {
            $success[] = "Upload directory is writable: $uploadDir";
        } else {
            $warnings[] = "Upload directory is not writable: $uploadDir (needs 775 or 777)";
        }
    }
}

// 7. Session Configuration
echo "7. Session Configuration...\n";
if (defined('SESSION_NAME')) {
    $success[] = "SESSION_NAME is defined: " . SESSION_NAME;
} else {
    $errors[] = "SESSION_NAME is not defined";
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$cookieParams = session_get_cookie_params();
if ($cookieParams['path'] === '/') {
    $success[] = "Session cookie path is correct: /";
} else {
    $errors[] = "Session cookie path is incorrect: " . $cookieParams['path'] . " (should be /)";
}

// 8. Security Headers Check
echo "8. Security Headers Check...\n";
if (defined('COOKIE_SECURE')) {
    $success[] = "COOKIE_SECURE is defined: " . (COOKIE_SECURE ? 'true' : 'false');
} else {
    $warnings[] = "COOKIE_SECURE is not defined";
}

// 9. PHP Version Check
echo "9. PHP Version Check...\n";
$phpVersion = PHP_VERSION;
$success[] = "PHP version: $phpVersion";
if (version_compare($phpVersion, '8.1.0', '>=')) {
    $success[] = "PHP 8.1+ detected (deprecated warnings should be handled)";
} else {
    $warnings[] = "PHP version is below 8.1 (some features may not work correctly)";
}

// 10. Error Reporting Check
echo "10. Error Reporting Check...\n";
if (ini_get('display_errors') == '0' || ini_get('display_errors') == '') {
    $success[] = "display_errors is off (correct for production)";
} else {
    $errors[] = "display_errors is on (should be off in production)";
}

if (ini_get('log_errors') == '1') {
    $success[] = "log_errors is on (correct for production)";
} else {
    $warnings[] = "log_errors is off (should be on in production)";
}

// Summary
echo "\n=== Validation Summary ===\n\n";

if (count($success) > 0) {
    echo "✓ Success (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "✗ Errors (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✓ All checks passed!\n";
    exit(0);
}


