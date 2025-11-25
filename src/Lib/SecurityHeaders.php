<?php
/**
 * Security Headers
 * Adds security headers to HTTP responses
 */
class SecurityHeaders
{
    /**
     * Set security headers
     * STAGE 4.1: Standardized and hardened security headers
     */
    public static function set(): void
    {
        // STAGE 4.1: Prevent clickjacking (SAMEORIGIN for flexibility, allows same-origin embedding)
        // Changed from DENY to SAMEORIGIN to allow same-origin iframe embedding if needed
        header('X-Frame-Options: SAMEORIGIN');
        
        // STAGE 4.1: Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // STAGE 4.1: Disable legacy XSS filter (modern browsers use CSP instead)
        // Changed from 1; mode=block to 0 for modern browser compatibility
        header('X-XSS-Protection: 0');
        
        // Content Security Policy (can be overridden via ENV CSP_OVERRIDE)
        $defaultCsp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
               "https://unpkg.com " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' " .
               "https://fonts.googleapis.com " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com; " .
               "font-src 'self' data: " .
               "https://fonts.gstatic.com " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com " .
               "https://fonts.googleapis.com " .
               "https://fonts.gstatic.com; " .
               "frame-ancestors 'none';";
        $csp = $_ENV['CSP_OVERRIDE'] ?? $defaultCsp;
        header((($_ENV['CSP_REPORT_ONLY'] ?? null) ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy') . ": {$csp}");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy (formerly Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // ===== PHASE 7: HSTS (only when HTTPS) =====
        // Use robust HTTPS detection (same as config.php)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Cross-origin isolation (optional; enable only when explicitly requested)
        $enableIsolation = filter_var($_ENV['ENABLE_CROSS_ORIGIN_ISOLATION'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        if ($enableIsolation) {
            header('Cross-Origin-Opener-Policy: same-origin');
            header('Cross-Origin-Embedder-Policy: require-corp');
        }
    }
    
    /**
     * Set CORS headers for API endpoints
     * ===== ERR-017 FIX: CORS Policy Implementation =====
     */
    public static function setCors(): void
    {
        // Get allowed origins from ENV (comma-separated list)
        $allowedOriginsEnv = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        $allowedOrigins = [];
        
        if (!empty($allowedOriginsEnv)) {
            $allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
        } else {
            // Default: allow same origin only (for development)
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $allowedOrigins = [$scheme . '://' . $host];
        }
        
        // Get request origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // ===== ERR-017 FIX: Enhanced origin validation =====
        // Only use HTTP_ORIGIN for CORS (more secure than HTTP_REFERER)
        // HTTP_REFERER can be spoofed and is not reliable for CORS
        if (empty($origin) && !empty($_SERVER['HTTP_REFERER'])) {
            // Fallback to referer only if origin is not set (for same-origin requests)
            $parsed = parse_url($_SERVER['HTTP_REFERER']);
            if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                $origin = $parsed['scheme'] . '://' . $parsed['host'];
                // Add port if present and not default
                if (isset($parsed['port']) && 
                    !(($parsed['scheme'] === 'http' && $parsed['port'] == 80) ||
                      ($parsed['scheme'] === 'https' && $parsed['port'] == 443))) {
                    $origin .= ':' . $parsed['port'];
                }
            }
        }
        
        // Validate origin format (must be valid URL)
        if (!empty($origin)) {
            $parsedOrigin = parse_url($origin);
            if (!$parsedOrigin || !isset($parsedOrigin['scheme']) || !isset($parsedOrigin['host'])) {
                $origin = '';
            } elseif (!in_array($parsedOrigin['scheme'], ['http', 'https'], true)) {
                // Only allow http and https schemes
                $origin = '';
            } elseif (filter_var($parsedOrigin['host'], FILTER_VALIDATE_IP) && 
                      !filter_var($parsedOrigin['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                // Block private IP ranges (security risk)
                $origin = '';
            }
        }
        // ===== ERR-017 FIX: End =====
        
        // Check if origin is allowed
        $isAllowed = false;
        if (!empty($origin)) {
            foreach ($allowedOrigins as $allowed) {
                // Support wildcard subdomains (e.g., *.example.com)
                if (strpos($allowed, '*') !== false) {
                    $pattern = '/^' . str_replace(['.', '*'], ['\.', '.*'], $allowed) . '$/';
                    if (preg_match($pattern, $origin)) {
                        $isAllowed = true;
                        break;
                    }
                } elseif ($origin === $allowed || $allowed === '*') {
                    $isAllowed = true;
                    break;
                }
            }
        }
        
        // Set CORS headers
        if ($isAllowed || empty($origin)) {
            // Allow specific origin or same origin
            if (!empty($origin) && $isAllowed) {
                header("Access-Control-Allow-Origin: {$origin}");
            } elseif (in_array('*', $allowedOrigins, true)) {
                // Only allow * if explicitly configured
                header('Access-Control-Allow-Origin: *');
            }
            
            // Allow credentials (cookies, authorization headers)
            $allowCredentials = filter_var($_ENV['CORS_ALLOW_CREDENTIALS'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
            if ($allowCredentials && !in_array('*', $allowedOrigins, true)) {
                header('Access-Control-Allow-Credentials: true');
            }
            
            // Allowed methods
            $allowedMethods = $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET, POST, PUT, DELETE, PATCH, OPTIONS';
            header("Access-Control-Allow-Methods: {$allowedMethods}");
            
            // Allowed headers
            $allowedHeaders = $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token';
            header("Access-Control-Allow-Headers: {$allowedHeaders}");
            
            // Exposed headers
            $exposedHeaders = $_ENV['CORS_EXPOSED_HEADERS'] ?? 'X-Total-Count, X-Page-Count';
            if (!empty($exposedHeaders)) {
                header("Access-Control-Expose-Headers: {$exposedHeaders}");
            }
            
            // Max age for preflight cache
            $maxAge = (int)($_ENV['CORS_MAX_AGE'] ?? 86400); // 24 hours default
            header("Access-Control-Max-Age: {$maxAge}");
        }
    }
    
    /**
     * Handle OPTIONS preflight request
     * ===== ERR-017 FIX: OPTIONS Preflight Handler =====
     */
    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setCors();
            http_response_code(204); // No Content
            exit;
        }
    }
    // ===== ERR-017 FIX: End =====
}

