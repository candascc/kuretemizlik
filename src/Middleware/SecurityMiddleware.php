<?php
/**
 * Security Middleware
 * Enforces security measures on all requests
 */

class SecurityMiddleware implements MiddlewareInterface
{
    public function __invoke(callable $next): callable
    {
        return function() use ($next) {
            // Set security headers
            $this->setSecurityHeaders();
            
            // Enforce CSRF on state-changing requests
            $this->enforceCsrf();
            
            // Continue with request
            return $next();
        };
    }
    
    /**
     * Set security headers
     */
    private function setSecurityHeaders(): void
    {
        // Use existing SecurityHeaders class
        if (class_exists('SecurityHeaders')) {
            SecurityHeaders::set();
        }
    }
    
    /**
     * Enforce CSRF protection
     */
    private function enforceCsrf(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Skip CSRF check for safe methods
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }
        
        // Skip CSRF check for API endpoints with token authentication
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($path, '/api/') !== false) {
            // API endpoints typically use token auth instead of CSRF
            return;
        }
        
        // Verify CSRF token
        if (!CSRF::verifyRequest()) {
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'text/html';
            $isApiRequest = strpos($acceptHeader, 'application/json') !== false;
            
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'CSRF token verification failed'
                ]);
                exit;
            } else {
                Utils::flash('error', 'Güvenlik hatası: CSRF token doğrulaması başarısız. Lütfen tekrar deneyin.');
                
                // Redirect back or to home
                $referer = $_SERVER['HTTP_REFERER'] ?? base_url();
                redirect($referer);
            }
        }
    }
}

