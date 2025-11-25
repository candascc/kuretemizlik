<?php
/**
 * Cache Warmer Orchestrator
 * Manages cache warming for different data types
 */

class CacheWarmer
{
    private static $warmers = [];
    private static $results = [];
    
    /**
     * Register a cache warmer
     */
    public static function register(string $name, callable $warmer): void
    {
        self::$warmers[$name] = $warmer;
    }
    
    /**
     * Warm all caches
     */
    public static function warmAll(): array
    {
        self::$results = [];
        
        foreach (self::$warmers as $name => $warmer) {
            $startTime = microtime(true);
            
            try {
                $result = $warmer();
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                self::$results[$name] = [
                    'success' => true,
                    'duration_ms' => $duration,
                    'result' => $result
                ];
                
                Logger::info("Cache warmed: $name", [
                    'duration_ms' => $duration,
                    'result' => $result
                ]);
            } catch (Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                self::$results[$name] = [
                    'success' => false,
                    'duration_ms' => $duration,
                    'error' => $e->getMessage()
                ];
                
                Logger::error("Cache warming failed: $name", [
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration
                ]);
            }
        }
        
        return self::$results;
    }
    
    /**
     * Warm specific cache
     */
    public static function warm(string $name): array
    {
        if (!isset(self::$warmers[$name])) {
            throw new Exception("Cache warmer '$name' not found");
        }
        
        $startTime = microtime(true);
        $warmer = self::$warmers[$name];
        
        try {
            $result = $warmer();
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Logger::info("Cache warmed: $name", [
                'duration_ms' => $duration,
                'result' => $result
            ]);
            
            return [
                'success' => true,
                'duration_ms' => $duration,
                'result' => $result
            ];
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Logger::error("Cache warming failed: $name", [
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            return [
                'success' => false,
                'duration_ms' => $duration,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get warming results
     */
    public static function getResults(): array
    {
        return self::$results;
    }
    
    /**
     * Get registered warmers
     */
    public static function getWarmers(): array
    {
        return array_keys(self::$warmers);
    }
    
    /**
     * Initialize default warmers
     */
    public static function initializeDefaults(): void
    {
        // Permissions
        self::register('permissions', function() {
            require_once __DIR__ . '/CacheWarming/PermissionsWarmer.php';
            $warmer = new PermissionsWarmer();
            return $warmer->warm();
        });
        
        // Settings
        self::register('settings', function() {
            require_once __DIR__ . '/CacheWarming/SettingsWarmer.php';
            $warmer = new SettingsWarmer();
            return $warmer->warm();
        });
        
        // User Roles
        self::register('user_roles', function() {
            require_once __DIR__ . '/CacheWarming/UserRolesWarmer.php';
            $warmer = new UserRolesWarmer();
            return $warmer->warm();
        });
        
        // Database Queries
        self::register('database_queries', function() {
            require_once __DIR__ . '/CacheWarming/DatabaseQueriesWarmer.php';
            $warmer = new DatabaseQueriesWarmer();
            return $warmer->warm();
        });
    }
}

