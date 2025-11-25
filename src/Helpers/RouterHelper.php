<?php
/**
 * Router Helper
 * 
 * Centralized router instance management to prevent duplicate creation logic
 */

require_once __DIR__ . '/../Lib/Router.php';

class RouterHelper
{
    /**
     * Get or create router instance
     * 
     * Tries to get router from global scope first, creates new one if not available
     * 
     * @return Router Router instance
     */
    public static function getOrCreateRouter(): Router
    {
        // Try to get from global scope
        if (isset($GLOBALS['router']) && $GLOBALS['router'] instanceof Router) {
            return $GLOBALS['router'];
        }
        
        // Create new router if not available
        $appBase = defined('APP_BASE') ? APP_BASE : '/app';
        return new Router($appBase);
    }
}

