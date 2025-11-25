<?php
/**
 * Crawl Client Helper
 * 
 * PATH_CRAWL_SYSADMIN_V1: Shared HTTP client for crawl scripts
 * 
 * Provides:
 * - Login with CSRF handling
 * - GET requests with cookie persistence
 * - Response parsing (status, body, markers)
 */

class CrawlClient
{
    private string $baseUrl;
    private string $baseOrigin;
    private array $cookies = [];
    private string $logFile;
    private string $requestId;
    
    public function __construct(string $baseUrl, string $logFile)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        
        // Derive base origin (scheme + host + optional port) for normalized URLs
        $parsed = parse_url($this->baseUrl);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $this->baseOrigin = $scheme . '://' . $host . $port;
        
        $this->logFile = $logFile;
        $this->requestId = bin2hex(random_bytes(8));
        $this->ensureLogDir();
    }
    
    private function ensureLogDir(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }
    
    private function log(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$this->requestId}] {$message}{$contextStr}\n";
        @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
    
    /**
     * Perform HTTP request
     */
    private function request(string $url, array $opts = []): array
    {
        $ch = curl_init($url);
        $defaultOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIE => $this->buildCookieHeader(),
        ];
        
        if (stripos($url, 'https://') === 0) {
            // Enable SSL verification for security
            $defaultOpts[CURLOPT_SSL_VERIFYPEER] = true;
            $defaultOpts[CURLOPT_SSL_VERIFYHOST] = 2;
            
            // Set CA bundle path from environment variable or use system default
            $caBundle = getenv('SSL_CA_BUNDLE');
            if ($caBundle && file_exists($caBundle)) {
                $defaultOpts[CURLOPT_CAINFO] = $caBundle;
            } else {
                // Try common system CA bundle paths
                $commonPaths = [
                    '/etc/ssl/certs/ca-certificates.crt',  // Debian/Ubuntu
                    '/etc/ssl/certs/ca-bundle.crt',       // CentOS/RHEL
                    '/usr/local/etc/openssl/cert.pem',     // macOS (Homebrew)
                    '/etc/pki/tls/certs/ca-bundle.crt',    // Fedora
                ];
                
                foreach ($commonPaths as $path) {
                    if (file_exists($path)) {
                        $defaultOpts[CURLOPT_CAINFO] = $path;
                        break;
                    }
                }
            }
        }
        
        curl_setopt_array($ch, $opts + $defaultOpts);
        $raw = curl_exec($ch);
        
        if ($raw === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            $errorMsg = $error ?: 'cURL exec failed';
            if ($errno) {
                $errorMsg .= " (cURL error {$errno})";
            }
            $this->log('CRAWL_REQUEST_ERROR', ['error' => $errorMsg, 'url' => $url]);
            throw new RuntimeException($errorMsg);
        }
        
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $header = substr($raw, 0, $info['header_size']);
        $body = substr($raw, $info['header_size']);
        
        // Extract cookies from response
        $this->extractCookies($header);
        
        return [$header, $body, $info];
    }
    
    /**
     * Extract cookies from response headers
     */
    private function extractCookies(string $header): void
    {
        preg_match_all('/^Set-Cookie:\s*([^\r\n]+)/mi', $header, $matches);
        foreach ($matches[1] ?? [] as $rawCookie) {
            $parts = explode(';', $rawCookie);
            $kv = explode('=', trim($parts[0]), 2);
            if (count($kv) === 2) {
                $this->cookies[$kv[0]] = $kv[1];
            }
        }
    }
    
    /**
     * Build cookie header string
     */
    private function buildCookieHeader(): string
    {
        $pairs = [];
        foreach ($this->cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }
        return implode('; ', $pairs);
    }
    
    /**
     * Login with username and password
     */
    public function login(string $username, string $password): bool
    {
        $this->log('CRAWL_LOGIN_START', ['username' => $username]);
        
        try {
            // Get login page to extract CSRF token
            [$header, $body] = $this->request($this->baseUrl . '/login');
            
            if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $body, $matches)) {
                $this->log('CRAWL_LOGIN_ERROR', ['error' => 'CSRF token not found']);
                return false;
            }
            
            $csrfToken = $matches[1];
            
            // Perform login POST
            $postFields = http_build_query([
                'username' => $username,
                'password' => $password,
                'csrf_token' => $csrfToken,
            ]);
            
            [$postHeader, $postBody, $postInfo] = $this->request($this->baseUrl . '/login', [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);
            
            // Check for redirect
            $redirect = null;
            if (preg_match('/^Location:\s*([^\r\n]+)/mi', $postHeader, $locMatch)) {
                $redirect = trim($locMatch[1]);
            }
            
            // Normalize redirect URL
            if ($redirect) {
                // Remove query string and fragment for comparison
                $redirectPath = parse_url($redirect, PHP_URL_PATH) ?? $redirect;
                $redirectPath = rtrim($redirectPath, '/');
                
                // Check if redirect is to login page (failed login)
                if (strpos($redirectPath, '/login') !== false || $redirectPath === '/app/login') {
                    $this->log('CRAWL_LOGIN_ERROR', [
                        'username' => $username,
                        'http_code' => $postInfo['http_code'],
                        'redirect' => $redirect,
                        'reason' => 'Redirected to login page - authentication failed'
                    ]);
                    return false;
                }
                
                // Check if redirect is to dashboard (successful login)
                // /app/ or /app means successful login
                if ($redirectPath === '/app' || $redirectPath === '/app/' || strpos($redirectPath, '/app/') === 0) {
                    // Build full redirect URL
                    $redirectUrl = $redirect;
                    if (!preg_match('#^https?://#i', $redirectUrl)) {
                        $parsed = parse_url($this->baseUrl);
                        $origin = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                        // Handle relative redirects
                        if (strpos($redirect, '/') === 0) {
                            $redirectUrl = $origin . $redirect;
                        } else {
                            $redirectUrl = rtrim($origin . '/' . $redirect, '/');
                        }
                    }
                    
                    // Follow redirect to verify
                    try {
                        [$finalHeader, $finalBody, $finalInfo] = $this->request($redirectUrl);
                        
                        // Success if we get 200 and not redirected back to login
                        if ($finalInfo['http_code'] === 200) {
                            // Check if body contains login form (indicates failed login)
                            if (strpos($finalBody, 'name="csrf_token"') !== false && strpos($finalBody, 'login') !== false) {
                                $this->log('CRAWL_LOGIN_ERROR', [
                                    'username' => $username,
                                    'http_code' => $postInfo['http_code'],
                                    'redirect' => $redirect,
                                    'reason' => 'Redirected but page contains login form'
                                ]);
                                return false;
                            }
                            
                            $this->log('CRAWL_LOGIN_SUCCESS', ['username' => $username, 'redirect' => $redirectUrl]);
                            return true;
                        }
                    } catch (Exception $e) {
                        // If redirect follow fails, but redirect path indicates success, still consider it success
                        if ($redirectPath === '/app' || $redirectPath === '/app/') {
                            $this->log('CRAWL_LOGIN_SUCCESS', [
                                'username' => $username,
                                'redirect' => $redirect,
                                'note' => 'Redirect follow failed but redirect path indicates success: ' . $e->getMessage()
                            ]);
                            return true;
                        }
                    }
                }
            }
            
            // If we get here, login failed
            $this->log('CRAWL_LOGIN_ERROR', [
                'username' => $username,
                'http_code' => $postInfo['http_code'],
                'redirect' => $redirect ?? 'none',
                'reason' => 'No valid redirect detected'
            ]);
            return false;
            
        } catch (Exception $e) {
            $this->log('CRAWL_LOGIN_EXCEPTION', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Perform GET request and return status, body, and markers
     * 
     * PATH_CRAWL_SYSADMIN_DEEPCLICK_V1: Returns full body for link extraction
     */
    public function get(string $url): array
    {
        $fullUrl = $url;
        if (!preg_match('#^https?://#i', $fullUrl)) {
            if (strpos($url, '/') === 0) {
                // Absolute path from origin (e.g., /app/dashboard)
                $fullUrl = rtrim($this->baseOrigin, '/') . $url;
            } else {
                // Relative path, append to base URL
                $fullUrl = $this->baseUrl . '/' . ltrim($url, '/');
            }
        }
        
        try {
            [$header, $body, $info] = $this->request($fullUrl);
            
            $status = $info['http_code'];
            $hasMarker = strpos($body, 'GLOBAL_R50_MARKER_1') !== false;
            $errorFlag = $status >= 400;
            
            return [
                'url' => $url,
                'status' => $status,
                'body' => $body, // PATH_CRAWL_SYSADMIN_DEEPCLICK_V1: Return full body for link extraction
                'has_marker' => $hasMarker,
                'error_flag' => $errorFlag,
                'body_length' => strlen($body),
            ];
        } catch (Exception $e) {
            return [
                'url' => $url,
                'status' => 0,
                'body' => '',
                'has_marker' => false,
                'error_flag' => true,
                'body_length' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get request ID for correlation
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}

