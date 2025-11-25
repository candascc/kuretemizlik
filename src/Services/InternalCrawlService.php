<?php
/**
 * Internal Crawl Service
 * 
 * Performs crawling without HTTP requests by directly executing routes.
 * This eliminates 403 errors, session issues, and CSRF token problems.
 * 
 * PATH_CRAWL_INTERNAL_V1: Direct route execution for zero-error crawling
 */

require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/Database.php';
require_once __DIR__ . '/../Lib/Router.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/../Config/CrawlConfig.php';
require_once __DIR__ . '/ErrorDetector.php';
require_once __DIR__ . '/CrawlLogger.php';
require_once __DIR__ . '/CrawlProgressTracker.php';
require_once __DIR__ . '/CrawlStatusManager.php';
require_once __DIR__ . '/../Exceptions/InternalHttpException.php';

class InternalCrawlService
{
    // Limits are now read from CrawlConfig
    
    /**
     * Special seed URLs (important endpoints that may not be linked from dashboard)
     */
    private static array $specialSeedUrls = [
        // '/app/performance/metrics', // Skipped: uses exit; which breaks internal execution
        '/app/health',
    ];
    
    private ?Router $router = null;
    private array $visited = [];
    private array $results = [];
    private int $startTime;
    private string $logFile;
    private string $requestId;
    private SessionManager $sessionManager;
    private ErrorDetector $errorDetector;
    private CrawlLogger $logger;
    private ?CrawlProgressTracker $progressTracker = null;
    private ?string $testId = null;
    private ?string $currentExecutingPath = null;
    private int $currentPageStartTime = 0;
    private array $problematicPaths = []; // Track paths that timeout
    private ?string $currentRole = null;
    
    /**
     * Role-based path policies (prefix/route deny lists).
     * The defaults are conservative to keep operator crawls inside günlük kullanım alanları.
     */
    private static array $rolePathPolicies = [
        'OPERATOR' => [
            'deny_prefixes' => [
                '/app/sysadmin',
                '/app/admin',
                '/app/security',
                '/app/settings',
                '/app/performance',
                '/app/monitoring',
                '/app/analytics',
                '/app/finance',
                '/app/billing',
                '/app/audit',
                '/app/devtools',
                '/app/docs',
            ],
            'deny_exact' => [
                '/app/MANUAL_TEST_CHECKLIST.md',
                '/app/UX_IMPLEMENTATION_GUIDE.md',
                '/app/DEPLOYMENT_CHECKLIST.md',
            ],
        ],
    ];
    
