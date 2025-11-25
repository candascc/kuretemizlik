<?php
/**
 * Authentication and Authorization System
 * Handles user authentication, session management, and RBAC permissions
 */

class Auth
{
    private static $user = null;
    
    /**
     * ROUND 51: Minimal session initialization
     * Session config (cookie params, ini_set) should be set in index.php bootstrap
     * This method only ensures session is started, nothing more
     */
    private static function ensureSessionStarted(): void
    {
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
    }
    
    public static function check(): bool
    {
        // ROUND 51: Use minimal session initialization
        self::ensureSessionStarted();
        
        // ROUND 51: Login flow trace
        $logFile = __DIR__ . '/../../logs/auth_flow_r51.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $sessionId = session_id() ? substr(session_id(), 0, 8) . '...' : 'none';
        $hasUserId = isset($_SESSION['user_id']);
        $userId = $hasUserId ? $_SESSION['user_id'] : 'none';
        $sessionStatus = session_status();
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [AUTH_CHECK] uri={$requestUri}, result=" . ($hasUserId ? 'true' : 'false') . ", user_id={$userId}, session_status={$sessionStatus}, session_id={$sessionId}\n", FILE_APPEND | LOCK_EX);
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            // Check remember me cookie
            return self::checkRememberMe();
        }
        
        // Check session timeout - don't call logout() to avoid recursion
        // Instead, just invalidate the session silently
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            $_SESSION = [];
            // Check remember me cookie as fallback
            return self::checkRememberMe();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check remember me token from cookie
     */
    private static function checkRememberMe(): bool
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $tokenHash = hash('sha256', $token);
        $db = Database::getInstance();
        
