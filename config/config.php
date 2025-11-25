<?php
/**
 * Uygulama Konfigürasyonu
 */

// ===== PHASE 2: Base Path Constant =====
// Absolute base path for the application root directory
define('APP_ROOT', dirname(__DIR__));
define('APP_BASE', '/app');
define('TIMEZONE', 'Europe/Istanbul');
define('SESSION_NAME', 'temizlik_sess');

// CLI/test ortamlarında başlık/oturum uyarılarını engellemek için erken çıktı tamponu
if (PHP_SAPI === 'cli' && function_exists('ob_get_level') && ob_get_level() === 0) {
	@ob_start();
}

define('APP_CHARSET', 'UTF-8');
ini_set('default_charset', APP_CHARSET);
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding(APP_CHARSET);
    mb_http_output(APP_CHARSET);
    mb_regex_encoding(APP_CHARSET);
}
if (function_exists('mb_language')) {
    mb_language('uni');
}
if (function_exists('mb_detect_order')) {
    mb_detect_order(['UTF-8', 'ISO-8859-9', 'ASCII']);
}

// ===== PHASE 8: Locale Robustness =====
// Try multiple locale options with fallback to 'C' if none available
$localeSet = false;
$localeOptions = ['tr_TR.UTF-8', 'tr_TR.utf8', 'tr_TR', 'tr', 'turkish', 'C'];
foreach ($localeOptions as $locale) {
    if (setlocale(LC_ALL, $locale) !== false) {
        $localeSet = true;
        break;
    }
}
if (!$localeSet) {
    // Final fallback to 'C' locale (always available)
    setlocale(LC_ALL, 'C');
}


// ===== PHASE 1: Robust Environment Detection =====
// ROUND 53: Prevent redeclare error with function_exists check
if (!function_exists('kozmos_is_https')) {
    function kozmos_is_https(): bool {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
        return false;
    }
}

// Environment detection: Check HTTP_HOST, SERVER_NAME, and APP_ENV
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;

// Production detection: domain contains 'kuretemizlik.com' OR APP_ENV=production
$isProduction = (stripos($httpHost, 'kuretemizlik.com') !== false) || 
                (stripos($serverName, 'kuretemizlik.com') !== false) ||
                ($appEnv === 'production');

// Localhost detection
$KOZMOS_IS_LOCALHOST = (stripos($httpHost, 'localhost') === 0) || 
                       ($httpHost === '127.0.0.1') ||
                       (stripos($httpHost, '.local') !== false);

$is_https = kozmos_is_https();
// Lokal geliştirmede HTTPS yoksa cookie_secure'u zorla kapat:
if ($KOZMOS_IS_LOCALHOST && !$is_https) { $is_https = false; }

// Güvenlik ayarları
define('COOKIE_SECURE', $is_https);
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? ($_SERVER['CSRF_SECRET'] ?? 'temizlik_app_secret_key_2025'));

// ===== PHASE 1: APP_DEBUG default based on environment =====
// Production'da default false, local'de true
$defaultDebug = $isProduction ? false : true;
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? ($_SERVER['APP_DEBUG'] ?? $defaultDebug));

// Session timeout (seconds)
define('SESSION_TIMEOUT', $_ENV['SESSION_TIMEOUT'] ?? ($_SERVER['SESSION_TIMEOUT'] ?? 3600));

// Zaman dilimi ayarla (her zaman Europe/Istanbul)
date_default_timezone_set(TIMEZONE);

// ===== PHASE 2 & 4: Path Resolution & Error Handling Production-Safe =====
// Standardize log path: always use APP_ROOT/logs/error.log
$logDir = APP_ROOT . '/logs';
$logFile = $logDir . '/error.log';

// Auto-create log directory if it doesn't exist
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
    // Try to set permissions (may fail on some systems, that's OK)
    @chmod($logDir, 0775);
}

// ===== IMPROVEMENT: Cache Directory Auto-Creation =====
$cacheDir = APP_ROOT . '/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
    @chmod($cacheDir, 0775);
}

// ===== IMPROVEMENT: Set Global $isProduction for env() function =====
$GLOBALS['isProduction'] = $isProduction;

// Hata raporlama (production'da kapatılmalı)
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', $logFile);
} else {
    // Production: no error display, but log errors
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT); // Log all except deprecations
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $logFile);
    
    // ===== ROUND 50: Set custom error handler for production =====
    // Only E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR should throw exceptions
    // E_WARNING, E_NOTICE, E_USER_WARNING, etc. should only be logged
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
        // Mask sensitive data in error messages
        if (function_exists('mask_sensitive_data')) {
            $errstr = mask_sensitive_data($errstr);
        }
        
        // Sanitize file path in production
        $errfile = basename($errfile);
        
        $logMessage = sprintf(
            "[%s] PHP %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $errno,
            $errstr,
            $errfile,
            $errline
        );
        
        error_log($logMessage, 3, $logFile);
        
        // ROUND 50: Only critical errors should throw exceptions
        // E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR → throw exception
        // E_WARNING, E_NOTICE, E_USER_WARNING, E_DEPRECATED, E_STRICT → log only
        if ($errno === E_ERROR || $errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR) {
            // Convert critical errors to exceptions
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        
        // For warnings/notices, just log and continue
        // Don't execute PHP internal error handler
        return true;
    });
}

// ===== PHASE 2: Database Path Standardization =====
// Use absolute path from APP_ROOT
if (!defined('DB_PATH')) {
    define('DB_PATH', APP_ROOT . '/db/app.sqlite');
}

// ===== PRODUCTION FIX: Session cookie params moved to index.php =====
// Session cookie params artık index.php'de session başlatılmadan önce ayarlanıyor
// Bu, cookie path'inin doğru ayarlanmasını garanti eder
// ===== PRODUCTION FIX END =====

