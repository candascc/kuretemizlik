<?php
/**
 * Authentication Controller
 */

class AuthController
{
    /**
     * Show login form
     */
    public function login()
    {
        $this->debugLog('login form requested', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 160),
        ]);

        $this->sendNoCacheHeaders();

        // ROUND 51: Session is already started in index.php bootstrap
        // No need to start session here

        // Ensure a CSRF token is available for the rendered form
        CSRF::get();

        if (Auth::check()) {
            $this->debugLog('user already authenticated, redirecting to dashboard');
            redirect(base_url('/'));
        }

        $data = [
            'title' => 'Giriş Yap'
        ];
        
        view('auth/login', $data);
    }
    
    /**
     * Process login
     */
    public function processLogin()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            $this->debugLog('blocked non-post login attempt', ['method' => $method]);
            redirect(base_url('/login'));
        }

        // ROUND 51: Session is already started in index.php bootstrap
        // No need to start session here

        // ===== PRODUCTION FIX: Log CSRF verification details =====
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionTokens = isset($_SESSION['csrf_tokens']) ? $_SESSION['csrf_tokens'] : [];
        $cookieName = session_name();
        $cookieExists = isset($_COOKIE[$cookieName]);
        
        $this->debugLog('csrf verification attempt', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'session_id' => session_id(),
            'session_status' => session_status(),
            'cookie_name' => $cookieName,
            'cookie_exists' => $cookieExists ? 'yes' : 'no',
            'cookie_value' => $cookieExists ? substr($_COOKIE[$cookieName], 0, 10) . '...' : 'none',
            'post_token' => $csrfToken ? substr($csrfToken, 0, 16) . '...' : 'none',
            'header_token' => $csrfHeader ? substr($csrfHeader, 0, 16) . '...' : 'none',
            'session_tokens_count' => count($sessionTokens),
            'session_tokens_keys' => array_map(function($k) { return substr($k, 0, 8) . '...'; }, array_keys($sessionTokens)),
        ]);
        // ===== PRODUCTION FIX END =====
        
        if (!CSRF::verifyRequest()) {
            $this->debugLog('csrf validation failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'session_id' => session_id(),
                'session_status' => session_status(),
                'cookie_name' => $cookieName,
                'cookie_exists' => $cookieExists ? 'yes' : 'no',
                'cookie_params' => session_get_cookie_params(),
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'n/a',
                'post_token' => $csrfToken ? substr($csrfToken, 0, 16) . '...' : 'none',
                'header_token' => $csrfHeader ? substr($csrfHeader, 0, 16) . '...' : 'none',
                'session_tokens_count' => count($sessionTokens),
                'session_tokens_keys' => array_map(function($k) { return substr($k, 0, 8) . '...'; }, array_keys($sessionTokens)),
            ]);
            set_flash('error', HumanMessages::error('csrf'));
            redirect(base_url('/login'));
        }

        $username = InputSanitizer::string($_POST['username'] ?? '', 100);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($username === '' || $password === '') {
            $this->debugLog('missing credentials', ['username' => $username]);
            set_flash('error', HumanMessages::error('validation'));
            redirect(base_url('/login'));
        }

        // ROUND 3: IP Access Control check (if enabled)
        if (class_exists('IpAccessControl')) {
            $ipCheck = IpAccessControl::checkAccess();
            if (!$ipCheck['allowed']) {
                $ip = RateLimitHelper::getClientIp();
                $this->debugLog('IP access denied', [
                    'ip' => $ip,
                    'reason' => $ipCheck['reason']
                ]);
                
                // Audit log IP access denial
                if (class_exists('AuditLogger')) {
                    AuditLogger::getInstance()->logSecurity('IP_ACCESS_DENIED', null, [
                        'ip_address' => $ip,
                        'reason' => $ipCheck['reason'],
                        'username' => $username
                    ]);
                }
                
                set_flash('error', $ipCheck['message'] ?? 'Erişim engellendi.');
                redirect(base_url('/login'));
            }
        }

        // STAGE 2 ROUND 2: Use RateLimitHelper for centralized rate limiting
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($username, 'login');
        if (!$rateLimitResult['allowed']) {
            $this->debugLog('rate limit exceeded', [
                'username' => $username,
                'remaining' => $rateLimitResult['remaining_seconds']
            ]);
            
            // STAGE 4.3: Audit log rate limit exceeded
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logSecurity('LOGIN_RATE_LIMIT_EXCEEDED', null, [
                    'username' => $username,
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'remaining_seconds' => $rateLimitResult['remaining_seconds']
                ]);
            }
            
            // ROUND 4: Send security alert for rate limit abuse
            if (class_exists('SecurityAlertService') && SecurityAlertService::isEnabled()) {
                try {
                    SecurityAlertService::notifyAnomaly([
                        'type' => 'RATE_LIMIT_ABUSE',
                        'severity' => 'MEDIUM',
                        'ip_address' => RateLimitHelper::getClientIp(),
                        'count' => 1,
                        'message' => 'Login rate limit exceeded',
                        'username' => $username,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                } catch (Exception $e) {
                    // Non-blocking: don't fail if alerting fails
                    error_log("AuthController: Failed to send rate limit alert: " . $e->getMessage());
                }
            }
            
            set_flash('error', $rateLimitResult['message']);
            redirect(base_url('/login'));
        }
        
        $rateLimitKey = $rateLimitResult['rate_limit_key'];

        $this->debugLog('login attempt received', [
            'username' => $username,
            'remember' => $remember,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $result = Auth::login($username, $password);
        
        // STAGE 2 ROUND 2: Record login attempt using RateLimitHelper
        if ($result !== true && $result !== '2fa_required') {
            RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
            
            // STAGE 4.3: Audit log failed login attempt
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('LOGIN_FAILED', null, [
                    'username' => $username,
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'reason' => 'invalid_credentials'
                ]);
            }
        } else {
            // Clear rate limit on successful login
            RateLimitHelper::clearRateLimit($rateLimitKey);
            
            // STAGE 4.3: Audit log successful login (will be logged after user_id is available)
        }

        if ($result === '2fa_required') {
            $this->debugLog('2fa step required', ['username' => $username]);
            redirect(base_url('/two-factor/verify-login'));
        }

        if ($result === true) {
            // ROUND 4: Check MFA requirement after successful login
            if (class_exists('MfaService') && MfaService::isEnabled()) {
                $user = Auth::user();
                // Check if MFA is required for user's role AND user has MFA enabled
                if ($user && MfaService::isRequiredForUser($user) && MfaService::isEnabledForUser($user)) {
                    // Start MFA challenge
                    $mfaResult = MfaService::startMfaChallenge($user, 'totp');
                    if ($mfaResult['success']) {
                        // Set MFA pending flag in session
                        $_SESSION['mfa_pending'] = true;
                        $_SESSION['mfa_user_id'] = $user['id'];
                        $_SESSION['mfa_challenge_id'] = $mfaResult['challenge_id'] ?? null;
                        
                        // Audit log MFA challenge started
                        if (class_exists('AuditLogger')) {
                            AuditLogger::getInstance()->logAuth('MFA_CHALLENGE_STARTED', $user['id'], [
                                'method' => $mfaResult['method'] ?? 'totp',
                                'ip_address' => RateLimitHelper::getClientIp()
                            ]);
                        }
                        
                        // ROUND 4: Redirect to MFA verification page
                        set_flash('info', 'İki faktörlü doğrulama gerekiyor. Lütfen TOTP kodunuzu girin.');
                        redirect(base_url('/mfa/verify'));
                    } else {
                        // MFA challenge failed to start - log and continue with normal login (fallback)
                        if (class_exists('AuditLogger')) {
                            AuditLogger::getInstance()->logAuth('MFA_CHALLENGE_FAILED_START', $user['id'], [
                                'reason' => $mfaResult['message'] ?? 'unknown',
                                'ip_address' => RateLimitHelper::getClientIp()
                            ]);
                        }
                        // Continue with normal login (MFA challenge failed)
                    }
                }
            }
            
            if ($remember && isset($_SESSION['user_id'])) {
                Auth::createRememberToken((int) $_SESSION['user_id']);
                $this->debugLog('remember token issued', ['username' => $username]);
            }

            $this->debugLog('login succeeded', [
                'username' => $username,
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);
            
            // STAGE 4.3: Audit log successful login
            if (class_exists('AuditLogger') && isset($_SESSION['user_id'])) {
                AuditLogger::getInstance()->logAuth('LOGIN_SUCCESS', (int)$_SESSION['user_id'], [
                    'username' => $username,
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'remember_me' => $remember ?? false
                ]);
            }

            // ===== LOGIN_500_STAGE1: Log session state before redirect =====
            $logFile = __DIR__ . '/../../logs/login_500_trace.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $sessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
            $sessionStatus = session_status();
            $cookieName = session_name();
            $cookieExists = isset($_COOKIE[$cookieName]);
            $cookieValue = $cookieExists ? substr($_COOKIE[$cookieName], 0, 12) . '...' : 'none';
            $userId = $_SESSION['user_id'] ?? 'none';
            $loginTime = $_SESSION['login_time'] ?? 'none';
            $cookieParams = session_get_cookie_params();
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
            $userRole = $_SESSION['role'] ?? null;
            $userRoleNormalized = $userRole ? strtoupper(trim($userRole)) : 'null';
            $username = $_SESSION['username'] ?? null;
            $isAdminLike = $userRole ? in_array(strtoupper(trim($userRole)), ['ADMIN', 'SUPERADMIN'], true) : false;
            // ===== LOGIN_500_STAGE1_ROLE END =====
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [AuthController::processLogin] uri={$requestUri}, ip={$ip}, session_id={$sessionId}, session_status={$sessionStatus}, cookie_name={$cookieName}, cookie_exists=" . ($cookieExists ? 'yes' : 'no') . ", cookie_value={$cookieValue}, user_id={$userId}, login_time={$loginTime}, cookie_path={$cookieParams['path']}, cookie_domain=" . ($cookieParams['domain'] ?: 'null') . ", cookie_secure=" . ($cookieParams['secure'] ? 'yes' : 'no') . ", cookie_httponly=" . ($cookieParams['httponly'] ? 'yes' : 'no') . ", cookie_samesite={$cookieParams['samesite']}, user_role={$userRoleNormalized}, username={$username}, is_admin_like=" . ($isAdminLike ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====

            // ===== PRODUCTION FIX: Set flash message BEFORE closing session =====
            set_flash('success', HumanMessages::success('logged_in'));
            
            // ===== CRITICAL FIX: regenerateSession() already calls session_write_close() =====
            // Do NOT call session_write_close() again here as it will close the session
            // that was just reopened by regenerateSession()
            // The session is already written in regenerateSession()
            // ===== CRITICAL FIX END =====

            redirect(base_url('/'));
        }

        $this->debugLog('login failed', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        set_flash('error', HumanMessages::error('credentials'));
        redirect(base_url('/login'));
    }
    
    /**
     * Show MFA verification page
     * ROUND 4: MFA challenge UI
     */
    public function showMfaVerify()
    {
        // Check if MFA challenge is pending
        if (!isset($_SESSION['mfa_pending']) || !$_SESSION['mfa_pending']) {
            set_flash('error', 'MFA doğrulaması gerekmiyor.');
            redirect(base_url('/login'));
        }
        
        $userId = $_SESSION['mfa_user_id'] ?? null;
        if (!$userId) {
            set_flash('error', 'Geçersiz MFA oturumu.');
            redirect(base_url('/login'));
        }
        
        // Get user to check MFA status
        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user || !MfaService::isEnabledForUser($user)) {
            set_flash('error', 'MFA aktif değil.');
            unset($_SESSION['mfa_pending'], $_SESSION['mfa_user_id'], $_SESSION['mfa_challenge_id']);
            redirect(base_url('/login'));
        }
        
        $data = [
            'title' => 'İki Faktörlü Doğrulama',
            'user' => $user,
            'challenge_id' => $_SESSION['mfa_challenge_id'] ?? null
        ];
        
        view('auth/mfa_challenge', $data);
    }
    
    /**
     * Process MFA verification
     * ROUND 4: MFA code verification
     */
    public function processMfaVerify()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            redirect(base_url('/mfa/verify'));
        }
        
        // Check if MFA challenge is pending
        if (!isset($_SESSION['mfa_pending']) || !$_SESSION['mfa_pending']) {
            set_flash('error', 'MFA doğrulaması gerekmiyor.');
            redirect(base_url('/login'));
        }
        
        $userId = $_SESSION['mfa_user_id'] ?? null;
        if (!$userId) {
            set_flash('error', 'Geçersiz MFA oturumu.');
            redirect(base_url('/login'));
        }
        
        // CSRF verification
        if (!CSRF::verifyRequest()) {
            set_flash('error', 'Güvenlik doğrulaması başarısız.');
            redirect(base_url('/mfa/verify'));
        }
        
        $code = InputSanitizer::string($_POST['mfa_code'] ?? '', 10);
        $challengeId = $_SESSION['mfa_challenge_id'] ?? null;
        
        if (empty($code)) {
            set_flash('error', 'Lütfen TOTP kodunuzu girin.');
            redirect(base_url('/mfa/verify'));
        }
        
        // Get user
        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            set_flash('error', 'Kullanıcı bulunamadı.');
            unset($_SESSION['mfa_pending'], $_SESSION['mfa_user_id'], $_SESSION['mfa_challenge_id']);
            redirect(base_url('/login'));
        }
        
        // Verify MFA code
        $verifyResult = MfaService::verifyMfaCode($user, $code, $challengeId);
        
        if ($verifyResult['success']) {
            // MFA verified - clear pending flags
            unset($_SESSION['mfa_pending'], $_SESSION['mfa_user_id'], $_SESSION['mfa_challenge_id']);
            
            // Audit log MFA verification success
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('MFA_CHALLENGE_PASSED', $user['id'], [
                    'used_recovery_code' => $verifyResult['used_recovery_code'] ?? false,
                    'ip_address' => RateLimitHelper::getClientIp()
                ]);
            }
            
            // Continue with normal login flow
            if ($remember = isset($_POST['remember'])) {
                Auth::createRememberToken((int)$user['id']);
            }
            
            // Audit log successful login (with MFA)
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('LOGIN_SUCCESS', (int)$user['id'], [
                    'username' => $user['username'] ?? '',
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'remember_me' => $remember ?? false,
                    'mfa_verified' => true
                ]);
            }
            
            set_flash('success', 'İki faktörlü doğrulama başarılı. Giriş yapılıyor...');
            redirect(base_url('/'));
        } else {
            // MFA verification failed
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('MFA_CHALLENGE_FAILED', $user['id'], [
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'reason' => $verifyResult['message'] ?? 'invalid_code'
                ]);
            }
            
            set_flash('error', $verifyResult['message'] ?? 'Geçersiz TOTP kodu. Lütfen tekrar deneyin.');
            redirect(base_url('/mfa/verify'));
        }
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        // Clear MFA pending state on logout
        unset($_SESSION['mfa_pending'], $_SESSION['mfa_user_id'], $_SESSION['mfa_challenge_id']);
        
        Auth::logout();
        set_flash('success', '🔒 Oturumunuz sonlandırıldı. Görüşmek üzere!');
        redirect(base_url('/login'));
    }
    
    /**
     * Show password reset form
     */
    public function forgotPassword()
    {
        $this->sendNoCacheHeaders();
        $data = [
            'title' => 'Şifre Sıfırlama'
        ];
        
        view('auth/forgot-password', $data);
    }
    
    /**
     * Process password reset request
     */
    public function processForgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/forgot-password'));
        }
        
        // STAGE 2 ROUND 2: Rate limiting for password reset using RateLimitHelper
        $username = InputSanitizer::string($_POST['username'] ?? '', 100);
        $identifier = $username ?: RateLimitHelper::getClientIp();
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($identifier, 'password_reset');
        if (!$rateLimitResult['allowed']) {
            set_flash('error', $rateLimitResult['message']);
            redirect(base_url('/forgot-password'));
        }
        $rateLimitKey = $rateLimitResult['rate_limit_key'];
        
        if (empty($username)) {
            // STAGE 2 ROUND 2: Record failed attempt (empty username)
            RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
            set_flash('error', HumanMessages::error('validation'));
            redirect(base_url('/forgot-password'));
        }
        
        // STAGE 2 ROUND 2: Record password reset attempt
        RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
        
        try {
            $result = Auth::requestPasswordReset($username);
            
            if ($result['success']) {
                set_flash('success', 'Şifre sıfırlama bağlantısı gönderildi. E-postanızı kontrol edin.');
            } else {
                set_flash('info', 'Kullanıcı adı bulunduysa şifre sıfırlama bağlantısı gönderildi.');
            }
            
            redirect(base_url('/login'));
        } catch (Exception $e) {
            set_flash('error', HumanMessages::error('server'));
            redirect(base_url('/forgot-password'));
        }
    }
    
    /**
     * Show password reset form
     */
    public function resetPassword()
    {
        $this->sendNoCacheHeaders();
        $token = InputSanitizer::string($_GET['token'] ?? '', 200);
        
        if (empty($token)) {
            set_flash('error', HumanMessages::error('not_found'));
            redirect(base_url('/login'));
        }
        
        // Verify token
        $db = Database::getInstance();
        $resetToken = $db->fetch(
            "SELECT prt.*, u.username, u.id as user_id
             FROM password_reset_tokens prt
             INNER JOIN users u ON prt.user_id = u.id
             WHERE prt.token = ? AND prt.expires_at > datetime('now') AND prt.used_at IS NULL AND u.is_active = 1",
            [$token]
        );
        
        if (!$resetToken) {
            set_flash('error', HumanMessages::error('not_found'));
            redirect(base_url('/forgot-password'));
        }
        
        $data = [
            'title' => 'Yeni Şifre Belirle',
            'token' => $token,
            'username' => $resetToken['username']
        ];
        
        view('auth/reset-password', $data);
    }
    
    /**
     * Process password reset
     */
    public function processResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/forgot-password'));
        }
        
        // STAGE 2 ROUND 2: Rate limiting for password reset using RateLimitHelper
        $token = InputSanitizer::string($_POST['token'] ?? '', 200);
        $identifier = $token ?: RateLimitHelper::getClientIp();
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($identifier, 'password_reset');
        if (!$rateLimitResult['allowed']) {
            set_flash('error', $rateLimitResult['message']);
            redirect(base_url('/forgot-password'));
        }
        $rateLimitKey = $rateLimitResult['rate_limit_key'];
        
        $token = InputSanitizer::string($_POST['token'] ?? '', 200);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            set_flash('error', HumanMessages::error('validation'));
            redirect(base_url('/forgot-password'));
        }
        
        if ($password !== $passwordConfirm) {
            set_flash('error', 'Şifreler eşleşmiyor 🔑 Lütfen aynı şifreyi girin.');
            redirect(base_url('/reset-password?token=' . urlencode($token)));
        }
        
        if (strlen($password) < 8) {
            set_flash('error', HumanMessages::warning('password'));
            redirect(base_url('/reset-password?token=' . urlencode($token)));
        }
        
        try {
            $result = Auth::resetPassword($token, $password);
            
            if ($result) {
                // STAGE 2 ROUND 2: Clear rate limit on successful password reset
                RateLimitHelper::clearRateLimit($rateLimitKey);
                set_flash('success', HumanMessages::success('password_changed'));
                redirect(base_url('/login'));
            } else {
                // STAGE 2 ROUND 2: Record failed attempt
                RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
                set_flash('error', HumanMessages::error('generic'));
                redirect(base_url('/forgot-password'));
            }
        } catch (Exception $e) {
            // STAGE 2 ROUND 2: Record failed attempt
            RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
            set_flash('error', HumanMessages::error('generic'));
            redirect(base_url('/forgot-password'));
        }
    }

    private function debugLog(string $message, array $context = []): void
    {
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            return;
        }

        $payload = '';
        if (!empty($context)) {
            $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $payload = ' ' . $encoded;
            }
        }

        error_log('[auth] ' . $message . $payload);
    }

    private function sendNoCacheHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
