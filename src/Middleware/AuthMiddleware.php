<?php
/**
 * Auth related middleware shortcuts.
 */

class AuthMiddleware
{
    public static function requireAuth(): callable
    {
        return function (callable $next): callable {
            return function (...$args) use ($next) {
                Auth::require();
                return $next(...$args);
            };
        };
    }

    public static function requireAdmin(): callable
    {
        return function (callable $next): callable {
            return function (...$args) use ($next) {
                Auth::requireAdmin();
                return $next(...$args);
            };
        };
    }

    public static function requireRole($roles): callable
    {
        $roles = (array) $roles;

        return function (callable $next) use ($roles): callable {
            return function (...$args) use ($next, $roles) {
                Auth::requireRole($roles);
                return $next(...$args);
            };
        };
    }

    // ===== KOZMOS_OPERATOR_READONLY: operator readonly middleware (begin)
    public static function requireOperatorReadOnly(): callable
    {
        return function (callable $next): callable {
            return function (...$args) use ($next) {
                Auth::require();
                
                // Operatör rolü için sadece GET metodlarına izin ver
                if (Auth::role() === 'OPERATOR' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
                    ActivityLogger::log('FORBIDDEN', 'auth', [
                        'message' => 'Operatör rolü sadece görüntüleme yetkisine sahip',
                        'method' => $_SERVER['REQUEST_METHOD'],
                        'path' => $_SERVER['REQUEST_URI'] ?? null,
                    ]);
                    View::forbidden();
                }
                
                return $next(...$args);
            };
        };
    }
    // ===== KOZMOS_OPERATOR_READONLY: operator readonly middleware (end)
}
