<?php
/**
 * Two-Factor Authentication Controller
 */

class TwoFactorController
{
    /**
     * Show 2FA setup page
     */
    public function setup()
    {
        Auth::require();
        
        $userId = Auth::id();
        
        // Check if 2FA is already enabled
        if (TwoFactorAuth::isEnabled($userId)) {
            redirect('/settings/security');
        }
        
        // Generate new secret
        $secret = TwoFactorAuth::generateSecret();
        $username = Auth::user()['username'];
        $qrCodeUrl = TwoFactorAuth::getQRCodeUrl($username, $secret);
        
        // Store temporary secret in session for verification
        $_SESSION['temp_2fa_secret'] = $secret;
        
        $data = [
            'title' => 'İki Faktörlü Kimlik Doğrulama Kurulumu',
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'username' => $username
        ];
        
        view('two-factor/setup', $data);
    }
    
    /**
     * Verify 2FA setup
     */
    public function verify()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/two-factor/setup'));
        }
        
        $userId = Auth::id();
        $code = $_POST['code'] ?? '';
        
        if (empty($code)) {
            set_flash('error', 'Doğrulama kodu gerekli.');
            redirect(base_url('/two-factor/setup'));
        }
        
        $secret = $_SESSION['temp_2fa_secret'] ?? null;
        if (!$secret) {
            set_flash('error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
            redirect(base_url('/two-factor/setup'));
        }
        
        // Verify the code
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            set_flash('error', 'Geçersiz doğrulama kodu. Lütfen tekrar deneyin.');
            redirect(base_url('/two-factor/setup'));
        }
        
        // Generate backup codes
        $backupCodes = TwoFactorAuth::generateBackupCodes();
        
        // Enable 2FA
        if (TwoFactorAuth::enable($userId, $secret, $backupCodes)) {
            // Clear temporary secret
            unset($_SESSION['temp_2fa_secret']);
            
            // Store backup codes in session for display
            $_SESSION['backup_codes'] = $backupCodes;
            
            // ===== ERR-008 FIX: Regenerate session after 2FA enable =====
            Auth::regenerateSession();
            // ===== ERR-008 FIX: End =====
            
            set_flash('success', 'İki faktörlü kimlik doğrulama başarıyla etkinleştirildi.');
            redirect(base_url('/two-factor/backup-codes'));
        } else {
            set_flash('error', '2FA etkinleştirilirken bir hata oluştu.');
            redirect(base_url('/two-factor/setup'));
        }
    }
    
    /**
     * Show backup codes
     */
    public function backupCodes()
    {
        Auth::require();
        
        $backupCodes = $_SESSION['backup_codes'] ?? [];
        
        if (empty($backupCodes)) {
            redirect('/settings/security');
        }
        
        $data = [
            'title' => 'Yedek Kodlar',
            'backup_codes' => $backupCodes
        ];
        
        view('two-factor/backup-codes', $data);
    }
    
    /**
     * Download backup codes as PDF
     */
    public function downloadBackupCodes()
    {
        Auth::require();
        
        $backupCodes = $_SESSION['backup_codes'] ?? [];
        
        if (empty($backupCodes)) {
            redirect('/settings/security');
        }
        
        // Generate PDF content
        $content = "İki Faktörlü Kimlik Doğrulama Yedek Kodları\n";
        $content .= "==========================================\n\n";
        $content .= "Kullanıcı: " . Auth::user()['username'] . "\n";
        $content .= "Tarih: " . date('d.m.Y H:i:s') . "\n\n";
        $content .= "Bu kodları güvenli bir yerde saklayın. Her kod sadece bir kez kullanılabilir.\n\n";
        
        foreach ($backupCodes as $i => $code) {
            $content .= ($i + 1) . ". " . $code . "\n";
        }
        
        $content .= "\n\nBu dosyayı güvenli bir yerde saklayın ve başkalarıyla paylaşmayın.";
        
        // Set headers for download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="2fa-backup-codes-' . date('Y-m-d') . '.txt"');
        header('Content-Length: ' . strlen($content));
        
        echo $content;
        exit;
    }
    
    /**
     * Disable 2FA
     */
    public function disable()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/settings/security');
        }
        
        $userId = Auth::id();
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            set_flash('error', 'Şifre gerekli.');
            redirect('/settings/security');
        }
        
        // Verify password
        $user = Auth::user();
        $passwordHash = (string)($user['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            set_flash('error', 'Geçersiz şifre.');
            redirect('/settings/security');
        }
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $userModel = new User();
                    $userModel->update($userId, ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$userId}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        // Disable 2FA
        if (TwoFactorAuth::disable($userId)) {
            set_flash('success', 'İki faktörlü kimlik doğrulama devre dışı bırakıldı.');
        } else {
            set_flash('error', '2FA devre dışı bırakılırken bir hata oluştu.');
        }
        
        redirect('/settings/security');
    }
    
    /**
     * Show 2FA verification page (for login)
     */
    public function verifyLogin()
    {
        if (!isset($_SESSION['temp_user_id'])) {
            set_flash('error', 'Oturum doğrulaması süresi doldu. Lütfen tekrar giriş yapın.');
            redirect(base_url('/login'));
        }
        
        $data = [
            'title' => 'İki Faktörlü Kimlik Doğrulama'
        ];
        
        view('two-factor/verify-login', $data);
    }
    
    /**
     * Process 2FA verification for login
     */
    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/two-factor/verify-login'));
        }

        if (!CSRF::verifyRequest()) {
            set_flash('error', 'Oturum doğrulaması başarısız oldu. Lütfen kodu yeniden girin.');
            redirect(base_url('/two-factor/verify-login'));
        }
        
        if (!isset($_SESSION['temp_user_id'])) {
            redirect(base_url('/login'));
        }
        
        $userId = $_SESSION['temp_user_id'];
        $code = $_POST['code'] ?? '';
        
        if (empty($code)) {
            set_flash('error', 'Doğrulama kodu gerekli.');
            redirect(base_url('/two-factor/verify-login'));
        }
        
        // Verify 2FA code
        if (TwoFactorAuth::verify($userId, $code)) {
            // Complete login
            $user = Database::getInstance()->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$userId]
            );
            
            if ($user) {
                // Prevent session fixation after 2FA verification
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Clear temporary session
                unset($_SESSION['temp_user_id']);
                
                // Log successful 2FA verification
                Logger::info('2FA login successful', [
                    'user_id' => $userId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                redirect(base_url('/'));
            }
        }
        
        set_flash('error', 'Geçersiz doğrulama kodu. Lütfen tekrar deneyin.');
        redirect(base_url('/two-factor/verify-login'));
    }
    
    /**
     * Generate new backup codes
     */
    public function regenerateBackupCodes()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/settings/security'));
        }
        
        $userId = Auth::id();
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            set_flash('error', 'Şifre gerekli.');
            redirect(base_url('/settings/security'));
        }
        
        // Verify password
        $user = Auth::user();
        $passwordHash = (string)($user['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            set_flash('error', 'Geçersiz şifre.');
            redirect(base_url('/settings/security'));
        }
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $userModel = new User();
                    $userModel->update($userId, ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$userId}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        // Generate new backup codes
        $backupCodes = TwoFactorAuth::generateBackupCodes();
        
        // Update backup codes
        $db = Database::getInstance();
        if ($db->query(
            "UPDATE users SET two_factor_backup_codes = ? WHERE id = ?",
            [json_encode(TwoFactorAuth::hashBackupCodes($backupCodes)), $userId]
        )) {
            $_SESSION['backup_codes'] = $backupCodes;
            set_flash('success', 'Yeni yedek kodlar oluşturuldu.');
            redirect(base_url('/two-factor/backup-codes'));
        } else {
            set_flash('error', 'Yedek kodlar oluşturulurken bir hata oluştu.');
            redirect(base_url('/settings/security'));
        }
    }
}
