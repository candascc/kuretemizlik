<?php
/**
 * View Helper Functions for RBAC
 */

if (!function_exists('can')) {
    function can(string $permission): bool {
        return Auth::can($permission);
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission(array $permissions): bool {
        return Auth::hasAnyPermission($permissions);
    }
}

if (!function_exists('hasAllPermissions')) {
    function hasAllPermissions(array $permissions): bool {
        return Auth::hasAllPermissions($permissions);
    }
}