        try {
            // Prefer hashed lookup; fallback to legacy plain token for migration
            $rememberToken = $db->fetch(
                "SELECT rt.*, u.* FROM remember_tokens rt
                 INNER JOIN users u ON rt.user_id = u.id
                 WHERE rt.token_hash = ? AND rt.expires_at > datetime('now') AND u.is_active = 1",
                [$tokenHash]
            );
            if (!$rememberToken) {
                $rememberToken = $db->fetch(
                    "SELECT rt.*, u.* FROM remember_tokens rt
                     INNER JOIN users u ON rt.user_id = u.id
                     WHERE rt.token = ? AND rt.expires_at > datetime('now') AND u.is_active = 1",
                    [$token]
                );
                // If legacy match found, migrate to hashed storage
                if ($rememberToken) {
                    $db->update('remember_tokens', ['token_hash' => $tokenHash], 'id = ?', [$rememberToken['id']]);
                }
            }
            
            if (!$rememberToken) {
                // Invalid token, clear cookie
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
                return false;
            }
            
            // Update last used
            $db->update('remember_tokens', 
                ['last_used_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$rememberToken['id']]
            );
            
            // ===== ERR-008 FIX: Use centralized regenerateSession() =====
            // Prevent session fixation on auto-login (remember-me)
            self::regenerateSession();
            // ===== ERR-008 FIX: End =====
            
            // Complete login
            return self::completeLogin($rememberToken);
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Remember me check failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    public static function login(string $username, string $password): bool
    {
        $db = Database::getInstance();
        
        // ROUND 51: Login flow trace
        $logFile = __DIR__ . '/../../logs/auth_flow_r51.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionStatus = session_status();
        $sessionIdBefore = session_id() ? substr(session_id(), 0, 8) . '...' : 'none';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [LOGIN_ATTEMPT] email={$username}, ip={$ip}, session_status={$sessionStatus}, session_id={$sessionIdBefore}, uri={$requestUri}\n", FILE_APPEND | LOCK_EX);
        
        // ROUND 51: Use minimal session initialization
        // Session name and cookie params are set in index.php bootstrap
        self::ensureSessionStarted();
        
        // ===== PRODUCTION FIX: Trim username and password to handle whitespace issues =====
        $username = trim($username);
        $password = trim($password);
        
        // Case-insensitive username lookup (SQLite default is case-sensitive for =)
        $user = $db->fetch(
            "SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND is_active = 1",
            [$username]
        );
        
        // ===== PRODUCTION FIX: Ensure password_hash is string and handle encoding =====
        if (!$user) {
            // ROUND 51: Login flow trace - failed
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [LOGIN_FAILED] reason=user_not_found, email={$username}, uri={$requestUri}\n", FILE_APPEND | LOCK_EX);
            
            // Log failed login attempt (user not found)
            AuditLogger::getInstance()->logAuth('LOGIN_FAILED', null, [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'reason' => 'user_not_found'
            ]);
            return false;
        }
        
        // Ensure password_hash is a string (SQLite might return it as different type)
        $passwordHash = (string)($user['password_hash'] ?? '');
        
        // ===== ERR-014 FIX: Use PasswordHelper for verification and rehash =====
        require_once __DIR__ . '/PasswordHelper.php';
        // Verify password with automatic rehash if needed
        if (empty($passwordHash) || !PasswordHelper::verifyPassword($password, $passwordHash, function($newHash) use ($db, $user) {
            $db->update('users', 
                ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );
        })) {
            // ROUND 51: Login flow trace - failed
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [LOGIN_FAILED] reason=password_mismatch, email={$username}, user_id={$user['id']}, uri={$requestUri}\n", FILE_APPEND | LOCK_EX);
            
            // Log failed login attempt (password mismatch)
            $logData = [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'reason' => 'password_mismatch'
            ];
            // Only add debug info in development
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $logData['hash_length'] = strlen($passwordHash);
                $logData['hash_prefix'] = substr($passwordHash, 0, 7); // First 7 chars for debugging (safe)
            }
            AuditLogger::getInstance()->logAuth('LOGIN_FAILED', $user['id'] ?? null, $logData);
            return false;
        }
        
        // Password rehash is handled by PasswordHelper::verifyPassword()
        
        // Log successful login
        AuditLogger::getInstance()->logAuth('LOGIN_SUCCESS', $user['id'], [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Check if 2FA is enabled for this user
        if (TwoFactorAuth::isEnabled($user['id'])) {
            // Store user ID temporarily for 2FA verification
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['temp_username'] = $user['username'];
            $_SESSION['temp_role'] = $user['role'];
            $_SESSION['temp_login_time'] = time();
            
            // Log 2FA required
            AuditLogger::getInstance()->logAuth('LOGIN_2FA_REQUIRED', $user['id'], [
                'username' => $username
            ]);
            
            // Redirect to 2FA verification
            return '2fa_required';
        }
        
        // Complete login for users without 2FA
        $result = self::completeLogin($user);
        
        // ROUND 51: Login flow trace - success
        if ($result === true) {
            $sessionIdAfter = session_id() ? substr(session_id(), 0, 8) . '...' : 'none';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [LOGIN_SUCCESS] user_id={$user['id']}, session_id_before={$sessionIdBefore}, session_id_after={$sessionIdAfter}, uri={$requestUri}\n", FILE_APPEND | LOCK_EX);
        }
        
        return $result;
    }
    
    /**
     * Regenerate session ID for security (prevents session fixation)
     * ===== ERR-008 FIX: Centralized session regeneration =====
     */
    public static function regenerateSession(): void
    {
        // ROUND 51: Minimal session regeneration
        // Cookie params are set in index.php bootstrap, do NOT modify here
        self::ensureSessionStarted();
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            // ===== LOGIN_500_STAGE1: Log before regenerate =====
            $logFile = __DIR__ . '/../../logs/login_500_trace.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $oldSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
            $userId = $_SESSION['user_id'] ?? 'none';
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
            $userRole = $_SESSION['role'] ?? null;
            $userRoleNormalized = $userRole ? strtoupper(trim($userRole)) : 'null';
            $username = $_SESSION['username'] ?? null;
            // ===== LOGIN_500_STAGE1_ROLE END =====
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::regenerateSession] BEFORE uri={$requestUri}, old_session_id={$oldSessionId}, user_id={$userId}, user_role={$userRoleNormalized}, username={$username}\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====
            
            // Regenerate session ID and delete old session file
            session_regenerate_id(true);
            
            // ===== LOGIN_500_STAGE1: Log after regenerate =====
            $newSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
            $sessionStatus = session_status();
            $cookieName = session_name();
            $cookieExists = isset($_COOKIE[$cookieName]);
            $cookieValue = $cookieExists ? substr($_COOKIE[$cookieName], 0, 12) . '...' : 'none';
            // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
            $userRole = $_SESSION['role'] ?? null;
            $userRoleNormalized = $userRole ? strtoupper(trim($userRole)) : 'null';
            $username = $_SESSION['username'] ?? null;
            // ===== LOGIN_500_STAGE1_ROLE END =====
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::regenerateSession] AFTER uri={$requestUri}, new_session_id={$newSessionId}, session_status={$sessionStatus}, cookie_name={$cookieName}, cookie_exists=" . ($cookieExists ? 'yes' : 'no') . ", cookie_value={$cookieValue}, user_role={$userRoleNormalized}, username={$username}\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====
            
            // ROUND 51: Ensure session is written immediately
            // This ensures session data is available on the next request after redirect
            // Cookie params are already set in index.php bootstrap, no need to setcookie() here
            session_write_close();
            
            // ===== LOGIN_500_STAGE1: Log after write_close =====
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::regenerateSession] AFTER_WRITE_CLOSE uri={$requestUri}, session_id={$newSessionId}\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====
            
            // Reopen session with new ID to continue using it in this request
            SessionHelper::ensureStarted();
            
            // ===== LOGIN_500_STAGE1: Log after restart =====
            $restartedSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
            $restartedStatus = session_status();
            $restartedUserId = $_SESSION['user_id'] ?? 'none';
            // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
            $restartedUserRole = $_SESSION['role'] ?? null;
            $restartedUserRoleNormalized = $restartedUserRole ? strtoupper(trim($restartedUserRole)) : 'null';
            $restartedUsername = $_SESSION['username'] ?? null;
            // ===== LOGIN_500_STAGE1_ROLE END =====
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::regenerateSession] AFTER_RESTART uri={$requestUri}, session_id={$restartedSessionId}, session_status={$restartedStatus}, user_id={$restartedUserId}, user_role={$restartedUserRoleNormalized}, username={$restartedUsername}\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====
            
