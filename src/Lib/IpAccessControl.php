<?php
/**
 * IP Access Control Helper
 * 
 * Provides IP allowlist and blocklist functionality for access control.
 * 
 * ROUND 3 - STAGE 4: Advanced Auth Features Skeleton
 * 
 * @package App\Lib
 * @author System
 * @version 1.0
 */

class IpAccessControl
{
    private static $config = null;
    
    /**
     * Load security configuration
     * 
     * @return array Security configuration
     */
    private static function loadConfig(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $configPath = __DIR__ . '/../../config/security.php';
        if (file_exists($configPath)) {
            self::$config = require $configPath;
        } else {
            // Fallback to default config
            self::$config = [
                'ip_allowlist' => ['enabled' => false, 'list' => []],
                'ip_blocklist' => ['enabled' => false, 'list' => []],
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Check if IP is allowed (not blocked and optionally in allowlist)
     * 
     * @param string|null $ip IP address to check (if null, uses getClientIp())
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public static function checkAccess(?string $ip = null): array
    {
        $ip = $ip ?? RateLimitHelper::getClientIp();
        $config = self::loadConfig();
        
        // Check blocklist first (highest priority)
        if (!empty($config['ip_blocklist']['enabled']) && !empty($config['ip_blocklist']['list'])) {
            if (self::isIpInList($ip, $config['ip_blocklist']['list'])) {
                return [
                    'allowed' => false,
                    'reason' => 'IP_BLOCKLISTED',
                    'message' => 'Bu IP adresi engellenmiştir.'
                ];
            }
        }
        
        // Check allowlist (if enabled)
        if (!empty($config['ip_allowlist']['enabled']) && !empty($config['ip_allowlist']['list'])) {
            if (!self::isIpInList($ip, $config['ip_allowlist']['list'])) {
                return [
                    'allowed' => false,
                    'reason' => 'IP_NOT_ALLOWLISTED',
                    'message' => 'Bu IP adresinden erişim izni verilmemiştir.'
                ];
            }
        }
        
        return [
            'allowed' => true,
            'reason' => null,
            'message' => null
        ];
    }
    
    /**
     * Check if IP is in a list (supports CIDR notation)
     * 
     * @param string $ip IP address to check
     * @param array $list List of IPs or CIDR ranges
     * @return bool
     */
    private static function isIpInList(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            $entry = trim($entry);
            if (empty($entry)) {
                continue;
            }
            
            // Check for CIDR notation (e.g., 192.168.1.0/24)
            if (strpos($entry, '/') !== false) {
                if (self::isIpInCidr($ip, $entry)) {
                    return true;
                }
            } else {
                // Exact IP match
                if ($ip === $entry) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     * 
     * @param string $ip IP address
     * @param string $cidr CIDR notation (e.g., 192.168.1.0/24)
     * @return bool
     */
    private static function isIpInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        
        $maskLong = -1 << (32 - (int)$mask);
        $subnetLong &= $maskLong;
        
        return ($ipLong & $maskLong) === $subnetLong;
    }
    
    /**
     * Get client IP address (delegate to RateLimitHelper)
     * 
     * @return string
     */
    private static function getClientIp(): string
    {
        if (class_exists('RateLimitHelper')) {
            return RateLimitHelper::getClientIp();
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

