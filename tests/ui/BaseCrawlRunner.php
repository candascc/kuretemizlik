<?php
/**
 * Base Crawl Runner
 * 
 * Abstract base class for crawl runners with common logic
 * Reduces code duplication between AdminCrawlRunner and SysadminCrawlRunner
 */

require_once __DIR__ . '/CrawlClient.php';
require_once __DIR__ . '/../../src/Config/CrawlConfig.php';

abstract class BaseCrawlRunner
{
    /**
     * Track current path context for relative URL resolution
     */
    private array $pathContext = [];
    
    /**
     * Get special seed URLs (role-specific)
     * 
     * @return array Array of special seed URLs
     */
    abstract protected function getSpecialSeedUrls(): array;
    
    /**
     * Get maximum number of URLs to crawl
     * 
     * @return int
     */
    abstract protected function getMaxUrls(): int;
    
    /**
     * Get maximum crawl depth
     * 
     * @return int
     */
    abstract protected function getMaxDepth(): int;
    
    /**
     * Get maximum execution time in seconds
     * 
     * @return int
     */
    abstract protected function getMaxExecutionTime(): int;
    
    /**
     * Get default log file prefix
     * 
     * @return string
     */
    abstract protected function getLogFilePrefix(): string;
    
    /**
     * Run crawl (recursive link-based)
     * 
     * @param string $baseUrl Base URL (e.g., https://www.kuretemizlik.com/app)
     * @param string $username Username
     * @param string $password Password
     * @param string|null $logFile Optional log file path
     * @return array Crawl results
     */
    public function run(string $baseUrl, string $username, string $password, ?string $logFile = null): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        
        // Set execution time limit
        $startTime = time();
        $maxTime = $this->getMaxExecutionTime();
        if (function_exists('set_time_limit')) {
            @set_time_limit($maxTime + 10); // Add buffer
        }
        
        // Initialize crawl client
        $logFile = $logFile ?? (sys_get_temp_dir() . '/' . $this->getLogFilePrefix() . '_' . date('Y-m-d_H-i-s') . '.log');
        
        try {
            $client = new CrawlClient($baseUrl, $logFile);
        } catch (Throwable $e) {
            return [
                'base_url' => $baseUrl,
                'username' => $username,
                'total_count' => 0,
                'success_count' => 0,
                'error_count' => 1,
                'items' => [],
                'error' => 'CrawlClient initialization failed: ' . $e->getMessage(),
            ];
        }
        
        // Login
        if (!$client->login($username, $password)) {
            return [
                'base_url' => $baseUrl,
                'username' => $username,
                'total_count' => 0,
                'success_count' => 0,
                'error_count' => 1,
                'items' => [],
                'error' => 'Login failed. Check credentials.',
            ];
        }
        
        // Recursive link-based crawl
        $queue = [];
        $visited = [];
        $items = [];
        $successCount = 0;
        $errorCount = 0;
        
        // Seed URLs: dashboard + special endpoints
        $seedUrls = ['/app/'] + $this->getSpecialSeedUrls();
        foreach ($seedUrls as $seedUrl) {
            $normalized = $this->normalizeUrl($seedUrl, $baseUrl, '/app/');
            if ($normalized && !isset($visited[$normalized])) {
                $queue[] = ['url' => $normalized, 'depth' => 0];
                $visited[$normalized] = true;
            }
        }
        
        // BFS crawl loop
        $loopCount = 0;
        $lastResponseTime = 0;
        
        while (!empty($queue) && count($items) < $this->getMaxUrls()) {
            // Check execution time limit
            $elapsed = time() - $startTime;
            if ($elapsed >= $maxTime) {
                break;
            }
            
            $loopCount++;
            $current = array_shift($queue);
            $url = $current['url'];
            $depth = $current['depth'];
            
            if ($depth > $this->getMaxDepth()) {
                continue; // Skip URLs beyond max depth
            }
            
            // Track current path for relative URL resolution
            $this->pathContext[$url] = $url;
            
            // Fetch URL
            $fetchStart = microtime(true);
            $result = $client->get($url);
            $fetchTime = microtime(true) - $fetchStart;
            $lastResponseTime = (int)($fetchTime * 1000000); // Convert to microseconds
            
            $status = $result['status'];
            $errorFlag = $result['error_flag'];
            $hasMarker = $result['has_marker'];
            $bodyLength = $result['body_length'];
            $body = $result['body'] ?? '';
            
            $note = '';
            if ($errorFlag) {
                $errorCount++;
                if ($status >= 500) {
                    $note = 'Critical error - check logs';
                } elseif ($status === 403) {
                    $note = 'Forbidden - permission issue';
                } elseif ($status === 404) {
                    $note = 'Not found - route may not exist';
                } else {
                    $note = 'Error';
                }
                if (isset($result['error'])) {
                    $note .= ': ' . $result['error'];
                }
            } else {
                $successCount++;
            }
            
            $items[] = [
                'url' => $url,
                'status' => $status,
                'has_error' => $errorFlag,
                'has_marker' => $hasMarker,
                'body_length' => $bodyLength,
                'note' => $note,
                'depth' => $depth,
            ];
            
            // Extract links from successful HTML responses
            if ($status === 200 && !empty($body) && strpos($body, '<html') !== false) {
                $links = $this->extractLinks($body, $baseUrl, $url);
                
                foreach ($links as $link) {
                    $normalized = $this->normalizeUrl($link, $baseUrl, $url);
                    if ($normalized && !isset($visited[$normalized])) {
                        $queue[] = ['url' => $normalized, 'depth' => $depth + 1];
                        $visited[$normalized] = true;
                    }
                }
            }
            
            // Adaptive delay based on response time
            $delay = $this->calculateDelay($lastResponseTime);
            usleep($delay);
        }
        