    public function __construct(?Router $router = null, ?string $logFile = null, ?string $testId = null)
    {
        $this->router = $router;
        $this->startTime = time();
        $this->testId = $testId;
        $this->requestId = bin2hex(random_bytes(8));
        $this->logFile = $logFile ?? (sys_get_temp_dir() . '/crawl_internal_' . date('Y-m-d_H-i-s') . '.log');
        
        // Initialize CrawlLogger FIRST (before any log() calls)
        $logLevel = getenv('CRAWL_LOG_LEVEL') ?: (defined('APP_DEBUG') && APP_DEBUG ? 'DEBUG' : 'INFO');
        $this->logger = new CrawlLogger($this->logFile, $logLevel);
        $this->requestId = $this->logger->getRequestId();
        
        // Initialize CrawlProgressTracker if testId is provided
        if ($this->testId) {
            $this->progressTracker = new CrawlProgressTracker($this->testId);
            $this->logger->log('INTERNAL_PROGRESS_TRACKER_INIT', ['testId' => $this->testId]);
        }
        
        // CRITICAL: Increase memory limit for crawl operations
        // Crawl can generate large amounts of data (HTML bodies)
        $currentMemoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($currentMemoryLimit);
        if ($memoryLimitBytes < 2048 * 1024 * 1024) { // Less than 2GB
            @ini_set('memory_limit', '2048M'); // 2GB for large crawl operations
            $this->logger->log('INTERNAL_MEMORY_LIMIT_SET', ['old' => $currentMemoryLimit, 'new' => '2048M']);
        }
        
        // CRITICAL: Increase PHP execution time limit for crawl operations
        // Crawl can take up to CrawlConfig::getMaxExecutionTime() seconds (default 60)
        // Add buffer for safety (10 seconds)
        $maxExecutionTime = CrawlConfig::getMaxExecutionTime() + 10;
        if (function_exists('set_time_limit')) {
            @set_time_limit($maxExecutionTime);
            $this->logger->log('INTERNAL_TIME_LIMIT_SET', ['limit' => $maxExecutionTime]);
        }
        
        // CRITICAL: Check if we're in a background process (after fastcgi_finish_request)
        // In background processes, we should NOT backup/restore session to avoid session hijacking
        // We detect background process by checking the CRAWL_BACKGROUND_PROCESS constant
        $isBackgroundProcess = defined('CRAWL_BACKGROUND_PROCESS') && CRAWL_BACKGROUND_PROCESS === true;
        
        // Also check if session was already closed (indicates background process)
        if (!$isBackgroundProcess && (session_status() === PHP_SESSION_NONE || !isset($_SESSION))) {
            $isBackgroundProcess = true;
        }
        
        // Only backup session if we're NOT in a background process
        // In background processes, we'll use an isolated session for the test user
        if (!$isBackgroundProcess) {
            // Use SessionManager for session backup (only in non-background processes)
            $this->sessionManager = new SessionManager();
            if ($this->sessionManager->backup()) {
                $snapshot = $this->sessionManager->getSnapshot();
                $this->logger->log('INTERNAL_SESSION_BACKUP', [
                    'user_id' => $snapshot['user_id'] ?? null,
                    'username' => $snapshot['username'] ?? null,
                    'login_time' => $snapshot['login_time'] ?? null,
                    'keys' => array_keys($snapshot)
                ]);
            }
        } else {
            // In background process, create a new isolated session
            $this->logger->log('INTERNAL_BACKGROUND_PROCESS_DETECTED', [
                'note' => 'Session backup skipped - using isolated session for test user'
            ]);
            // Close any existing session to prevent interference
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
        
        // Initialize ErrorDetector
        $this->errorDetector = new ErrorDetector();
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $memoryLimit Memory limit string (e.g., "512M", "1G")
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        if (empty($memoryLimit)) {
            return 0;
        }
        
        $unit = strtoupper(substr($memoryLimit, -1));
        if (is_numeric($unit)) {
            // No unit, assume bytes
            return (int)$memoryLimit;
        }
        
        $value = (int)substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int)$memoryLimit;
        }
    }
    
    /**
     * Restore original session (call this after crawl is done)
     * Uses SessionManager for safe restore with locking
     */
    public function restoreSession(): void
    {
        $this->log('INTERNAL_SESSION_RESTORE_START', [
            'sessionWasActive' => $this->sessionManager->wasActive(),
            'sessionStatus' => session_status(),
            'snapshotKeys' => implode(',', array_keys($this->sessionManager->getSnapshot()))
        ]);
        
        if ($this->sessionManager->restore()) {
            $snapshot = $this->sessionManager->getSnapshot();
            $restoreInfo = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'login_time' => $_SESSION['login_time'] ?? null,
                'logged_in' => $_SESSION['logged_in'] ?? false,
                'session_keys' => array_keys($_SESSION)
            ];
            $this->log('INTERNAL_SESSION_RESTORED', $restoreInfo);
        } else {
            $this->log('INTERNAL_SESSION_RESTORE_FAILED', [
                'reason' => 'SessionManager restore returned false'
            ]);
        }
    }
    
    /**
     * Get router instance (from global or create new)
     */
    private function getRouter(): Router
    {
        if ($this->router !== null) {
            return $this->router;
        }
        
        // Try to get from global
        if (isset($GLOBALS['router']) && $GLOBALS['router'] instanceof Router) {
            return $GLOBALS['router'];
        }
        
        // Create new router (will have no routes, but won't crash)
        $basePath = defined('APP_BASE') ? APP_BASE : '/app';
        return new Router($basePath);
    }
    
    // Log directory is now handled by CrawlLogger
    
    private function log(string $message, array $context = []): void
    {
        // Use CrawlLogger for centralized logging
        $this->logger->log($message, $context);
    }
    
    /**
     * Login user directly (no HTTP request)
     * This temporarily replaces the current session with the test user's session
     */
    public function login(string $username, string $password): bool
    {
        $this->log('INTERNAL_LOGIN_START', ['username' => $username]);
        
        try {
            if (!defined('KUREAPP_INTERNAL_REQUEST')) {
                define('KUREAPP_INTERNAL_REQUEST', true);
            }
            // Direct database login (bypass HTTP)
            $db = Database::getInstance();
            $user = $db->fetch(
                "SELECT * FROM users WHERE username = ? AND is_active = 1",
                [$username]
            );
            
            if (!$user) {
                // Check if user exists but is inactive
                $inactiveUser = $db->fetch(
                    "SELECT * FROM users WHERE username = ?",
                    [$username]
                );
                if ($inactiveUser) {
                    $this->log('INTERNAL_LOGIN_ERROR', [
                        'username' => $username, 
                        'reason' => 'User found but is inactive',
                        'is_active' => $inactiveUser['is_active'] ?? 'unknown'
                    ]);
                } else {
                    $this->log('INTERNAL_LOGIN_ERROR', [
                        'username' => $username, 
                        'reason' => 'User not found in database'
                    ]);
                }
                return false;
            }
            
            // Check if password hash exists
            if (empty($user['password_hash'])) {
                $this->log('INTERNAL_LOGIN_ERROR', [
                    'username' => $username, 
                    'reason' => 'User has no password hash'
                ]);
                return false;
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                $this->log('INTERNAL_LOGIN_ERROR', [
                    'username' => $username, 
                    'reason' => 'Invalid password',
                    'has_hash' => !empty($user['password_hash'])
                ]);
                return false;
            }
            
            // CRITICAL: Check if we're in a background process
            // In background processes, we need to use an isolated session to prevent session hijacking
            $isBackgroundProcess = defined('CRAWL_BACKGROUND_PROCESS') && CRAWL_BACKGROUND_PROCESS === true;
            
            // Also check if session was already closed (indicates background process)
            if (!$isBackgroundProcess && (session_status() === PHP_SESSION_NONE || !isset($_SESSION))) {
                $isBackgroundProcess = true;
            }
            
            if ($isBackgroundProcess) {
                // In background process: Use isolated session (without sending cookies)
                // Close any existing session first
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                
                // Clear in-memory session data to prevent leaking user context
                $_SESSION = [];
                
                // Use a temporary session save path to ensure isolation
                $isolatedSessionPath = sys_get_temp_dir() . '/crawl_sessions_' . getmypid();
                if (!is_dir($isolatedSessionPath)) {
                    @mkdir($isolatedSessionPath, 0700, true);
                }
                ini_set('session.save_path', $isolatedSessionPath);
                
                // Disable cookie + cache headers since response was already sent
                ini_set('session.use_cookies', '0');
                ini_set('session.use_only_cookies', '0');
                ini_set('session.use_trans_sid', '0');
                ini_set('session.cache_limiter', '');
                @session_cache_limiter('');
                
                // Attempt to switch to a dedicated session ID only if headers are still modifiable
                if (!headers_sent()) {
                    $isolatedSessionId = 'crawl' . bin2hex(random_bytes(16));
                    session_id($isolatedSessionId);
                } else {
                    $this->log('INTERNAL_LOGIN_ISOLATED_SESSION_ID_SKIP', [
                        'username' => $username,
                        'reason' => 'Headers already sent - keeping existing session ID',
                        'existing_session_id' => session_id()
                    ]);
                }
                
                // Start isolated session
                session_start();
                
                $this->log('INTERNAL_LOGIN_ISOLATED_SESSION', [
                    'username' => $username,
                    'session_id' => session_id(),
                    'session_path' => $isolatedSessionPath,
                    'headers_sent' => headers_sent()
                ]);
            } else {
                // In normal process: Use existing session (session already backed up)
                SessionHelper::ensureStarted();
            }
            
            // Set test user's session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time(); // Required by Auth::check()
            $this->currentRole = isset($user['role']) ? strtoupper((string)$user['role']) : null;
            
            // CRITICAL: In background process, don't close session - keep it open
            // Session write/close can cause data loss in background processes
            if ($isBackgroundProcess) {
                // Just ensure session is active and data is set
                // Don't close/reopen as it can lose session data
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
            } else {
                // In normal process, write and reopen session
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                    SessionHelper::ensureStarted();
                }
            }
            
            // Verify session data is actually set after write/reopen
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
                $this->log('INTERNAL_LOGIN_ERROR', [
                    'username' => $username,
                    'reason' => 'Session data lost after write',
                    'has_user_id' => isset($_SESSION['user_id']),
                    'has_login_time' => isset($_SESSION['login_time']),
                    'session_status' => session_status()
                ]);
                return false;
            }
            
            // Verify Auth::check() will work by checking session data
            $authCheckWillPass = isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
            if (!$authCheckWillPass) {
                $this->log('INTERNAL_LOGIN_ERROR', [
                    'username' => $username,
                    'reason' => 'Session data validation failed - Auth::check() will fail',
                    'session_keys' => array_keys($_SESSION ?? [])
                ]);
                return false;
            }
            
            // Don't update last_login_at for test users during crawl (to avoid polluting logs)
            // This is an internal crawl, not a real login
            
            $this->log('INTERNAL_LOGIN_SUCCESS', [
                'username' => $username, 
                'user_id' => $user['id'],
                'session_id' => session_id(),
                'session_status' => session_status(),
                'has_user_id' => isset($_SESSION['user_id']),
                'has_login_time' => isset($_SESSION['login_time'])
            ]);
            return true;
            
        } catch (Throwable $e) {
            $this->log('INTERNAL_LOGIN_EXCEPTION', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Execute a route internally and capture output
     */
    private function executeRoute(string $path): array
    {
        // CRITICAL: Normalize path using the same method as queue processing
        // This ensures visited tracking is consistent
        $normalizedPath = $this->normalizePath($path);
        
        // Check if this path is known to be problematic (has timed out before)
        if (isset($this->problematicPaths[$normalizedPath])) {
            $timeoutCount = $this->problematicPaths[$normalizedPath];
            if ($timeoutCount >= 2) {
                // Skip paths that have timed out 2+ times
                $this->log('INTERNAL_ROUTE_SKIP_PROBLEMATIC', [
                    'path' => $normalizedPath,
                    'timeout_count' => $timeoutCount
                ]);
                return [
                    'url' => $path,
                    'status' => 504,
                    'body' => '',
                    'has_marker' => false,
                    'error_flag' => true,
                    'body_length' => 0,
                    'note' => 'Skipped - known problematic path (timeout ' . $timeoutCount . ' times)',
                ];
            }
        }
        
        // Check if already visited (double-check to prevent race conditions)
        if (isset($this->visited[$normalizedPath])) {
            $this->log('INTERNAL_ROUTE_ALREADY_VISITED', ['path' => $path, 'normalized' => $normalizedPath]);
            return [
                'url' => $path,
                'status' => 200,
                'body' => '',
                'has_marker' => false,
                'error_flag' => false,
                'body_length' => 0,
                'note' => 'Already visited',
            ];
        }
        
        // Mark as visited immediately to prevent concurrent processing
        $this->visited[$normalizedPath] = true;
        
        // Check timeout
        if (time() - $this->startTime > CrawlConfig::getMaxExecutionTime()) {
            return [
                'url' => $path,
                'status' => 0,
                'body' => '',
                'has_marker' => false,
                'error_flag' => true,
                'body_length' => 0,
                'note' => 'Timeout',
            ];
        }
        
        // Check max URLs
        if (count($this->visited) > CrawlConfig::getMaxUrls()) {
            return [
                'url' => $path,
                'status' => 0,
                'body' => '',
                'has_marker' => false,
                'error_flag' => true,
                'body_length' => 0,
                'note' => 'Max URLs reached',
            ];
        }
        
        $note = '';
        
        // Save current output buffer state
        $obLevel = ob_get_level();
        $body = '';
        $routeExecuted = false;
        
        try {
            // CRITICAL: Define internal request flag BEFORE any route execution
            // This allows controllers to detect internal requests and skip header manipulation
            if (!defined('KUREAPP_INTERNAL_REQUEST')) {
                define('KUREAPP_INTERNAL_REQUEST', true);
            }
            
            // Start new output buffer
            ob_start();
            
            // Save current $_SERVER state
            $originalServer = $_SERVER;
            
            // Set per-page timeout - if page takes too long, skip it
            $pageStartTime = time();
            $this->currentPageStartTime = $pageStartTime;
            $this->currentExecutingPath = $normalizedPath;
            $maxTimePerPage = CrawlConfig::getMaxTimePerPage();
            $originalTimeLimit = ini_get('max_execution_time');
            
            // Set a time limit for this page execution
            if (function_exists('set_time_limit')) {
                @set_time_limit($maxTimePerPage);
            }
            
            // Register shutdown function to catch fatal errors (timeout)
            $self = $this;
            $shutdownRegistered = false;
            $shutdownHandler = function() use ($self, $normalizedPath, $maxTimePerPage, &$shutdownRegistered) {
                if ($shutdownRegistered) {
                    return; // Already handled
                }
                $shutdownRegistered = true;
                
                $error = error_get_last();
                if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
                    // Fatal error occurred - might be timeout
                    if ($self->currentExecutingPath === $normalizedPath) {
                        $elapsed = time() - $self->currentPageStartTime;
                        if ($elapsed > $maxTimePerPage) {
                            // This was likely a timeout
                            $self->log('INTERNAL_ROUTE_FATAL_TIMEOUT', [
                                'path' => $normalizedPath,
                                'elapsed' => $elapsed,
                                'max_time' => $maxTimePerPage,
                                'error' => $error['message'] ?? 'Fatal error'
                            ]);
                        }
                    }
                }
            };
            register_shutdown_function($shutdownHandler);
            
            try {
                // Simulate request
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = $normalizedPath;
                $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'off';
                
                // Execute route (internal request - bypass CSRF and rate limiting)
                $router = $this->getRouter();
                
                // Router's normalizePath will strip basePath (/app) from the path
                // So /app/ becomes /, /app/dashboard becomes /dashboard, etc.
                $routeExecuted = $router->run('GET', $normalizedPath, true);
                
                // Clear shutdown handler since we completed successfully
                $this->currentExecutingPath = null;
                
                // Check if page took too long
                $pageElapsed = time() - $pageStartTime;
                if ($pageElapsed > $maxTimePerPage) {
                    // Mark as problematic
                    $this->problematicPaths[$normalizedPath] = ($this->problematicPaths[$normalizedPath] ?? 0) + 1;
                    
                    $this->log('INTERNAL_ROUTE_TIMEOUT_WARNING', [
                        'path' => $normalizedPath,
                        'elapsed' => $pageElapsed,
                        'max_time' => $maxTimePerPage,
                        'timeout_count' => $this->problematicPaths[$normalizedPath]
                    ]);
                }
                
                // Restore original time limit
                if (function_exists('set_time_limit') && $originalTimeLimit !== false) {
                    @set_time_limit((int)$originalTimeLimit);
                }
                
                // Capture output - get content but keep buffer active if needed
                $body = '';
                if (ob_get_level() > $obLevel) {
                    $body = ob_get_clean();
                }
                
                // Log for debugging
                $this->log('INTERNAL_ROUTE_EXECUTED', [
                    'path' => $normalizedPath,
                    'executed' => $routeExecuted,
                    'body_length' => strlen($body),
                    'has_marker' => strpos($body, 'GLOBAL_R50_MARKER_1') !== false,
                    'first_100_chars' => substr($body, 0, 100)
                ]);
                
            } catch (Throwable $e) {
                // Clear shutdown handler
                $this->currentExecutingPath = null;
                
                // Restore original time limit on exception
                if (function_exists('set_time_limit') && isset($originalTimeLimit) && $originalTimeLimit !== false) {
                    @set_time_limit((int)$originalTimeLimit);
                }
                
                // Capture any output before exception
                $body = '';
                if (ob_get_level() > $obLevel) {
                    $body = ob_get_clean();
                }
                
                // Check if this was a timeout
                $pageElapsed = time() - ($pageStartTime ?? time());
                if ($pageElapsed > $maxTimePerPage) {
                    // Mark as problematic
                    $this->problematicPaths[$normalizedPath] = ($this->problematicPaths[$normalizedPath] ?? 0) + 1;
                    
                    $this->log('INTERNAL_ROUTE_TIMEOUT_EXCEPTION', [
                        'path' => $normalizedPath,
                        'elapsed' => $pageElapsed,
                        'max_time' => $maxTimePerPage,
                        'error' => $e->getMessage(),
                        'timeout_count' => $this->problematicPaths[$normalizedPath]
                    ]);
                    // Return timeout result instead of throwing
                    return [
                        'url' => $path,
                        'status' => 504,
                        'body' => '',
                        'has_marker' => false,
                        'error_flag' => true,
                        'body_length' => 0,
                        'note' => "Page timeout (>{$maxTimePerPage}s) - skipped",
                    ];
                }
                
                if ($e instanceof InternalHttpException) {
                    $status = $e->getStatusCode();
                    $responseBody = $e->getResponseBody() ?? $body ?? '';
                    $hasMarker = strpos($responseBody, 'GLOBAL_R50_MARKER_1') !== false;
                    $bodyLength = strlen($responseBody);
                    
                    $this->log('INTERNAL_ROUTE_FORBIDDEN', [
                        'path' => $normalizedPath,
                        'status' => $status,
                        'message' => $e->getMessage(),
                        'body_length' => $bodyLength,
                    ]);
                    
                    // Return graceful result without propagating exception
                    return [
                        'url' => $path,
                        'status' => $status,
                        'body' => '',
                        'has_marker' => $hasMarker,
                        'error_flag' => $status >= 400,
                        'body_length' => $bodyLength,
                        'note' => 'Internal HTTP ' . $status . ' - ' . $e->getMessage(),
                    ];
                }
                
                throw $e; // Re-throw to outer catch
            } finally {
                // Clear shutdown handler
                $this->currentExecutingPath = null;
                // Restore original time limit
                if (function_exists('set_time_limit') && isset($originalTimeLimit) && $originalTimeLimit !== false) {
                    @set_time_limit((int)$originalTimeLimit);
                }
                // Always restore output buffer state, even on exception
                while (ob_get_level() > $obLevel) {
                    ob_end_clean();
                }
                
                // Restore $_SERVER
                $_SERVER = $originalServer;
            }
            
            if (!$routeExecuted) {
                // Use ErrorDetector for 404 detection
                $note = $this->errorDetector->detectError($body, 404);
                $hasError = ($note !== null);
                if (!$hasError) {
                    $note = 'Route not found';
                    $hasError = true; // 404 is always an error
                }
                // Don't store 404 body either
                $bodyLength = strlen($body);
                $hasMarker = strpos($body, 'GLOBAL_R50_MARKER_1') !== false;
                
                // For 404, we don't need body for link extraction, so clear it immediately
                unset($body);
                
                return [
                    'url' => $path,
                    'status' => 404,
                    'body' => '', // Don't store body to save memory
                    'has_marker' => $hasMarker,
                    'error_flag' => $hasError,
                    'body_length' => $bodyLength,
                    'note' => $note,
                ];
            }
            
            // Use ErrorDetector for error detection
            $note = $this->errorDetector->detectError($body, 200);
            $hasError = ($note !== null);
            
            // CRITICAL: Don't store body in results to save memory
            // We only need body for link extraction, which is done before storing result
            // Extract marker info before discarding body
            $hasMarker = strpos($body, 'GLOBAL_R50_MARKER_1') !== false;
            $bodyLength = strlen($body);
            
            // Store body temporarily for link extraction (will be cleared after extraction)
            // Use _temp_body key so it can be easily removed from result
            $result = [
                'url' => $path,
                'status' => 200,
                'body' => '', // Don't store body in final result
                'has_marker' => $hasMarker,
                'error_flag' => $hasError,
                'body_length' => $bodyLength,
                'note' => $note,
                '_temp_body' => $body, // Temporary - will be removed after link extraction
            ];
            
            // Note: body will be cleared in run() method after link extraction
            
            return $result;
            
        } catch (Throwable $e) {
            $this->log('INTERNAL_ROUTE_EXCEPTION', [
                'path' => $path,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return [
                'url' => $path,
                'status' => 500,
                'body' => '',
                'has_marker' => false,
                'error_flag' => true,
                'body_length' => 0,
                'note' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Extract links from HTML body
     */
    private function extractLinks(string $body, string $basePath): array
    {
        $links = [];
        $rawHrefs = [];
        
        // Extract <a href="..."> - improved regex to handle various formats
        // Matches: href="...", href='...', href=... (without quotes)
        if (preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $body, $matches)) {
            $rawHrefs = array_merge($rawHrefs, $matches[1]);
        }
        
        // Also try without quotes (less common but possible)
        if (preg_match_all('/<a[^>]+href\s*=\s*([^\s>]+)[^>]*>/i', $body, $matches)) {
            foreach ($matches[1] as $href) {
                // Skip if it looks like a quoted value we already captured
                if (!in_array($href, $rawHrefs)) {
                    $rawHrefs[] = $href;
                }
            }
        }
        
        // Also extract links from navigation menus and other common patterns
        // Look for data-href, data-url, etc.
        if (preg_match_all('/(?:data-href|data-url|data-link)\s*=\s*["\']([^"\']+)["\']/i', $body, $matches)) {
            $rawHrefs = array_merge($rawHrefs, $matches[1]);
        }
        
        $this->log('INTERNAL_CRAWL_EXTRACT_RAW', [
            'basePath' => $basePath,
            'raw_hrefs_count' => count($rawHrefs),
            'sample_hrefs' => array_slice($rawHrefs, 0, 10)
        ]);
        
        foreach ($rawHrefs as $href) {
            $cleanHref = $this->sanitizeHref($href);
            if ($cleanHref === '') {
                $this->log('INTERNAL_CRAWL_LINK_SKIP_FILTERED', ['href' => $href, 'reason' => 'empty_after_sanitize', 'basePath' => $basePath]);
                continue;
            }
            $normalized = $this->normalizeLink($cleanHref, $basePath);
            if ($normalized) {
                // normalizeLink() already returns normalized path with /app prefix
                // No need to call normalizePath() again
                
                // CRITICAL: Skip logout and other destructive URLs
                if (strpos($normalized, '/logout') !== false || 
                    strpos($normalized, '/delete') !== false ||
                    strpos($normalized, '/destroy') !== false) {
                    $this->log('INTERNAL_CRAWL_LINK_SKIP_DESTRUCTIVE', ['href' => $href, 'normalized' => $normalized]);
                    continue;
                }
                
                // Check if already visited using the normalized path
                if (!isset($this->visited[$normalized])) {
                    $links[] = $normalized;
                } else {
                    $this->log('INTERNAL_CRAWL_LINK_SKIP_VISITED', ['href' => $href, 'normalized' => $normalized]);
                }
            } else {
                $this->log('INTERNAL_CRAWL_LINK_SKIP_FILTERED', ['href' => $cleanHref, 'basePath' => $basePath]);
            }
        }
        
        $uniqueLinks = array_unique($links);
        $this->log('INTERNAL_CRAWL_EXTRACT_RESULT', [
            'basePath' => $basePath,
            'unique_links_count' => count($uniqueLinks),
            'links' => array_slice($uniqueLinks, 0, 20)
        ]);
        
        return $uniqueLinks;
    }
    
    /**
     * Normalize escaped href values before processing
     */
    private function sanitizeHref(string $href): string
    {
        if ($href === '') {
            return '';
        }
        
        // Replace common escape sequences
        $href = str_replace(['\\"', "\\'", '\\/'], ['"', "'", '/'], $href);
        
        // Trim whitespace and surrounding quotes
        $href = trim($href);
        $href = preg_replace('/^["\']+/', '', $href);
        $href = preg_replace('/["\']+$/', '', $href);
        
        // Remove any lingering escaped quotes at the beginning
        while (strpos($href, '"') === 0 || strpos($href, "'") === 0) {
            $href = substr($href, 1);
        }
        
        return trim($href);
    }
    
    /**
     * Normalize link to absolute path
     */
    private function normalizeLink(string $href, string $basePath): ?string
    {
        // Remove fragments and query strings
        $href = preg_replace('/[#?].*$/', '', $href);
        
        // Skip external links
        if (preg_match('#^https?://#i', $href)) {
            return null;
        }
        
        // Skip javascript:, mailto:, etc.
        if (preg_match('/^(javascript|mailto|tel|#):/i', $href)) {
            return null;
        }
        
        // Skip empty hrefs
        if (empty($href) || trim($href) === '') {
            return null;
        }
        
        // Convert to absolute path
        if ($href[0] === '/') {
            // Absolute path - use as is
            $path = $href;
        } else {
            // Relative path - resolve against basePath
            // basePath is like "/app/" or "/app/dashboard"
            // Remove /app prefix temporarily for resolution
            $basePathWithoutApp = $basePath;
            if (strpos($basePathWithoutApp, '/app') === 0) {
                $basePathWithoutApp = substr($basePathWithoutApp, 4); // Remove "/app"
            }
            if ($basePathWithoutApp === '') {
                $basePathWithoutApp = '/';
            }
            
            $baseDir = dirname($basePathWithoutApp);
            if ($baseDir === '/' || $baseDir === '.' || $baseDir === '') {
                $baseDir = '/';
            }
            
            // Resolve relative path
            $resolvedPath = rtrim($baseDir, '/') . '/' . ltrim($href, '/');
            
            // Normalize .. and . in path
            $pathParts = explode('/', $resolvedPath);
            $resolvedParts = [];
            foreach ($pathParts as $part) {
                if ($part === '..') {
                    if (!empty($resolvedParts)) {
                        array_pop($resolvedParts);
                    }
                } elseif ($part !== '.' && $part !== '') {
                    $resolvedParts[] = $part;
                }
            }
            $path = '/' . implode('/', $resolvedParts);
        }
        
        // Normalize using normalizePath (this will add /app prefix if needed)
        $normalized = $this->normalizePath($path);
        
        // Only return /app routes
        if (strpos($normalized, '/app') === 0) {
            return $normalized;
        }
        
        return null;
    }
    
    /**
     * Normalize path
     * CRITICAL: This method must produce consistent results for the same input
     * Used for visited tracking to prevent infinite loops
     * 
     * Note: Router's normalizePath strips /app prefix internally, but we keep it
     * in visited tracking for consistency with queue items
     */
    private function normalizePath(string $path): string
    {
        // Remove query string and fragment
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        
        // Ensure path starts with /
        $path = '/' . ltrim($path, '/');
        
        // Remove trailing slash (except for root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // Ensure /app prefix for consistency (but don't double-add it)
        if ($path === '/') {
            return '/app/';
        }
        
        if (strpos($path, '/app') !== 0) {
            $path = '/app' . $path;
        }
        
        return $path;
    }

    private function isPathAllowedForCurrentRole(string $normalizedPath): bool
    {
        if (empty($this->currentRole)) {
            return true;
        }
        
        $role = strtoupper($this->currentRole);
        $rules = self::$rolePathPolicies[$role] ?? null;
        if (!$rules) {
            return true;
        }
        
        $candidate = rtrim($normalizedPath, '/');
        if ($candidate === '') {
            $candidate = '/';
        }
        
        foreach ($rules['deny_exact'] ?? [] as $exact) {
            $exactNormalized = rtrim($exact, '/');
            if ($exactNormalized === '') {
                $exactNormalized = '/';
            }
            if ($candidate === $exactNormalized) {
                return false;
            }
        }
        
        foreach ($rules['deny_prefixes'] ?? [] as $prefix) {
            $prefixNormalized = rtrim($prefix, '/');
            if ($prefixNormalized === '') {
                continue;
            }
            if (strpos($candidate, $prefixNormalized) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Run crawl
     */
    public function run(string $username, string $password): array
    {
        $this->log('INTERNAL_CRAWL_START', ['username' => $username]);
        
        // CRITICAL: Ensure execution time limit is set
        $maxExecutionTime = CrawlConfig::getMaxExecutionTime() + 10;
        if (function_exists('set_time_limit')) {
            @set_time_limit($maxExecutionTime);
        }
        
        // Login
        if (!$this->login($username, $password)) {
            return [
                'base_url' => '/app',
                'username' => $username,
                'total_count' => 0,
                'success_count' => 0,
                'error_count' => 1,
                'items' => [],
                'error' => 'Login failed',
            ];
        }
        
        // Seed URLs - normalize all seed URLs before adding to queue
        // CRITICAL: Don't mark as visited yet - let executeRoute() handle it to ensure proper processing
        $queue = [];
        $seedUrls = ['/app/'] + self::$specialSeedUrls;
        foreach ($seedUrls as $seedUrl) {
            $normalizedSeed = $this->normalizePath($seedUrl);
            if (!$this->isPathAllowedForCurrentRole($normalizedSeed)) {
                $this->log('INTERNAL_CRAWL_ROLE_SKIP', [
                    'path' => $normalizedSeed,
                    'reason' => 'seed_deny',
                    'role' => $this->currentRole
                ]);
                continue;
            }
            if (!isset($this->visited[$normalizedSeed])) {
                $queue[] = [$seedUrl, 0];
                // Don't mark as visited here - executeRoute() will mark it after processing
            }
        }
        
        // Safety counter to prevent infinite loops
        $loopCount = 0;
        $maxLoops = CrawlConfig::getMaxUrls() * 2; // Safety limit
        
        // Crawl loop
        while (!empty($queue) && count($this->visited) < CrawlConfig::getMaxUrls() && $loopCount < $maxLoops) {
            $loopCount++;
            
            // Check timeout
            if (time() - $this->startTime > CrawlConfig::getMaxExecutionTime()) {
                $this->log('INTERNAL_CRAWL_TIMEOUT', ['visited' => count($this->visited), 'loop_count' => $loopCount]);
                break;
            }
            
            // Check if crawl was cancelled (by checking lock status)
            if ($this->testId && class_exists('CrawlStatusManager')) {
                try {
                    $statusManager = new CrawlStatusManager();
                    $lockStatus = $statusManager->getStatus($this->testId);
                    if ($lockStatus && ($lockStatus['status'] === 'failed' || $lockStatus['status'] === 'cancelled')) {
                        $this->log('INTERNAL_CRAWL_CANCELLED', ['testId' => $this->testId, 'visited' => count($this->visited)]);
                        break;
                    }
                } catch (Throwable $e) {
                    // Ignore errors when checking lock status
                    $this->log('INTERNAL_CRAWL_LOCK_CHECK_ERROR', ['error' => $e->getMessage()]);
                }
            }
            
            [$currentPath, $depth] = array_shift($queue);
            
            if ($depth > CrawlConfig::getMaxDepth()) {
                continue;
            }
            
            $this->log('INTERNAL_CRAWL_VISIT', ['path' => $currentPath, 'depth' => $depth, 'loop' => $loopCount]);
            
            // Update progress tracker BEFORE executing route (so UI shows we're working on this page)
            // Note: We don't update items here because result hasn't been added yet
            if ($this->progressTracker) {
                $successCount = count(array_filter($this->results, fn($r) => !($r['error_flag'] ?? false)));
                $errorCount = count(array_filter($this->results, fn($r) => ($r['error_flag'] ?? false)));
                $current = count($this->results);
                $total = min(count($this->results) + count($queue) + 1, CrawlConfig::getMaxUrls());
                
                // Update without items (will preserve existing items)
                $this->progressTracker->update(
                    $current,
                    $total,
                    $currentPath,
                    $successCount,
                    $errorCount,
                    [] // Empty items - will preserve existing
                );
            }
            
            // Execute route with timeout protection
            // If route takes too long, it will be skipped
            $routeStartTime = time();
            $maxTimePerPage = CrawlConfig::getMaxTimePerPage();
            
            try {
                $result = $this->executeRoute($currentPath);
                
                // Check if route took too long (even if it completed)
                $routeElapsed = time() - $routeStartTime;
                if ($routeElapsed > $maxTimePerPage) {
                    // Mark as problematic
                    $normalizedPath = $this->normalizePath($currentPath);
                    $this->problematicPaths[$normalizedPath] = ($this->problematicPaths[$normalizedPath] ?? 0) + 1;
                    
                    $this->log('INTERNAL_CRAWL_ROUTE_SLOW', [
                        'path' => $currentPath,
                        'elapsed' => $routeElapsed,
                        'max_time' => $maxTimePerPage,
                        'timeout_count' => $this->problematicPaths[$normalizedPath]
                    ]);
                }
            } catch (Throwable $e) {
                // If route execution throws, check if it was a timeout
                $routeElapsed = time() - $routeStartTime;
                if ($routeElapsed > $maxTimePerPage) {
                    // Mark as problematic
                    $normalizedPath = $this->normalizePath($currentPath);
                    $this->problematicPaths[$normalizedPath] = ($this->problematicPaths[$normalizedPath] ?? 0) + 1;
                    
                    $this->log('INTERNAL_CRAWL_ROUTE_TIMEOUT', [
                        'path' => $currentPath,
                        'elapsed' => $routeElapsed,
                        'max_time' => $maxTimePerPage,
                        'error' => $e->getMessage(),
                        'timeout_count' => $this->problematicPaths[$normalizedPath]
                    ]);
                    // Return timeout result
                    $result = [
                        'url' => $currentPath,
                        'status' => 504,
                        'body' => '',
                        'has_marker' => false,
                        'error_flag' => true,
                        'body_length' => 0,
                        'note' => "Route timeout (>{$maxTimePerPage}s) - skipped",
                    ];
                } else {
                    // Re-throw if not a timeout
                    throw $e;
                }
            }
            
            // Get body temporarily for link extraction (before storing result)
            // executeRoute() returns body in a temporary variable for extraction
            $bodyForExtraction = $result['_temp_body'] ?? '';
            unset($result['_temp_body']); // Remove temp body from result
            
            // Store result (without body)
            $this->results[] = $result;
            
            // Update progress tracker AFTER executing route (with items list)
            if ($this->progressTracker) {
                $successCount = count(array_filter($this->results, fn($r) => !($r['error_flag'] ?? false)));
                $errorCount = count(array_filter($this->results, fn($r) => ($r['error_flag'] ?? false)));
                $current = count($this->results);
                $total = min(count($this->results) + count($queue), CrawlConfig::getMaxUrls());
                
                // Update with items list (limited to last 50 items to prevent large files)
                $this->progressTracker->update(
                    $current,
                    $total,
                    $currentPath,
                    $successCount,
                    $errorCount,
                    $this->results // Include all results (will be limited to last 50 in update method)
                );
            }
            
            // Extract links if successful (even if error_flag is set, try to extract links)
            // This allows crawling to continue even if a page has minor issues
            if ($result['status'] === 200 && $depth < CrawlConfig::getMaxDepth() && !empty($bodyForExtraction)) {
                $links = $this->extractLinks($bodyForExtraction, $currentPath);
                // Clear body from memory immediately after extraction
                unset($bodyForExtraction);
                
                $this->log('INTERNAL_CRAWL_LINKS_EXTRACTED', [
                    'path' => $currentPath,
                    'links_found' => count($links),
                    'links' => array_slice($links, 0, 10) // Log first 10 links
                ]);
                
                if (empty($links)) {
                    $this->log('INTERNAL_CRAWL_NO_LINKS', [
                        'path' => $currentPath,
                        'body_length' => $result['body_length'] ?? 0
                    ]);
                }
                
                foreach ($links as $link) {
                    // CRITICAL: Use same normalization as executeRoute to prevent duplicates
                    $normalizedLink = $this->normalizePath($link);
                    if (!$this->isPathAllowedForCurrentRole($normalizedLink)) {
                        $this->log('INTERNAL_CRAWL_ROLE_SKIP', [
                            'path' => $normalizedLink,
                            'source' => $currentPath,
                            'role' => $this->currentRole
                        ]);
                        continue;
                    }
                    if (!isset($this->visited[$normalizedLink])) {
                        // Don't mark as visited here - let executeRoute() handle it
                        $queue[] = [$link, $depth + 1];
                        $this->log('INTERNAL_CRAWL_QUEUE_ADD', ['link' => $link, 'normalized' => $normalizedLink, 'depth' => $depth + 1]);
                    } else {
                        $this->log('INTERNAL_CRAWL_QUEUE_SKIP_VISITED', ['link' => $link, 'normalized' => $normalizedLink]);
                    }
                }
            } else {
                // Clear body if not used
                unset($bodyForExtraction);
                
                $this->log('INTERNAL_CRAWL_SKIP_LINKS', [
                    'path' => $currentPath,
                    'status' => $result['status'],
                    'error_flag' => $result['error_flag'] ?? false,
                    'depth' => $depth,
                    'body_length' => $result['body_length'] ?? 0
                ]);
            }
        }
        
        if ($loopCount >= $maxLoops) {
            $this->log('INTERNAL_CRAWL_MAX_LOOPS_REACHED', [
                'visited' => count($this->visited),
                'loop_count' => $loopCount,
                'max_loops' => $maxLoops
            ]);
        }
        
        // Calculate statistics
        $successCount = 0;
        $errorCount = 0;
        foreach ($this->results as $result) {
            if ($result['error_flag'] || $result['status'] >= 400) {
                $errorCount++;
            } else {
                $successCount++;
            }
        }
        
        $this->log('INTERNAL_CRAWL_COMPLETE', [
            'total' => count($this->results),
            'success' => $successCount,
            'error' => $errorCount,
        ]);
        
        // CRITICAL: Only restore session if we're NOT in a background process
        // In background processes, restoring session would cause session hijacking
        $isBackgroundProcess = defined('CRAWL_BACKGROUND_PROCESS') && CRAWL_BACKGROUND_PROCESS === true;
        
        // Also check if session was already closed (indicates background process)
        if (!$isBackgroundProcess && (session_status() === PHP_SESSION_NONE || !isset($_SESSION))) {
            $isBackgroundProcess = true;
        }
        
        if (!$isBackgroundProcess && $this->sessionManager) {
            // Restore original session before returning (only in non-background processes)
            $snapshot = $this->sessionManager->getSnapshot();
            $this->log('INTERNAL_CRAWL_BEFORE_RESTORE', [
                'sessionWasActive' => $this->sessionManager->wasActive(),
                'snapshotKeys' => implode(',', array_keys($snapshot)),
                'snapshotCount' => count($snapshot)
            ]);
            $this->restoreSession();
            $this->log('INTERNAL_CRAWL_AFTER_RESTORE', [
                'sessionStatus' => session_status(),
                'currentUser_id' => $_SESSION['user_id'] ?? null,
                'currentUsername' => $_SESSION['username'] ?? null
            ]);
        } else {
            // In background process: Just close the isolated session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            $this->log('INTERNAL_CRAWL_SKIP_RESTORE', [
                'reason' => 'Background process - session restore skipped to prevent session hijacking'
            ]);
        }
        
        return [
            'base_url' => '/app',
            'username' => $username,
            'total_count' => count($this->results),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'items' => $this->results,
        ];
    }
}

