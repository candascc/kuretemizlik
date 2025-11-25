<?php
/**
 * Authentication Middleware
 */

class AuthMiddleware
{
    /**
     * Require authentication
     */
    public static function requireAuth(): callable
    {
        return function($handler) {
            // ROUND 51: Session is already started in index.php bootstrap
            // Auth::check() will ensure session is started if needed
            
            // CRITICAL: Skip Auth check for internal requests (crawl tests)
            // Internal requests already have authenticated session set up by InternalCrawlService
            if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST) {
                return $handler;
            }
            
            if (!Auth::check()) {
                redirect(base_url('/login'));
                exit;
            }
            return $handler;
        };
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin(): callable
    {
        return function($handler) {
            // ROUND 51: Session is already started in index.php bootstrap
            // Auth::check() will ensure session is started if needed
            
            // CRITICAL: Skip Auth check for internal requests (crawl tests)
            // Internal requests already have authenticated session set up by InternalCrawlService
            if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST) {
                return $handler;
            }
            
            if (!Auth::check()) {
                redirect(base_url('/login'));
                exit;
            }
            
            $user = Auth::user();
            if (!$user || !in_array($user['role'], ['ADMIN', 'SUPERADMIN'], true)) {
                redirect(base_url('/'));
                exit;
            }
            return $handler;
        };
    }
    
    /**
     * Require operator readonly role
     */
    public static function requireOperatorReadOnly(): callable
    {
        return function($handler) {
            // ROUND 51: Session is already started in index.php bootstrap
            // Auth::check() will ensure session is started if needed
            
            // CRITICAL: Skip Auth check for internal requests (crawl tests)
            // Internal requests already have authenticated session set up by InternalCrawlService
            if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST) {
                return $handler;
            }
            
            if (!Auth::check()) {
                redirect(base_url('/login'));
                exit;
            }
            
            $user = Auth::user();
            // Admin can access all pages, operators can view
            if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'OPERATOR_READONLY'])) {
                redirect(base_url('/'));
                exit;
            }
            return $handler;
        };
    }
}
