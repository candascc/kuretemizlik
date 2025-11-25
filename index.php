<?php
// ===== PATH_ISAPP_KILLVAR: Global $isAppRequest variable removed, use kureapp_is_app_request() function instead =====
// The helper function is defined after config.php is loaded (see line ~51)
// ===== PATH_ISAPP_KILLVAR END =====

if (isset($_GET['__ver']) && $_GET['__ver'] === 'r39') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "INDEX_VERSION=R39\n";
    echo "__FILE__=" . __FILE__ . "\n";
    exit;
}
// ... geri kalan index.php

/**
 * Temizlik İş Takip Uygulaması - Ana Router
 */

// ===== KOZMOS_PATCH: Start output buffering early (begin) =====
// Headers already sent hatalarını önlemek için output buffering'i en başta başlat
if (!ob_get_level()) {
    ob_start();
}
// ===== KOZMOS_PATCH: Start output buffering early (end) =====

// ===== KOZMOS_PATCH: Force session cookie path to APP_BASE before config loads (begin) =====
// PHP'nin default session cookie path'i script'in bulunduğu dizin olabilir
// ===== PRODUCTION FIX: Set session cookie path BEFORE any session operations =====
// Sistem /app klasöründe çalışıyorsa path /app olmalı
// Config yüklenmeden önce APP_BASE'i bilmediğimiz için /app olarak varsayıyoruz
// Config yüklendikten sonra SessionHelper doğru path'i ayarlayacak
// Note: SessionHelper will override these settings with APP_BASE after config loads
$preConfigPath = '/app';
$preConfigHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// ===== PRODUCTION FIX: DO NOT start session here =====
// Session'ı config.php yüklendikten sonra SessionHelper ile başlatacağız
// Sadece ini_set ile ön ayarları yapıyoruz (SessionHelper bunları override edecek)
ini_set('session.cookie_path', $preConfigPath);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $preConfigHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
// ===== PRODUCTION FIX END =====
// ===== KOZMOS_PATCH: Force session cookie path to APP_BASE before config loads (end) =====

require_once __DIR__ . '/config/config.php';

// ===== PATH_ISAPP_KILLVAR: Pure helper function instead of global variable =====
if (!function_exists('kureapp_is_app_request')) {
    /**
     * Determine if the current request is an HTML /app request
     * 
     * This replaces the global $isAppRequest variable to prevent undefined variable errors.
     * Returns true only for GET requests to /app HTML pages (not CLI, not API JSON, not health/metrics).
     * 
     * @return bool
     */
    function kureapp_is_app_request(): bool
    {
        // CLI requests are never app requests
        if (PHP_SAPI === 'cli') {
            return false;
        }
        
        $serverRequestUri = $_SERVER['REQUEST_URI'] ?? '';
        $serverRequestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only GET requests to /app HTML pages
        if ($serverRequestMethod !== 'GET') {
            return false;
        }
        
        // Check if URI starts with /app or is exactly /app or /
        if (strpos($serverRequestUri, '/app') === 0 || $serverRequestUri === '/app' || $serverRequestUri === '/') {
            return true;
        }
        
        return false;
    }
}
// ===== PATH_ISAPP_KILLVAR END =====

// ===== PATH_ISAPP_KILLVAR_COMPAT: Provide legacy $isAppRequest variable for legacy includes =====
if (!isset($GLOBALS['isAppRequest'])) {
    $GLOBALS['isAppRequest'] = kureapp_is_app_request();
}

// ROUND 33: Build tag for production fingerprinting
// This tag identifies which code version is running in production
define('KUREAPP_BUILD_TAG', 'KUREAPP_R33_2025-11-22');

// Set security headers early
require_once __DIR__ . '/src/Lib/SecurityHeaders.php';
SecurityHeaders::set();

// Load Rate Limit Helper (STAGE 4.2)
require_once __DIR__ . '/src/Lib/RateLimitHelper.php';

// ROUND 3: Load Security Services
require_once __DIR__ . '/src/Services/SecurityAlertService.php';
require_once __DIR__ . '/src/Lib/IpAccessControl.php';
require_once __DIR__ . '/src/Services/MfaService.php';

// Load Router
require_once __DIR__ . '/src/Lib/Router.php';

// Load Middleware
require_once __DIR__ . '/src/Lib/AuthMiddleware.php';
require_once __DIR__ . '/src/Middleware/ValidationMiddleware.php';
require_once __DIR__ . '/src/Middleware/SecurityMiddleware.php';
require_once __DIR__ . '/src/Lib/InputSanitizer.php';
require_once __DIR__ . '/src/Lib/Validator.php';

// Load View
require_once __DIR__ . '/src/Lib/View.php';

// Load Session Helper (must be loaded before Auth)
require_once __DIR__ . '/src/Lib/SessionHelper.php';

// Load Auth
require_once __DIR__ . '/src/Lib/Auth.php';

// Load Helpers
require_once __DIR__ . '/src/Helpers/SuperAdmin.php';
require_once __DIR__ . '/src/Lib/HeaderManager.php';

// Load View Permission Helpers (available globally in views)
require_once __DIR__ . '/src/Views/helpers/permission.php';
// Load View Escape Helpers (available globally in views)
require_once __DIR__ . '/src/Views/helpers/escape.php';

// Load Database (only if not already loaded)
if (!class_exists('Database')) {
    require_once __DIR__ . '/src/Lib/Database.php';
}

// Load EagerLoader (Phase 3.1: N+1 Query Optimization)
require_once __DIR__ . '/src/Lib/EagerLoader.php';

// Load MemoryCleanupHelper (Phase 3.3: Memory Leak Prevention)
require_once __DIR__ . '/src/Lib/MemoryCleanupHelper.php';

// Load Cache
require_once __DIR__ . '/src/Lib/Cache.php';

// Mock helper
require_once __DIR__ . '/src/Lib/Mock/MockHelper.php';
MockHelper::bootstrap();

// Helper functions
if (!function_exists('view')) {
    function view(string $template, array $data = []): void {
        extract($data);
        $templatePath = __DIR__ . '/src/Views/' . $template . '.php';
        if (file_exists($templatePath)) {
            require $templatePath;
        } else {
            throw new Exception("View template not found: {$template}");
        }
    }
}

