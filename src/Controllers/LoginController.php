<?php
/**
 * Login Controller
 */

class LoginController
{
    public function show()
    {
        // Zaten giriş yapmışsa dashboard'a yönlendir
        if (Auth::check()) {
            redirect(base_url('/'));
        }
        
        echo View::renderWithLayout('auth/login', [
            'csrf_token' => CSRF::get()
        ]);
    }
    
    public function login()
    {
        // ===== KOZMOS_PATCH: ensure session started (begin) =====
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
        // ===== KOZMOS_PATCH: ensure session started (end) =====
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/login'));
        }
        
        // ===== PRODUCTION FIX: Trim username and password =====
        $username = InputSanitizer::string($_POST['username'] ?? '', 100);
        $password = $_POST['password'] ?? '';
        
        // Validate inputs
        if (empty($username) || empty($password)) {
            Utils::flash('error', 'Kullanıcı adı ve şifre gereklidir.');
            redirect(base_url('/login'));
        }
        
        // Rate limiting kontrolü
        if (!RateLimit::check($username)) {
            $remaining = RateLimit::getBlockTimeRemaining($username);
            Utils::flash('error', "Çok fazla hatalı deneme. $remaining saniye sonra tekrar deneyin.");
            redirect(base_url('/login'));
        }
        
        
        // Giriş denemesi
        if (Auth::login($username, $password)) {
            // ===== KOZMOS_PATCH: session cookie fix after login (begin) =====
            // Session ID'yi yenile ve cookie'yi tekrar set et
            session_regenerate_id(true);
            
            // Cookie'yi tekrar set et - path /app, domain null, samesite Lax
            $is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie(session_name(), session_id(), [
                'expires' => 0,
                'path' => '/app',
                'domain' => null,
                'secure' => $is_https,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            // ===== KOZMOS_PATCH: session cookie fix after login (end) =====
            
            // Başarılı giriş - rate limit temizle
            RateLimit::clear($username);
            
            // Aktivite log
            ActivityLogger::login($username);
            
            // ===== PRODUCTION FIX: Ensure session is written before redirect =====
            // Commit session data immediately to ensure it's available after redirect
            // Note: redirect() function will also call session_write_close(), but
            // we do it here to ensure data is persisted before any potential output
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Dashboard'a yönlendir
            redirect(base_url('/'));
        } else {
            // Hatalı giriş - rate limit kaydet
            RateLimit::recordAttempt($username);
            
            $remaining = RateLimit::getRemainingAttempts($username);
            if ($remaining > 0) {
                Utils::flash('error', "Hatalı kullanıcı adı veya şifre. $remaining deneme hakkınız kaldı.");
            } else {
                Utils::flash('error', 'Çok fazla hatalı deneme. 5 dakika sonra tekrar deneyin.');
            }
            
            redirect(base_url('/login'));
        }
    }
    
    public function logout()
    {
        if (Auth::check()) {
            $user = Auth::user();
            ActivityLogger::logout($user['username']);
        }
        
        Auth::logout();
        redirect(base_url('/login'));
    }
}