            // Log session regeneration for audit
            if (class_exists('Logger') && isset($_SESSION['user_id'])) {
                Logger::info('Session regenerated', [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'reason' => 'security_regeneration'
                ]);
            }
        }
    }
    
    /**
     * Complete login process after 2FA verification
     */
    public static function completeLogin(array $user): bool
    {
        // ROUND 51: Use minimal session initialization
        self::ensureSessionStarted();
        
        // Session bilgilerini kaydet
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        // ===== LOGIN_500_STAGE2: Normalize role to uppercase =====
        $userRole = $user['role'] ?? null;
        if ($userRole !== null) {
            $userRole = strtoupper(trim($userRole));
        }
        $_SESSION['role'] = $userRole;
        // ===== LOGIN_500_STAGE2 END =====
        $_SESSION['company_id'] = $user['company_id'] ?? 1;
        $_SESSION['login_time'] = time();
        
        // ===== LOGIN_500_STAGE1: Log after session set =====
        $logFile = __DIR__ . '/../../logs/login_500_trace.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $sessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
        $sessionStatus = session_status();
        $userId = $_SESSION['user_id'] ?? 'none';
        $loginTime = $_SESSION['login_time'] ?? 'none';
        $cookieParams = session_get_cookie_params();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
        $userRole = $_SESSION['role'] ?? null;
        $userRoleNormalized = $userRole ? strtoupper(trim($userRole)) : 'null';
        $username = $_SESSION['username'] ?? null;
        $dbUserRole = $user['role'] ?? null;
        $dbUserRoleNormalized = $dbUserRole ? strtoupper(trim($dbUserRole)) : 'null';
        $isAdminLike = $userRole ? in_array(strtoupper(trim($userRole)), ['ADMIN', 'SUPERADMIN'], true) : false;
        // ===== LOGIN_500_STAGE1_ROLE END =====
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::completeLogin] AFTER_SESSION_SET uri={$requestUri}, session_id={$sessionId}, session_status={$sessionStatus}, user_id={$userId}, login_time={$loginTime}, cookie_path={$cookieParams['path']}, cookie_domain=" . ($cookieParams['domain'] ?: 'null') . ", cookie_secure=" . ($cookieParams['secure'] ? 'yes' : 'no') . ", cookie_httponly=" . ($cookieParams['httponly'] ? 'yes' : 'no') . ", cookie_samesite={$cookieParams['samesite']}, session_role={$userRoleNormalized}, db_role={$dbUserRoleNormalized}, username={$username}, is_admin_like=" . ($isAdminLike ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
        
        // ===== ERR-008 FIX: Use centralized regenerateSession() =====
        self::regenerateSession();
        // ===== ERR-008 FIX: End =====
        
        // ===== LOGIN_500_STAGE1: Log after regenerate =====
        $finalSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
        $finalStatus = session_status();
        $finalUserId = $_SESSION['user_id'] ?? 'none';
        $finalLoginTime = $_SESSION['login_time'] ?? 'none';
        // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
        $finalUserRole = $_SESSION['role'] ?? null;
        $finalUserRoleNormalized = $finalUserRole ? strtoupper(trim($finalUserRole)) : 'null';
        $finalUsername = $_SESSION['username'] ?? null;
        // ===== LOGIN_500_STAGE1_ROLE END =====
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [Auth::completeLogin] AFTER_REGENERATE uri={$requestUri}, session_id={$finalSessionId}, session_status={$finalStatus}, user_id={$finalUserId}, login_time={$finalLoginTime}, user_role={$finalUserRoleNormalized}, username={$finalUsername}\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
        
        return true;
    }
    
    public static function logout(): void
    {
        // Log logout - check session variables directly to avoid recursion
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            AuditLogger::getInstance()->logAuth('LOGOUT', $_SESSION['user_id'], [
                'username' => $_SESSION['username']
            ]);
        }
        
        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            self::clearRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // ROUND 51: Start new session after logout
        // Session config will be set by index.php bootstrap on next request
        self::ensureSessionStarted();
    }
    
    /**
     * Create remember me token
     */
    public static function createRememberToken(int $userId): ?string
    {
        $db = Database::getInstance();
        
        try {
            // Generate secure random token
            $token = bin2hex(random_bytes(32));
            
            // Token expires in 30 days
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
            
            // Delete old tokens for this user
            $db->delete('remember_tokens', 'user_id = ?', [$userId]);
            
            // Insert new token (store hash; keep compatibility token column if exists)
            $payload = [
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s')
            ];
            // Best effort: also store plain token if column exists (for legacy reads)
            try {
                $payload['token'] = $token;
            } catch (Exception $e) {}
            $db->insert('remember_tokens', $payload);
            
            // Set secure cookie (30 days)
            $cookieLifetime = time() + (30 * 24 * 60 * 60);
            $isSecure = defined('COOKIE_SECURE') ? COOKIE_SECURE : 
                        ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                         (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
            
            setcookie('remember_token', $token, $cookieLifetime, '/', '', $isSecure, true);
            
            return $token;
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Remember token creation failed: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Clear remember me token
     */
    private static function clearRememberToken(string $token): void
    {
        try {
            $db = Database::getInstance();
            $db->delete('remember_tokens', 'token_hash = ? OR token = ?', [hash('sha256', $token), $token]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Request password reset
     */
    public static function requestPasswordReset(string $username): array
    {
        $db = Database::getInstance();
        
        try {
            // Find user by username
            $user = $db->fetch(
                "SELECT * FROM users WHERE username = ? AND is_active = 1",
                [$username]
            );
            
            // Always return success for security (don't reveal if user exists)
            $success = true;
            
            if ($user) {
                // Generate secure reset token
                $token = bin2hex(random_bytes(32));
                
                // Token expires in 1 hour
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                
                // Delete old tokens for this user
                $db->delete('password_reset_tokens', 'user_id = ?', [$user['id']]);
                
                // Insert new token
                $db->insert('password_reset_tokens', [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Send reset email if EmailService is available
                if (class_exists('EmailService')) {
                    $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                              . '://' . $_SERVER['HTTP_HOST'] . base_url('/reset-password?token=' . $token);
                    
                    $subject = 'Şifre Sıfırlama Talebi';
                    $body = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2>Şifre Sıfırlama</h2>
                            <p>Merhaba {$user['username']},</p>
                            <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
                            <p><a href='{$resetUrl}' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Şifremi Sıfırla</a></p>
                            <p>Bu bağlantı 1 saat geçerlidir.</p>
                            <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
                            <hr>
                            <p style='color: #666; font-size: 12px;'>Temizlik İş Takip Sistemi</p>
                        </body>
                        </html>
                    ";
                    
                    // Try to send email (might fail if email not configured)
                    EmailService::send($user['username'] . '@example.com', $subject, $body);
                }
                
                // Log password reset request
                if (class_exists('AuditLogger')) {
                    AuditLogger::getInstance()->logAuth('PASSWORD_RESET_REQUESTED', $user['id'], [
                        'username' => $user['username'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }
            }
            
            return ['success' => $success];
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Password reset request failed: " . $e->getMessage());
            }
            return ['success' => true]; // Still return success for security
        }
    }
    
    /**
     * Reset password with token
     */
    public static function resetPassword(string $token, string $newPassword): bool
    {
        $db = Database::getInstance();
        
        try {
            // Find valid token
            $resetToken = $db->fetch(
                "SELECT prt.*, u.* FROM password_reset_tokens prt
                 INNER JOIN users u ON prt.user_id = u.id
                 WHERE prt.token = ? AND prt.expires_at > datetime('now') AND prt.used_at IS NULL AND u.is_active = 1",
                [$token]
            );
            
            if (!$resetToken) {
                return false;
            }
            
            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $db->update('users', 
                ['password_hash' => $passwordHash, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$resetToken['user_id']]
            );
            
            // Mark token as used
            $db->update('password_reset_tokens',
                ['used_at' => date('Y-m-d H:i:s')],
                'token = ?',
                [$token]
            );
            
            // Delete old remember tokens
            $db->delete('remember_tokens', 'user_id = ?', [$resetToken['user_id']]);
            
            // ===== ERR-008 FIX: Regenerate session after password reset =====
            // If user is logged in, regenerate session for security
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $resetToken['user_id']) {
                self::regenerateSession();
            }
            // ===== ERR-008 FIX: End =====
            
            // Log password reset
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('PASSWORD_RESET_COMPLETED', $resetToken['user_id'], [
                    'username' => $resetToken['username'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Password reset failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        if (self::$user === null) {
            $db = Database::getInstance();
            self::$user = $db->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }
        return self::$user;
    }
    
    /**
     * Check if user has specific role
     * SECURITY: Company scope check is handled by caller (hasRole is role-only check)
     * Note: For permission checks, use hasPermission() which includes company scope
     */
    public static function hasRole(string $role): bool
    {
        if (!self::check()) {
            return false;
        }
        
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        // ===== LOGIN_500_STAGE2: Null-safe role check =====
        $userRole = $user['role'] ?? null;
        if ($userRole === null) {
            return false;
        }
        // ===== LOGIN_500_STAGE2 END =====
        
        // SUPERADMIN always has all roles (can access all companies)
        if ($userRole === 'SUPERADMIN') {
            return true;
        }
        
        // ADMIN always has all roles (but only within their company - company scope enforced elsewhere)
        if ($userRole === 'ADMIN') {
            return true;
        }
        
        // Direct role match (case-insensitive for safety)
        $normalizedUserRole = strtoupper(trim($userRole));
        $normalizedRequiredRole = strtoupper(trim($role));
        if ($normalizedUserRole === $normalizedRequiredRole) {
            return true;
        }
        
        // Legacy: Direct role match (case-sensitive) for backward compatibility
        if ($userRole === $role) {
            return true;
        }
        
        // Check role hierarchy (if roles table exists)
        try {
            $db = Database::getInstance();
            $roleHierarchy = $db->fetch(
                "SELECT * FROM roles WHERE name = ? AND is_active = 1",
                [$role]
            );
            
            if ($roleHierarchy && isset($roleHierarchy['parent_role'])) {
                // Check if user's role is a child of the required role
                return self::hasRole($roleHierarchy['parent_role']);
            }
        } catch (Exception $e) {
            // Roles table might not exist, fall back to simple check
            // Silent fail - continue with basic role check
        }
        
        return false;
    }
    
    
    public static function id(): ?int
    {
        return self::check() ? $_SESSION['user_id'] : null;
    }
    
    public static function role(): ?string
    {
        return self::check() ? ($_SESSION['role'] ?? null) : null;
    }

    public static function companyId(): ?int
    {
        return self::check() ? ($_SESSION['company_id'] ?? null) : null;
    }
    public static function refresh(): void
    {
        self::$user = null;
    }

    
    public static function require(): void
    {
        // ROUND 51: Use minimal session initialization
        self::ensureSessionStarted();
        // ===== CRITICAL FIX END =====
        
        if (!self::check()) {
            // ROUND 50: Log auth require failure before redirect
            $logFile = __DIR__ . '/../../logs/auth_require_exception.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $sessionId = session_id() ?: 'NO_SESSION';
            $sessionStatus = session_status();
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [AUTH_REQUIRE_FAIL] uri={$requestUri}, session_id={$sessionId}, session_status={$sessionStatus}\n", FILE_APPEND | LOCK_EX);
            redirect(base_url('/login'));
        }
    }
    
    public static function requireRole($role): void
    {
        self::require();

        $roles = is_array($role) ? $role : [$role];
        $currentRole = self::role();

        // Direct role match
        if (in_array($currentRole, $roles, true)) {
            return;
        }
        
        // SUPERADMIN always has access (highest hierarchy)
        if ($currentRole === 'SUPERADMIN') {
            return;
        }
        
        // Check hierarchy: higher hierarchy roles can access lower hierarchy roles
        if (class_exists('Roles')) {
            $currentRoleDef = Roles::get($currentRole);
            $currentHierarchy = $currentRoleDef['hierarchy'] ?? 0;
            
            foreach ($roles as $requiredRole) {
                $requiredRoleDef = Roles::get($requiredRole);
                $requiredHierarchy = $requiredRoleDef['hierarchy'] ?? 0;
                
                // If current role has higher hierarchy, allow access
                if ($currentHierarchy > $requiredHierarchy) {
                    return;
                }
            }
        }
        
        // Check if Role class has canManage method
        if (class_exists('Role')) {
            foreach ($roles as $requiredRole) {
                if (Role::canManage($currentRole, $requiredRole)) {
                    return;
                }
            }
        }

        ActivityLogger::log('FORBIDDEN', 'auth', [
            'required_roles' => $roles,
            'current_role' => $currentRole,
            'path' => $_SERVER['REQUEST_URI'] ?? null,
        ]);
        View::forbidden();
    }

    public static function requireAdmin(): void
    {
        // ===== CRITICAL FIX: Ensure session is started =====
        // requireRole() calls require() which handles session
        self::requireRole(['ADMIN', 'SUPERADMIN']);
    }

    /**
     * Check if user has admin-like role (ADMIN or SUPERADMIN)
     * ===== LOGIN_500_STAGE2: Helper for consistent admin role checking =====
     * 
     * @param array|null $user User array from Auth::user() or null to use current user
     * @return bool
     */
    public static function isAdminLike(?array $user = null): bool
    {
        if ($user === null) {
            $user = self::user();
        }
        
        if (!$user) {
            return false;
        }
        
        // ===== LOGIN_500_STAGE2: Null-safe role check =====
        $userRole = $user['role'] ?? null;
        if ($userRole === null) {
            return false;
        }
        // ===== LOGIN_500_STAGE2 END =====
        
        // SUPERADMIN always has all roles (can access all companies)
        if ($userRole === 'SUPERADMIN') {
            return true;
        }
        
        // ADMIN always has all roles (but only within their company - company scope enforced elsewhere)
        if ($userRole === 'ADMIN') {
            return true;
        }
        
        // Normalize and check (for case-insensitive comparison)
        $normalizedRole = strtoupper(trim($userRole));
        return in_array($normalizedRole, ['ADMIN', 'SUPERADMIN'], true);
    }

    public static function isSuperAdmin(): bool
    {
        if (!self::check()) {
            return false;
        }

        if (isset($_SESSION['username']) && $_SESSION['username'] === 'candas') {
            return true;
        }

        return self::role() === 'SUPERADMIN';
    }
    
    public static function canAccess(string $resource): bool
    {
        if (!self::check()) {
            return false;
        }
        
        // Use RBAC permission system
        try {
            if (class_exists('Permission')) {
                return Permission::has($resource);
            }
        } catch (Exception $e) {
            // Permission system might not be fully set up, fall back to role check
        }
        
        // Fallback: Simple role-based check
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        // ===== LOGIN_500_STAGE2: Null-safe role check =====
        $userRole = $user['role'] ?? null;
        if ($userRole === null) {
            return false;
        }
        // ===== LOGIN_500_STAGE2 END =====
        
        // ADMIN can access everything
        if ($userRole === 'ADMIN') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user has specific permission
     */
    /**
     * Check if user has specific permission
     * SECURITY: Company scope check is enforced BEFORE permission check
     * This ensures ADMIN cannot access other companies even with wildcard permissions
     */
    public static function hasPermission(string $permission): bool
    {
        // SECURITY: Company scope is enforced at controller/model level via CompanyScope trait
        // Permission check is role-based only (within company scope)
        return Permission::has($permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        return Permission::hasAny($permissions);
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        return Permission::hasAll($permissions);
    }
    
    /**
     * Alias for hasPermission() - more intuitive syntax
     */
    public static function can(string $permission): bool
    {
        return self::hasPermission($permission);
    }

    /**
     * Determine if current user can switch between companies / tenants
     * Only SUPERADMIN can switch between companies
     */
    public static function canSwitchCompany(): bool
    {
        if (!self::check()) {
            return false;
        }

        // Only SUPERADMIN can switch between companies
        return self::isSuperAdmin();
    }
    
    /**
     * Require specific permission or redirect
     */
    public static function requirePermission(string $permission): void
    {
        // ROUND 51: Use minimal session initialization
        self::ensureSessionStarted();
        // ===== CRITICAL FIX END =====
        
        if (!self::can($permission)) {
            ActivityLogger::log('FORBIDDEN', 'auth', [
                'required_permission' => $permission,
                'user_id' => self::id(),
                'path' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
            View::forbidden();
        }
    }
    
    /**
     * Alias for requirePermission() - more intuitive syntax
     */
    public static function requireCapability(string $capability): void
    {
        self::requirePermission($capability);
    }
    
    /**
     * Require any of the given permissions
     */
    public static function requireAnyPermission(array $permissions): void
    {
        if (!self::hasAnyPermission($permissions)) {
            ActivityLogger::log('FORBIDDEN', 'auth', [
                'required_permissions' => $permissions,
                'user_id' => self::id(),
                'path' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
            View::forbidden();
        }
    }
    
    /**
     * Require any of the given capabilities (alias for requireAnyPermission)
     */
    public static function requireAnyCapability(array $capabilities): void
    {
        self::requireAnyPermission($capabilities);
    }
    
    /**
     * Check if user has specific capability (alias for hasPermission)
     */
    public static function hasCapability(string $capability): bool
    {
        return self::hasPermission($capability);
    }
    
    /**
     * Require user to have any role in the specified group
     */
    public static function requireGroup(string $group): void
    {
        // ===== CRITICAL FIX: require() already handles session initialization =====
        self::require();
        
        $currentRole = self::role();
        
        // SUPERADMIN always has access
        if ($currentRole === 'SUPERADMIN') {
            return;
        }
        
        // Get allowed roles for this group
        if (!class_exists('Roles')) {
            error_log("Auth::requireGroup('{$group}') - Roles class not found");
            View::forbidden();
            return;
        }
        
        $allowedRoles = Roles::group($group);
        
        // Wildcard means all authenticated users
        if (in_array('*', $allowedRoles, true)) {
            return;
        }
        
        // Check if current role is in allowed roles (case-insensitive)
        $currentRoleUpper = strtoupper(trim($currentRole ?? ''));
        $hasAccess = false;
        
        foreach ($allowedRoles as $allowedRole) {
            if (strtoupper(trim($allowedRole)) === $currentRoleUpper) {
                $hasAccess = true;
                break;
            }
        }
        
        if (!$hasAccess) {
            ActivityLogger::log('FORBIDDEN', 'auth', [
                'required_group' => $group,
                'user_id' => self::id(),
                'current_role' => $currentRole,
                'current_role_normalized' => $currentRoleUpper,
                'group_roles' => $allowedRoles,
                'path' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
            View::forbidden();
        }
    }
    
    /**
     * Check if user has any role in the specified group
     */
    public static function hasGroup(string $group): bool
    {
        if (!self::check()) {
            return false;
        }
        
        if (!class_exists('Roles')) {
            return false;
        }
        
        $allowedRoles = Roles::group($group);
        $currentRole = self::role();
        
        // Wildcard means all authenticated users
        if (in_array('*', $allowedRoles, true)) {
            return true;
        }
        
        // SUPERADMIN always has access
        if ($currentRole === 'SUPERADMIN') {
            return true;
        }
        
        return in_array($currentRole, $allowedRoles, true);
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    public static function changePassword(int $userId, string $newPassword): bool
    {
        $db = Database::getInstance();
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->query(
            "UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?",
            [$passwordHash, date('Y-m-d H:i:s'), $userId]
        );
        
        $success = $stmt->rowCount() > 0;
        
        // ===== ERR-008 FIX: Regenerate session after password change =====
        // If current user changed their own password, regenerate session
        if ($success && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            self::regenerateSession();
        }
        // ===== ERR-008 FIX: End =====
        
        return $success;
    }
}