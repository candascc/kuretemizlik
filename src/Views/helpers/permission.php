<?php
/**
 * Permission Helper Functions for Views
 * 
 * Usage in views:
 * <?php if (can('jobs.create')): ?>
 *     <button>Create Job</button>
 * <?php endif; ?>
 */

if (!function_exists('can')) {
    /**
     * Check if user has specific permission/capability
     */
    function can(string $permission): bool
    {
        return Auth::can($permission);
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if user has specific role(s)
     */
    function hasRole($roles): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        $currentRole = Auth::role();
        
        return in_array($currentRole, $roles, true);
    }
}

if (!function_exists('hasGroup')) {
    /**
     * Check if user has any role in the specified group
     */
    function hasGroup(string $group): bool
    {
        return Auth::hasGroup($group);
    }
}