if (!function_exists('get_flash')) {
    function get_flash(string $key): ?string {
        // ROUND 51: Session is already started in index.php bootstrap
        // No need to start session here
        
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool {
        // ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
        if (session_status() === PHP_SESSION_NONE) {
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            session_set_cookie_params([
                'lifetime'  => 0,
                'path'      => $cookiePath,
                'domain'    => null,
                'secure'    => $isHttps ? 1 : 0,
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            ini_set('session.cookie_path', $cookiePath);
            
            try {
                session_start();
            } catch (Exception $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Session start failed in has_flash(): " . $e->getMessage());
                }
                return false;
            }
        }
        // ===== CRITICAL FIX END =====
        
        return isset($_SESSION['flash'][$key]);
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $key, string $message): void {
        // ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
        if (session_status() === PHP_SESSION_NONE) {
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            session_set_cookie_params([
                'lifetime'  => 0,
                'path'      => $cookiePath,
                'domain'    => null,
                'secure'    => $isHttps ? 1 : 0,
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            ini_set('session.cookie_path', $cookiePath);
            
            try {
                session_start();
            } catch (Exception $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Session start failed in set_flash(): " . $e->getMessage());
                }
                // If session can't be started, can't store flash message
                return;
            }
        }
        // ===== CRITICAL FIX END =====
        
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void {
        // CRITICAL: Skip redirect for internal requests
        // Internal requests should not redirect, as they're executed within output buffer
        if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST) {
            // For internal requests, just return without redirecting
            return;
        }
        
        // ===== PRODUCTION FIX: Prevent redirect caching =====
        // Clear any output buffers (but only if headers not sent)
        if (!headers_sent() && ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Prevent caching of redirect response
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('ETag: "' . md5(time() . rand()) . '"');
        }
        
        // Ensure session is written before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // ===== PRODUCTION FIX: Add cache-busting parameter to dynamic pages =====
        // Add timestamp parameter to prevent browser cache for all dynamic pages
        // This includes: list/index pages, show/detail pages, form pages, dashboard, etc.
        $dynamicPagePatterns = [
            // List/Index pages
            '/customers', '/jobs', '/finance', '/staff', '/services', '/contracts', 
            '/appointments', '/recurring', '/roles', '/settings', '/reports',
            '/buildings', '/units', '/expenses', '/meetings', '/announcements',
            '/resident/requests', '/resident/profile', '/resident/dashboard',
            // Detail/Show pages
            '/customers/show', '/jobs/show', '/staff/show', '/contracts/show',
            // Form pages
            '/customers/edit', '/customers/new', '/jobs/edit', '/jobs/new',
            '/staff/edit', '/staff/new', '/services/edit', '/services/new',
            '/contracts/edit', '/contracts/new',
            // Dashboard and other dynamic pages
            '/dashboard', '/management/dashboard', '/resident/dashboard',
            // Settings and profile
            '/settings', '/profile', '/resident/profile',
        ];
        
        $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
        $urlQuery = parse_url($url, PHP_URL_QUERY) ?? '';
        
        // Check if URL matches any dynamic page pattern and doesn't already have cache-busting param
        $isDynamicPage = false;
        foreach ($dynamicPagePatterns as $pattern) {
            if (strpos($urlPath, $pattern) !== false) {
                $isDynamicPage = true;
                break;
            }
        }
        
        // Also check for common dynamic page patterns (edit, show, new, create, etc.)
        if (!$isDynamicPage) {
            $dynamicPatterns = ['/edit/', '/show/', '/new', '/create', '/dashboard', '/profile', '/settings'];
            foreach ($dynamicPatterns as $pattern) {
                if (strpos($urlPath, $pattern) !== false) {
                    $isDynamicPage = true;
                    break;
                }
            }
        }
        
        // Add cache-busting parameter if it's a dynamic page and doesn't already have it
        if ($isDynamicPage && strpos($urlQuery, '_=') === false) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . '_=' . time();
        }
        // ===== PRODUCTION FIX END =====
        
        // CRITICAL: Skip header manipulation for internal requests
        // Internal requests should not send headers or exit, as they're executed within output buffer
        // BUT: Only skip if we're actually in an internal request context (headers not sent yet)
        if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST && !headers_sent()) {
            // For internal requests, just return without sending headers or exiting
            // This prevents "headers already sent" errors during crawl
            return;
        }
        
        // Set response code and redirect
        // Only send headers if they haven't been sent yet
        if (!headers_sent()) {
            http_response_code($status);
            header("Location: $url", true, $status);
        }
        exit;
    }
}

// ===== PRODUCTION FIX: Set session cookie params BEFORE starting session =====
// Config yüklendikten sonra APP_BASE'i kullanarak cookie path'ini ayarla
$cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
$is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// CRITICAL: Session başlatılmadan önce cookie params'ı MUTLAKA ayarla
// Eğer session zaten başlatılmışsa, cookie params değiştirilemez!
if (session_status() === PHP_SESSION_NONE) {
    // Session name'i ayarla
    $sessionName = defined('SESSION_NAME') ? SESSION_NAME : 'temizlik_sess';
    session_name($sessionName);
    
    // Cookie params'ı ayarla
    session_set_cookie_params([
        'lifetime'  => 0,
        'path'      => $cookiePath,
        'domain'    => null,
        'secure'    => $is_https ? 1 : 0,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    
    // ini_set ile de ayarla (çift güvence)
    ini_set('session.cookie_path', $cookiePath);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $is_https ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    
    // Session'ı başlat
    session_start();
    
    // Cookie'nin doğru ayarlandığını doğrula
    $actualParams = session_get_cookie_params();
    if ($actualParams['path'] !== $cookiePath) {
        error_log("[SESSION] WARNING: Cookie path mismatch! Expected: {$cookiePath}, Actual: {$actualParams['path']}");
    }
} else {
    // Session zaten aktifse, mevcut cookie params'ı kontrol et
    $actualParams = session_get_cookie_params();
    if ($actualParams['path'] !== $cookiePath) {
        error_log("[SESSION] WARNING: Session already started with wrong cookie path! Expected: {$cookiePath}, Actual: {$actualParams['path']}");
        error_log("[SESSION] Session cookie cannot be changed after session_start()!");
    }
}

// ===== PRODUCTION FIX: Verify and fix session cookie if needed =====
// Production'da session cookie'nin doğru path/domain'de set edildiğinden emin ol
if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
    $cookieName = session_name();
    $sessionId = session_id();
    $cookieParams = session_get_cookie_params();
    
    // Eğer cookie path yanlışsa VEYA cookie gönderilmiyorsa, düzelt
    $cookieExists = isset($_COOKIE[$cookieName]);
    $pathCorrect = ($cookieParams['path'] === $cookiePath);
    
    // Cookie yoksa veya path yanlışsa, yeniden set et
    if (!$cookieExists || !$pathCorrect) {
        // Yanlış path'li cookie'yi sil (farklı path kombinasyonları için)
        if ($cookieExists) {
            // Tüm olası yanlış path'lerde cookie'yi sil
            $wrongPaths = ['/', '/app'];
            foreach ($wrongPaths as $wrongPath) {
                if ($wrongPath !== $cookiePath) {
                    setcookie($cookieName, '', time() - 3600, $wrongPath, '', $is_https ? 1 : 0, true);
                }
            }
        }
        
        // Doğru path'de yeni cookie set et
        if ($sessionId) {
            setcookie($cookieName, $sessionId, [
                'expires' => 0,
                'path' => $cookiePath,
                'domain' => null,
                'secure' => $is_https ? 1 : 0,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Cookie'yi $_COOKIE array'ine de ekle (aynı request içinde erişilebilir olması için)
            $_COOKIE[$cookieName] = $sessionId;
        }
    }
}
// ===== PRODUCTION FIX END =====

// Load Logger System
require_once __DIR__ . '/src/Lib/LogLevel.php';
require_once __DIR__ . '/src/Lib/LogFormatter.php';
require_once __DIR__ . '/src/Lib/Logger.php';

// Configure Logger
if (file_exists(__DIR__ . '/config/logging.php')) {
    $loggingConfig = require __DIR__ . '/config/logging.php';
    Logger::configure($loggingConfig);
}

// Log application start
Logger::info('Application started', [
    'url' => $_SERVER['REQUEST_URI'] ?? '/',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Load Composer autoloader if available
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Load environment variables safely
$envPath = __DIR__ . '/env.local';
if (file_exists($envPath)) {
    try {
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, 'env.local');
            $dotenv->load();
        } else {
            // Fallback: Load environment variables manually
            $envContent = file_get_contents($envPath);
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail if environment loading fails
        error_log('Environment loading failed: ' . $e->getMessage());
    }
}

// Register error handler
require_once __DIR__ . '/src/Lib/ErrorHandler.php';
ErrorHandler::register();

// Register exception handler (after ErrorHandler for layered handling)
require_once __DIR__ . '/src/Lib/ExceptionHandler.php';
ExceptionHandler::register();

// OPS HARDENING ROUND 1: Load AppErrorHandler for structured error logging
require_once __DIR__ . '/src/Lib/AppErrorHandler.php';

// Load Translator (for __() helper)
require_once __DIR__ . '/src/Lib/Translator.php';

$router = new Router(APP_BASE);
// Make router globally accessible for InternalCrawlService
$GLOBALS['router'] = $router;

// ===== PATH_ISAPP_KILLVAR: $isAppRequest global variable removed, use kureapp_is_app_request() function instead =====

// ===== LOGIN_500_PATHC: Log bootstrap start for /app requests =====
if (kureapp_is_app_request() && class_exists('PathCLogger')) {
    require_once __DIR__ . '/src/Lib/PathCLogger.php';
    PathCLogger::log('PATHC_BOOTSTRAP_START', []);
}
// ===== LOGIN_500_PATHC END =====

// ROUND 38: Global Request Probe (debug instrumentation)
// Log when index.php is executed (only if __r38=1 query param is present)
if (isset($_GET['__r38']) && $_GET['__r38'] === '1') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $line = date('c') . ' URI=' . ($_SERVER['REQUEST_URI'] ?? 'unknown')
          . ' FILE=' . __FILE__
          . ' APP_BASE=' . (defined('APP_BASE') ? APP_BASE : 'UNDEF')
          . ' DOCUMENT_ROOT=' . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown')
          . PHP_EOL;
    @file_put_contents($logDir . '/r38_route_probe.log', $line, FILE_APPEND);
}

// Load Controllers
require_once __DIR__ . '/src/Controllers/DashboardController.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/SimpleAuthController.php';
require_once __DIR__ . '/src/Controllers/TwoFactorController.php';
require_once __DIR__ . '/src/Controllers/RoleController.php';
require_once __DIR__ . '/src/Controllers/QueueController.php';
require_once __DIR__ . '/src/Controllers/CacheController.php';
require_once __DIR__ . '/src/Controllers/AuditController.php';
require_once __DIR__ . '/src/Controllers/SettingsController.php';
require_once __DIR__ . '/src/Controllers/SecurityController.php';
require_once __DIR__ . '/src/Controllers/DocsController.php';
require_once __DIR__ . '/src/Controllers/PortalController.php';
require_once __DIR__ . '/src/Controllers/ApiController.php';

// Initialize database indexes after all classes are loaded
Database::getInstance()->initializeIndexes();

// ===== AUTO-MIGRATION: Run pending migrations automatically =====
// This ensures migrations run when files are copied to hosting
// Only runs in production or when explicitly enabled
if (class_exists('MigrationManager')) {
    try {
        // Check if auto-migration is disabled via environment variable
        $autoMigrate = $_ENV['AUTO_MIGRATE'] ?? ($_SERVER['AUTO_MIGRATE'] ?? true);
        
        // Only auto-migrate if enabled and not in CLI mode (unless explicitly requested)
        if ($autoMigrate && PHP_SAPI !== 'cli') {
            // Run migrations silently (errors are logged)
            $migrationResult = MigrationManager::migrate();
            
            // Log if migrations were executed
            if ($migrationResult['executed'] > 0 && class_exists('Logger')) {
                Logger::info("Auto-migration executed {$migrationResult['executed']} migration(s)");
            }
        }
    } catch (Exception $e) {
        // Silent fail - don't break the application if migration fails
        // Errors are logged via error handler
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Auto-migration error: ' . $e->getMessage());
        }
    }
}
// ===== AUTO-MIGRATION END =====

// Load new services and libraries (only if not already loaded)
if (!class_exists('BackupService') && file_exists(__DIR__ . '/src/Services/BackupService.php')) {
    require_once __DIR__ . '/src/Services/BackupService.php';
}
if (!class_exists('ErrorTracker') && file_exists(__DIR__ . '/src/Lib/ErrorTracker.php')) {
    require_once __DIR__ . '/src/Lib/ErrorTracker.php';
}
if (!class_exists('CacheManager') && file_exists(__DIR__ . '/src/Lib/CacheManager.php')) {
    require_once __DIR__ . '/src/Lib/CacheManager.php';
}
if (!class_exists('ResponseFormatter') && file_exists(__DIR__ . '/src/Lib/ResponseFormatter.php')) {
    require_once __DIR__ . '/src/Lib/ResponseFormatter.php';
}
if (!class_exists('AdvancedSearch') && file_exists(__DIR__ . '/src/Lib/AdvancedSearch.php')) {
    require_once __DIR__ . '/src/Lib/AdvancedSearch.php';
}
if (!class_exists('EmailService') && file_exists(__DIR__ . '/src/Services/EmailService.php')) {
    require_once __DIR__ . '/src/Services/EmailService.php';
}
if (!class_exists('ApiVersion') && file_exists(__DIR__ . '/src/Lib/ApiVersion.php')) {
    require_once __DIR__ . '/src/Lib/ApiVersion.php';
}
if (!class_exists('SystemHealth') && file_exists(__DIR__ . '/src/Lib/SystemHealth.php')) {
    require_once __DIR__ . '/src/Lib/SystemHealth.php';
}
if (!class_exists('MigrationManager') && file_exists(__DIR__ . '/src/Lib/MigrationManager.php')) {
    require_once __DIR__ . '/src/Lib/MigrationManager.php';
    // Run migrations after loading
    try {
        $result = MigrationManager::migrate();
        if ($result['success'] && $result['executed'] > 0 && defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Migrations executed: " . $result['executed']);
        }
    } catch (Exception $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Migration error: " . $e->getMessage());
        }
    }
}
if (!class_exists('ReportGenerator') && file_exists(__DIR__ . '/src/Services/ReportGenerator.php')) {
    require_once __DIR__ . '/src/Services/ReportGenerator.php';
}
if (!class_exists('AnalyticsController') && file_exists(__DIR__ . '/src/Controllers/AnalyticsController.php')) {
    require_once __DIR__ . '/src/Controllers/AnalyticsController.php';
}
if (!class_exists('EmailController') && file_exists(__DIR__ . '/src/Controllers/EmailController.php')) {
    require_once __DIR__ . '/src/Controllers/EmailController.php';
}
// Load EmailService
if (!class_exists('EmailService') && file_exists(__DIR__ . '/src/Services/EmailService.php')) {
    require_once __DIR__ . '/src/Services/EmailService.php';
}
// Logger is already loaded above, skip duplicate

// Load Notification Controller
require_once __DIR__ . '/src/Controllers/NotificationController.php';

// Load Sysadmin Tools Controller (PATH_CRAWL_SYSADMIN_WEB_V1)
require_once __DIR__ . '/src/Controllers/SysadminToolsController.php';

// Initialize CacheManager
if (class_exists('CacheManager')) {
    // CacheManager::loadTags(); // Method doesn't exist, skip
}

// Load Core Module Models (before controllers)
require_once __DIR__ . '/src/Models/Job.php';
require_once __DIR__ . '/src/Models/Customer.php';
require_once __DIR__ . '/src/Models/Service.php';
require_once __DIR__ . '/src/Models/MoneyEntry.php';
require_once __DIR__ . '/src/Models/Address.php';
require_once __DIR__ . '/src/Models/Appointment.php';
require_once __DIR__ . '/src/Models/Contract.php';
require_once __DIR__ . '/src/Models/JobContract.php';
require_once __DIR__ . '/src/Models/ContractTemplate.php';
require_once __DIR__ . '/src/Models/ContractOtpToken.php';
require_once __DIR__ . '/src/Models/JobPayment.php';

// Load Core Module Controllers
require_once __DIR__ . '/src/Controllers/JobController.php';
require_once __DIR__ . '/src/Controllers/JobWizardController.php'; // UX-CRIT-001: Job wizard
require_once __DIR__ . '/src/Controllers/CustomerController.php';
require_once __DIR__ . '/src/Controllers/FinanceController.php';
require_once __DIR__ . '/src/Controllers/CalendarController.php';
require_once __DIR__ . '/src/Controllers/CalendarFeedController.php';
require_once __DIR__ . '/src/Controllers/CalendarWebhookController.php';
require_once __DIR__ . '/src/Controllers/OAuthController.php';
require_once __DIR__ . '/src/Services/CalendarSyncService.php';
require_once __DIR__ . '/src/Services/ReminderService.php';
require_once __DIR__ . '/src/Models/CalendarSync.php';
require_once __DIR__ . '/src/Models/CalendarExternalEvent.php';
require_once __DIR__ . '/src/Lib/Calendar/Normalizer.php';
require_once __DIR__ . '/src/Controllers/AppointmentController.php';
require_once __DIR__ . '/src/Controllers/ContractController.php';
require_once __DIR__ . '/src/Controllers/StaffController.php';
require_once __DIR__ . '/src/Controllers/RecurringJobController.php';
require_once __DIR__ . '/src/Controllers/ReportController.php';
require_once __DIR__ . '/src/Controllers/TasksController.php';
require_once __DIR__ . '/src/Controllers/ExportController.php';
require_once __DIR__ . '/src/Controllers/ServiceController.php';

// Load Building Management Models
require_once __DIR__ . '/src/Models/Building.php';
require_once __DIR__ . '/src/Models/Unit.php';
require_once __DIR__ . '/src/Models/ManagementFee.php';
require_once __DIR__ . '/src/Models/ManagementFeeDefinition.php';
require_once __DIR__ . '/src/Models/BuildingExpense.php';
require_once __DIR__ . '/src/Models/BuildingDocument.php';
require_once __DIR__ . '/src/Models/BuildingMeeting.php';
require_once __DIR__ . '/src/Models/MeetingAttendee.php';
require_once __DIR__ . '/src/Models/MeetingTopic.php';
require_once __DIR__ . '/src/Models/MeetingVote.php';
require_once __DIR__ . '/src/Models/BuildingAnnouncement.php';
require_once __DIR__ . '/src/Models/BuildingSurvey.php';
require_once __DIR__ . '/src/Models/SurveyQuestion.php';
require_once __DIR__ . '/src/Models/SurveyResponse.php';
require_once __DIR__ . '/src/Models/OnlinePayment.php';
require_once __DIR__ . '/src/Models/BuildingFacility.php';
require_once __DIR__ . '/src/Models/BuildingReservation.php';

// Load Building Management Controllers
require_once __DIR__ . '/src/Controllers/BuildingController.php';
require_once __DIR__ . '/src/Controllers/UnitController.php';
require_once __DIR__ . '/src/Controllers/ManagementFeeController.php';
require_once __DIR__ . '/src/Controllers/BuildingExpenseController.php';
require_once __DIR__ . '/src/Controllers/BuildingDocumentController.php';
require_once __DIR__ . '/src/Controllers/BuildingMeetingController.php';
require_once __DIR__ . '/src/Controllers/BuildingAnnouncementController.php';
require_once __DIR__ . '/src/Controllers/BuildingSurveyController.php';
require_once __DIR__ . '/src/Controllers/BuildingFacilityController.php';
require_once __DIR__ . '/src/Controllers/BuildingReservationController.php';
require_once __DIR__ . '/src/Controllers/BuildingReportController.php';

// Load File Upload Controller
require_once __DIR__ . '/src/Controllers/FileUploadController.php';
require_once __DIR__ . '/src/Services/FileUploadService.php';

// Load Comments System
require_once __DIR__ . '/src/Controllers/CommentController.php';
require_once __DIR__ . '/src/Models/Comment.php';

// Load Mobile API
require_once __DIR__ . '/src/Controllers/MobileApiController.php';
require_once __DIR__ . '/src/Lib/JWTAuth.php';
require_once __DIR__ . '/src/Lib/ApiRateLimiter.php';

// Load Resident Portal
require_once __DIR__ . '/src/Models/ResidentUser.php';
require_once __DIR__ . '/src/Models/ResidentRequest.php';
require_once __DIR__ . '/src/Controllers/ResidentController.php';

// Load Building Documents
require_once __DIR__ . '/src/Models/BuildingDocument.php';
require_once __DIR__ . '/src/Controllers/BuildingDocumentController.php';

// Load Notification System
require_once __DIR__ . '/src/Services/NotificationService.php';
require_once __DIR__ . '/src/Services/EmailQueue.php';
require_once __DIR__ . '/src/Services/SMSQueue.php';

// Load ActivityLogger
require_once __DIR__ . '/src/Lib/ActivityLogger.php';

// Load Utils
require_once __DIR__ . '/src/Lib/Utils.php';

// Load Human Messages (UX Optimization)
require_once __DIR__ . '/src/Lib/HumanMessages.php';

// Load Resident API
require_once __DIR__ . '/src/Controllers/ResidentApiController.php';

// Load Payment Service
require_once __DIR__ . '/src/Services/PaymentService.php';

// Load Performance Optimization
require_once __DIR__ . '/src/Controllers/PerformanceController.php';
require_once __DIR__ . '/src/Controllers/ManagementDashboardController.php';
require_once __DIR__ . '/src/Controllers/ManagementResidentsController.php';
require_once __DIR__ . '/src/Lib/QueryOptimizer.php';

// Load Services
require_once __DIR__ . '/src/Services/RecurringGenerator.php';
require_once __DIR__ . '/src/Services/RecurringScheduler.php';

// Load System Libs
require_once __DIR__ . '/src/Lib/Role.php';
require_once __DIR__ . '/src/Lib/Permission.php';
require_once __DIR__ . '/src/Lib/QueueManager.php';
require_once __DIR__ . '/src/Lib/AuditLogger.php';
require_once __DIR__ . '/src/Lib/CacheManager.php';

// ROUND 39: BRÜTAL BASİT JSON-ONLY HEALTH ENDPOINT
// MUST be defined BEFORE auth middlewares
// Public endpoint - no auth, no session, no HTML render
$router->get('/health', function() {
    $isInternalRequest = defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST;
    // ROUND 38: Health Handler Probe (debug instrumentation - keep for now)
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    @file_put_contents(
        $logDir . '/r38_health_exec.log',
        date('c') . ' HEALTH_EXEC URI=' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL,
        FILE_APPEND
    );
    
    // ROUND 39: Clear ALL output buffers - start completely fresh
    if (!$isInternalRequest) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    
    // ROUND 39: Set JSON headers FIRST - before any processing
    if (!$isInternalRequest) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    // ROUND 39: Base health array - minimum dependencies (PHP runtime only)
    $health = [
        'status' => 'ok',
        'build' => defined('KUREAPP_BUILD_TAG') ? KUREAPP_BUILD_TAG : null,
        'time' => date(DATE_ATOM),
        'marker' => 'KUREAPP_R36_MARKER_HEALTH_JSON_V1',
    ];
    
    // ROUND 41: WEB HEALTH için daima QUICK mod
    // SystemHealth::check() BYPASS edildi (ileride admin/CLI için kullanılacak)
    // Public web health endpoint'i hafif, JSON-only, bozulması zor olmalı
    try {
        if (class_exists('SystemHealth')) {
            // WEB HEALTH için her zaman quick() kullan
            // $_GET['quick'] parametresi artık kullanılmıyor (her zaman quick mod)
            try {
                $systemHealth = SystemHealth::quick();
                
                // Merge SystemHealth status if available
                if (isset($systemHealth['status'])) {
                    $health['status'] = $systemHealth['status'];
                }
                
                // Add SystemHealth details if available (quick() returns db_response_time_ms)
                if (isset($systemHealth['db_response_time_ms'])) {
                    $health['details'] = [
                        'db_response_time_ms' => $systemHealth['db_response_time_ms']
                    ];
                }
                
                // Set HTTP status code based on health status
                if (!$isInternalRequest) {
                    if ($health['status'] === 'error') {
                        http_response_code(503);
                    } else {
                        http_response_code(200);
                    }
                }
            } catch (Throwable $e) {
                // SystemHealth::quick() fail oldu - mark as error but still return JSON
                $health['status'] = 'error';
                $health['details'] = ['internal' => true];
                http_response_code(503);
                // Don't include exception message in JSON for security
            }
        }
    } catch (Throwable $e) {
        // Any exception during SystemHealth class check - mark as error but still return JSON
        $health['status'] = 'error';
        $health['details'] = ['internal' => true];
        if (!$isInternalRequest) {
            http_response_code(503);
        }
        // Don't include exception message in JSON for security
    }
    
    // ROUND 39: Output JSON - guaranteed to be JSON, never HTML
    // BUT: Skip output for internal requests (crawl context)
    if (!$isInternalRequest) {
        echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // ROUND 39: Clear all buffers and exit immediately - prevent any global error handler from interfering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        exit;
    }
    // Internal request: just return, don't output anything
    return;
});

// ROUND 34: Initialize auth middlewares AFTER /health route (to prevent /health from being intercepted)
$requireAuth = AuthMiddleware::requireAuth();
$requireAdmin = AuthMiddleware::requireAdmin();
// ===== KOZMOS_OPERATOR_READONLY: add operator readonly middleware (begin)
$requireOperatorReadOnly = AuthMiddleware::requireOperatorReadOnly();
// ===== KOZMOS_OPERATOR_READONLY: add operator readonly middleware (end)

// Dashboard
$router->get('/', function () {
    // ROUND 51: Session is already started in index.php bootstrap
    // No need to start session here
    
    if (!Auth::check()) {
        (new SimpleAuthController())->showLoginForm();
        return;
    }

    // Seçili modüle göre yönlendir
    // ===== PRODUCTION FIX: Wrap HeaderManager calls in try-catch =====
    // ROUND 19: Enhanced error handling for HeaderManager
    try {
        HeaderManager::bootstrap();
        $currentMode = HeaderManager::getCurrentMode();
    } catch (Exception $e) {
        // ROUND 19: Log error with AppErrorHandler if available
        if (class_exists('AppErrorHandler')) {
            AppErrorHandler::logException($e, ['context' => 'Root route - HeaderManager']);
        } else {
            error_log("HeaderManager error in root route: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
        $currentMode = 'operations'; // Default fallback
    }
    // ===== PRODUCTION FIX END =====
    
    if ($currentMode === 'management') {
        redirect(base_url('/management/dashboard'));
        return;
    }
    
    // ===== LOGIN_500_PATHC: Log bootstrap end for /app requests =====
if (kureapp_is_app_request() && class_exists('PathCLogger')) {
    PathCLogger::log('PATHC_BOOTSTRAP_END', []);
}
// ===== LOGIN_500_PATHC END =====

// ROUND 19: Wrap DashboardController call in try/catch as final safety net
    try {
        // Default: operations dashboard
        (new DashboardController())->today();
    } catch (Throwable $e) {
        // ROUND 19: Catch any errors from DashboardController
        if (class_exists('AppErrorHandler')) {
            AppErrorHandler::logException($e, ['context' => 'Root route - DashboardController']);
        } else {
            error_log("DashboardController error in root route: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
        
        // ROUND 31: Show error page with 200 status (not 500) to prevent breaking user flow
        View::error('Dashboard yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.', 200, $e->getMessage());
    }
});

// ROUND 18: /dashboard route (redirect to root dashboard or show dashboard directly)
$router->get('/dashboard', function () {
    // ===== CRITICAL FIX: Ensure session is active before Auth::check() =====
    // Session must be started with correct cookie path before any Auth/HeaderManager calls
    if (session_status() === PHP_SESSION_NONE) {
        $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        session_set_cookie_params([
            'lifetime'  => 0,
            'path'      => $cookiePath,
            'domain'    => null,
            'secure'    => $isHttps ? 1 : 0,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);
        ini_set('session.cookie_path', $cookiePath);
        
        try {
            session_start();
        } catch (Exception $e) {
            error_log("Session start failed in dashboard route: " . $e->getMessage());
            // Continue anyway - session might already be started
        }
    }
    // ===== CRITICAL FIX END =====
    
    if (!Auth::check()) {
        redirect(base_url('/login'));
        return;
    }

    // Seçili modüle göre yönlendir
    // ===== PRODUCTION FIX: Wrap HeaderManager calls in try-catch =====
    try {
        HeaderManager::bootstrap();
        $currentMode = HeaderManager::getCurrentMode();
    } catch (Exception $e) {
        // Log error but continue with default mode
        error_log("HeaderManager error in dashboard route: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $currentMode = 'operations'; // Default fallback
    }
    // ===== PRODUCTION FIX END =====
    
    if ($currentMode === 'management') {
        redirect(base_url('/management/dashboard'));
        return;
    }
    
    // Default: operations dashboard
    (new DashboardController())->today();
}, ['middlewares' => []]);

$router->get('/dashboard/stats', [DashboardController::class, 'stats'], ['middlewares' => [$requireAdmin]]);
$router->get('/management/dashboard', [ManagementDashboardController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/management/residents', [ManagementResidentsController::class, 'index'], ['middlewares' => [$requireAuth]]);

// Analytics
$router->get('/analytics', [AnalyticsController::class, 'index'], ['middlewares' => [$requireAuth]]);

// Test endpoints (TEST ENVIRONMENT ONLY)
if (defined('APP_DEBUG') && APP_DEBUG && ($_ENV['APP_ENV'] ?? 'production') === 'test') {
    $router->get('/tests/seed', function() {
        require __DIR__ . '/tests/seed.php';
    });
    $router->post('/tests/seed', function() {
        require __DIR__ . '/tests/seed.php';
    });
    $router->get('/tests/cleanup', function() {
        require __DIR__ . '/tests/cleanup.php';
    });
    $router->post('/tests/cleanup', function() {
        require __DIR__ . '/tests/cleanup.php';
    });
}

// Favicon handler to avoid 404 noise
$router->get('/favicon.ico', function() {
    header('Content-Type: image/x-icon');
    http_response_code(204); // No Content
    exit;
});

// Authentication - FIXED: Use SimpleAuthController to prevent redirect loop
$router->get('/login', [SimpleAuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'processLogin']);
$router->post('/auth/process-login', [AuthController::class, 'processLogin']);
$router->get('/logout', [AuthController::class, 'logout'], ['middlewares' => [$requireAuth]]);
$router->get('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'processForgotPassword']);
$router->get('/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/reset-password', [AuthController::class, 'processResetPassword']);
// ROUND 4: MFA routes
$router->get('/mfa/verify', [AuthController::class, 'showMfaVerify']);
$router->post('/mfa/verify', [AuthController::class, 'processMfaVerify']);

// Two-Factor Authentication
$router->get('/two-factor/setup', [TwoFactorController::class, 'setup'], ['middlewares' => [$requireAuth]]);
$router->post('/two-factor/verify', [TwoFactorController::class, 'verify'], ['middlewares' => [$requireAuth]]);
$router->get('/two-factor/backup-codes', [TwoFactorController::class, 'backupCodes'], ['middlewares' => [$requireAuth]]);
$router->get('/two-factor/download-backup-codes', [TwoFactorController::class, 'downloadBackupCodes'], ['middlewares' => [$requireAuth]]);
$router->post('/two-factor/disable', [TwoFactorController::class, 'disable'], ['middlewares' => [$requireAuth]]);
$router->post('/two-factor/regenerate-backup-codes', [TwoFactorController::class, 'regenerateBackupCodes'], ['middlewares' => [$requireAuth]]);
$router->get('/two-factor/verify-login', [TwoFactorController::class, 'verifyLogin']);
$router->post('/two-factor/process-login', [TwoFactorController::class, 'processLogin']);

// API endpoints
$router->get('/api/jobs', [ApiController::class, 'jobs'], ['middlewares' => [$requireAuth]]);
$router->get('/api/customers', [ApiController::class, 'customers'], ['middlewares' => [$requireAuth]]);
$router->post('/api/customers', [ApiController::class, 'createCustomer'], ['middlewares' => [$requireAuth]]);
$router->post('/api/customers/{id}/addresses', [ApiController::class, 'addCustomerAddress'], ['middlewares' => [$requireAuth]]);
$router->get('/api/finance', [ApiController::class, 'finance'], ['middlewares' => [$requireAuth]]);
$router->get('/api/calendar', [ApiController::class, 'calendar'], ['middlewares' => [$requireAuth]]);
$router->get('/api/calendar/{id}', [ApiController::class, 'calendar'], ['middlewares' => [$requireAuth]]);
$router->get('/api/stats', [ApiController::class, 'stats'], ['middlewares' => [$requireAdmin]]);
$router->get('/api/activity', [ApiController::class, 'activity'], ['middlewares' => [$requireAdmin]]);
$router->get('/api/search-customers', [ApiController::class, 'searchCustomers'], ['middlewares' => [$requireAuth]]);
$router->get('/api/global-search', [ApiController::class, 'globalSearch'], ['middlewares' => [$requireAuth]]);
$router->get('/api/customer-addresses/{id}', [ApiController::class, 'customerAddresses'], ['middlewares' => [$requireAuth]]);
$router->get('/api/job-status/{id}', [ApiController::class, 'jobStatus'], ['middlewares' => [$requireAuth]]);
$router->post('/api/jobs/{id}/update-date', [ApiController::class, 'updateJobDate'], ['middlewares' => [$requireAuth]]);
$router->post('/api/recurring-preview', [ApiController::class, 'recurringPreview'], ['middlewares' => [$requireAuth]]);
$router->get('/api/recurring-preview', [ApiController::class, 'recurringPreview'], ['middlewares' => [$requireAuth]]);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index'], ['middlewares' => [$requireAuth]]);

// Notifications API (header dropdown)
$router->get('/api/notifications/list', [ApiController::class, 'notificationsList'], ['middlewares' => [$requireAuth]]);
// Backward-compat camelCase alias
$router->get('/api/notificationsList', [ApiController::class, 'notificationsList'], ['middlewares' => [$requireAuth]]);
$router->post('/api/notifications/mark-all-read', [ApiController::class, 'notificationsMarkAllRead'], ['middlewares' => [$requireAuth]]);
$router->get('/api/notifications/prefs', [ApiController::class, 'notificationsPrefs'], ['middlewares' => [$requireAuth]]);
$router->post('/api/notifications/mute', [ApiController::class, 'notificationsMute'], ['middlewares' => [$requireAuth]]);
$router->post('/api/notifications/mark-read', [ApiController::class, 'notificationsMarkRead'], ['middlewares' => [$requireAuth]]);

// Recurring Scheduler API (for background processing)
$router->post('/api/recurring/process', function() {
    try {
        $summary = RecurringScheduler::processAll();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => $summary
        ]);
    } catch (Exception $e) {
        // ===== ERR-021 FIX: Prevent information disclosure in API responses =====
        error_log("API /api/recurring/generate error: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        $errorMessage = APP_DEBUG ? $e->getMessage() : 'İşler oluşturulurken bir hata oluştu.';
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
        // ===== ERR-021 FIX: End =====
    }
}, ['middlewares' => []]); // No auth required for automated calls

// Quick check and generate missing jobs
$router->post('/api/recurring/check-now', function() {
    // Verify CSRF token
    if (!CSRF::verifyRequest()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'CSRF doğrulaması başarısız'
        ]);
        return;
    }
    
    // Apply rate limiting (max 10 requests per hour per user)
    require_once 'src/Lib/ApiRateLimiter.php';
    if (!ApiRateLimiter::check('api.recurring.check-now', 10, 600)) {
        ApiRateLimiter::sendLimitExceededResponse('api.recurring.check-now', 3600);
    }
    ApiRateLimiter::record('api.recurring.check-now', 10, 600);
    
    try {
        $summary = RecurringScheduler::checkAndGenerateNow();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'message' => "{$summary['generated']} adet iş başarıyla oluşturuldu"
        ]);
    } catch (Exception $e) {
        // ===== ERR-021 FIX: Prevent information disclosure in API responses =====
        error_log("API /api/recurring/check-now error: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        $errorMessage = APP_DEBUG 
            ? 'İşler oluşturulurken bir hata oluştu: ' . $e->getMessage()
            : 'İşler oluşturulurken bir hata oluştu.';
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
        // ===== ERR-021 FIX: End =====
    }
}, ['middlewares' => [$requireAuth]]);

// Jobs recurring conversion
$router->post('/jobs/convert-to-recurring/{id}', [JobController::class, 'convertToRecurring'], ['middlewares' => [$requireAuth]]);

// Unified Job Management
$router->get('/jobs/manage/{id}', [JobController::class, 'manage'], ['middlewares' => [$requireAuth]]);

// Calendar
// ROUND 20: Enhanced error handling for calendar route
$router->get('/calendar', function() {
    try {
        (new CalendarController())->index();
    } catch (Throwable $e) {
        // ROUND 20: Catch any errors from CalendarController
        if (class_exists('AppErrorHandler')) {
            AppErrorHandler::logException($e, ['context' => 'Calendar route - CalendarController']);
        } else {
            error_log("CalendarController error in calendar route: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
        
        // Show error page instead of 500
        View::error('Takvim sayfası yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.', 503, $e->getMessage());
    }
}, ['middlewares' => [$requireAuth]]);
$router->get('/calendar/feed.ics', [CalendarFeedController::class, 'feed'], ['middlewares' => [$requireAuth]]);
$router->get('/calendar/sync', [CalendarController::class, 'sync'], ['middlewares' => [$requireAuth]]);
// OAuth connect
$router->get('/oauth/google', [OAuthController::class, 'google'], ['middlewares' => [$requireAuth]]);
$router->get('/oauth/google/callback', [OAuthController::class, 'googleCallback']);
$router->get('/oauth/microsoft', [OAuthController::class, 'microsoft'], ['middlewares' => [$requireAuth]]);
$router->get('/oauth/microsoft/callback', [OAuthController::class, 'microsoftCallback']);
// Webhooks (provider specific)
$router->post('/api/calendar/webhook/google', [CalendarWebhookController::class, 'google']);
$router->post('/api/calendar/webhook/microsoft', [CalendarWebhookController::class, 'microsoft']);
$router->post('/calendar/create', [CalendarController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/calendar/update/{id}', [CalendarController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/calendar/delete/{id}', [CalendarController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/calendar/status/{id}', [CalendarController::class, 'updateStatus'], ['middlewares' => [$requireAuth]]);

// Jobs
$router->get('/jobs', [JobController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/jobs/new', [JobController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->get('/jobs/wizard', [JobWizardController::class, 'index'], ['middlewares' => [$requireAuth]]); // UX-CRIT-001: New wizard UI
$router->post('/jobs/create', [JobController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/jobs/show/{id}', [JobController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/jobs/edit/{id}', [JobController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/jobs/update/{id}', [JobController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/jobs/delete/{id}', [JobController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// API: Customer addresses (for wizard typeahead)
$router->get('/api/customers/{id}/addresses', [JobWizardController::class, 'getCustomerAddresses'], ['middlewares' => []]);
$router->post('/jobs/status/{id}', [JobController::class, 'updateStatus'], ['middlewares' => [$requireAuth]]);
$router->post('/jobs/{id}/contract/send-sms', [JobController::class, 'sendContractSms'], ['middlewares' => [$requireAuth]]);
// ===== KOZMOS_BULK_OPERATIONS: bulk operations routes (begin)
$router->post('/jobs/bulk-status-update', [JobController::class, 'bulkStatusUpdate'], ['middlewares' => [$requireAuth]]);
$router->post('/jobs/bulk-delete', [JobController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);
// ===== KOZMOS_BULK_OPERATIONS: bulk operations routes (end)

// Public Contract Routes (müşteri onay akışı)
require_once __DIR__ . '/src/Controllers/PublicContractController.php';
$router->get('/contract/{id}', [PublicContractController::class, 'show']);
$router->post('/contract/{id}/approve', [PublicContractController::class, 'approve']);

// Customers
$router->get('/customers', [CustomerController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/customers/new', [CustomerController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/create', [CustomerController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/customers/show/{id}', [CustomerController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/customers/edit/{id}', [CustomerController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/update/{id}', [CustomerController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/delete/{id}', [CustomerController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/add-address/{id}', [CustomerController::class, 'addAddress'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/address-update/{id}', [CustomerController::class, 'updateAddress'], ['middlewares' => [$requireAuth]]);
$router->post('/customers/address-delete/{id}', [CustomerController::class, 'deleteAddress'], ['middlewares' => [$requireAuth]]);

// Finance
$router->get('/finance', [FinanceController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/finance/new', [FinanceController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/finance/create', [FinanceController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/finance/show/{id}', [FinanceController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/finance/{id}', [FinanceController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/finance/edit/{id}', [FinanceController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/finance/update/{id}', [FinanceController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/finance/delete/{id}', [FinanceController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->get('/finance/from-job/{id}', [FinanceController::class, 'createFromJob'], ['middlewares' => [$requireAuth]]);

// Settings
$router->get('/settings', [SettingsController::class, 'profile'], ['middlewares' => [$requireAuth]]);
$router->get('/settings/calendar', [SettingsController::class, 'calendar'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/calendar', [SettingsController::class, 'updateCalendar'], ['middlewares' => [$requireAuth]]);
$router->get('/settings/profile', [SettingsController::class, 'profile'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/password', [SettingsController::class, 'changePassword'], ['middlewares' => [$requireAuth]]);
$router->get('/settings/security', [SettingsController::class, 'security'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/enable-2fa', [SettingsController::class, 'enable2FA'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/disable-2fa', [SettingsController::class, 'disable2FA'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/regenerate-backup-codes', [SettingsController::class, 'regenerateBackupCodes'], ['middlewares' => [$requireAuth]]);
// ROUND 4: Admin MFA management routes
$router->get('/settings/user-mfa', [SettingsController::class, 'userMfa'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/enable-user-mfa', [SettingsController::class, 'enableUserMfa'], ['middlewares' => [$requireAuth]]);
$router->post('/settings/disable-user-mfa', [SettingsController::class, 'disableUserMfa'], ['middlewares' => [$requireAuth]]);
// ROUND 5: Recovery codes download
$router->get('/settings/download-recovery-codes', [SettingsController::class, 'downloadRecoveryCodes'], ['middlewares' => [$requireAuth]]);
// ROUND 5: Security Dashboard
$router->get('/security/dashboard', [SecurityController::class, 'dashboard'], ['middlewares' => [$requireAuth]]);
$router->get('/settings/logs', [SettingsController::class, 'logs'], ['middlewares' => [$requireAdmin]]);
$router->get('/settings/monitoring', [SettingsController::class, 'monitoring'], ['middlewares' => [$requireAdmin]]);

// Role-Based Access Control (RBAC)
$router->get('/admin/roles', [RoleController::class, 'index'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/create', [RoleController::class, 'create'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/roles', [RoleController::class, 'store'], ['middlewares' => [$requireAdmin]]);
// Special routes must come BEFORE generic {id} routes
$router->get('/admin/roles/permissions', [RoleController::class, 'permissionIndex'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/roles/permissions', [RoleController::class, 'createPermission'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/hierarchy', [RoleController::class, 'hierarchy'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/export', [RoleController::class, 'export'], ['middlewares' => [$requireAdmin]]);
// Generic {id} routes come last
$router->get('/admin/roles/{id}', [RoleController::class, 'show'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/{id}/users', [RoleController::class, 'users'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/{id}/edit', [RoleController::class, 'edit'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/roles/{id}', [RoleController::class, 'update'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/roles/{id}/delete', [RoleController::class, 'delete'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/roles/{id}/permissions', [RoleController::class, 'permissions'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/roles/{id}/permissions', [RoleController::class, 'updatePermissions'], ['middlewares' => [$requireAdmin]]);

// Cache Management
$router->get('/admin/cache', [CacheController::class, 'index'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/stats', [CacheController::class, 'stats'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/analytics', [CacheController::class, 'analytics'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/clear', [CacheController::class, 'clear'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/warm', [CacheController::class, 'warm'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/test', [CacheController::class, 'test'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/keys', [CacheController::class, 'keys'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/get', [CacheController::class, 'get'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/set', [CacheController::class, 'set'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/delete', [CacheController::class, 'delete'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/invalidate-tag', [CacheController::class, 'invalidateTag'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/invalidate-pattern', [CacheController::class, 'invalidatePattern'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/cache/cleanup', [CacheController::class, 'cleanup'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/cache/export', [CacheController::class, 'export'], ['middlewares' => [$requireAdmin]]);

// Queue Management
$router->get('/admin/queue', [QueueController::class, 'index'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/stats', [QueueController::class, 'stats'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/failed', [QueueController::class, 'failed'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/queue/retry', [QueueController::class, 'retry'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/queue/delete', [QueueController::class, 'delete'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/queue/clear-failed', [QueueController::class, 'clearFailed'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/queue/push-test', [QueueController::class, 'pushTest'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/health', [QueueController::class, 'health'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/metrics', [QueueController::class, 'metrics'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/alerts', [QueueController::class, 'alerts'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/recommendations', [QueueController::class, 'recommendations'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/queue/flush', [QueueController::class, 'flush'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/queue/export', [QueueController::class, 'export'], ['middlewares' => [$requireAdmin]]);

// Audit Logs
$router->get('/audit', [AuditController::class, 'index'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/roles', [AuditController::class, 'roleSummary'], ['middlewares' => [$requireAdmin]]);
// Specific routes must come BEFORE generic {id} routes
$router->get('/audit/export', [AuditController::class, 'export'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/roles/export', [AuditController::class, 'exportRoleSummary'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/alerts', [AuditController::class, 'alerts'], ['middlewares' => [$requireAdmin]]);
$router->post('/audit/alerts/{id}/read', [AuditController::class, 'markAlertRead'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/compliance', [AuditController::class, 'compliance'], ['middlewares' => [$requireAdmin]]);
$router->post('/audit/anonymize/{userId}', [AuditController::class, 'anonymizeUser'], ['middlewares' => [$requireAdmin]]);
$router->post('/audit/cleanup', [AuditController::class, 'cleanup'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/statistics', [AuditController::class, 'statistics'], ['middlewares' => [$requireAdmin]]);
// Generic routes come last
$router->get('/audit/user/{userId}', [AuditController::class, 'userActivity'], ['middlewares' => [$requireAdmin]]);
$router->get('/audit/{id}', [AuditController::class, 'show'], ['middlewares' => [$requireAdmin]]);
$router->get('/settings/activity-csv', [SettingsController::class, 'exportActivityCsv'], ['middlewares' => [$requireAdmin]]);
$router->get('/settings/backup', [SettingsController::class, 'backupStatus'], ['middlewares' => [$requireAdmin]]);
$router->get('/settings/users', [SettingsController::class, 'users'], ['middlewares' => [$requireAdmin]]);
$router->post('/settings/create-user', [SettingsController::class, 'createUser'], ['middlewares' => [$requireAdmin]]);
$router->post('/settings/update-user/{id}', [SettingsController::class, 'updateUser'], ['middlewares' => [$requireAdmin]]);
$router->post('/settings/delete-user/{id}', [SettingsController::class, 'deleteUser'], ['middlewares' => [$requireAdmin]]);
$router->post('/settings/toggle-user/{id}', [SettingsController::class, 'toggleUser'], ['middlewares' => [$requireAdmin]]);

// Email Management
$router->get('/admin/emails/logs', [EmailController::class, 'logs'], ['middlewares' => [$requireAdmin]]);
$router->get('/admin/emails/queue', [EmailController::class, 'queue'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/emails/retry', [EmailController::class, 'retry'], ['middlewares' => [$requireAdmin]]);
$router->post('/admin/emails/clear-logs', [EmailController::class, 'clearLogs'], ['middlewares' => [$requireAdmin]]);

// Appointments
$router->get('/appointments', [AppointmentController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/appointments/new', [AppointmentController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/appointments/create', [AppointmentController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/appointments/{id}', [AppointmentController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/appointments/{id}/edit', [AppointmentController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/appointments/{id}/update', [AppointmentController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/appointments/{id}/delete', [AppointmentController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/appointments/{id}/status', [AppointmentController::class, 'updateStatus'], ['middlewares' => [$requireAuth]]);
$router->get('/appointments/today', [AppointmentController::class, 'today'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/appointments/week', [AppointmentController::class, 'thisWeek'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/appointments/upcoming', [AppointmentController::class, 'upcoming'], ['middlewares' => [$requireOperatorReadOnly]]);

// Contracts
$router->get('/contracts', [ContractController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
// Place static routes before dynamic {id}
$router->get('/contracts/expiring', [ContractController::class, 'expiring'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/contracts/new', [ContractController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/create', [ContractController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/contracts/{id}', [ContractController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/contracts/{id}/print', [ContractController::class, 'showPrintable'], ['middlewares' => [$requireAuth]]);
$router->get('/contracts/{id}/edit', [ContractController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/update', [ContractController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/delete', [ContractController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/status', [ContractController::class, 'updateStatus'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/payment', [ContractController::class, 'addPayment'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/payment/{paymentId}/update', [ContractController::class, 'updatePayment'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/payment/{paymentId}/delete', [ContractController::class, 'deletePayment'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/upload', [ContractController::class, 'uploadFile'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/{id}/file/{attachmentId}/delete', [ContractController::class, 'deleteFile'], ['middlewares' => [$requireAuth]]);
$router->get('/contracts/expiring', [ContractController::class, 'expiring'], ['middlewares' => [$requireOperatorReadOnly]]);

// Staff Management
$router->get('/staff', [StaffController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/staff/create', [StaffController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/create', [StaffController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/staff/edit/{id}', [StaffController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/update/{id}', [StaffController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/delete/{id}', [StaffController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Staff Attendance
$router->get('/staff/attendance/{id}', [StaffController::class, 'attendance'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/check-in/{id}', [StaffController::class, 'checkIn'], ['middlewares' => [$requireAuth]]);

// Staff Job Assignments
$router->get('/staff/assignments/{id}', [StaffController::class, 'assignments'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/assign-job/{id}', [StaffController::class, 'assignJob'], ['middlewares' => [$requireAuth]]);

// Staff Payments
$router->get('/staff/payments/{id}', [StaffController::class, 'payments'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/add-payment/{id}', [StaffController::class, 'addPayment'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/add-balance/{id}', [StaffController::class, 'addBalance'], ['middlewares' => [$requireAuth]]);

// Staff Balances
$router->get('/staff/balances/{id}', [StaffController::class, 'balances'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/update-balance/{id}', [StaffController::class, 'updateBalance'], ['middlewares' => [$requireAuth]]);

// Staff API endpoints
$router->get('/api/staff/today-working', [ApiController::class, 'todayWorkingStaff'], ['middlewares' => [$requireAuth]]);
$router->get('/api/staff/monthly-salary', [ApiController::class, 'monthlySalary'], ['middlewares' => [$requireAuth]]);

// Recurring Jobs
$router->get('/recurring', [RecurringJobController::class, 'index'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->get('/recurring/new', [RecurringJobController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/create', [RecurringJobController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/recurring/{id}/edit', [RecurringJobController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/update', [RecurringJobController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->get('/recurring/{id}', [RecurringJobController::class, 'show'], ['middlewares' => [$requireOperatorReadOnly]]);
$router->post('/recurring/{id}/toggle', [RecurringJobController::class, 'toggle'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/generate-now', [RecurringJobController::class, 'generateOccurrencesNow'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/update-time', [RecurringJobController::class, 'updateTime'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/delete-future', [RecurringJobController::class, 'deleteFuture'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/cancel', [RecurringJobController::class, 'cancel'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/delete', [RecurringJobController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/recurring/{id}/generate-single', [RecurringJobController::class, 'generateSingle'], ['middlewares' => [$requireAuth]]);

// ROUND 34: API endpoints for recurring job form
// Note: /api/services requires auth but MUST return JSON-only (never HTML)
// The ApiController::services() method handles auth internally and returns JSON error if unauthenticated
$router->get('/api/services', [ApiController::class, 'services'], ['middlewares' => []]); // No middleware - auth handled in controller
$router->get('/api/customers/{id}', [ApiController::class, 'customer'], ['middlewares' => [$requireAuth]]);
$router->get('/api/customers/{id}/addresses', [ApiController::class, 'customerAddresses'], ['middlewares' => [$requireAuth]]);

// ===== KOZMOS_BULK_OPERATIONS: bulk operations routes (begin)
// Jobs bulk operations
$router->post('/jobs/bulk-status-update', [JobController::class, 'bulkStatusUpdate'], ['middlewares' => [$requireAuth]]);
$router->post('/jobs/bulk-delete', [JobController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);

// Customers bulk operations
$router->post('/customers/bulk-delete', [CustomerController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);

// Finance bulk operations
$router->post('/finance/bulk-delete', [FinanceController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);

// Contracts bulk operations
$router->post('/contracts/bulk-status-update', [ContractController::class, 'bulkStatusUpdate'], ['middlewares' => [$requireAuth]]);
$router->post('/contracts/bulk-delete', [ContractController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);

// Staff bulk operations
$router->post('/staff/bulk-status-update', [StaffController::class, 'bulkStatusUpdate'], ['middlewares' => [$requireAuth]]);
$router->post('/staff/bulk-delete', [StaffController::class, 'bulkDelete'], ['middlewares' => [$requireAuth]]);
// ===== KOZMOS_BULK_OPERATIONS: bulk operations routes (end)

// Export functionality
$router->get('/export', [ExportController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/export/customers', [ExportController::class, 'exportCustomers'], ['middlewares' => [$requireAuth]]);
$router->get('/export/jobs', [ExportController::class, 'exportJobs'], ['middlewares' => [$requireAuth]]);
$router->get('/export/finance', [ExportController::class, 'exportFinance'], ['middlewares' => [$requireAuth]]);

// Reports functionality
// ROUND 31: Reports routes
$router->get('/reports', [ReportController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/dashboard', [ReportController::class, 'dashboard'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/financial', [ReportController::class, 'financial'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/jobs', [ReportController::class, 'jobs'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/customers', [ReportController::class, 'customers'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/services', [ReportController::class, 'services'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/performance', [ReportController::class, 'performance'], ['middlewares' => [$requireAuth]]);
$router->get('/reports/customer/{id}', [ReportController::class, 'customer'], ['middlewares' => [$requireAuth]]);

// PATH_CRAWL_SYSADMIN_WEB_V1: Sysadmin crawl routes (system admin only)
$router->get('/sysadmin/crawl', [SysadminToolsController::class, 'crawl'], ['middlewares' => [$requireAuth]]);
$router->post('/sysadmin/crawl/start', [SysadminToolsController::class, 'startCrawl'], ['middlewares' => [$requireAuth]]);
$router->post('/sysadmin/crawl/cancel', [SysadminToolsController::class, 'cancelCrawl'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/crawl/status', [SysadminToolsController::class, 'getCrawlStatus'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/crawl/progress', [SysadminToolsController::class, 'showCrawlProgress'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/crawl/results', [SysadminToolsController::class, 'showCrawlResults'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/admin-crawl', [SysadminToolsController::class, 'adminCrawl'], ['middlewares' => [$requireAuth]]);
// PATH_CRAWL_REMOTE_V1: Remote crawl execution endpoint (for hosting without CLI)
$router->post('/sysadmin/remote-crawl', [SysadminToolsController::class, 'remoteCrawl'], ['middlewares' => [$requireAuth]]);

// Test Management Routes
if (!class_exists('SysadminTestsController')) {
    require_once __DIR__ . '/src/Controllers/SysadminTestsController.php';
}
$router->get('/sysadmin/tests', [SysadminTestsController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->post('/sysadmin/tests/run', [SysadminTestsController::class, 'run'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/tests/status/{runId}', [SysadminTestsController::class, 'status'], ['middlewares' => [$requireAuth]]);
$router->get('/sysadmin/tests/results/{runId}', [SysadminTestsController::class, 'results'], ['middlewares' => [$requireAuth]]);

// ROUND 31: Legal pages
require_once __DIR__ . '/src/Controllers/LegalController.php';
$router->get('/privacy-policy', [LegalController::class, 'privacyPolicy']);
$router->get('/terms-of-use', [LegalController::class, 'termsOfUse']);
$router->get('/status', [LegalController::class, 'status']);
$router->get('/MANUAL_TEST_CHECKLIST.md', [DocsController::class, 'manualTestChecklist'], ['middlewares' => [$requireAuth]]);
$router->get('/UX_IMPLEMENTATION_GUIDE.md', [DocsController::class, 'uxImplementationGuide'], ['middlewares' => [$requireAuth]]);
$router->get('/DEPLOYMENT_CHECKLIST.md', [DocsController::class, 'deploymentChecklist'], ['middlewares' => [$requireAuth]]);

// ROUND 31: Base domain appointments redirects (for legacy URLs)
$router->get('/appointments', function() {
    // Redirect to main dashboard
    redirect(base_url('/'), 301);
});
$router->get('/appointments/new', function() {
    // Redirect to login (appointments require authentication)
    redirect(base_url('/login'), 301);
});

// ROUND 33: URL normalization - handle typo/malformed URLs (ointments -> /app dashboard)
// Redirect directly to /app to avoid redirect loop (appointments -> / -> potential loop)
$router->get('/ointments', function() {
    // Redirect typo URL directly to main dashboard (avoid redirect loop)
    redirect(base_url('/'), 301);
});
$router->get('/ointments/new', function() {
    // Redirect typo URL directly to login (avoid redirect loop)
    redirect(base_url('/login'), 301);
});

// Services
$router->get('/services', [ServiceController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/services/new', [ServiceController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/services/create', [ServiceController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/services/edit/{id}', [ServiceController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/services/update/{id}', [ServiceController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/services/delete/{id}', [ServiceController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/services/toggle/{id}', [ServiceController::class, 'toggleActive'], ['middlewares' => [$requireAuth]]);
// Service Contract Template
$router->get('/services/{id}/contract-template/edit', [ServiceController::class, 'editContractTemplate'], ['middlewares' => [$requireAuth]]);
$router->post('/services/{id}/contract-template/update', [ServiceController::class, 'updateContractTemplate'], ['middlewares' => [$requireAuth]]);

// Building Management Routes
// Buildings
$router->get('/buildings', [BuildingController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/buildings/new', [BuildingController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/buildings/create', [BuildingController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/buildings/{id}', [BuildingController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->get('/buildings/{id}/edit', [BuildingController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/buildings/update/{id}', [BuildingController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/buildings/delete/{id}', [BuildingController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->get('/buildings/{id}/dashboard', [BuildingController::class, 'dashboard'], ['middlewares' => [$requireAuth]]);

// Units
$router->get('/units', [UnitController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/units/new', [UnitController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/units/create', [UnitController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/units/{id}', [UnitController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->get('/units/{id}/edit', [UnitController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/units/update/{id}', [UnitController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/units/delete/{id}', [UnitController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Management Fees
$router->get('/management-fees', [ManagementFeeController::class, 'index'], ['middlewares' => [$requireAuth]]);
// Static routes MUST come before dynamic {id} route to avoid conflicts
$router->get('/management-fees/generate', [ManagementFeeController::class, 'generate'], ['middlewares' => [$requireAuth]]);
$router->post('/management-fees/generate', [ManagementFeeController::class, 'generateProcess'], ['middlewares' => [$requireAuth]]);
$router->post('/management-fees/preview', [ManagementFeeController::class, 'preview'], ['middlewares' => [$requireAuth]]);
$router->get('/management-fees/{id}', [ManagementFeeController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->get('/management-fees/{id}/payment', [ManagementFeeController::class, 'paymentForm'], ['middlewares' => [$requireAuth]]);
$router->post('/management-fees/{id}/payment', [ManagementFeeController::class, 'recordPayment'], ['middlewares' => [$requireAuth]]);
$router->get('/management-fees/overdue', [ManagementFeeController::class, 'overdue'], ['middlewares' => [$requireAuth]]);
$router->post('/management-fees/calculate-late-fees', [ManagementFeeController::class, 'calculateLateFees'], ['middlewares' => [$requireAuth]]);

// Scheduler-safe task endpoints (protected via token header/query)
$router->get('/tasks/fees/generate', [TasksController::class, 'generateFees']);
$router->get('/tasks/fees/late-fees', [TasksController::class, 'calculateLateFees']);
$router->get('/tasks/reminders/dispatch', function(){
    $token = $_GET['token'] ?? ($_SERVER['HTTP_X_TASK_TOKEN'] ?? '');
    $expected = $_ENV['TASK_TOKEN'] ?? null;
    if (!$expected || $token !== $expected) { http_response_code(401); echo 'unauthorized'; return; }
    header('Content-Type: application/json');
    echo json_encode(ReminderService::dispatch(5));
});

// OPS HARDENING ROUND 1: Ops status endpoint (internal use, auth + token protected)
$router->get('/tools/ops/status', function() {
    // Protect this endpoint: only accessible via CLI or internal token/IP
    $token = $_GET['token'] ?? ($_SERVER['HTTP_X_TASK_TOKEN'] ?? '');
    $expectedToken = env('OPS_STATUS_TOKEN', null); // Get token from .env
    $isCli = (php_sapi_name() === 'cli');
    
    // Check authentication
    $isAuthenticated = false;
    if ($isCli) {
        $isAuthenticated = true; // CLI is trusted
    } elseif ($expectedToken && $token === $expectedToken) {
        $isAuthenticated = true; // Token matches
    } elseif (class_exists('Auth') && Auth::check() && Auth::hasRole('SUPERADMIN')) {
        $isAuthenticated = true; // Superadmin is allowed
    }
    
    if (!$isAuthenticated) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to ops status endpoint.']);
        exit;
    }
    
    // Get extended status
    $status = [
        'timestamp' => date('c'),
        'app_version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
        'environment' => defined('APP_ENV') ? APP_ENV : 'unknown',
        'request_id' => class_exists('AppErrorHandler') ? AppErrorHandler::getRequestId() : null,
    ];
    
    // Add health check results
    if (class_exists('SystemHealth')) {
        $status['health'] = SystemHealth::check();
    }
    
    // Add logging statistics (if available)
    if (class_exists('Logger')) {
        $status['logging'] = Logger::getStatistics();
    }
    
    // Add disk usage (lightweight)
    $diskFree = disk_free_space(__DIR__);
    $diskTotal = disk_total_space(__DIR__);
    if ($diskFree !== false && $diskTotal !== false) {
        $status['disk'] = [
            'free_bytes' => $diskFree,
            'total_bytes' => $diskTotal,
            'used_percentage' => round((($diskTotal - $diskFree) / $diskTotal) * 100, 2),
        ];
    }
    
    header('Content-Type: application/json');
    if (isset($status['request_id']) && !headers_sent()) {
        header('X-Request-Id: ' . $status['request_id']);
    }
    echo json_encode($status, JSON_PRETTY_PRINT);
    exit;
});

// ROUND 7: Web Tabanlı Migration Runner
// SSH erişimi olmayan hosting'lerde tarayıcı üzerinden migration tetiklemek için
$router->get('/tools/db/migrate', function() {
    // Load config
    $securityConfig = require __DIR__ . '/config/security.php';
    $migrationConfig = $securityConfig['db_migrations'] ?? [];
    
    // Check if web runner is enabled
    if (empty($migrationConfig['web_runner_enabled'])) {
        http_response_code(404);
        echo "Not Found";
        exit;
    }
    
    // Check authentication
    if (!class_exists('Auth') || !Auth::check()) {
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Check SUPERADMIN role
    if (!Auth::hasRole('SUPERADMIN')) {
        http_response_code(403);
        echo "Forbidden: Only SUPERADMIN can access this endpoint.";
        exit;
    }
    
    // Check token if configured
    if (!empty($migrationConfig['token'])) {
        $providedToken = $_GET['token'] ?? '';
        if ($providedToken !== $migrationConfig['token']) {
            http_response_code(403);
            echo "Forbidden: Invalid token.";
            exit;
        }
    }
    
    // Load required classes
    require_once __DIR__ . '/src/Lib/MigrationManager.php';
    
    // Get migration status
    $status = null;
    try {
        $status = MigrationManager::status();
    } catch (Exception $e) {
        $error = "Migration durumu alınamadı: " . $e->getMessage();
    }
    
    // Show status page
    $data = [
        'status' => $status,
        'config' => $migrationConfig,
    ];
    if (isset($error)) {
        $data['error'] = $error;
    }
    
    // Load view
    extract($data);
    require __DIR__ . '/src/Views/tools/db_migrate.php';
    exit;
});

$router->post('/tools/db/migrate', function() {
    // Load config
    $securityConfig = require __DIR__ . '/config/security.php';
    $migrationConfig = $securityConfig['db_migrations'] ?? [];
    
    // Check if web runner is enabled
    if (empty($migrationConfig['web_runner_enabled'])) {
        http_response_code(404);
        echo "Not Found";
        exit;
    }
    
    // Check authentication
    if (!class_exists('Auth') || !Auth::check()) {
        http_response_code(401);
        echo "Unauthorized";
        exit;
    }
    
    // Check SUPERADMIN role
    if (!Auth::hasRole('SUPERADMIN')) {
        http_response_code(403);
        echo "Forbidden: Only SUPERADMIN can access this endpoint.";
        exit;
    }
    
    // Check token if configured
    if (!empty($migrationConfig['token'])) {
        $providedToken = $_GET['token'] ?? '';
        if ($providedToken !== $migrationConfig['token']) {
            http_response_code(403);
            echo "Forbidden: Invalid token.";
            exit;
        }
    }
    
    // CSRF protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($csrfToken) || $csrfToken !== $sessionToken) {
        http_response_code(403);
        echo "Forbidden: Invalid CSRF token.";
        exit;
    }
    
    // Load required classes
    require_once __DIR__ . '/src/Lib/MigrationManager.php';
    
    // Run migrations
    $migrationResult = null;
    $status = null;
    try {
        $migrationResult = MigrationManager::migrate();
        $status = MigrationManager::status();
    } catch (Exception $e) {
        $error = "Migration çalıştırılamadı: " . $e->getMessage();
        // Log detailed error
        if (class_exists('Logger')) {
            Logger::error('Web migration runner failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    // Show result page
    $data = [
        'migrationResult' => $migrationResult,
        'status' => $status,
        'config' => $migrationConfig,
    ];
    if (isset($error)) {
        $data['error'] = $error;
    }
    
    // Load view
    extract($data);
    require __DIR__ . '/src/Views/tools/db_migrate.php';
    exit;
});

// ROUND 3: Security Analytics scheduled task endpoint
$router->get('/tools/security/analyze', function(){
    // ROUND 10: Token-based authentication for scheduled tasks
    // SECURITY_ANALYZE_TOKEN or TASK_TOKEN can be used (prefer SECURITY_ANALYZE_TOKEN)
    $token = $_GET['token'] ?? ($_SERVER['HTTP_X_TASK_TOKEN'] ?? '');
    $expected = env('SECURITY_ANALYZE_TOKEN', null) ?: env('TASK_TOKEN', null);
    if (!$expected || $token !== $expected) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'unauthorized']);
        return;
    }
    
    // Load SecurityAnalyticsService
    require_once __DIR__ . '/src/Services/SecurityAnalyticsService.php';
    
    try {
        $result = SecurityAnalyticsService::runScheduledAnalysis();
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
});

// Building Expenses
$router->get('/expenses', [BuildingExpenseController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/expenses/new', [BuildingExpenseController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/expenses/create', [BuildingExpenseController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/expenses/{id}', [BuildingExpenseController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->get('/expenses/{id}/edit', [BuildingExpenseController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/expenses/update/{id}', [BuildingExpenseController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/expenses/delete/{id}', [BuildingExpenseController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->get('/expenses/approval-queue', [BuildingExpenseController::class, 'approvalQueue'], ['middlewares' => [$requireAuth]]);
$router->post('/expenses/approve/{id}', [BuildingExpenseController::class, 'approve'], ['middlewares' => [$requireAuth]]);
$router->post('/expenses/reject/{id}', [BuildingExpenseController::class, 'reject'], ['middlewares' => [$requireAuth]]);

// Enhanced File Upload System
$router->get('/file-upload', [FileUploadController::class, 'uploadForm'], ['middlewares' => [$requireAuth]]);
$router->post('/file-upload/upload', [FileUploadController::class, 'upload'], ['middlewares' => [$requireAuth]]);
$router->post('/file-upload/chunk', [FileUploadController::class, 'uploadChunk'], ['middlewares' => [$requireAuth]]);
$router->get('/file-upload/progress', [FileUploadController::class, 'getProgress'], ['middlewares' => [$requireAuth]]);
$router->get('/file-upload/list', [FileUploadController::class, 'list'], ['middlewares' => [$requireAuth]]);
$router->get('/file-upload/download/{id}', [FileUploadController::class, 'download'], ['middlewares' => [$requireAuth]]);
$router->get('/file-upload/view/{id}', [FileUploadController::class, 'view'], ['middlewares' => [$requireAuth]]);
$router->get('/file-upload/thumbnail/{id}', [FileUploadController::class, 'thumbnail'], ['middlewares' => [$requireAuth]]);
$router->post('/file-upload/delete/{id}', [FileUploadController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Comments System
$router->post('/comments/create', [CommentController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/comments/update/{id}', [CommentController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/comments/delete/{id}', [CommentController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/get-by-entity', [CommentController::class, 'getByEntity'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/get-replies/{id}', [CommentController::class, 'getReplies'], ['middlewares' => [$requireAuth]]);
$router->post('/comments/toggle-pin/{id}', [CommentController::class, 'togglePin'], ['middlewares' => [$requireAuth]]);
$router->post('/comments/toggle-reaction/{id}', [CommentController::class, 'toggleReaction'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/get-by-user', [CommentController::class, 'getByUser'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/get-mentions', [CommentController::class, 'getMentions'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/get-recent', [CommentController::class, 'getRecent'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/search', [CommentController::class, 'search'], ['middlewares' => [$requireAuth]]);
$router->get('/comments/stats', [CommentController::class, 'getStats'], ['middlewares' => [$requireAuth]]);

// Mobile API Routes
$router->post('/api/mobile/auth', [MobileApiController::class, 'authenticate']);
$router->post('/api/mobile/refresh', [MobileApiController::class, 'refresh']);
$router->get('/api/mobile/profile', [MobileApiController::class, 'profile'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/dashboard', [MobileApiController::class, 'dashboard'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/jobs', [MobileApiController::class, 'jobs'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/jobs/{id}', [MobileApiController::class, 'job'], ['middlewares' => [$requireAuth]]);
$router->post('/api/mobile/jobs', [MobileApiController::class, 'createJob'], ['middlewares' => [$requireAuth]]);
$router->post('/api/mobile/jobs/{id}/status', [MobileApiController::class, 'updateJobStatus'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/customers', [MobileApiController::class, 'customers'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/customers/{id}', [MobileApiController::class, 'customer'], ['middlewares' => [$requireAuth]]);
$router->get('/api/mobile/services', [MobileApiController::class, 'services'], ['middlewares' => [$requireAuth]]);

// Performance Routes
$router->get('/performance', [PerformanceController::class, 'index'], ['middlewares' => [$requireAdmin]]);
$router->post('/performance/cache', [PerformanceController::class, 'cache'], ['middlewares' => [$requireAdmin]]);
$router->post('/performance/optimize', [PerformanceController::class, 'optimize'], ['middlewares' => [$requireAdmin]]);
// ROUND 18: /performance/metrics endpoint public (frontend status bar calls it without auth)
$router->get('/performance/metrics', [PerformanceController::class, 'metrics'], ['middlewares' => []]);

// Resident Portal Routes
$router->get('/manifest.json', function () {
    $file = __DIR__ . '/public/manifest.json';
    if (!is_file($file)) {
        http_response_code(404);
        return;
    }
    header('Content-Type: application/json');
    header('Cache-Control: public, max-age=604800, immutable');
    readfile($file);
    exit;
});

$router->get('/service-worker.js', function () {
    $file = __DIR__ . '/public/service-worker.js';
    if (!is_file($file)) {
        http_response_code(404);
        return;
    }
    header('Content-Type: application/javascript');
    header('Cache-Control: public, max-age=604800, immutable');
    readfile($file);
    exit;
});

$router->get('/resident/login', [ResidentController::class, 'login']);
$router->post('/resident/login', [ResidentController::class, 'processLogin']);
$router->post('/resident/login/password', [ResidentController::class, 'processPasswordChallenge']);
$router->post('/resident/login/otp', [ResidentController::class, 'processOtpVerification']);
$router->post('/resident/login/set-password', [ResidentController::class, 'processPasswordSetup']);
$router->post('/resident/login/forgot', [ResidentController::class, 'initiatePasswordReset']);
$router->post('/resident/login/resend', [ResidentController::class, 'resendLoginOtp']);
$router->post('/resident/login/cancel', [ResidentController::class, 'cancelLoginFlow']);
$router->get('/resident/logout', [ResidentController::class, 'logout']);
$router->get('/resident/dashboard', [ResidentController::class, 'dashboard']);
$router->get('/resident/fees', [ResidentController::class, 'fees']);
$router->get('/resident/pay-fee/{id}', [ResidentController::class, 'payFee']);
$router->post('/resident/pay-fee/{id}', [ResidentController::class, 'payFee']);
$router->get('/resident/requests', [ResidentController::class, 'requests']);
$router->get('/resident/request-detail/{id}', [ResidentController::class, 'requestDetail']);
$router->get('/resident/create-request', [ResidentController::class, 'createRequest']);
$router->post('/resident/create-request', [ResidentController::class, 'createRequest']);
$router->get('/resident/announcements', [ResidentController::class, 'announcements']);
$router->get('/resident/meetings', [ResidentController::class, 'meetings']);
$router->get('/resident/profile', [ResidentController::class, 'profile']);
$router->post('/resident/profile', [ResidentController::class, 'profile']);
$router->post('/resident/profile/verify', [ResidentController::class, 'verifyContact']);
$router->post('/resident/profile/request', [ResidentController::class, 'requestContactVerification']);
$router->post('/resident/profile/resend', [ResidentController::class, 'resendContactVerification']);

// Building Documents Routes
$router->get('/documents', [BuildingDocumentController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/documents/upload', [BuildingDocumentController::class, 'upload'], ['middlewares' => [$requireAuth]]);
$router->post('/documents/upload', [BuildingDocumentController::class, 'processUpload'], ['middlewares' => [$requireAuth]]);
$router->get('/documents/view/{id}', [BuildingDocumentController::class, 'view'], ['middlewares' => [$requireAuth]]);
$router->get('/documents/download/{id}', [BuildingDocumentController::class, 'download'], ['middlewares' => [$requireAuth]]);
$router->post('/documents/delete/{id}', [BuildingDocumentController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/documents/update/{id}', [BuildingDocumentController::class, 'update'], ['middlewares' => [$requireAuth]]);

// Resident API Routes
$router->post('/api/resident/login', [ResidentApiController::class, 'login']);
$router->get('/api/resident/profile', [ResidentApiController::class, 'profile']);
$router->get('/api/resident/dashboard', [ResidentApiController::class, 'dashboard']);
$router->get('/api/resident/fees', [ResidentApiController::class, 'fees']);
$router->post('/api/resident/fees/{id}/pay', [ResidentApiController::class, 'payFee']);
$router->get('/api/resident/requests', [ResidentApiController::class, 'requests']);
$router->post('/api/resident/requests', [ResidentApiController::class, 'createRequest']);
$router->get('/api/resident/announcements', [ResidentApiController::class, 'announcements']);
$router->get('/api/resident/meetings', [ResidentApiController::class, 'meetings']);
$router->get('/api/resident/documents', [ResidentApiController::class, 'documents']);
$router->post('/api/resident/profile', [ResidentApiController::class, 'updateProfile']);

// Building Meetings
$router->get('/meetings', [BuildingMeetingController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/meetings/new', [BuildingMeetingController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->get('/meetings/create', [BuildingMeetingController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/meetings/create', [BuildingMeetingController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/meetings/{id}', [BuildingMeetingController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->post('/meetings/update/{id}', [BuildingMeetingController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/meetings/complete/{id}', [BuildingMeetingController::class, 'complete'], ['middlewares' => [$requireAuth]]);

// Building Announcements
$router->get('/announcements', [BuildingAnnouncementController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/announcements/new', [BuildingAnnouncementController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->get('/announcements/create', [BuildingAnnouncementController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/announcements/create', [BuildingAnnouncementController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->post('/announcements/delete/{id}', [BuildingAnnouncementController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Building Facilities
$router->get('/facilities', [BuildingFacilityController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/facilities/new', [BuildingFacilityController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/facilities/create', [BuildingFacilityController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/facilities/{id}/edit', [BuildingFacilityController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/facilities/update/{id}', [BuildingFacilityController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/facilities/delete/{id}', [BuildingFacilityController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Building Surveys
$router->get('/surveys', [BuildingSurveyController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/surveys/new', [BuildingSurveyController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/surveys/create', [BuildingSurveyController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/surveys/{id}', [BuildingSurveyController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->get('/surveys/{id}/edit', [BuildingSurveyController::class, 'edit'], ['middlewares' => [$requireAuth]]);
$router->post('/surveys/update/{id}', [BuildingSurveyController::class, 'update'], ['middlewares' => [$requireAuth]]);
$router->post('/surveys/delete/{id}', [BuildingSurveyController::class, 'delete'], ['middlewares' => [$requireAuth]]);
$router->post('/surveys/publish/{id}', [BuildingSurveyController::class, 'publish'], ['middlewares' => [$requireAuth]]);
$router->post('/surveys/close/{id}', [BuildingSurveyController::class, 'close'], ['middlewares' => [$requireAuth]]);

// Building Reservations
$router->get('/reservations', [BuildingReservationController::class, 'index'], ['middlewares' => [$requireAuth]]);
$router->get('/reservations/new', [BuildingReservationController::class, 'create'], ['middlewares' => [$requireAuth]]);
$router->post('/reservations/create', [BuildingReservationController::class, 'store'], ['middlewares' => [$requireAuth]]);
$router->get('/reservations/{id}', [BuildingReservationController::class, 'show'], ['middlewares' => [$requireAuth]]);
$router->post('/reservations/approve/{id}', [BuildingReservationController::class, 'approve'], ['middlewares' => [$requireAuth]]);
$router->post('/reservations/reject/{id}', [BuildingReservationController::class, 'reject'], ['middlewares' => [$requireAuth]]);
$router->post('/reservations/cancel/{id}', [BuildingReservationController::class, 'cancel'], ['middlewares' => [$requireAuth]]);
$router->post('/reservations/delete/{id}', [BuildingReservationController::class, 'delete'], ['middlewares' => [$requireAuth]]);

// Building Reports
$router->get('/building-reports/financial', [BuildingReportController::class, 'financial'], ['middlewares' => [$requireAuth]]);
$router->get('/building-reports/collection', [BuildingReportController::class, 'collection'], ['middlewares' => [$requireAuth]]);
$router->get('/building-reports/export-fees', [BuildingReportController::class, 'exportFees'], ['middlewares' => [$requireAuth]]);
$router->get('/building-reports/export-expenses', [BuildingReportController::class, 'exportExpenses'], ['middlewares' => [$requireAuth]]);

// CLI endpoints (basic) via query param for scheduler fallback
if (php_sapi_name() === 'cli' && isset($argv) && !empty($argv)) {
    // Example: php index.php cli:recurring:generate 30
    if (($argv[1] ?? '') === 'cli:recurring:generate') {
        $days = (int)($argv[2] ?? 30);
        $model = new RecurringJob();
        $items = $model->getActive();
        foreach ($items as $rj) {
            RecurringGenerator::generateForJob((int)$rj['id'], $days);
            RecurringGenerator::materializeToJobs((int)$rj['id']);
        }
        echo "OK\n";
        exit(0);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// ===== PRODUCTION: Only log in debug mode =====
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log("Request Method: $method, URI: $uri, APP_BASE: " . APP_BASE);
}
// ===== PRODUCTION: End =====

// ===== ERR-017 FIX: Handle CORS for API endpoints =====
// Check if this is an API request
$isApiRequest = strpos($uri, '/api/') === 0 || strpos($uri, '/mobile/') === 0 || strpos($uri, '/resident/api') === 0;

if ($isApiRequest) {
    // Handle OPTIONS preflight request
    SecurityHeaders::handlePreflight();
    
    // Set CORS headers for API requests
    SecurityHeaders::setCors();
}
// ===== ERR-017 FIX: End =====

// ===== PRODUCTION: Only log in debug mode =====
if (defined('APP_DEBUG') && APP_DEBUG) {
error_log("Request Method: $method, URI: $uri, APP_BASE: " . APP_BASE);
}
// ===== PRODUCTION: End =====

// Customer Portal Routes
$router->get('/portal/login', [PortalController::class, 'login']);
$router->post('/portal/login', [PortalController::class, 'processLogin']);
$router->post('/portal/login/password', [PortalController::class, 'processPasswordChallenge']);
$router->post('/portal/login/otp', [PortalController::class, 'processOtpVerification']);
$router->post('/portal/login/set-password', [PortalController::class, 'processPasswordSetup']);
$router->post('/portal/login/forgot', [PortalController::class, 'initiatePasswordReset']);
$router->post('/portal/login/resend', [PortalController::class, 'resendLoginOtp']);
$router->post('/portal/login/cancel', [PortalController::class, 'cancelLoginFlow']);
$router->get('/portal/logout', [PortalController::class, 'logout']);
$router->get('/portal/dashboard', [PortalController::class, 'dashboard']);
$router->get('/portal/jobs', [PortalController::class, 'jobs']);
$router->get('/portal/invoices', [PortalController::class, 'invoices']);
$router->get('/portal/booking', [PortalController::class, 'booking']);
$router->post('/portal/booking/process', [PortalController::class, 'processBooking']);
$router->get('/portal/payment', [PortalController::class, 'payment']);
$router->post('/portal/payment/process', [PortalController::class, 'processPayment']);

// API v2 Routes
$router->post('/api/v2/auth/login', [\App\Controllers\Api\V2\AuthController::class, 'login']);
$router->post('/api/v2/auth/refresh', [\App\Controllers\Api\V2\AuthController::class, 'refresh']);
$router->get('/api/v2/auth/verify', [\App\Controllers\Api\V2\AuthController::class, 'verify']);

$router->get('/api/v2/customers', [\App\Controllers\Api\V2\CustomerController::class, 'index']);
$router->get('/api/v2/customers/{id}', [\App\Controllers\Api\V2\CustomerController::class, 'show']);
$router->post('/api/v2/customers', [\App\Controllers\Api\V2\CustomerController::class, 'create']);
$router->post('/api/v2/customers/{id}/update', [\App\Controllers\Api\V2\CustomerController::class, 'update']);
$router->post('/api/v2/customers/{id}/delete', [\App\Controllers\Api\V2\CustomerController::class, 'delete']);

$router->get('/api/v2/jobs', [\App\Controllers\Api\V2\JobController::class, 'index']);
$router->get('/api/v2/jobs/{id}', [\App\Controllers\Api\V2\JobController::class, 'show']);
$router->post('/api/v2/jobs', [\App\Controllers\Api\V2\JobController::class, 'create']);
$router->post('/api/v2/jobs/{id}/update', [\App\Controllers\Api\V2\JobController::class, 'update']);

$router->get('/offline', function () {
    include __DIR__ . '/src/Views/offline.php';
});

// Allow internal tools (e.g., CLI crawlers) to bootstrap routes without executing router
if (defined('KUREAPP_SKIP_ROUTER_RUN') && KUREAPP_SKIP_ROUTER_RUN === true) {
    $GLOBALS['router'] = $router;
    return;
}

// Run router
// ROUND 48: Global bootstrap 500 trace - log before router run
$r48_log_file = __DIR__ . '/logs/bootstrap_r48.log';
$r48_log_dir = dirname($r48_log_file);
if (!is_dir($r48_log_dir)) {
    @mkdir($r48_log_dir, 0755, true);
}
$r48_timestamp = date('Y-m-d H:i:s');
$r48_request_id = uniqid('req_', true);
$r48_log_entry = "[{$r48_timestamp}] [{$r48_request_id}] ROUTER_RUN_START: method={$method}, uri={$uri}, user_id=" . (Auth::check() ? Auth::id() : 'none') . "\n";
@file_put_contents($r48_log_file, $r48_log_entry, FILE_APPEND | LOCK_EX);

// ROUND 50: Global fatal 500 hunter - comprehensive try/catch for all Throwable types
try {
    // Execute router
    if (!$router->run($method, $uri)) {
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] ROUTER_RUN_NOT_FOUND: uri={$uri}\n", FILE_APPEND | LOCK_EX);
        View::notFound('Sayfa bulunamadı');
    }
    // NOTE: If router->run() sends JSON and exits, execution stops here
    // So logging after router->run() only happens if it didn't exit
    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] ROUTER_RUN_SUCCESS: uri={$uri}\n", FILE_APPEND | LOCK_EX);
} catch (Throwable $e) {
    // ROUND 50: Catch all Throwable types (Exception + Error) for PHP 8 compatibility
    // ROUND 50: Ensure logging happens even if file operations fail - use multiple methods
    $r48_exception_msg = $e->getMessage();
    $r48_exception_file = $e->getFile();
    $r48_exception_line = $e->getLine();
    $r48_exception_class = get_class($e);
    $r48_exception_trace = substr($e->getTraceAsString(), 0, 1000); // First 1000 chars
    
    // Method 1: PHP error_log (most reliable)
    @error_log("ROUND50_FATAL: {$r48_exception_class} - {$r48_exception_msg} in {$r48_exception_file}:{$r48_exception_line} [uri={$uri}, method={$method}, request_id={$r48_request_id}]");
    
    // Method 2: ROUND 48 bootstrap log
    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] ROUTER_RUN_EXCEPTION: message={$r48_exception_msg}, file={$r48_exception_file}, line={$r48_exception_line}, trace={$r48_exception_trace}\n", FILE_APPEND | LOCK_EX);
    
    // Method 3: ROUND 50 global fatal log
    $r50_fatal_log = __DIR__ . '/logs/global_r50_fatal.log';
    $r50_log_dir = dirname($r50_fatal_log);
    if (!is_dir($r50_log_dir)) {
        @mkdir($r50_log_dir, 0755, true);
    }
    $r50_full_trace = $e->getTraceAsString();
    @file_put_contents($r50_fatal_log, "[{$r48_timestamp}] [{$r48_request_id}] GLOBAL_FATAL: class={$r48_exception_class}, message={$r48_exception_msg}, file={$r48_exception_file}, line={$r48_exception_line}, uri={$uri}, method={$method}\nTRACE:\n{$r50_full_trace}\n---\n", FILE_APPEND | LOCK_EX);
    
    // ROUND 50: Check if this is an API request (JSON-only endpoints)
    $isApiRequest = strpos($uri, '/api/') === 0 || 
                    strpos($uri, '/health') !== false || 
                    strpos($uri, '/performance/metrics') !== false ||
                    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    if ($isApiRequest) {
        // ROUND 50: API requests - JSON-only response
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200); // 500 yerine 200 (JSON error)
        }
        echo json_encode([
            'success' => false,
            'error' => 'internal_error',
            'request_id' => $r48_request_id
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // ROUND 50: Web requests - graceful error page (200 status, not 500)
    // OPS HARDENING ROUND 1: Use AppErrorHandler for structured logging and safe error responses
    if (class_exists('AppErrorHandler')) {
        // ROUND 50: Don't use AppErrorHandler::handleAndRespond() as it sets 500 status
        // Instead, log and show graceful error page with 200 status
        AppErrorHandler::logException($e, [
            'route' => $uri,
            'method' => $method,
            'context' => 'ROUND50_GLOBAL_ROUTER_CATCH'
        ]);
        
        // Show graceful error page (200 status)
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        // Simple error page (GLOBAL_R50_MARKER_1)
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body>';
        echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        echo '<h1>Beklenmeyen Hata (GLOBAL_R50_MARKER_1)</h1>';
        echo '<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>';
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<p><strong>Hata:</strong> ' . htmlspecialchars($r48_exception_msg, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<p><strong>Dosya:</strong> ' . htmlspecialchars($r48_exception_file, ENT_QUOTES, 'UTF-8') . ':' . $r48_exception_line . '</p>';
        }
        echo '<p><small>Request ID: ' . htmlspecialchars($r48_request_id, ENT_QUOTES, 'UTF-8') . '</small></p>';
        echo '</div></body></html>';
        exit;
    } else {
        // ROUND 50: Fallback to old error handling if AppErrorHandler is not available
        $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
        $reqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $logMessage = $e->getMessage();
        $logFile = $e->getFile();
        $logLine = $e->getLine();
        $trace = $e->getTraceAsString();
        
        // ROUND 50: Log to global fatal log
        @file_put_contents($r50_fatal_log, "[{$r48_timestamp}] [{$r48_request_id}] GLOBAL_FATAL_FALLBACK: class=" . get_class($e) . ", message={$logMessage}, file={$logFile}, line={$logLine}, uri={$reqUri}, method={$reqMethod}\nTRACE:\n{$trace}\n---\n", FILE_APPEND | LOCK_EX);
        
        error_log('Application Error: ' . $logMessage . ' in ' . $logFile . ' on line ' . $logLine);
        error_log("Request: method={$reqMethod} uri={$reqUri}");
        
        if (class_exists('Logger')) {
            Logger::error('Unhandled exception', [
                'message' => $logMessage,
                'file' => $logFile,
                'line' => $logLine,
                'request_uri' => $reqUri,
                'request_method' => $reqMethod,
            ]);
        }

        // ROUND 50: Graceful error page (200 status, not 500)
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        if (APP_DEBUG) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body>';
            echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
            echo '<h1>Beklenmeyen Hata (GLOBAL_R50_MARKER_1 - DEBUG)</h1>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($logMessage, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<p><strong>File:</strong> ' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<p><strong>Line:</strong> ' . $logLine . '</p>';
            echo '<p><small>Request ID: ' . htmlspecialchars($r48_request_id, ENT_QUOTES, 'UTF-8') . '</small></p>';
            echo '</div></body></html>';
            exit;
        }

        // ROUND 50: Production - simple error page (200 status)
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body>';
        echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        echo '<h1>Beklenmeyen Hata (GLOBAL_R50_MARKER_1)</h1>';
        echo '<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>';
        echo '<p><small>Request ID: ' . htmlspecialchars($r48_request_id, ENT_QUOTES, 'UTF-8') . '</small></p>';
        echo '</div></body></html>';
        exit;
    }
}

// ROUND 50: Register shutdown handler for fatal errors
register_shutdown_function(function() use ($r48_log_file, $r48_timestamp, $r48_request_id) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
        $r50_fatal_log = __DIR__ . '/logs/global_r50_fatal.log';
        $r50_log_dir = dirname($r50_fatal_log);
        if (!is_dir($r50_log_dir)) {
            @mkdir($r50_log_dir, 0755, true);
        }
        
        $errorMsg = $error['message'] ?? 'Unknown fatal error';
        $errorFile = $error['file'] ?? 'unknown';
        $errorLine = $error['line'] ?? 0;
        $errorType = $error['type'] ?? E_ERROR;
        
        @file_put_contents($r50_fatal_log, "[{$r48_timestamp}] [{$r48_request_id}] SHUTDOWN_FATAL: type={$errorType}, message={$errorMsg}, file={$errorFile}, line={$errorLine}\n", FILE_APPEND | LOCK_EX);
        
        // ROUND 50: Graceful error page (200 status, not 500)
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body>';
        echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        echo '<h1>Beklenmeyen Hata (GLOBAL_R50_MARKER_1 - SHUTDOWN)</h1>';
        echo '<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>';
        echo '<p><small>Request ID: ' . htmlspecialchars($r48_request_id, ENT_QUOTES, 'UTF-8') . '</small></p>';
        echo '</div></body></html>';
    }
});

// Early static asset passthrough (before session/bootstrap)
$__requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$__base = rtrim(APP_BASE, '/');
if ($__base !== '' && strpos($__requestPath, $__base) === 0) {
    $__requestPath = substr($__requestPath, strlen($__base)) ?: '/';
}
$__normalizedPath = '/' . ltrim($__requestPath, '/');
// ===== PRODUCTION: Only log in debug mode =====
if (defined('APP_DEBUG') && APP_DEBUG) {
    file_put_contents(__DIR__ . '/logs/static-debug.log', date('c') . ' uri=' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' normalized=' . $__normalizedPath . PHP_EOL, FILE_APPEND);
    error_log('[static] request=' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' normalized=' . $__normalizedPath);
}
// ===== PRODUCTION: End =====

$__staticMap = [
    '/manifest.json' => __DIR__ . '/public/manifest.json',
    '/service-worker.js' => __DIR__ . '/public/service-worker.js',
];

$__maybeServeFile = function (string $file) {
    if (!is_file($file)) {
        return false;
    }
    $mime = function_exists('mime_content_type') ? mime_content_type($file) : null;
    if (!$mime) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'json' => 'application/json',
            'js' => 'application/javascript',
            'css' => 'text/css',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800, immutable');
    readfile($file);
    return true;
};

if (isset($__staticMap[$__normalizedPath])) {
    if ($__maybeServeFile($__staticMap[$__normalizedPath])) {
        exit;
    }
}

if (str_starts_with($__normalizedPath, '/assets/')) {
    $requested = realpath(__DIR__ . $__normalizedPath);
    $assetsRoot = realpath(__DIR__ . '/assets');
    if ($requested && $assetsRoot && str_starts_with($requested, $assetsRoot) && $__maybeServeFile($requested)) {
        exit;
    }
}
