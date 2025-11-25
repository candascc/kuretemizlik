<?php
/**
 * Error Detector
 * 
 * Detects errors in crawl responses using configurable patterns
 * Prevents false positives by using intelligent detection logic
 */

class ErrorDetector
{
    /**
     * Error patterns to detect
     * 
     * @var array
     */
    private array $errorPatterns = [
        'Sayfa yüklenirken bir hata oluştu',
        'Beklenmeyen Hata',
    ];
    
    /**
     * Minimum body length to consider as valid page
     * 
     * @var int
     */
    private int $minBodyLength = 200;
    
    /**
     * Constructor
     * 
     * @param array $customPatterns Optional custom error patterns
     */
    public function __construct(array $customPatterns = [])
    {
        if (!empty($customPatterns)) {
            $this->errorPatterns = array_merge($this->errorPatterns, $customPatterns);
        }
    }
    
    /**
     * Detect error in response
     * 
     * @param string $body Response body
     * @param int $statusCode HTTP status code
     * @return string|null Error message if error detected, null otherwise
     */
    public function detectError(string $body, int $statusCode): ?string
    {
        // HTTP status code errors
        if ($statusCode >= 500) {
            return "HTTP {$statusCode} - Critical server error";
        }
        
        if ($statusCode === 403) {
            // Check if it's an actual 403 error page or just mentioned in content
            if ($this->is403ErrorPage($body)) {
                return "HTTP 403 - Access forbidden";
            }
        }
        
        if ($statusCode === 404) {
            return "HTTP 404 - Not found";
        }
        
        if ($statusCode >= 400 && $statusCode < 500) {
            return "HTTP {$statusCode} - Client error";
        }
        
        // Check for error patterns in body
        foreach ($this->errorPatterns as $pattern) {
            if (strpos($body, $pattern) !== false) {
                return "Error pattern detected: {$pattern}";
            }
        }
        
        // Check for short error responses
        if (strlen($body) < $this->minBodyLength) {
            $errorKeywords = ['error', 'hata', 'exception', 'fatal'];
            foreach ($errorKeywords as $keyword) {
                if (stripos($body, $keyword) !== false) {
                    return "Error response (short body with '{$keyword}')";
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check if body is a 403 error page
     * 
     * @param string $body Response body
     * @return bool
     */
    private function is403ErrorPage(string $body): bool
    {
        // Check for 403 in title (likely an error page)
        if (stripos($body, '<title>') !== false && stripos($body, '403') !== false) {
            return true;
        }
        
        // Check for HTTP 403 response headers in body
        if (stripos($body, 'http_response_code(403)') !== false || 
            stripos($body, 'HTTP/1.1 403') !== false) {
            return true;
        }
        
        // Short body with "forbidden" = likely an error page
        if (strlen($body) < 500 && stripos($body, 'forbidden') !== false) {
            return true;
        }
        
        // Check for "Erişim reddedildi" (Turkish "Access denied")
        if (stripos($body, 'Erişim reddedildi') !== false && strlen($body) < 1000) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set minimum body length for valid pages
     * 
     * @param int $length
     */
    public function setMinBodyLength(int $length): void
    {
        $this->minBodyLength = $length;
    }
    
    /**
     * Add custom error pattern
     * 
     * @param string $pattern
     */
    public function addErrorPattern(string $pattern): void
    {
        $this->errorPatterns[] = $pattern;
    }
}

