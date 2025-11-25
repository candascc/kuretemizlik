<?php

class MockHelper
{
    private const SESSION_KEY = '_app_use_mocks';

    public static function bootstrap(): void
    {
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();

        if (isset($_GET['mock'])) {
            $value = strtolower((string)$_GET['mock']);
            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                $_SESSION[self::SESSION_KEY] = true;
            } elseif (in_array($value, ['0', 'false', 'no', 'off'], true)) {
                unset($_SESSION[self::SESSION_KEY]);
            }
        }
    }

    public static function enabled(): bool
    {
        if (isset($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY] === true) {
            return true;
        }

        $env = getenv('APP_USE_MOCKS');
        if ($env !== false) {
            $env = strtolower(trim($env));
            return in_array($env, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}

