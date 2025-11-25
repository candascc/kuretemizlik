<?php
/**
 * Permission Middleware
 * RBAC permission-based access control middleware
 */

class PermissionMiddleware
{
    /**
     * Require specific permission
     */
    public static function requirePermission(string $permission): callable
    {
        return function (callable $next) use ($permission): callable {
            return function (...$args) use ($next, $permission) {
                Auth::require();
                
                if (!Auth::can($permission)) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_permission' => $permission,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    
    /**
     * Require any of the given permissions
     */
    public static function requireAnyPermission(array $permissions): callable
    {
        return function (callable $next) use ($permissions): callable {
            return function (...$args) use ($next, $permissions) {
                Auth::require();
                
                if (!Auth::hasAnyPermission($permissions)) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_permissions' => $permissions,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    
    /**
     * Require all of the given permissions
     */
    public static function requireAllPermissions(array $permissions): callable
    {
        return function (callable $next) use ($permissions): callable {
            return function (...$args) use ($next, $permissions) {
                Auth::require();
                
                if (!Auth::hasAllPermissions($permissions)) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_permissions' => $permissions,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    
    /**
     * Require specific capability
     */
    public static function requireCapability(string $capability): callable
    {
        return function (callable $next) use ($capability): callable {
            return function (...$args) use ($next, $capability) {
                Auth::require();
                
                if (!Auth::hasCapability($capability)) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_capability' => $capability,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    
    /**
     * Require any of the given capabilities
     */
    public static function requireAnyCapability(array $capabilities): callable
    {
        return function (callable $next) use ($capabilities): callable {
            return function (...$args) use ($next, $capabilities) {
                Auth::require();
                
                $hasAny = false;
                foreach ($capabilities as $capability) {
                    if (Auth::hasCapability($capability)) {
                        $hasAny = true;
                        break;
                    }
                }
                
                if (!$hasAny) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_capabilities' => $capabilities,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    
    /**
     * Require specific group access
     */
    public static function requireGroup(string $group): callable
    {
        return function (callable $next) use ($group): callable {
            return function (...$args) use ($next, $group) {
                Auth::require();
                
                if (!Auth::hasGroup($group)) {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'required_group' => $group,
                        'user_id' => Auth::id(),
                        'role' => Auth::role(),
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
}

