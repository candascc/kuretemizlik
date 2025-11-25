<?php
/**
 * Basit HTTP router.
 */

class Router
{
    /** @var array<int, array<string, mixed>> */
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * @param string|string[] $methods
     * @param callable|array|string $handler
     */
    public function add($methods, string $path, $handler, array $options = []): self
    {
        $methods = array_map('strtoupper', (array) $methods);
        $compiled = $this->compilePath($path);
        $middlewares = $options['middlewares'] ?? [];
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        $middlewares = array_values(array_filter($middlewares, static function ($middleware) {
            return $middleware !== null;
        }));

        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => $method,
                'regex' => $compiled['regex'],
                'parameters' => $compiled['parameters'],
                'handler' => $handler,
                'middlewares' => $middlewares,
            ];
        }

        return $this;
    }

    public function get(string $path, $handler, array $options = []): self
    {
        return $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, $handler, array $options = []): self
    {
        return $this->add('POST', $path, $handler, $options);
    }

    public function map(array $methods, string $path, $handler, array $options = []): self
    {
        return $this->add($methods, $path, $handler, $options);
    }

    public function run(string $method, string $uri, bool $internalRequest = false): bool
    {
        $match = $this->dispatch($method, $uri);
        if ($match === null) {
            return false;
        }

        // Skip CSRF and rate limiting for internal requests
        if (!$internalRequest) {
        // Global CSRF enforcement for POST requests (exclude API routes)
        if (strtoupper($method) === 'POST') {
            $path = $this->normalizePath($uri);
            $isApi = (strpos($path, '/api/') === 0) || (strpos($path, '/api') === 0);
            $csrfExemptPaths = [
                '/login',
                '/forgot-password',
                '/reset-password',
                '/two-factor/verify',
                '/two-factor/process-login',
                // Resident portal login paths (CSRF handled in controller if needed)
                '/resident/login',
                '/resident/login/password',
                '/resident/login/otp',
                '/resident/login/resend',
                '/resident/login/set-password',
                '/resident/login/forgot',
                '/resident/login/cancel',
            ];

            if (!$isApi && !in_array($path, $csrfExemptPaths, true) && class_exists('CSRF')) {
                if (!\CSRF::verifyRequest()) {
                    // ===== PRODUCTION FIX: Return user-friendly error =====
                    // Check if this is an AJAX request
                    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    
                    // CRITICAL: Clear output buffer BEFORE any output
                    // This prevents any accidental output from polluting the JSON response
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    // Prepare logging data (but don't log yet - log after response is sent)
                    $logData = [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'referer' => $_SERVER['HTTP_REFERER'] ?? 'n/a',
                        'session_id' => session_id() ?: 'NO_SESSION',
                        'has_session' => session_status() === PHP_SESSION_ACTIVE,
                        'cookie_params' => session_get_cookie_params(),
                        'has_cookie' => isset($_COOKIE[session_name()]),
                    ];
                    
                    if ($isAjax) {
                        // Prepare response first
                        $response = json_encode([
                            'success' => false,
                            'error' => 'CSRF token validation failed',
                            'debug' => defined('APP_DEBUG') && APP_DEBUG ? [
                                'session_id' => $logData['session_id'],
                                'has_session' => $logData['has_session'],
                                'has_cookie' => $logData['has_cookie'],
                                'cookie_path' => $logData['cookie_params']['path'] ?? 'UNKNOWN'
                            ] : null
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        
                        // Send response
                        header('Content-Type: application/json; charset=UTF-8');
                        http_response_code(403);
                        echo $response;
                        
                        // CRITICAL: Log AFTER sending response but before exit
                        // Use register_shutdown_function to ensure it happens after response is fully sent
                        register_shutdown_function(function() use ($logData, $method, $path) {
                            $logMessage = sprintf(
                                '[router] CSRF validation failed method=%s path=%s ip=%s referer=%s session_id=%s has_session=%s has_cookie=%s cookie_path=%s',
                                strtoupper($method),
                                $path,
                                $logData['ip'],
                                $logData['referer'],
                                $logData['session_id'],
                                $logData['has_session'] ? 'YES' : 'NO',
                                $logData['has_cookie'] ? 'YES' : 'NO',
                                $logData['cookie_params']['path'] ?? 'UNKNOWN'
                            );
                            $logFile = defined('APP_ROOT') ? APP_ROOT . '/logs/router_csrf.log' : __DIR__ . '/../../logs/router_csrf.log';
                            $logDir = dirname($logFile);
                            if (!is_dir($logDir)) {
                                @mkdir($logDir, 0755, true);
                            }
                            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $logMessage . "\n", FILE_APPEND | LOCK_EX);
                        });
                        
                        // CRITICAL: Exit immediately to prevent any further execution
                        // This prevents index.php from logging after router->run()
                        exit(0);
                    } else {
                        // For regular form submissions, redirect with flash message
                        Utils::flash('error', 'Güvenlik hatası. Lütfen sayfayı yenileyip tekrar deneyin.');
                        redirect(base_url('/customers'));
                    }
                    return true; // Should not reach here due to exit/redirect above
                }
            }
        }
        } // End of !$internalRequest check

        // Lightweight rate limiting for critical endpoints (skip for internal requests)
        if (!$internalRequest && class_exists('ApiRateLimiter')) {
            $path = $this->normalizePath($uri);
            $key = null; $limit = null; $window = null; $retry = 600;
            if (strtoupper($method) === 'POST') {
                if ($path === '/login' || $path === '/auth/process-login' || $path === '/api/mobile/auth' || $path === '/api/v2/auth/login') {
                    $key = 'auth.login'; $limit = 10; $window = 600; $retry = 600;
                } elseif ($path === '/forgot-password' || $path === '/reset-password' || $path === '/two-factor/verify' || $path === '/two-factor/process-login') {
                    $key = 'auth.reset'; $limit = 5; $window = 600; $retry = 600;
                } elseif (strpos($path, '/file-upload/') === 0) {
                    $key = 'upload.general'; $limit = 120; $window = 600; $retry = 120;
                } elseif (strpos($path, '/admin/') === 0) {
                    $key = 'admin.post'; $limit = 200; $window = 600; $retry = 300;
                }
            }
            if ($key) {
                // Bind to IP address
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $rateKey = $key . ':' . $ip;
                if (!\ApiRateLimiter::check($rateKey, $limit, $window)) {
                    \ApiRateLimiter::sendLimitExceededResponse($rateKey, $retry);
                }
                \ApiRateLimiter::record($rateKey, $limit, $window);
            }
        }

        $handler = $this->resolveHandler($match['handler']);
        
        // CRITICAL: Skip middleware execution for internal requests (crawl tests)
        // Internal requests already have authenticated session set up by InternalCrawlService
        // Middleware execution would cause Auth::check() to fail and redirect to login
        if (!$internalRequest) {
            $handler = $this->applyMiddlewares($match['middlewares'], $handler);
        }

        // ROUND 50 FIX: Wrap handler call in try/catch to catch and log exceptions before re-throwing
        try {
            call_user_func_array($handler, $match['params']);
        } catch (Throwable $e) {
            // Log before re-throwing to index.php's outer catch
            $logFile = __DIR__ . '/../../logs/router_handler_exception.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorClass = get_class($e);
            $errorTrace = substr($e->getTraceAsString(), 0, 2000);
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $userId = class_exists('Auth') && Auth::check() ? Auth::id() : 'none';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [ROUTER_HANDLER_EXCEPTION] class={$errorClass}, message={$errorMsg}, file={$errorFile}, line={$errorLine}, uri={$requestUri}, method={$requestMethod}, user_id={$userId}\nTRACE:\n{$errorTrace}\n---\n", FILE_APPEND | LOCK_EX);
            // Re-throw to be caught by index.php's outer try/catch
            throw $e;
        }
        return true;
    }

    private function dispatch(string $method, string $uri): ?array
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = $this->normalizePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['parameters'] as $name) {
                    $params[] = $matches[$name] ?? null;
                }

                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middlewares' => $route['middlewares'],
                ];
            }
        }

        return null;
    }

    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }

        // Accept URLs that include index.php in the path (e.g., /app/index.php/buildings)
        if (strpos($path, '/index.php') === 0) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        if ($path === '' || $path === false) {
            $path = '/';
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * @return array{regex:string, parameters: string[]}
     */
    private function compilePath(string $path): array
    {
        $normalized = trim($path);
        if ($normalized === '' || $normalized === '/') {
            return [
                'regex' => '#^/$#',
                'parameters' => [],
            ];
        }

        if ($normalized[0] !== '/') {
            $normalized = '/' . $normalized;
        }

        if (substr($normalized, -1) === '/') {
            $normalized = rtrim($normalized, '/');
        }

        $segments = explode('/', trim($normalized, '/'));
        $parameters = [];
        $parts = [];

        foreach ($segments as $segment) {
            if (preg_match('#^\{([a-zA-Z_][a-zA-Z0-9_-]*)\}$#', $segment, $matches)) {
                $paramName = $matches[1];
                $parameters[] = $paramName;
                $parts[] = '(?P<' . $paramName . '>[^/]+)';
                continue;
            }

            $parts[] = preg_quote($segment, '#');
        }

        $pattern = '#^/' . implode('/', $parts) . '$#';

        return [
            'regex' => $pattern,
            'parameters' => $parameters,
        ];
    }

    /**
     * @param callable|array|string $handler
     * @return callable
     */
    private function resolveHandler($handler): callable
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                [$class, $method] = explode('@', $handler, 2);
                return [$this->resolveController($class), $method];
            }

            if (function_exists($handler)) {
                return $handler;
            }
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                return [$this->resolveController($class), $method];
            }
        }

        if (!is_callable($handler)) {
            throw new InvalidArgumentException('Route handler is not callable.');
        }

        return $handler;
    }

    private function resolveController(string $class)
    {
        static $instances = [];
        if (!isset($instances[$class])) {
            $instances[$class] = new $class();
        }

        return $instances[$class];
    }

    private function applyMiddlewares(array $middlewares, callable $handler): callable
    {
        if (empty($middlewares)) {
            return $handler;
        }

        $pipeline = new MiddlewarePipeline($handler);

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = new $middleware();
            }

            if ($middleware instanceof MiddlewareInterface) {
                $pipeline->pipe($middleware);
                continue;
            }

            if (is_callable($middleware)) {
                $pipeline->pipe($middleware);
            }
        }

        return $pipeline->resolve();
    }
}
