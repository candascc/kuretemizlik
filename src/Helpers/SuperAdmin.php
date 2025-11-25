<?php
/**
 * Super Admin Helper
 * Detects if current user is the system owner (super admin)
 */

class SuperAdmin
{
    /**
     * Super admin username (system owner)
     * This should be the primary SaaS system administrator
     */
    private static $SUPER_ADMIN_USERNAME = 'admin';
    
    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        return isset($user['username']) && $user['username'] === self::$SUPER_ADMIN_USERNAME;
    }
    
    /**
     * Get super admin username
     */
    public static function getUsername(): string
    {
        return self::$SUPER_ADMIN_USERNAME;
    }
}

