<?php

declare(strict_types=1);

/**
 * Settings Controller
 */

require_once __DIR__ . '/../Lib/AuditLogger.php';

class SettingsController
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    public function profile()
    {
        Auth::require();
        
        $user = Auth::user();
        
        $csrfToken = CSRF::generate();

        echo View::renderWithLayout('settings/profile', [
            'user' => $user,
            'csrf_token' => $csrfToken,
        ]);
    }
    
    public function changePassword()
    {
        Auth::require();

        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/settings/profile')) {
            return;
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('[settings] password change attempt');
            error_log('[settings] session csrf tokens: ' . json_encode($_SESSION['csrf_tokens'] ?? []));
            error_log('[settings] incoming token: ' . substr((string)(InputSanitizer::string($_POST['csrf_token'] ?? '', 200) ?? ''), 0, 16));
        }

        if (!ControllerHelper::verifyCsrfOrRedirect('/settings/profile')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $validator = new Validator($_POST);
        $validator->required('current_password', 'Mevcut şifre zorunludur')
                 ->required('new_password', 'Yeni şifre zorunludur')
                 ->min('new_password', 6, 'Yeni şifre en az 6 karakter olmalıdır')
                 ->required('confirm_password', 'Şifre onayı zorunludur')
                 ->same('confirm_password', 'new_password', 'Şifreler eşleşmiyor.');

        if ($validator->fails()) {
            ControllerHelper::flashErrorAndRedirect($validator->firstError(), '/settings/profile');
            return;
        }

        $currentPassword = $validator->get('current_password');
        $newPassword = $validator->get('new_password');

        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (!$this->userModel->verifyPassword(Auth::id(), $currentPassword)) {
                Utils::flash('error', 'Mevcut şifre yanlış.');
                redirect(base_url('/settings/profile'));
            }

            if (!$this->userModel->changePassword(Auth::id(), $newPassword)) {
                Utils::flash('error', 'Şifre güncellenemedi.');
                redirect(base_url('/settings/profile'));
            }

            // ===== ERR-008 FIX: Regenerate session after password change =====
            Auth::regenerateSession();
            // ===== ERR-008 FIX: End =====

            Auth::refresh();
            ActivityLogger::passwordChanged(Auth::id());
            
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logSecurity('PASSWORD_CHANGED', Auth::id(), [
                'user_id' => Auth::id(),
                'username' => Auth::user()['username'] ?? null
            ]);
            // ===== ERR-018 FIX: End =====

            Utils::flash('success', 'Şifreniz başarıyla değiştirildi.');
        } catch (Exception $e) {
            error_log("SettingsController::changePassword() error: " . $e->getMessage());
            Utils::flash('error', 'Şifre değiştirilirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====
        
        redirect(base_url('/settings/profile'));
    }
    public function users()
    {
        Auth::requireAdmin();
        
        $users = $this->userModel->all();
        $stats = $this->userModel->getStats();
        $roleDefinitions = $this->getRoleDefinitions();
        $assignableRoles = $this->getAssignableRoles();
        
        echo View::renderWithLayout('settings/users', [
            'users' => $users,
            'stats' => $stats,
            'flash' => Utils::getFlash(),
            'roleDefinitions' => $roleDefinitions,
            'assignableRoles' => $assignableRoles,
        ]);
    }

    public function logs()
    {
        Auth::requireAdmin();
        
        $errorLogPath = __DIR__ . '/../../../30-Logs/error/php_error.log';
        $lines = [];
        if (file_exists($errorLogPath)) {
            $content = @file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($content)) {
                $lines = array_slice(array_reverse($content), 0, 200);
            }
        }
        
        echo View::renderWithLayout('settings/logs', [
            'errorLines' => $lines
        ]);
    }

    public function exportActivityCsv()
    {
        Auth::requireAdmin();
        
        $activityModel = new ActivityLog();
        $logs = $activityModel->all(1000, 0);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=activity_' . date('Ymd_His') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['id','actor','action','entity','meta','created_at']);
        foreach ($logs as $r) {
            fputcsv($output, [
                $r['id'],
                $r['actor_name'] ?? '',
                $r['action'] ?? '',
                $r['entity'] ?? '',
                $r['meta_json'] ?? '',
                $r['created_at'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    }

    public function monitoring()
    {
        Auth::requireAdmin();
        
        echo View::renderWithLayout('admin/monitoring', [
            'title' => 'System Monitoring'
        ]);
    }

    public function calendar()
    {
        Auth::require();
        $db = Database::getInstance();
        $prefs = $db->fetch("SELECT * FROM notification_prefs WHERE user_id = ?", [Auth::id()]) ?: [];
        echo View::renderWithLayout('settings/calendar', [
            'prefs' => $prefs,
            'flash' => Utils::getFlash()
        ]);
    }

    public function updateCalendar()
    {
        Auth::require();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::verifyRequest()) {
            redirect('/settings/calendar');
        }
        $db = Database::getInstance();
        $data = [
            'calendar_reminders_email' => isset($_POST['calendar_reminders_email']) ? 1 : 0,
            'calendar_reminders_sms' => isset($_POST['calendar_reminders_sms']) ? 1 : 0,
            'timezone' => InputSanitizer::string($_POST['timezone'] ?? 'Europe/Istanbul', 100),
            'work_start' => InputSanitizer::string($_POST['work_start'] ?? '09:00', 10),
            'work_end' => InputSanitizer::string($_POST['work_end'] ?? '18:00', 10),
            'weekend_shading' => isset($_POST['weekend_shading']) ? 1 : 0,
            'calendar_density' => InputSanitizer::string($_POST['calendar_density'] ?? 'comfortable', 50)
        ];
        // upsert
        $existing = $db->fetch("SELECT user_id FROM notification_prefs WHERE user_id = ?", [Auth::id()]);
        if ($existing) {
            $db->update('notification_prefs', $data, 'user_id = :id', ['id' => Auth::id()]);
        } else {
            $data['user_id'] = Auth::id();
            $db->insert('notification_prefs', $data);
        }
        set_flash('success', 'Takvim tercihleri güncellendi');
        redirect('/settings/calendar');
    }

    public function backupStatus()
    {
        Auth::requireAdmin();
        $dbPath = DB_PATH;
        $backupDir = dirname($dbPath) . '/backups';
        $backups = [];
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '/app-*.sqlite') as $f) {
                $backups[] = [
                    'file' => basename($f),
                    'size' => filesize($f),
                    'mtime' => filemtime($f)
                ];
            }
            usort($backups, function($a,$b){ return $b['mtime'] <=> $a['mtime']; });
        }
        $last = $backups[0] ?? null;
        
        echo View::renderWithLayout('settings/backup', [
            'backups' => $backups,
            'last' => $last,
            'dbSize' => file_exists($dbPath) ? filesize($dbPath) : 0
        ]);
    }
    
    public function createUser()
    {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/settings/users'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/settings/users'));
        }
        
        $allowedRoles = array_keys($this->getAssignableRoles());
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('username', 'Kullanıcı adı zorunludur')
                 ->min('username', 3, 'Kullanıcı adı en az 3 karakter olmalıdır')
                 ->max('username', 50, 'Kullanıcı adı en fazla 50 karakter olabilir')
                 ->unique('username', 'users', null, null, 'Bu kullanıcı adı zaten kullanılıyor')
                 ->required('password', 'Şifre zorunludur')
                 ->min('password', 6, 'Şifre en az 6 karakter olmalıdır')
                 ->required('role', 'Rol seçimi zorunludur')
                 ->in('role', $allowedRoles, 'Geçerli bir rol seçin');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/settings/users'));
        }
        
        // Kullanıcı oluştur
        $userData = [
            'username' => $validator->get('username'),
            'password' => $validator->get('password'),
            'role' => strtoupper($validator->get('role')),
        ];
        
        $userId = $this->userModel->create($userData);
        
        // ===== ERR-018 FIX: Add audit logging =====
        AuditLogger::getInstance()->logAdmin('USER_CREATED', Auth::id(), [
            'created_user_id' => $userId,
            'username' => $userData['username'],
            'role' => $userData['role']
        ]);
        // ===== ERR-018 FIX: End =====
        
        Utils::flash('success', 'Kullanıcı başarıyla oluşturuldu.');
        redirect(base_url('/settings/users'));
    }
    
    public function updateUser($id)
    {
        Auth::requireAdmin();
        
        $user = $this->userModel->find($id);
        if (!$user) {
            View::notFound('Kullanıcı bulunamadı');
        }
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/settings/users')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/settings/users')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        $allowedRoles = array_keys($this->getAssignableRoles());
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('username', 'Kullanıcı adı zorunludur')
                 ->min('username', 3, 'Kullanıcı adı en az 3 karakter olmalıdır')
                 ->max('username', 50, 'Kullanıcı adı en fazla 50 karakter olabilir')
                 ->unique('username', 'users', null, $id, 'Bu kullanıcı adı zaten kullanılıyor')
                 ->required('role', 'Rol seçimi zorunludur')
                 ->in('role', $allowedRoles, 'Geçerli bir rol seçin');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/settings/users'));
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            // Kullanıcı güncelle
            $newRole = strtoupper($validator->get('role'));
            $oldRole = $user['role'] ?? '';
            $roleChanged = $newRole !== $oldRole;
            
            $userData = [
                'username' => $validator->get('username'),
                'role' => $newRole,
            ];
            
            // ===== ERR-008 FIX: Regenerate session if role changed for current user =====
            $isCurrentUser = Auth::id() == $id;
            // ===== ERR-008 FIX: End =====
            
            // Şifre değiştirilmişse
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password']; // Password is hashed, don't sanitize
            }
            
            $this->userModel->update($id, $userData);
            
            // ===== ERR-018 FIX: Add audit logging =====
            $logMetadata = [
                'user_id' => $id,
                'username' => $user['username'] ?? null,
                'old_role' => $oldRole,
                'new_role' => $newRole
            ];
            if ($roleChanged) {
                $logMetadata['role_changed'] = true;
            }
            if (!empty($_POST['password'])) {
                $logMetadata['password_changed'] = true;
            }
            AuditLogger::getInstance()->logAdmin('USER_UPDATED', Auth::id(), $logMetadata);
            // ===== ERR-018 FIX: End =====
            
            // ===== ERR-008 FIX: Regenerate session if role or password changed for current user =====
            if ($isCurrentUser && ($roleChanged || !empty($_POST['password']))) {
                // Update session role if changed
                if ($roleChanged) {
                    $_SESSION['role'] = $newRole;
                }
                Auth::regenerateSession();
            }
            // ===== ERR-008 FIX: End =====
            
            ControllerHelper::flashSuccessAndRedirect('Kullanıcı başarıyla güncellendi.', '/settings/users');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'SettingsController::updateUser()', 'Kullanıcı güncellenirken bir hata oluştu', '/settings/users');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    public function deleteUser($id)
    {
        Auth::requireAdmin();
        
        $user = $this->userModel->find($id);
        if (!$user) {
            View::notFound('Kullanıcı bulunamadı');
        }
        
        // Kendi hesabını silmeye çalışıyorsa
        if ($id == Auth::id()) {
            ControllerHelper::flashErrorAndRedirect('Kendi hesabınızı silemezsiniz.', '/settings/users');
            return;
        }
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/settings/users')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/settings/users')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            // Kullanıcı sil
            $this->userModel->delete($id);
            
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logAdmin('USER_DELETED', Auth::id(), [
                'deleted_user_id' => $id,
                'deleted_username' => $user['username'] ?? null,
                'deleted_role' => $user['role'] ?? null
            ]);
            // ===== ERR-018 FIX: End =====
            
            ControllerHelper::flashSuccessAndRedirect('Kullanıcı başarıyla silindi.', '/settings/users');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'SettingsController::deleteUser()', 'Kullanıcı silinirken bir hata oluştu', '/settings/users');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    public function toggleUser($id)
    {
        Auth::requireAdmin();
        
        $user = $this->userModel->find($id);
        if (!$user) {
            View::notFound('Kullanıcı bulunamadı');
        }
        
        // Kendi hesabını deaktive etmeye çalışıyorsa
        if ($id == Auth::id()) {
            Utils::flash('error', 'Kendi hesabınızı deaktive edemezsiniz.');
            redirect(base_url('/settings/users'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/settings/users'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/settings/users'));
        }
        
        // Kullanıcı durumunu değiştir
        $this->userModel->toggleActive($id);
        
        $status = $user['is_active'] ? 'deaktive' : 'aktive';
        Utils::flash('success', "Kullanıcı $status edildi.");
        redirect(base_url('/settings/users'));
    }
    
    /**
     * Show security settings page
     */
    public function security()
    {
        Auth::require();
        
        $userId = Auth::id();
        $user = Auth::user();
        
        $data = [
            'title' => 'Güvenlik Ayarları',
            'user' => $user,
            'two_factor_enabled' => TwoFactorAuth::isEnabled($userId),
            'two_factor_required' => TwoFactorAuth::isRequired($userId),
            'backup_codes_count' => count(TwoFactorAuth::getBackupCodes($userId))
        ];
        
        view('settings/security', $data);
    }
    
    /**
     * Enable 2FA for user
     */
    public function enable2FA()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/settings/security');
        }
        
        $userId = Auth::id();
        
        // Check if already enabled
        if (TwoFactorAuth::isEnabled($userId)) {
            set_flash('info', 'İki faktörlü kimlik doğrulama zaten etkin.');
            redirect('/settings/security');
        }
        
        redirect('/two-factor/setup');
    }
    
    /**
     * Disable 2FA for user
     */
    public function disable2FA()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/settings/security');
        }
        
        $userId = Auth::id();
        $password = $_POST['password'] ?? ''; // Password is hashed, don't sanitize
        
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
            // ===== ERR-008 FIX: Regenerate session after 2FA disable =====
            Auth::regenerateSession();
            // ===== ERR-008 FIX: End =====
            set_flash('success', 'İki faktörlü kimlik doğrulama devre dışı bırakıldı.');
        } else {
            set_flash('error', '2FA devre dışı bırakılırken bir hata oluştu.');
        }
        
        redirect('/settings/security');
    }
    
    /**
     * Regenerate backup codes
     */
    /**
     * ROUND 4: Admin MFA management - Show MFA setup for a user (SUPERADMIN only)
     */
    public function userMfa()
    {
        Auth::require();
        
        // Only SUPERADMIN can manage MFA for other users
        $currentUser = Auth::user();
        if (!isset($currentUser['role']) || $currentUser['role'] !== 'SUPERADMIN') {
            Utils::flash('error', 'Bu işlem için SUPERADMIN yetkisi gereklidir.');
            redirect(base_url('/settings/users'));
        }
        
        // Check if MFA is enabled globally
        if (!class_exists('MfaService') || !MfaService::isEnabled()) {
            Utils::flash('error', 'MFA sistemi aktif değil.');
            redirect(base_url('/settings/users'));
        }
        
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            Utils::flash('error', 'Geçersiz kullanıcı ID.');
            redirect(base_url('/settings/users'));
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            Utils::flash('error', 'Kullanıcı bulunamadı.');
            redirect(base_url('/settings/users'));
        }
        
        // Get MFA status
        $mfaEnabled = MfaService::isEnabledForUser($user);
        $mfaSecret = null;
        $qrCodeUri = null;
        
        if ($mfaEnabled) {
            $mfaSecret = $user['two_factor_secret'] ?? null;
            if ($mfaSecret) {
                $qrCodeUri = MfaService::getOtpUri($user, $mfaSecret);
            }
        }
        
        // ROUND 5: Get recovery codes from session (first-time display only)
        $recoveryCodes = [];
        $showRecoveryCodes = false;
        $sessionKey = 'mfa_recovery_codes_' . $userId;
        if (isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey])) {
            $recoveryCodes = $_SESSION[$sessionKey];
            $showRecoveryCodes = true;
            // Clear from session after first display (security)
            unset($_SESSION[$sessionKey]);
        } elseif ($mfaEnabled) {
            // If MFA already enabled, get recovery codes from DB (for regenerate/download)
            $backupCodesJson = $user['two_factor_backup_codes'] ?? null;
            if (!empty($backupCodesJson)) {
                $recoveryCodes = json_decode($backupCodesJson, true) ?: [];
            }
        }
        
        $data = [
            'title' => 'MFA Yönetimi - ' . ($user['username'] ?? 'Kullanıcı'),
            'user' => $user,
            'mfa_enabled' => $mfaEnabled,
            'mfa_secret' => $mfaSecret,
            'qr_code_uri' => $qrCodeUri,
            'recovery_codes' => $recoveryCodes,
            'show_recovery_codes' => $showRecoveryCodes,
            'csrf_token' => CSRF::generate(),
        ];
        
        echo View::renderWithLayout('settings/user_mfa', $data);
    }
    
    /**
     * ROUND 4: Admin MFA management - Enable MFA for a user (SUPERADMIN only)
     */
    public function enableUserMfa()
    {
        Auth::require();
        
        // Only SUPERADMIN can manage MFA for other users
        $currentUser = Auth::user();
        if (!isset($currentUser['role']) || $currentUser['role'] !== 'SUPERADMIN') {
            Utils::flash('error', 'Bu işlem için SUPERADMIN yetkisi gereklidir.');
            redirect(base_url('/settings/users'));
        }
        
        if (!ControllerHelper::requirePostOrRedirect('/settings/users')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/settings/users')) {
            return;
        }
        
        // Check if MFA is enabled globally
        if (!class_exists('MfaService') || !MfaService::isEnabled()) {
            Utils::flash('error', 'MFA sistemi aktif değil.');
            redirect(base_url('/settings/users'));
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            Utils::flash('error', 'Geçersiz kullanıcı ID.');
            redirect(base_url('/settings/users'));
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            Utils::flash('error', 'Kullanıcı bulunamadı.');
            redirect(base_url('/settings/users'));
        }
        
        // Generate MFA secret and recovery codes
        $result = MfaService::enableForUser($user);
        
        if ($result['success']) {
            // ROUND 5: Store recovery codes in session for first-time display
            $_SESSION['mfa_recovery_codes_' . $userId] = $result['recovery_codes'] ?? [];
            
            // Audit log
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logSecurity('MFA_ENABLED', $userId, [
                    'enabled_by' => Auth::id(),
                    'username' => $user['username'] ?? null,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
            Utils::flash('success', 'MFA başarıyla etkinleştirildi. Kullanıcıya QR kodu ve recovery code\'larını gösterin.');
            redirect(base_url('/settings/user-mfa?user_id=' . $userId));
        } else {
            Utils::flash('error', $result['message'] ?? 'MFA etkinleştirilemedi.');
            redirect(base_url('/settings/users'));
        }
    }
    
    /**
     * ROUND 5: Download recovery codes as TXT file
     */
    public function downloadRecoveryCodes()
    {
        Auth::require();
        
        // Only SUPERADMIN can download recovery codes for other users
        $currentUser = Auth::user();
        if (!isset($currentUser['role']) || $currentUser['role'] !== 'SUPERADMIN') {
            Utils::flash('error', 'Bu işlem için SUPERADMIN yetkisi gereklidir.');
            redirect(base_url('/settings/users'));
        }
        
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            Utils::flash('error', 'Geçersiz kullanıcı ID.');
            redirect(base_url('/settings/users'));
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            Utils::flash('error', 'Kullanıcı bulunamadı.');
            redirect(base_url('/settings/users'));
        }
        
        // Get recovery codes from session (first-time) or DB
        $recoveryCodes = [];
        $sessionKey = 'mfa_recovery_codes_' . $userId;
        if (isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey])) {
            $recoveryCodes = $_SESSION[$sessionKey];
        } else {
            $backupCodesJson = $user['two_factor_backup_codes'] ?? null;
            if (!empty($backupCodesJson)) {
                $recoveryCodes = json_decode($backupCodesJson, true) ?: [];
            }
        }
        
        if (empty($recoveryCodes)) {
            Utils::flash('error', 'Recovery code bulunamadı.');
            redirect(base_url('/settings/user-mfa?user_id=' . $userId));
        }
        
        // Generate TXT file
        $filename = 'mfa_recovery_codes_' . ($user['username'] ?? 'user') . '_' . date('Y-m-d') . '.txt';
        $content = "MFA Recovery Codes\n";
        $content .= "==================\n\n";
        $content .= "Kullanıcı: " . ($user['username'] ?? 'N/A') . "\n";
        $content .= "Oluşturulma Tarihi: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "ÖNEMLİ: Bu kodları güvenli bir yere kaydedin. TOTP uygulamanıza erişemediğinizde bu kodlarla giriş yapabilirsiniz.\n\n";
        $content .= "Recovery Codes:\n";
        $content .= "---------------\n";
        foreach ($recoveryCodes as $code) {
            $content .= $code . "\n";
        }
        $content .= "\n\nHer kod sadece bir kez kullanılabilir. Kullanıldıktan sonra listeden çıkarılır.\n";
        
        // Send file
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
    
    /**
     * ROUND 4: Admin MFA management - Disable MFA for a user (SUPERADMIN only)
     */
    public function disableUserMfa()
    {
        Auth::require();
        
        // Only SUPERADMIN can manage MFA for other users
        $currentUser = Auth::user();
        if (!isset($currentUser['role']) || $currentUser['role'] !== 'SUPERADMIN') {
            Utils::flash('error', 'Bu işlem için SUPERADMIN yetkisi gereklidir.');
            redirect(base_url('/settings/users'));
        }
        
        if (!ControllerHelper::requirePostOrRedirect('/settings/users')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/settings/users')) {
            return;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            Utils::flash('error', 'Geçersiz kullanıcı ID.');
            redirect(base_url('/settings/users'));
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            Utils::flash('error', 'Kullanıcı bulunamadı.');
            redirect(base_url('/settings/users'));
        }
        
        // Disable MFA
        $result = MfaService::disableForUser($user);
        
        if ($result['success']) {
            // Audit log
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logSecurity('MFA_DISABLED', $userId, [
                    'disabled_by' => Auth::id(),
                    'username' => $user['username'] ?? null,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
            Utils::flash('success', 'MFA başarıyla devre dışı bırakıldı.');
            redirect(base_url('/settings/users'));
        } else {
            Utils::flash('error', $result['message'] ?? 'MFA devre dışı bırakılamadı.');
            redirect(base_url('/settings/users'));
        }
    }
    
    public function regenerateBackupCodes()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/settings/security');
        }
        
        $userId = Auth::id();
        $password = $_POST['password'] ?? ''; // Password is hashed, don't sanitize
        
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
            redirect('/two-factor/backup-codes');
        } else {
            set_flash('error', 'Yedek kodlar oluşturulurken bir hata oluştu.');
            redirect('/settings/security');
        }
    }

    private function getRoleDefinitions(): array
    {
        if (class_exists('Roles')) {
            $definitions = Roles::byScope('staff');
            if (!empty($definitions)) {
                return $definitions;
            }
        }

        return [
            'ADMIN' => [
                'label' => 'Yönetici',
                'description' => 'Operasyon ve yönetim modüllerinin tamamında yetkili.',
            ],
            'OPERATOR' => [
                'label' => 'Operasyon Uzmanı',
                'description' => 'İş planlama ve saha koordinasyonu görevleri.',
            ],
        ];
    }

    private function getAssignableRoles(): array
    {
        $definitions = $this->getRoleDefinitions();
        $currentRole = method_exists('Auth', 'role') ? Auth::role() : null;
        $isSuperAdminUser = in_array($currentRole, ['SUPERADMIN', 'ADMIN'], true)
            || (class_exists('SuperAdmin') && SuperAdmin::isSuperAdmin());

        if (!$isSuperAdminUser) {
            unset($definitions['SUPERADMIN']);
        }

        return $definitions;
    }
}
