<?php
/**
 * Test Bootstrap
 * Minimal bootstrap for running tests without full application initialization
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Load config first (defines APP_ROOT and other constants)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// Define constants if not already defined by config
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('DB_PATH')) {
    define('DB_PATH', APP_ROOT . '/storage/database.sqlite');
}

// Load Composer autoloader if available
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Load PHPUnit if available (for test execution)
if (file_exists(APP_ROOT . '/vendor/phpunit/phpunit/src/Framework/TestCase.php')) {
    require_once APP_ROOT . '/vendor/phpunit/phpunit/src/Framework/TestCase.php';
}

// Load essential classes in order
$essentialClasses = [
    // Core
    APP_ROOT . '/src/Lib/SessionHelper.php',
    APP_ROOT . '/src/Lib/Database.php',
    APP_ROOT . '/src/Lib/Validator.php',
    APP_ROOT . '/src/Lib/CSRF.php',
    APP_ROOT . '/src/Lib/CsrfMiddleware.php',
    APP_ROOT . '/src/Lib/ApiRateLimiter.php',
    APP_ROOT . '/src/Lib/ApiRateLimitMiddleware.php',
    APP_ROOT . '/src/Lib/RateLimitHelper.php',
    APP_ROOT . '/src/Lib/FileUploadValidator.php',
    APP_ROOT . '/src/Lib/ExceptionHandler.php',
    APP_ROOT . '/src/Lib/InputSanitizer.php',
    APP_ROOT . '/src/Lib/View.php',
    APP_ROOT . '/src/Lib/Utils.php',
    APP_ROOT . '/src/Lib/Auth.php',
    // Phase 4: Code Quality Improvements
    APP_ROOT . '/src/Lib/ControllerHelper.php', // Phase 4.1: Required for ControllerTrait
    APP_ROOT . '/src/Lib/ControllerTrait.php', // Phase 4.1: ControllerTrait
    APP_ROOT . '/src/Constants/AppConstants.php', // Phase 4.2: AppConstants
    // Services
    APP_ROOT . '/src/Services/ResidentOtpService.php',
    APP_ROOT . '/src/Services/FileUploadService.php',
    // Models
    APP_ROOT . '/src/Models/ResidentUser.php',
];

foreach ($essentialClasses as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Load helper functions
$helperFiles = [
    APP_ROOT . '/src/Views/helpers/escape.php',
];

foreach ($helperFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Load test helpers
if (file_exists(__DIR__ . '/TestHelper.php')) {
    require_once __DIR__ . '/TestHelper.php';
}

// Load test factories
if (file_exists(__DIR__ . '/Support/TestFactory.php')) {
    require_once __DIR__ . '/Support/TestFactory.php';
}
if (file_exists(__DIR__ . '/Support/FactoryRegistry.php')) {
    require_once __DIR__ . '/Support/FactoryRegistry.php';
}

// Simple autoloader for remaining classes
$autoloadPaths = [
    APP_ROOT . '/src/Lib',
    APP_ROOT . '/src/Models',
    APP_ROOT . '/src/Services',
    APP_ROOT . '/src/Controllers',
];

spl_autoload_register(function ($class) use ($autoloadPaths) {
    foreach ($autoloadPaths as $path) {
        $file = $path . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

