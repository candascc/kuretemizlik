<?php
/**
 * Simple Auth Controller - NO Auth::check() on login page
 * Prevents redirect loop
 */

class SimpleAuthController
{
    /**
     * Show login form - NO redirect check
     */
    public function showLoginForm()
    {
        // CRITICAL: Do NOT check if logged in!
        // Just show the form to prevent redirect loop
        $this->sendNoCacheHeaders();

        // ===== PRODUCTION FIX: Ensure session is active before CSRF token =====
        // Session zaten index.php'de başlatılmış olmalı
        // Eğer başlatılmamışsa, cookie params'ları ayarla ve başlat
        if (session_status() === PHP_SESSION_NONE) {
            // Cookie params'ları ayarla (index.php'deki ayarlarla aynı)
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            session_name(defined('SESSION_NAME') ? SESSION_NAME : 'temizlik_sess');
            // Use SessionHelper for centralized session management
            SessionHelper::ensureStarted();
        }
        // ===== PRODUCTION FIX END =====

        echo View::renderWithLayout('auth/login', [
            'title' => 'Giriş Yap',
            'csrf_token' => CSRF::get(),
            'hideHeader' => true,
            'hideFooter' => true,
        ]);
    }

    private function sendNoCacheHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}

