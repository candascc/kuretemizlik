<?php
/**
 * API Version Handler
 * Manages API versioning and backward compatibility
 */
class ApiVersion
{
    const CURRENT_VERSION = 'v1';
    const DEFAULT_VERSION = 'v1';
    const SUPPORTED_VERSIONS = ['v1', 'v2'];
    
    /**
     * Get API version from request
     */
    public static function getVersion(): string
    {
        // Check URL path (e.g., /api/v2/jobs)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\/api\/(v\d+)\//', $uri, $matches)) {
            $version = $matches[1];
            if (in_array($version, self::SUPPORTED_VERSIONS)) {
                return $version;
            }
        }
        
        // Check header
        $header = $_SERVER['HTTP_API_VERSION'] ?? $_SERVER['HTTP_X_API_VERSION'] ?? '';
        if (!empty($header) && in_array($header, self::SUPPORTED_VERSIONS)) {
            return $header;
        }
        
        // Check query parameter
        $queryVersion = $_GET['api_version'] ?? '';
        if (!empty($queryVersion) && in_array($queryVersion, self::SUPPORTED_VERSIONS)) {
            return $queryVersion;
        }
        
        return self::DEFAULT_VERSION;
    }
    
    /**
     * Check if version is supported
     */
    public static function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED_VERSIONS);
    }
    
    /**
     * Get versioned response format
     */
    public static function formatResponse(array $data, string $version = null): array
    {
        $version = $version ?? self::getVersion();
        
        if ($version === 'v2') {
            // v2 format: More structured
            return [
                'version' => $version,
                'success' => $data['success'] ?? true,
                'data' => $data['data'] ?? $data,
                'meta' => [
                    'timestamp' => date('c'),
                    'request_id' => self::generateRequestId(),
                    'pagination' => $data['pagination'] ?? null
                ],
                'errors' => $data['errors'] ?? null
            ];
        }
        
        // v1 format: Backward compatible
        return $data;
    }
    
    /**
     * Generate unique request ID
     */
    private static function generateRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }
    
    /**
     * Add version headers to response
     */
    public static function addHeaders(string $version = null): void
    {
        $version = $version ?? self::getVersion();
        header("X-API-Version: {$version}");
        header("X-API-Supported-Versions: " . implode(', ', self::SUPPORTED_VERSIONS));
        header("X-API-Deprecated-Version: " . ($version !== self::CURRENT_VERSION ? 'true' : 'false'));
    }
}

