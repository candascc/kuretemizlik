<?php
/**
 * CSRF Middleware
 * Standardized CSRF verification for all state-changing endpoints
 * 
 * Usage:
 *   CsrfMiddleware::require();
 *   // or
 *   if (!CsrfMiddleware::check()) {
 *       CsrfMiddleware::reject();
 *   }
 */
class CsrfMiddleware
{
    /**
     * Require CSRF token verification for state-changing requests
     * Exits with 403 response if verification fails
     * 
     * @param bool $allowGet Allow GET requests without CSRF (default: true)
     * @return void
     */
    public static function require(bool $allowGet = true): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // GET requests are typically safe (idempotent)
        if ($allowGet && $method === 'GET') {
            return;
        }
        
        // State-changing methods require CSRF verification
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if (!CSRF::verifyRequest()) {
                self::reject();
            }
        }
    }
    
    /**
     * Check if CSRF token is valid (non-blocking)
     * 
     * @return bool True if valid, false otherwise
     */
    public static function check(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // GET requests don't need CSRF verification
        if ($method === 'GET') {
            return true;
        }
        
        // State-changing methods require CSRF verification
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return CSRF::verifyRequest();
        }
        
        return true;
    }
    
    /**
     * Reject request with 403 response
     * 
     * @return void
     */
    public static function reject(): void
    {
        // Check if this is an AJAX/API request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        
        if ($isAjax || $isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token verification failed',
                'message' => 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Regular form submission - redirect with error
        http_response_code(403);
        Utils::flash('error', 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.');
        
        // Try to redirect to referrer or home
        $referrer = $_SERVER['HTTP_REFERER'] ?? base_url('/');
        redirect($referrer);
    }
    
    /**
     * Get CSRF token for forms
     * 
     * @return string CSRF token
     */
    public static function token(): string
    {
        return CSRF::get();
    }
    
    /**
     * Generate CSRF token field for forms
     * 
     * @return string HTML input field
     */
    public static function field(): string
    {
        return CSRF::field();
    }
}