// Otomatik yükleme
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Lib/',
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Models/',
        __DIR__ . '/../src/Middleware/',
        __DIR__ . '/../src/Services/',
        __DIR__ . '/../src/Cache/',
        __DIR__ . '/../src/Contracts/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Yardımcı fonksiyonlar
function config($key, $default = null) {
    $config = [
        'app_base' => APP_BASE,
        'db_path' => DB_PATH,
        'timezone' => TIMEZONE,
        'csrf_secret' => CSRF_SECRET,
    ];
    
    return $config[$key] ?? $default;
}

// ===== PHASE 9: Environment Variables Production-Safe =====
function env(string $key, $default = null) {
    static $loaded = false;
    static $isProd = null;
    
    if (!$loaded) {
        // Detect production environment once (use global $isProduction if available)
        if (isset($GLOBALS['isProduction'])) {
            $isProd = $GLOBALS['isProduction'];
        } else {
            $httpHost = $_SERVER['HTTP_HOST'] ?? '';
            $serverName = $_SERVER['SERVER_NAME'] ?? '';
            $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
            $isProd = (stripos($httpHost, 'kuretemizlik.com') !== false) || 
                      (stripos($serverName, 'kuretemizlik.com') !== false) ||
                      ($appEnv === 'production');
        }
        
        // Load environment files in priority order:
        // 1. .env.production (if production)
        // 2. .env.local (if local)
        // 3. .env (fallback)
        
        $envFiles = [];
        if ($isProd) {
            $envFiles[] = APP_ROOT . '/.env.production';
        } else {
            $envFiles[] = APP_ROOT . '/env.local';
        }
        $envFiles[] = APP_ROOT . '/.env';
        
        foreach ($envFiles as $envFile) {
        if (is_file($envFile) && is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    [$k, $v] = array_map('trim', explode('=', $line, 2));
                    $v = trim($v, "\"'");
                        // Only set if not already set (first file wins)
                        if (!isset($_ENV[$k])) {
                    $_ENV[$k] = $v;
                        }
                    }
                }
                // Stop after first found file
                break;
            }
        }
        $loaded = true;
    }
    // Fallback chain: $_ENV -> $_SERVER -> default
    return $_ENV[$key] ?? $_SERVER[$key] ?? ($default instanceof Closure ? $default() : $default);
}

// ===== IMPROVEMENT: Sensitive Data Masking Helper =====
/**
 * Mask sensitive data in error messages for production safety
 * 
 * @param string $message The message to sanitize
 * @return string Sanitized message
 */
function mask_sensitive_data(string $message): string {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        // In debug mode, don't mask (for development)
        return $message;
    }
    
    // Patterns to mask: password, token, secret, key, api_key, etc.
    $patterns = [
        '/password[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'password=[HIDDEN]',
        '/token[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'token=[HIDDEN]',
        '/secret[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'secret=[HIDDEN]',
        '/api[_-]?key[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'api_key=[HIDDEN]',
        '/auth[_-]?token[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'auth_token=[HIDDEN]',
        '/jwt[_-]?secret[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'jwt_secret=[HIDDEN]',
        '/csrf[_-]?secret[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'csrf_secret=[HIDDEN]',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $message = preg_replace($pattern, $replacement, $message);
    }
    
    return $message;
}

// ===== IMPROVEMENT: Safe Error Logging =====
/**
 * Safely log errors with sensitive data masking
 * 
 * @param string $message The error message
 * @param int $messageType Error log type (default: 0 = system logger)
 * @param string|null $destination Log file destination (null = use default)
 */
function safe_error_log(string $message, int $messageType = 0, ?string $destination = null): void {
    // Mask sensitive data
    $message = mask_sensitive_data($message);
    
    // Use default error log path if not specified
    if ($destination === null && defined('APP_ROOT')) {
        $logDir = APP_ROOT . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $destination = $logDir . '/error.log';
    }
    
    // Log the sanitized message
    if ($destination !== null) {
        error_log($message, 3, $destination);
    } else {
        error_log($message, $messageType);
    }
}

function base_url($path = '') {
    $base = defined('APP_BASE') ? APP_BASE : '/app';
    
    // Base boş ise (root'ta çalışıyorsa)
    if (empty($base) || $base === '/') {
        return empty($path) ? '/' : '/' . ltrim($path, '/');
    }
    
    // Path boş ise sadece base'i döndür
    if (empty($path)) {
        return $base;
    }
    
    // Path'i temizle (başındaki /'i koru)
    $path = '/' . ltrim($path, '/');
    
    // Normalize: Eğer path zaten base ile başlıyorsa, base'i kaldır (çift prefix önleme)
    // Örnek: base='/app', path='/app/sysadmin/crawl' -> path='/sysadmin/crawl' olur
    if (strpos($path, $base) === 0 && strlen($path) > strlen($base)) {
        $path = substr($path, strlen($base));
        // Eğer path boş kaldıysa veya / ile başlamıyorsa / ekle
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
    }
    
    // Eğer path zaten base ile başlıyorsa (örn: /app/login -> /app/login)
    if (strpos($path, $base) === 0) {
        return $path;
    }
    
    // Base ve path'i birleştir (örn: /app + /login -> /app/login)
    return rtrim($base, '/') . $path;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Note: view(), set_flash(), get_flash(), has_flash(), redirect(), csrf_token(), verify_csrf_token()
// functions are now defined in index.php to avoid duplicate definition errors

/**
 * Escape output for safe HTML display (XSS prevention)
 */
function e($value) {
    if ($value === null) {
        return '';
    }
    
    if (is_array($value)) {
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    }
    
    if (is_object($value)) {
        if (method_exists($value, '__toString')) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    }
    
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}