        return [
            'base_url' => $baseUrl,
            'username' => $username,
            'total_count' => count($items),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'items' => $items,
        ];
    }
    
    /**
     * Extract all clickable links from HTML content
     * 
     * @param string $html HTML content
     * @param string $baseUrl Base URL for resolving relative links
     * @param string $currentPath Current path for relative URL resolution
     * @return array Array of normalized URLs
     */
    protected function extractLinks(string $html, string $baseUrl, string $currentPath = '/app/'): array
    {
        $links = [];
        
        // Use optimized regex for link extraction (more memory efficient than DOMDocument)
        // Extract <a href="..."> tags
        if (preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                if (empty($href)) {
                    continue;
                }
                
                // Skip non-HTTP links
                if (preg_match('/^(javascript:|mailto:|tel:|#)/i', $href)) {
                    continue;
                }
                
                // Skip dangerous links
                if ($this->isDangerousLink($href)) {
                    continue;
                }
                
                // Normalize and add to links array
                $normalized = $this->normalizeUrl($href, $baseUrl, $currentPath);
                if ($normalized) {
                    $links[] = $normalized;
                }
            }
        }
        
        return array_unique($links);
    }
    
    /**
     * Normalize URL to /app/... format
     * Now supports relative paths with current path context
     * 
     * @param string $url Raw URL (absolute or relative)
     * @param string $baseUrl Base URL (e.g., https://www.kuretemizlik.com/app)
     * @param string $currentPath Current path for relative URL resolution (e.g., /app/dashboard)
     * @return string|null Normalized URL path (e.g., /app/calendar) or null if invalid
     */
    protected function normalizeUrl(string $url, string $baseUrl, string $currentPath = '/app/'): ?string
    {
        // Remove fragment
        $url = preg_replace('/#.*$/', '', $url);
        
        // Parse absolute URLs
        if (preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '/';
            $query = !empty($parsed['query']) ? '?' . $parsed['query'] : '';
            
            // Check if same origin
            $baseParsed = parse_url($baseUrl);
            $baseHost = $baseParsed['host'] ?? '';
            $urlHost = $parsed['host'] ?? '';
            
            if ($baseHost !== $urlHost) {
                return null; // Different origin, skip
            }
            
            // Extract /app/... path
            if (strpos($path, '/app') === 0) {
                return $path . $query;
            }
            
            return null;
        }
        
        // Handle absolute paths (starting with /)
        if (strpos($url, '/') === 0) {
            if (strpos($url, '/app') === 0) {
                return $url;
            }
            return null;
        }
        
        // Handle relative paths (e.g., "calendar" or "../calendar")
        // Use current path context to resolve
        if (!empty($currentPath)) {
            // Normalize current path
            $currentDir = dirname($currentPath);
            if ($currentDir === '/' || $currentDir === '.') {
                $currentDir = '/app';
            }
            
            // Resolve relative path
            $resolved = rtrim($currentDir, '/') . '/' . ltrim($url, '/');
            
            // Normalize path (remove .. and .)
            $parts = explode('/', $resolved);
            $normalized = [];
            foreach ($parts as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                } elseif ($part === '..') {
                    array_pop($normalized);
                } else {
                    $normalized[] = $part;
                }
            }
            
            $resolvedPath = '/' . implode('/', $normalized);
            
            // Ensure it starts with /app
            if (strpos($resolvedPath, '/app') === 0) {
                return $resolvedPath;
            }
        }
        
        return null;
    }
    
    /**
     * Check if link is dangerous (logout, delete, etc.)
     * 
     * @param string $url URL to check
     * @return bool True if link should be skipped
     */
    protected function isDangerousLink(string $url): bool
    {
        $urlLower = strtolower($url);
        
        // Destructive action patterns
        $dangerousPatterns = [
            'logout',
            'log-out',
            'signout',
            'sign-out',
            'delete',
            'destroy',
            'remove',
            'drop',
            'truncate',
            '?action=delete',
            '?do=delete',
            '?action=destroy',
            '?do=destroy',
            '?action=remove',
            '?do=remove',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate adaptive delay based on response time
     * 
     * @param int $responseTimeMicroseconds Response time in microseconds
     * @return int Delay in microseconds
     */
    protected function calculateDelay(int $responseTimeMicroseconds): int
    {
        // Base delay from config
        $baseDelay = CrawlConfig::getBaseDelay();
        
        // If response was fast (<50ms), use shorter delay
        if ($responseTimeMicroseconds < 50000) {
            return 50000; // 50ms
        }
        
        // If response was slow (>500ms), use longer delay
        if ($responseTimeMicroseconds > 500000) {
            return 200000; // 200ms
        }
        
        // Otherwise use base delay
        return $baseDelay;
    }
}

