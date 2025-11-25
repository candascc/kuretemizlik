<?php
/**
 * API Version Middleware
 * Handles API version routing
 */
class ApiVersionMiddleware
{
    /**
     * Process request and route to correct version
     */
    public static function handle(): ?string
    {
        $version = ApiVersion::getVersion();
        
        if (!ApiVersion::isSupported($version)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => "Unsupported API version: {$version}",
                'supported_versions' => ApiVersion::SUPPORTED_VERSIONS
            ]);
            exit;
        }
        
        // Add version headers
        ApiVersion::addHeaders($version);
        
        return $version;
    }
}

