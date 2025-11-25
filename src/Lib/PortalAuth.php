<?php

class PortalAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['portal_customer_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['portal_customer_id'] : null;
    }

    public static function role(): string
    {
        $role = $_SESSION['portal_customer_role'] ?? null;
        if (class_exists('Customer')) {
            return Customer::normalizeRole($role);
        }
        return $role ?: 'CUSTOMER_STANDARD';
    }

    public static function hasRole($roles): bool
    {
        if (!$roles) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        $current = self::role();
        foreach ($roles as $role) {
            if (Customer::normalizeRole($role) === $current) {
                return true;
            }
        }

        return false;
    }

    public static function require(array $roles = []): void
    {
        if (!self::check()) {
            redirect(base_url('/portal/login'));
        }

        if ($roles && !self::hasRole($roles)) {
            Utils::flash('error', 'Bu alana erişim yetkiniz bulunmuyor.');
            redirect(base_url('/portal/dashboard'));
        }
    }
}

