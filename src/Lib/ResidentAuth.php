<?php

class ResidentAuth
{
    /**
     * ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
     */
    private static function ensureSession(): void
    {
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
    }
    // ===== CRITICAL FIX END =====
    
    public static function check(): bool
    {
        self::ensureSession();
        return isset($_SESSION['resident_user_id']);
    }

    public static function id(): ?int
    {
        self::ensureSession();
        return self::check() ? (int) $_SESSION['resident_user_id'] : null;
    }

    public static function role(): string
    {
        self::ensureSession();
        $role = $_SESSION['resident_role'] ?? null;
        if (class_exists('ResidentUser')) {
            return ResidentUser::normalizeRole($role);
        }
        return $role ?: 'RESIDENT_TENANT';
    }

    public static function hasRole($roles): bool
    {
        if ($roles === null || $roles === []) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        $current = self::role();
        foreach ($roles as $role) {
            if (ResidentUser::normalizeRole($role) === $current) {
                return true;
            }
        }
        return false;
    }

    public static function require(array $roles = []): void
    {
        // ===== CRITICAL FIX: ensureSession() is called by check() =====
        if (!self::check()) {
            Utils::flash('error', 'Giriş yapmanız gerekiyor');
            redirect(base_url('/resident/login'));
        }

        if (!empty($roles) && !self::hasRole($roles)) {
            Utils::flash('error', 'Bu alan için yetkiniz yok.');
            redirect(base_url('/resident/dashboard'));
        }
    }
}

