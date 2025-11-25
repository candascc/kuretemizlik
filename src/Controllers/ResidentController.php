<?php

declare(strict_types=1);

/**
 * Resident Portal Controller
 * Sakin portali - giriş, dashboard, talepler, ödemeler
 */
class ResidentController
{
    private $residentUserModel;
    private $residentRequestModel;
    private $managementFeeModel;
    private $buildingModel;
    private $unitModel;
    private $notificationService;

    public function __construct()
    {
        $this->residentUserModel = new ResidentUser();
        $this->residentRequestModel = new ResidentRequest();
        $this->managementFeeModel = new ManagementFee();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
        $this->notificationService = new NotificationService();
    }

    /**
     * Resident login page
     */
    public function login()
    {
        // ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
        // ===== CRITICAL FIX END =====
        
        // CSRF token üret (form render'da hazır olsun)
        if (class_exists('CSRF')) { CSRF::get(); }
        // Login sayfasını cache'leme (CSRF/token/oturum tutarlılığı için)
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // If already logged in, redirect to dashboard
        if (isset($_SESSION['resident_user_id'])) {
            redirect(base_url('/resident/dashboard'));
        }

        $flowState = $_SESSION['resident_login_flow'] ?? [
            'step' => 'phone',
            'phone' => '',
        ];

        view('resident/login', [
            'title' => 'Sakin Girişi',
            'flash' => Utils::getFlash(),
            'flowState' => $this->enrichFlowState($flowState),
        ]);
    }

    /**
     * Process resident login
     */
    public function processLogin()
    {
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/resident/login')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        // STAGE 2 ROUND 2: Use RateLimitHelper for centralized rate limiting
        $phoneInput = $_POST['phone'] ?? '';
        $normalizedPhone = $this->validateResidentPhone($phoneInput)['phone'] ?? null;
        $identifier = $normalizedPhone ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($identifier, 'login');
        if (!$rateLimitResult['allowed']) {
            // STAGE 4.3: Audit log rate limit exceeded
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logSecurity('RESIDENT_LOGIN_RATE_LIMIT_EXCEEDED', null, [
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'remaining_seconds' => $rateLimitResult['remaining_seconds']
                ]);
            }
            
            Utils::flash('error', $rateLimitResult['message']);
            redirect(base_url('/resident/login'));
            return;
        }
        
        $rateLimitKey = $rateLimitResult['rate_limit_key'];

        // ===== ERR-027 FIX: Extract validation logic to separate method =====
        $phoneValidation = $this->validateResidentPhone($_POST['phone'] ?? '');
        if (!$phoneValidation['valid']) {
            Utils::flash('error', $phoneValidation['error']);
            Utils::flash('phone_error', $phoneValidation['error']);
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
            return;
        }

        $normalizedPhone = $phoneValidation['phone'];
        // ===== ERR-027 FIX: End =====

        // ===== ERR-027 FIX: Extract resident validation logic to separate method =====
        $residentValidation = $this->findAndValidateResident($normalizedPhone);
        if (!$residentValidation['valid']) {
            // Rate limiting is already handled in findAndValidateResident
            Utils::flash('error', $residentValidation['error']);
            Utils::flash('phone_error', $residentValidation['error']);
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
            return;
        }

        $resident = $residentValidation['resident'];
        // ===== ERR-027 FIX: End =====

        // ===== ERR-027 FIX: Extract password flow setup to separate method =====
        if (ResidentUser::hasPassword($resident)) {
            $this->setupPasswordFlow($resident, $normalizedPhone);
            return;
        }
        // ===== ERR-027 FIX: End =====

        $this->triggerOtpChallenge(
            $resident,
            'set_password',
            'Telefonunuza doğrulama kodu gönderdik.'
        );

        redirect(base_url('/resident/login'));
    }

    public function processPasswordChallenge()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/login'));
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'password') {
            Utils::flash('error', 'Oturum doğrulama süresi doldu. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $password = $_POST['password'] ?? '';
        if ($password === '') {
            Utils::flash('error', 'Lütfen şifrenizi girin.');
            Utils::flash('password_error', 'Lütfen şifrenizi girin.');
            redirect(base_url('/resident/login'));
        }

        $resident = $this->residentUserModel->find((int)$flow['resident_id']);
        if (!$resident) {
            Utils::flash('error', 'Sakin kaydı bulunamadı.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        if (!ResidentUser::hasPassword($resident)) {
            $this->triggerOtpChallenge($resident, 'set_password', 'Telefonunuza doğrulama kodu gönderdik.');
            redirect(base_url('/resident/login'));
        }

        $passwordHash = (string)$resident['password_hash'];
        if (!password_verify($password, $passwordHash)) {
            $flow['password_attempts'] = ($flow['password_attempts'] ?? 0) + 1;
            $_SESSION['resident_login_flow'] = $flow;
            // Ensure session is written before redirect
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
                SessionHelper::ensureStarted();
            }

            // STAGE 2 ROUND 2: Record failed password attempt using RateLimitHelper
            if (isset($rateLimitKey)) {
                RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'] ?? 5, $rateLimitResult['block_duration'] ?? 300);
            }

            ActivityLogger::log('resident.login.password_failed', 'resident_user', (int)$resident['id'], [
                'attempt' => $flow['password_attempts'],
                'ip' => RateLimitHelper::getClientIp(),
            ]);

            if ($flow['password_attempts'] >= 5) {
                $this->triggerOtpChallenge($resident, 'login', 'Çok fazla hatalı deneme. Telefonunuza doğrulama kodu gönderdik.');
                redirect(base_url('/resident/login'));
            }

            Utils::flash('error', 'Şifre geçersiz. Lütfen tekrar deneyin.');
            Utils::flash('password_error', 'Şifre geçersiz. Lütfen tekrar deneyin.');
            redirect(base_url('/resident/login'));
        }

        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $this->residentUserModel->updatePassword((int)$resident['id'], $password);
                } catch (Exception $e) {
                    // Log but don't fail login if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for resident {$resident['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====

        // STAGE 2 ROUND 2: Clear rate limit on successful login
        if (isset($rateLimitKey)) {
            RateLimitHelper::clearRateLimit($rateLimitKey);
        }

        $this->residentUserModel->resetOtpState((int)$resident['id']);
        $this->completeResidentLogin((int)$resident['id'], 'password');
        Utils::flash('success', 'Giriş başarılı.');
        redirect(base_url('/resident/dashboard'));
    }

    public function processOtpVerification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/login'));
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'otp') {
            Utils::flash('error', 'Doğrulama adımı bulunamadı. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        // STAGE 2 ROUND 2: Rate limiting for OTP verification using RateLimitHelper
        $residentId = $flow['resident_id'] ?? null;
        $identifier = $residentId ? "resident_{$residentId}" : RateLimitHelper::getClientIp();
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($identifier, 'otp');
        if (!$rateLimitResult['allowed']) {
            Utils::flash('error', $rateLimitResult['message']);
            redirect(base_url('/resident/login'));
        }
        $rateLimitKey = $rateLimitResult['rate_limit_key'];

        $code = InputSanitizer::string($_POST['code'] ?? '', 10);
        if ($code === '') {
            Utils::flash('error', 'Lütfen doğrulama kodunu girin.');
            Utils::flash('otp_error', 'Lütfen doğrulama kodunu girin.');
            redirect(base_url('/resident/login'));
        }

        $otpService = new ResidentOtpService();
        $result = $otpService->verifyToken((int)($flow['token_id'] ?? 0), $code);

        if (!$result['success']) {
            $message = match ($result['reason'] ?? 'mismatch') {
                'expired' => 'Doğrulama kodunun süresi doldu. Lütfen yeniden kod talep edin.',
                'attempts_exceeded' => 'Çok fazla başarısız deneme yaptınız. Lütfen yeni kod isteyin.',
                'not_found', 'consumed' => 'Doğrulama kodu geçersiz. Lütfen yeniden deneyin.',
                default => 'Doğrulama kodu hatalı. Lütfen tekrar deneyin.',
            };

            if (!empty($result['attempts_remaining'])) {
                $message .= ' Kalan deneme: ' . $result['attempts_remaining'];
            }

            Utils::flash('error', $message);
            Utils::flash('otp_error', $message);

            if (in_array($result['reason'] ?? '', ['expired', 'attempts_exceeded'], true)) {
                $resident = $this->residentUserModel->find((int)$flow['resident_id']);
                if ($resident) {
                    $this->triggerOtpChallenge($resident, $flow['context'] ?? 'login', 'Yeni doğrulama kodu gönderdik.');
                } else {
                    $this->clearLoginFlow();
                }
            }

            redirect(base_url('/resident/login'));
        }

        $residentId = (int)$result['resident_user_id'];

        if (in_array($flow['context'] ?? 'login', ['set_password', 'password_reset'], true)) {
            $_SESSION['resident_login_flow'] = [
                'step' => 'set_password',
                'resident_id' => $residentId,
                'phone' => $flow['phone'] ?? null,
                'context' => $flow['context'],
            ];

            Utils::flash('success', 'Doğrulama başarılı. Şifrenizi belirleyin.');
            redirect(base_url('/resident/login'));
        }

        // STAGE 2 ROUND 2: Clear rate limit on successful OTP verification
        if (isset($rateLimitKey)) {
            RateLimitHelper::clearRateLimit($rateLimitKey);
        }
        
        $this->completeResidentLogin($residentId, 'otp');
        Utils::flash('success', 'Giriş başarılı.');
        redirect(base_url('/resident/dashboard'));
    }

    public function resendLoginOtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/login'));
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'otp') {
            Utils::flash('error', 'Doğrulama adımı bulunamadı. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $resident = $this->residentUserModel->find((int)$flow['resident_id']);
        if (!$resident) {
            Utils::flash('error', 'Sakin kaydı bulunamadı.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $this->triggerOtpChallenge($resident, $flow['context'] ?? 'login', 'Yeni doğrulama kodu telefonunuza gönderildi.');
        redirect(base_url('/resident/login'));
    }

    public function processPasswordSetup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/login'));
        }

        $flow = $_SESSION['resident_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'set_password') {
            Utils::flash('error', 'Şifre belirleme süresi doldu. Lütfen yeniden doğrulama yapın.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $validator = new Validator($_POST);
        $validator
            ->required('password', 'Lütfen şifrenizi girin.')
            ->password('password', ['min' => 8, 'require_symbol' => false], 'Şifreniz en az 8 karakter olmalı ve rakam içermelidir.')
            ->required('password_confirmation', 'Lütfen şifrenizi doğrulayın.')
            ->confirmed('password', 'password_confirmation', 'Şifreler eşleşmiyor.');

        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            Utils::flash('set_password_error', $validator->firstError());
            redirect(base_url('/resident/login'));
        }

        $resident = $this->residentUserModel->find((int)$flow['resident_id']);
        if (!$resident) {
            Utils::flash('error', 'Sakin kaydı bulunamadı.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $password = $validator->get('password');
        $this->residentUserModel->updatePassword($resident['id'], $password);
        $this->residentUserModel->resetOtpState((int)$resident['id']);

        ActivityLogger::log('resident.password_set', 'resident_user', (int)$resident['id'], [
            'context' => $flow['context'] ?? 'set_password',
        ]);

        $this->completeResidentLogin((int)$resident['id'], 'password_set');
        Utils::flash('success', 'Şifreniz oluşturuldu.');
        redirect(base_url('/resident/dashboard'));
    }

    public function initiatePasswordReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/login'));
        }

        $phoneInput = InputSanitizer::phone($_POST['phone'] ?? '');
        if ($phoneInput === '') {
            Utils::flash('error', 'Lütfen telefon numaranızı girin.');
            Utils::flash('phone_error', 'Lütfen telefon numaranızı girin.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $normalizedPhone = Utils::normalizePhone($phoneInput);
        if ($normalizedPhone === null) {
            Utils::flash('error', 'Geçerli bir telefon numarası girin.');
            Utils::flash('phone_error', 'Geçerli bir telefon numarası girin.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $resident = $this->residentUserModel->findByPhone($normalizedPhone);
        if (!$resident) {
            Utils::flash('error', 'Bu telefon numarasıyla eşleşen sakin bulunamadı.');
            Utils::flash('phone_error', 'Bu telefon numarasıyla eşleşen sakin bulunamadı.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        if (!(int)($resident['is_active'] ?? 0)) {
            Utils::flash('error', 'Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.');
            Utils::flash('phone_error', 'Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.');
            $this->clearLoginFlow();
            redirect(base_url('/resident/login'));
        }

        $this->triggerOtpChallenge($resident, 'password_reset', 'Şifre sıfırlama kodunu telefonunuza gönderdik.');

        ActivityLogger::log('resident.password_reset.requested', 'resident_user', (int)$resident['id'], [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        redirect(base_url('/resident/login'));
    }

    public function cancelLoginFlow()
    {
        $this->clearLoginFlow();
        redirect(base_url('/resident/login'));
    }

    private function triggerOtpChallenge(array $resident, string $context, ?string $successMessage = null): void
    {
        $otpService = new ResidentOtpService();

        try {
            $result = $otpService->requestToken($resident, 'sms', $_SERVER['REMOTE_ADDR'] ?? null, $context);
            $normalizedPhone = Utils::normalizePhone($resident['phone'] ?? '');

            $_SESSION['resident_login_flow'] = [
                'step' => 'otp',
                'resident_id' => (int)$resident['id'],
                'phone' => $normalizedPhone ?? ($resident['phone'] ?? null),
                'context' => $context,
                'token_id' => $result['token_id'],
                'expires_at' => $result['expires_at'],
                'masked_contact' => $result['masked_contact'],
                'otp_attempts' => 0,
                'resend_available_at' => date('Y-m-d H:i:s', time() + ResidentOtpService::RESEND_COOLDOWN_SECONDS),
            ];

            ActivityLogger::log('resident.login.code_sent', 'resident_user', (int)$resident['id'], [
                'channel' => 'sms',
                'context' => $context,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            if ($successMessage) {
                Utils::flash('success', $successMessage);
            }
        } catch (Exception $e) {
            ActivityLogger::log('resident.login.code_failed', 'resident_user', (int)($resident['id'] ?? 0), [
                'channel' => 'sms',
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            Utils::flash('error', $e->getMessage());
        }
    }

    private function clearLoginFlow(): void
    {
        unset($_SESSION['resident_login_flow'], $_SESSION['resident_login_pending']);
    }

    /**
     * Validate and normalize resident phone number
     * 
     * @param string $phoneInput Raw phone input
     * @return array ['valid' => bool, 'phone' => string|null, 'error' => string|null]
     */
    private function validateResidentPhone(string $phoneInput): array
    {
        $phoneInput = InputSanitizer::phone($phoneInput);
        if ($phoneInput === '') {
            return [
                'valid' => false,
                'phone' => null,
                'error' => 'Lütfen telefon numaranızı girin.'
            ];
        }

        $normalizedPhone = Utils::normalizePhone($phoneInput);
        if ($normalizedPhone === null) {
            return [
                'valid' => false,
                'phone' => null,
                'error' => 'Geçerli bir telefon numarası girin.'
            ];
        }

        return [
            'valid' => true,
            'phone' => $normalizedPhone,
            'error' => null
        ];
    }

    /**
     * Find and validate resident by phone number
     * 
     * @param string $normalizedPhone Normalized phone number
     * @return array ['valid' => bool, 'resident' => array|null, 'error' => string|null]
     */
    private function findAndValidateResident(string $normalizedPhone): array
    {
        $resident = $this->residentUserModel->findByPhone($normalizedPhone);
        if (!$resident) {
            // ===== ERR-012 FIX: Record failed login attempt =====
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateLimitKey = 'resident_login_' . $ipAddress;
            RateLimit::recordAttempt($rateLimitKey, 5, 300);
            // ===== ERR-012 FIX: End =====
            
            return [
                'valid' => false,
                'resident' => null,
                'error' => 'Girilen telefon numarası sistemde kayıtlı değil.'
            ];
        }

        if (!(int)($resident['is_active'] ?? 0)) {
            return [
                'valid' => false,
                'resident' => null,
                'error' => 'Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.'
            ];
        }

        return [
            'valid' => true,
            'resident' => $resident,
            'error' => null
        ];
    }

    /**
     * Setup password flow for resident login
     * 
     * @param array $resident Resident data
     * @param string $normalizedPhone Normalized phone number
     * @return void
     */
    private function setupPasswordFlow(array $resident, string $normalizedPhone): void
    {
        $_SESSION['resident_login_flow'] = [
            'step' => 'password',
            'resident_id' => (int)$resident['id'],
            'phone' => $resident['phone'] ?? $normalizedPhone,
            'password_attempts' => 0,
        ];

        Utils::flash('info', 'Lütfen şifrenizi girerek devam edin.');
        redirect(base_url('/resident/login'));
    }

    private function enrichFlowState(array $flow): array
    {
        $flow['step'] = $flow['step'] ?? 'phone';

        if (!empty($flow['phone'])) {
            $flow['phone_display'] = Utils::formatPhone($flow['phone']);
        }

        if (($flow['step'] ?? '') === 'otp') {
            $flow['otp_max_attempts'] = ResidentOtpService::MAX_ATTEMPTS;
            $flow['resend_cooldown'] = ResidentOtpService::RESEND_COOLDOWN_SECONDS;
            if (!empty($flow['expires_at'])) {
                $flow['expires_timestamp'] = strtotime($flow['expires_at']);
            }
        }

        return $flow;
    }

    /**
     * Resident logout
     */
    public function logout()
    {
        $residentId = $_SESSION['resident_user_id'] ?? null;
        
        // Phase 3.3: Proper session cleanup
        // Clear all resident session data
        unset($_SESSION['resident_user_id']);
        unset($_SESSION['resident_unit_id']);
        unset($_SESSION['resident_name']);
        unset($_SESSION['resident_email']);
        unset($_SESSION['resident_role']);
        $this->clearLoginFlow();

        if ($residentId) {
            ActivityLogger::log('resident.logout', 'resident_user', $residentId);
        }
        
        // Regenerate session ID for security
        if (SessionHelper::isActive()) {
            session_regenerate_id(true);
        }

        Utils::flash('success', 'Çıkış yapıldı');
        redirect(base_url('/resident/login'));
    }

    /**
     * Resident dashboard
     */
    public function dashboard()
    {
        $this->requireResidentAuth();

        $residentId = $_SESSION['resident_user_id'];
        $unitId = $_SESSION['resident_unit_id'];

        // Phase 3.1: Use EagerLoader for batch loading (no N+1 here, but good practice)
        $resident = $this->residentUserModel->find($residentId);
        $unit = $this->unitModel->find($unitId);
        $building = $unit ? EagerLoader::loadBuildings([$unit['building_id']])[$unit['building_id']] ?? null : null;

        require_once __DIR__ . '/../Constants/AppConstants.php';
        // Phase 4.2: Use constant for dashboard limits
        // Get recent fees
        $recentFees = $this->managementFeeModel->list([
            'unit_id' => $unitId
        ], AppConstants::DASHBOARD_RECENT_ITEMS, 0);

        // Get pending requests
        $pendingRequests = $this->residentRequestModel->list([
            'unit_id' => $unitId,
            'status' => 'open'
        ], AppConstants::DASHBOARD_RECENT_ITEMS, 0);

        // Get building announcements
        $announcements = $this->getBuildingAnnouncements($unit['building_id']);

        // Get upcoming meetings
        $meetings = $this->getUpcomingMeetings($unit['building_id']);

        $metricsService = new ResidentPortalMetricsService();
        $dashboardMetrics = $metricsService->getDashboardMetrics($unitId, (int)($unit['building_id'] ?? 0));

        $verificationService = new ResidentContactVerificationService();
        $pendingVerifications = $verificationService->listPending($residentId);

        // Phase 3.2: Optimize with array_filter and array_search
        $pendingByType = [
            'email' => null,
            'phone' => null,
        ];

        // Filter pending verifications and group by type
        $pendingOnly = array_filter($pendingVerifications, function($v) {
            return ($v['status'] ?? '') === 'pending';
        });
        
        foreach ($pendingOnly as $verification) {
            $type = $verification['verification_type'] ?? null;
            if (isset($pendingByType[$type]) && $pendingByType[$type] === null) {
                $pendingByType[$type] = $verification;
            }
        }

        $emailVerified = (int)($resident['email_verified'] ?? 0) === 1 && $pendingByType['email'] === null;
        $phoneVerified = (int)($resident['phone_verified'] ?? 0) === 1 && $pendingByType['phone'] === null;

        $verificationStatus = [
            'email' => [
                'key' => 'email',
                'label' => 'E-posta',
                'verified' => $emailVerified,
                'pending' => $pendingByType['email'] !== null,
                'contact' => $resident['email'] ?? '',
                'last_verified_at' => $resident['email_verified_at'] ?? null,
            ],
            'phone' => [
                'key' => 'phone',
                'label' => 'Telefon',
                'verified' => $phoneVerified,
                'pending' => $pendingByType['phone'] !== null,
                'contact' => $resident['phone'] ?? '',
                'last_verified_at' => $resident['phone_verified_at'] ?? null,
            ],
        ];

        $pendingOutstanding = (float)(($dashboardMetrics['pendingFees']['outstanding'] ?? null) ?? 0);
        $openRequestsCount = (int)($dashboardMetrics['openRequests'] ?? 0);
        $upcomingMeetingsCount = (int)($dashboardMetrics['meetings'] ?? 0);

        $onboardingCards = [];

        $onboardingCards[] = [
            'key' => 'fees',
            'icon' => 'fa-wallet',
            'accent' => $pendingOutstanding > 0 ? 'rose' : 'emerald',
            'title' => $pendingOutstanding > 0 ? 'Aidat Ödemesi Bekleniyor' : 'Aidatlarınız Güncel',
            'description' => $pendingOutstanding > 0
                ? 'Bu ay ödenecek tutar: ₺' . number_format($pendingOutstanding, 2, ',', '.')
                : 'Ödenmemiş aidat bulunmuyor.',
            'cta' => [
                'type' => 'link',
                'url' => base_url('/resident/fees'),
                'label' => $pendingOutstanding > 0 ? 'Aidat Öde' : 'Aidatları Görüntüle',
            ],
            'requires_action' => $pendingOutstanding > 0,
        ];

        $onboardingCards[] = [
            'key' => 'requests',
            'icon' => 'fa-screwdriver-wrench',
            'accent' => $openRequestsCount > 0 ? 'amber' : 'sky',
            'title' => $openRequestsCount > 0 ? 'Takipte Talepleriniz Var' : 'Tüm Talepler Tamamlandı',
            'description' => $openRequestsCount > 0
                ? sprintf('%d talep işlem aşamasında. Son durumu kontrol edebilirsiniz.', $openRequestsCount)
                : 'Yeni bir ihtiyaç için talep oluşturabilirsiniz.',
            'cta' => [
                'type' => 'link',
                'url' => $openRequestsCount > 0 ? base_url('/resident/requests') : base_url('/resident/create-request'),
                'label' => $openRequestsCount > 0 ? 'Talepleri Gör' : 'Talep Oluştur',
            ],
            'requires_action' => $openRequestsCount > 0,
        ];

        if (!$emailVerified || !$phoneVerified) {
            $missing = [];
            if (!$emailVerified) {
                $missing[] = 'e-posta';
            }
            if (!$phoneVerified) {
                $missing[] = 'telefon';
            }
            $onboardingCards[] = [
                'key' => 'verification',
                'icon' => 'fa-shield-halved',
                'accent' => 'fuchsia',
                'title' => 'İletişim Doğrulamasını Tamamlayın',
                'description' => ucfirst(implode(' ve ', $missing)) . ' doğrulama adımı bekliyor.',
                'cta' => [
                    'type' => 'modal',
                    'target' => 'contactVerificationModal',
                    'label' => 'Doğrulamayı Başlat',
                ],
                'requires_action' => true,
            ];
        } else {
            $onboardingCards[] = [
                'key' => 'meetings',
                'icon' => 'fa-calendar-check',
                'accent' => $upcomingMeetingsCount > 0 ? 'sky' : 'emerald',
                'title' => $upcomingMeetingsCount > 0 ? 'Yaklaşan Toplantılar' : 'Planlanan toplantı yok',
                'description' => $upcomingMeetingsCount > 0
                    ? sprintf('%d toplantı için hazırlanın. Gündemi incelemeyi unutmayın.', $upcomingMeetingsCount)
                    : 'Yeni toplantılar planlandığında burada göreceksiniz.',
                'cta' => [
                    'type' => 'link',
                    'url' => base_url('/resident/meetings'),
                    'label' => 'Toplantıları Görüntüle',
                ],
                'requires_action' => $upcomingMeetingsCount > 0,
            ];
        }

        $layoutData = $this->residentLayoutContext('resident-dashboard', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/dashboard', array_merge($layoutData, [
            'title' => 'Sakin Paneli',
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
            'recentFees' => $recentFees,
            'pendingRequests' => $pendingRequests,
            'announcements' => $announcements,
            'meetings' => $meetings,
            'dashboardMetrics' => $dashboardMetrics,
            'pendingVerifications' => $pendingVerifications,
            'pendingVerificationMap' => $pendingByType,
            'verificationStatus' => $verificationStatus,
            'onboardingCards' => $onboardingCards,
        ]));
    }

    /**
     * Management fees page
     */
    public function fees()
    {
        $this->requireResidentAuth();

        $unitId = $_SESSION['resident_unit_id'];
        require_once __DIR__ . '/../Constants/AppConstants.php';
        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $status = InputSanitizer::string($_GET['status'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);

        $filters = ['unit_id' => $unitId];
        if ($status) $filters['status'] = $status;

        // Phase 4.2: Use constant for pagination limit
        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $result = $this->managementFeeModel->paginate($filters, $limit, $offset);
        $fees = $result['data'];
        $total = $result['total'];
        $pagination = Utils::paginate($total, $limit, $page);

        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $unit = $this->unitModel->find($unitId);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $paymentConfirmation = $_SESSION['resident_payment_confirmation'] ?? null;
        if ($paymentConfirmation) {
            unset($_SESSION['resident_payment_confirmation']);
        }

        $layoutData = $this->residentLayoutContext('resident-fees', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/fees', array_merge($layoutData, [
            'title' => 'Aidatlarım',
            'fees' => $fees,
            'pagination' => $pagination,
            'filters' => $filters,
            'paymentConfirmation' => $paymentConfirmation,
        ]));
    }

    /**
     * Pay management fee
     */
    public function payFee($feeId)
    {
        $this->requireResidentAuth();

        $fee = $this->managementFeeModel->find($feeId);
        if (!$fee) {
            Utils::flash('error', 'Aidat bulunamadı');
            redirect(base_url('/resident/fees'));
        }

        // Check if fee belongs to resident's unit
        if ($fee['unit_id'] != $_SESSION['resident_unit_id']) {
            Utils::flash('error', 'Bu aidat sizin dairenize ait değil');
            redirect(base_url('/resident/fees'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processFeePayment($feeId);
            return;
        }

        // Phase 3.1: Use EagerLoader for batch loading
        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $unit = $this->unitModel->find($_SESSION['resident_unit_id']);
        $building = $unit ? (EagerLoader::loadBuildings([$unit['building_id']])[$unit['building_id']] ?? null) : null;

        $layoutData = $this->residentLayoutContext('resident-fees', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/pay-fee', array_merge($layoutData, [
            'title' => 'Aidat Ödeme',
            'fee' => $fee
        ]));
    }

    /**
     * Process fee payment
     */
    private function processFeePayment($feeId)
    {
        $paymentMethod = InputSanitizer::string($_POST['payment_method'] ?? '', 50);
        $amount = Utils::normalizeMoney($_POST['amount'] ?? '');
        $notes = InputSanitizer::string($_POST['notes'] ?? '', 500);

        $fee = $this->managementFeeModel->find($feeId);
        $remainingAmount = $fee['total_amount'] - $fee['paid_amount'];

        if ($amount <= 0 || $amount > $remainingAmount) {
            Utils::flash('error', 'Geçersiz ödeme tutarı');
            redirect(base_url('/resident/pay-fee/' . $feeId));
        }

        try {
            // Apply payment to management fee (this will also create money entry)
            $paymentResult = $this->managementFeeModel->applyPayment($feeId, $amount, $paymentMethod, date('Y-m-d'), $notes);

            $methodLabels = [
                'cash' => 'Nakit',
                'transfer' => 'Havale/EFT',
                'card' => 'Kredi Kartı',
                'check' => 'Çek',
            ];

            $_SESSION['resident_payment_confirmation'] = [
                'fee_id' => $feeId,
                'reference' => $paymentResult['reference'] ?? null,
                'money_entry_id' => $paymentResult['money_entry_id'] ?? null,
                'amount' => $amount,
                'method' => $paymentMethod,
                'method_label' => $methodLabels[$paymentMethod] ?? ucfirst($paymentMethod),
                'notes' => $notes,
                'status' => $paymentResult['status'] ?? null,
                'paid_total' => $paymentResult['paid_total'] ?? null,
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            // Send payment confirmation notification
            $this->notificationService->sendPaymentConfirmation($feeId, $amount);

            // Log activity
            ActivityLogger::log('resident.fee_payment', 'management_fee', $feeId, [
                'amount' => $amount,
                'method' => $paymentMethod,
                'resident_id' => $_SESSION['resident_user_id']
            ]);

            $this->clearResidentMetricsCache();

            Utils::flash('success', 'Ödeme başarıyla kaydedildi');
            redirect(base_url('/resident/fees'));

        } catch (Exception $e) {
            if (get_class($e) === 'RedirectException') {
                throw $e;
            }
            Utils::flash('error', 'Ödeme işlemi başarısız: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/resident/pay-fee/' . $feeId));
        }
    }


    /**
     * Requests page
     */
    public function requests()
    {
        $this->requireResidentAuth();

        $unitId = $_SESSION['resident_unit_id'];
        require_once __DIR__ . '/../Constants/AppConstants.php';
        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $status = InputSanitizer::string($_GET['status'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);

        $filters = ['unit_id' => $unitId];
        if ($status) $filters['status'] = $status;

        // Phase 4.2: Use constant for pagination limit
        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $result = $this->residentRequestModel->paginate($filters, $limit, $offset);
        $requests = $result['data'];
        $total = $result['total'];
        $pagination = Utils::paginate($total, $limit, $page);

        $statusSummary = $this->residentRequestModel->statusSummary([
            'unit_id' => $unitId,
        ]);

        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $unit = $this->unitModel->find($unitId);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $layoutData = $this->residentLayoutContext('resident-requests', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/requests', array_merge($layoutData, [
            'title' => 'Taleplerim',
            'requests' => $requests,
            'pagination' => $pagination,
            'filters' => $filters,
            'activeStatus' => $status,
            'statusSummary' => $statusSummary,
        ]));
    }

    /**
     * Request detail page
     */
    public function requestDetail($requestId)
    {
        $this->requireResidentAuth();

        $unitId = (int)($_SESSION['resident_unit_id'] ?? 0);
        $requestId = (int)$requestId;

        $request = $this->residentRequestModel->find($requestId);

        if (!$request || (int)($request['unit_id'] ?? 0) !== $unitId) {
            Utils::flash('error', 'Talep bulunamadı veya bu talebe erişim yetkiniz yok.');
            redirect(base_url('/resident/requests'));
        }

        $unit = $this->unitModel->find($unitId);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $timeline = [
            [
                'label' => 'Talep oluşturuldu',
                'timestamp' => $request['created_at'] ?? null,
                'description' => $request['subject'] ?? '',
            ],
        ];

        if (!empty($request['response'])) {
            $timeline[] = [
                'label' => 'Yanıt verildi',
                'timestamp' => $request['updated_at'] ?? null,
                'description' => $request['response'],
            ];
        }

        if (!empty($request['resolved_at'])) {
            $timeline[] = [
                'label' => $request['status'] === 'closed' ? 'Talep kapatıldı' : 'Talep çözüldü',
                'timestamp' => $request['resolved_at'],
                'description' => $request['status'] === 'closed'
                    ? 'Talep yönetim tarafından kapatıldı.'
                    : 'Talep çözüm süreci tamamlandı.',
            ];
        }

        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);

        $layoutData = $this->residentLayoutContext('resident-requests', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/request-detail', array_merge($layoutData, [
            'title' => 'Talep Detayı',
            'request' => $request,
            'timeline' => $timeline,
            'unit' => $unit,
            'building' => $building,
        ]));
    }

    /**
     * Create new request
     */
    public function createRequest()
    {
        $this->requireResidentAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCreateRequest();
            return;
        }

        // Phase 3.1: Use EagerLoader for batch loading
        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $unit = $this->unitModel->find($_SESSION['resident_unit_id']);
        $building = $unit ? (EagerLoader::loadBuildings([$unit['building_id']])[$unit['building_id']] ?? null) : null;

        $layoutData = $this->residentLayoutContext('resident-requests', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/create-request', array_merge($layoutData, [
            'title' => 'Yeni Talep'
        ]));
    }

    /**
     * Process create request
     */
    private function processCreateRequest()
    {
        $unitId = $_SESSION['resident_unit_id'];
        $unit = $this->unitModel->find($unitId);

        $data = [
            'building_id' => $unit['building_id'],
            'unit_id' => $unitId,
            'resident_user_id' => $_SESSION['resident_user_id'],
            'request_type' => InputSanitizer::string($_POST['request_type'] ?? '', 50),
            'category' => InputSanitizer::string($_POST['category'] ?? '', 100),
            'subject' => InputSanitizer::string($_POST['subject'] ?? '', 200),
            'description' => InputSanitizer::string($_POST['description'] ?? '', 2000),
            'priority' => InputSanitizer::string($_POST['priority'] ?? 'normal', 20)
        ];

        if (empty($data['subject']) || empty($data['description'])) {
            Utils::flash('error', 'Konu ve açıklama gereklidir');
            redirect(base_url('/resident/create-request'));
        }

        try {
            $requestId = $this->residentRequestModel->create($data);

            ActivityLogger::log('resident.request_created', 'resident_request', $requestId, [
                'type' => $data['request_type'],
                'subject' => $data['subject']
            ]);

            $this->clearResidentMetricsCache();

            Utils::flash('success', 'Talebiniz başarıyla oluşturuldu');
            redirect(base_url('/resident/requests'));

        } catch (Exception $e) {
            Utils::flash('error', 'Talep oluşturulamadı: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/resident/create-request'));
        }
    }

    /**
     * Building announcements
     */
    public function announcements()
    {
        $this->requireResidentAuth();

        $unitId = $_SESSION['resident_unit_id'];
        $unit = $this->unitModel->find($unitId);
        $buildingId = $unit['building_id'];

        $announcements = $this->getBuildingAnnouncements($buildingId);

        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $layoutData = $this->residentLayoutContext('resident-announcements', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/announcements', array_merge($layoutData, [
            'title' => 'Duyurular',
            'announcements' => $announcements
        ]));
    }

    /**
     * Building meetings
     */
    public function meetings()
    {
        $this->requireResidentAuth();

        $unitId = $_SESSION['resident_unit_id'];
        $unit = $this->unitModel->find($unitId);
        $buildingId = $unit['building_id'];

        $meetings = $this->getUpcomingMeetings($buildingId);

        $resident = $this->residentUserModel->find($_SESSION['resident_user_id']);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $layoutData = $this->residentLayoutContext('resident-meetings', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/meetings', array_merge($layoutData, [
            'title' => 'Toplantılar',
            'meetings' => $meetings
        ]));
    }

    /**
     * Profile page
     */
    public function profile()
    {
        $this->requireResidentAuth();

        $residentId = $_SESSION['resident_user_id'];
        $resident = $this->residentUserModel->find($residentId);
        $verificationService = new ResidentContactVerificationService();
        $preferenceService = new ResidentNotificationPreferenceService();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateProfile($residentId, $resident, $verificationService, $preferenceService);
            return;
        }

        $unit = $this->unitModel->find($_SESSION['resident_unit_id']);
        $building = $unit ? $this->buildingModel->find($unit['building_id']) : null;

        $layoutData = $this->residentLayoutContext('resident-profile', [
            'resident' => $resident,
            'unit' => $unit,
            'building' => $building,
        ]);

        echo View::renderWithLayout('resident/profile', array_merge($layoutData, [
            'title' => 'Profil',
            'resident' => $resident,
            'pendingVerifications' => $verificationService->listPending($residentId),
            'notificationCategories' => $preferenceService->getCategories(),
            'notificationPreferences' => $preferenceService->getResidentPreferences($residentId),
            'unit' => $unit,
            'building' => $building,
            'flash' => Utils::getFlash(),
        ]));
    }

    /**
     * Update profile
     */
    private function updateProfile(int $residentId, array $resident, ResidentContactVerificationService $verificationService, ResidentNotificationPreferenceService $preferenceService)
    {
        // CSRF verification for state-changing request
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.');
            redirect(base_url('/resident/profile'));
        }
        
        $formContext = InputSanitizer::string($_POST['form_context'] ?? 'contact', 50);

        if ($formContext === 'password') {
            $this->handlePasswordUpdate($residentId, $resident);
            return;
        }

        $name = InputSanitizer::string($_POST['name'] ?? '', 200);
        $email = InputSanitizer::email($_POST['email'] ?? '');
        $phoneInput = InputSanitizer::phone($_POST['phone'] ?? '');
        $secondaryEmail = InputSanitizer::email($_POST['secondary_email'] ?? '');
        $secondaryPhoneInput = InputSanitizer::phone($_POST['secondary_phone'] ?? '');
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $notifySms = isset($_POST['notify_sms']) ? 1 : 0;
        $existingPreferences = $preferenceService->getResidentPreferences($residentId);
        $categoryPreferences = [];
        foreach ($preferenceService->getCategories() as $key => $meta) {
            $existingEmail = !empty($existingPreferences[$key]['email']) ? 1 : 0;
            $existingSms = !empty($existingPreferences[$key]['sms']) ? 1 : 0;
            $emailField = 'pref_email_' . $key;
            $smsField = 'pref_sms_' . $key;

            $emailValue = $notifyEmail
                ? (isset($_POST[$emailField]) ? 1 : 0)
                : $existingEmail;

            if (empty($meta['supports_sms'])) {
                $smsValue = 0;
            } else {
                $smsValue = $notifySms
                    ? (isset($_POST[$smsField]) ? 1 : 0)
                    : $existingSms;
            }

            $categoryPreferences[$key] = [
                'email' => $emailValue,
                'sms' => $smsValue,
            ];
        }

        if ($name === '' || $email === '') {
            Utils::flash('error', 'Ad ve e-posta alanları zorunludur.');
            redirect(base_url('/resident/profile'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Utils::flash('error', 'Geçerli bir e-posta adresi girin.');
            redirect(base_url('/resident/profile'));
        }

        if ($secondaryEmail !== '' && !filter_var($secondaryEmail, FILTER_VALIDATE_EMAIL)) {
            Utils::flash('error', 'Geçerli bir ikincil e-posta adresi girin.');
            redirect(base_url('/resident/profile'));
        }

        $normalizedPhone = Utils::normalizePhone($phoneInput);
        if ($normalizedPhone === null) {
            Utils::flash('error', ResidentContactVerificationService::ERROR_INVALID_PHONE);
            redirect(base_url('/resident/profile'));
        }
        $this->assertNormalizedPhone($normalizedPhone);

        $normalizedSecondaryPhone = $secondaryPhoneInput !== ''
            ? Utils::normalizePhone($secondaryPhoneInput)
            : null;

        if ($normalizedSecondaryPhone === null && $secondaryPhoneInput !== '') {
            Utils::flash('error', ResidentContactVerificationService::ERROR_INVALID_PHONE);
            redirect(base_url('/resident/profile'));
        }
        if ($normalizedSecondaryPhone !== null) {
            $this->assertNormalizedPhone($normalizedSecondaryPhone);
            if ($normalizedSecondaryPhone === $normalizedPhone) {
                Utils::flash('error', ResidentContactVerificationService::ERROR_PHONE_DUPLICATE);
                redirect(base_url('/resident/profile'));
            }
        }

        if ($notifySms && $normalizedPhone === null) {
            Utils::flash('error', 'SMS bildirimleri için telefon numarası gereklidir.');
            redirect(base_url('/resident/profile'));
        }

        $existing = $this->residentUserModel->findByEmail($email);
        if ($existing && (int)$existing['id'] !== $residentId) {
            Utils::flash('error', 'Bu e-posta adresi başka bir hesapta kullanılıyor.');
            redirect(base_url('/resident/profile'));
        }

        $updateData = [
            'name' => $name,
            'secondary_email' => $secondaryEmail !== '' ? $secondaryEmail : null,
            'secondary_phone' => $normalizedSecondaryPhone,
            'notify_email' => $notifyEmail,
            'notify_sms' => $notifySms,
            'email_verified' => (int)($resident['email_verified'] ?? 0),
            'email_verified_at' => $resident['email_verified_at'] ?? null,
            'phone_verified' => (int)($resident['phone_verified'] ?? 0),
            'phone_verified_at' => $resident['phone_verified_at'] ?? null,
        ];

        $pendingMessages = [];

        if ($email !== ($resident['email'] ?? '')) {
            try {
                $verificationService->requestVerification($resident, 'email', $email);
                $pendingMessages[] = 'E-posta değişikliği için doğrulama kodu gönderildi.';
                ActivityLogger::log('resident.email_change_requested', 'resident_user', $residentId, [
                    'new_email' => $email,
                ]);
                $updateData['email_verified'] = 0;
                $updateData['email_verified_at'] = null;
            } catch (Exception $e) {
                Utils::flash('error', $e->getMessage());
                redirect(base_url('/resident/profile'));
            }
        } else {
            $updateData['email'] = $email;
        }

        if ($normalizedPhone !== ($resident['phone'] ?? null)) {
            try {
                $verificationService->requestVerification($resident, 'phone', $normalizedPhone);
                $pendingMessages[] = 'Telefon değişikliği için doğrulama kodu gönderildi.';
                ActivityLogger::log('resident.phone_change_requested', 'resident_user', $residentId, [
                    'new_phone' => $normalizedPhone,
                ]);
                $updateData['phone_verified'] = 0;
                $updateData['phone_verified_at'] = null;
            } catch (Exception $e) {
                Utils::flash('error', $e->getMessage());
                redirect(base_url('/resident/profile'));
            }
        } else {
            $updateData['phone'] = $normalizedPhone;
        }

        try {
            $this->residentUserModel->update($residentId, $updateData);
            $_SESSION['resident_name'] = $name;
            if (isset($updateData['email'])) {
                $_SESSION['resident_email'] = $updateData['email'];
            }

            $finalSecondaryPhone = $secondaryPhoneInput !== '' ? $normalizedSecondaryPhone : null;
            $hadSecondaryBefore = !empty($resident['secondary_phone']);
            if ($secondaryPhoneInput !== '' || $hadSecondaryBefore) {
                $this->residentUserModel->update($residentId, ['secondary_phone' => $finalSecondaryPhone]);
            }

            try {
                $preferenceService->updatePreferences($residentId, $categoryPreferences);
                ActivityLogger::log('resident.notification_preferences_updated', 'resident_user', $residentId, [
                    'preferences' => $categoryPreferences,
                ]);
                $this->clearResidentMetricsCache();
            } catch (Exception $e) {
                Utils::flash('error', 'Bildirim tercihleri güncellenemedi: ' . Utils::safeExceptionMessage($e));
                redirect(base_url('/resident/profile'));
            }

            if (!empty($pendingMessages)) {
                Utils::flash('success', implode(' ', $pendingMessages));
            } else {
                Utils::flash('success', 'İletişim bilgileriniz güncellendi.');
            }
            redirect(base_url('/resident/profile'));
        } catch (Exception $e) {
            Utils::flash('error', 'Profil güncellenemedi: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/resident/profile'));
        }
    }

    private function handlePasswordUpdate(int $residentId, array $resident): void
    {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            Utils::flash('error', 'Yeni şifre ve doğrulama alanları zorunludur.');
            redirect(base_url('/resident/profile'));
        }

        if ($newPassword !== $confirmPassword) {
            Utils::flash('error', 'Yeni şifreler eşleşmiyor.');
            redirect(base_url('/resident/profile'));
        }

        require_once __DIR__ . '/../Constants/AppConstants.php';
        // Phase 4.2: Use constant for password minimum length
        if (strlen($newPassword) < AppConstants::PASSWORD_MIN_LENGTH) {
            Utils::flash('error', 'Şifreniz en az ' . AppConstants::PASSWORD_MIN_LENGTH . ' karakter olmalıdır.');
            redirect(base_url('/resident/profile'));
        }

        $hasExistingPassword = ResidentUser::hasPassword($resident);

        if ($hasExistingPassword && !password_verify($currentPassword, $resident['password_hash'])) {
            Utils::flash('error', 'Mevcut şifrenizi doğru girdiğinizden emin olun.');
            redirect(base_url('/resident/profile'));
        }

        if ($hasExistingPassword && password_verify($newPassword, $resident['password_hash'])) {
            Utils::flash('error', 'Yeni şifreniz mevcut şifrenizle aynı olamaz.');
            redirect(base_url('/resident/profile'));
        }

        try {
            $this->residentUserModel->updatePassword($residentId, $newPassword);
            Utils::flash('success', 'Şifreniz başarıyla güncellendi.');
            redirect(base_url('/resident/profile'));
        } catch (Exception $e) {
            Utils::flash('error', 'Şifre güncellenemedi: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/resident/profile'));
        }
    }

    public function requestContactVerification()
    {
        $this->requireResidentAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/dashboard'));
        }

        $type = $_POST['type'] ?? '';
        if (!in_array($type, ['email', 'phone'], true)) {
            Utils::flash('error', 'Geçersiz doğrulama isteği.');
            redirect(base_url('/resident/dashboard'));
        }

        $residentId = (int)$_SESSION['resident_user_id'];
        $resident = $this->residentUserModel->find($residentId);
        if (!$resident) {
            Utils::flash('error', 'Sakin kaydı bulunamadı.');
            redirect(base_url('/resident/dashboard'));
        }

        $value = $type === 'email' ? trim((string)($resident['email'] ?? '')) : trim((string)($resident['phone'] ?? ''));
        if ($value === '') {
            Utils::flash('error', ucfirst($type) . ' bilgisi bulunamadı.');
            redirect(base_url('/resident/dashboard'));
        }

        $service = new ResidentContactVerificationService();

        try {
            $service->requestVerification($resident, $type, $value);
            $update = [];
            if ($type === 'email') {
                $update['email_verified'] = 0;
                $update['email_verified_at'] = null;
            } else {
                $update['phone_verified'] = 0;
                $update['phone_verified_at'] = null;
            }
            if (!empty($update)) {
                $this->residentUserModel->update($residentId, $update);
            }

            ActivityLogger::log('resident.contact_verification_requested', 'resident_user', $residentId, [
                'type' => $type,
                'context' => 'dashboard',
            ]);

            Utils::flash('success', 'Doğrulama kodu gönderildi.');
        } catch (Exception $e) {
            Utils::flash('error', $e->getMessage());
        }

        $redirectTo = $_POST['redirect'] ?? 'dashboard';
        $target = $redirectTo === 'profile'
            ? base_url('/resident/profile')
            : base_url('/resident/dashboard');
        redirect($target);
    }

    public function verifyContact()
    {
        $this->requireResidentAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/profile'));
        }

        $residentId = (int)$_SESSION['resident_user_id'];
        $verificationId = InputSanitizer::int($_POST['verification_id'] ?? 0, 1);
        $code = InputSanitizer::string($_POST['code'] ?? '', 10);

        if ($verificationId <= 0 || $code === '') {
            Utils::flash('error', 'Doğrulama kodunu girin.');
            redirect(base_url('/resident/profile'));
        }

        $service = new ResidentContactVerificationService();

        try {
            $result = $service->verify($residentId, $verificationId, $code);
            $update = [];
            if ($result['type'] === 'email') {
                $update['email'] = $result['new_value'];
                $update['email_verified'] = 1;
                $update['email_verified_at'] = date('Y-m-d H:i:s');
                $_SESSION['resident_email'] = $result['new_value'];
            } else {
                $update['phone'] = Utils::normalizePhone($result['new_value']);
                $update['phone_verified'] = 1;
                $update['phone_verified_at'] = date('Y-m-d H:i:s');
            }
            if (!empty($update)) {
                $this->residentUserModel->update($residentId, $update);
            }

            ActivityLogger::log('resident.contact_change_verified', 'resident_user', $residentId, [
                'type' => $result['type'],
            ]);

            Utils::flash('success', 'Yeni iletişim bilginiz doğrulandı.');
        } catch (Exception $e) {
            Utils::flash('error', $e->getMessage());
        }

        redirect(base_url('/resident/profile'));
    }

    public function resendContactVerification()
    {
        $this->requireResidentAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/resident/profile'));
        }

        $residentId = (int)$_SESSION['resident_user_id'];
        $verificationId = InputSanitizer::int($_POST['verification_id'] ?? 0, 1);

        if ($verificationId <= 0) {
            Utils::flash('error', 'Geçersiz doğrulama isteği.');
            redirect(base_url('/resident/profile'));
        }

        $service = new ResidentContactVerificationService();
        try {
            $service->resend($residentId, $verificationId);
            Utils::flash('success', 'Doğrulama kodu yeniden gönderildi.');
        } catch (Exception $e) {
            Utils::flash('error', $e->getMessage());
        }

        redirect(base_url('/resident/profile'));
    }

    /**
     * Helper methods
     */
    private function clearResidentMetricsCache(): void
    {
        $unitId = $_SESSION['resident_unit_id'] ?? null;
        if (!$unitId) {
            return;
        }

        $unit = $this->unitModel->find((int)$unitId);
        if (!$unit || empty($unit['building_id'])) {
            return;
        }

        ResidentPortalMetrics::clearCache();

        $metricsService = new ResidentPortalMetricsService();
        $metricsService->clearCache((int)$unitId, (int)$unit['building_id']);
    }

    private function requireResidentAuth(array $roles = [])
    {
        ResidentAuth::require($roles);
    }

    private function getBuildingAnnouncements($buildingId)
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM building_announcements 
             WHERE building_id = ? AND (expire_date IS NULL OR expire_date >= date('now'))
             ORDER BY priority DESC, publish_date DESC 
             LIMIT 10",
            [$buildingId]
        );
    }

    private function getUpcomingMeetings($buildingId)
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM building_meetings 
             WHERE building_id = ? AND meeting_date >= date('now') AND status = 'scheduled'
             ORDER BY meeting_date ASC 
             LIMIT 5",
            [$buildingId]
        );
    }

    private function completeResidentLogin(int $residentId, string $channel = 'otp'): void
    {
        $resident = $this->residentUserModel->find($residentId);
        if (!$resident) {
            throw new Exception('Sakin kaydı bulunamadı.');
        }

        // STAGE 2 ROUND 2: Rate limit clearing is handled in calling method

        // Set session data first
        $_SESSION['resident_user_id'] = $resident['id'];
        $_SESSION['resident_unit_id'] = $resident['unit_id'] ?? null;
        $_SESSION['resident_name'] = $resident['name'] ?? null;
        $_SESSION['resident_email'] = $resident['email'] ?? null;
        $_SESSION['resident_role'] = ResidentUser::normalizeRole($resident['role'] ?? null);
        
        // Regenerate session ID for security (prevent session fixation)
        // Do this AFTER setting session data to ensure data is preserved
        if (session_status() === PHP_SESSION_ACTIVE) {
            $oldSessionId = session_id();
            
            // Save current session data before regeneration
            $sessionData = $_SESSION;
            
            // Regenerate session ID (this may clear $_SESSION in some PHP versions)
            session_regenerate_id(true);
            $newSessionId = session_id();
            
            // Restore session data if it was cleared
            if (empty($_SESSION)) {
                $_SESSION = $sessionData;
            }
            
            // ===== KOZMOS_PATCH: session cookie fix after login (begin) =====
            // Eski cookie'yi sil (farklı path/domain kombinasyonları için)
            // This is critical for production environments (PHP-FPM)
            $is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            $cookieName = session_name();
            $cookieParams = session_get_cookie_params();
            
            // Eski cookie'yi farklı path/domain kombinasyonları için sil
            // Sadece headers gönderilmediyse cookie işlemleri yap
            if (!headers_sent()) {
                $pathsToClear = ['/', '/app'];
                $domainsToClear = ['', $cookieParams['domain'] ?? ''];
                
                foreach ($pathsToClear as $path) {
                    foreach ($domainsToClear as $domain) {
                        if ($oldSessionId && $oldSessionId !== $newSessionId) {
                            setcookie($cookieName, '', time() - 3600, $path, $domain, $is_https ? 1 : 0, true);
                        }
                    }
                }
                
                // Yeni cookie'yi set et - path /, domain '', samesite Lax
                setcookie($cookieName, $newSessionId, [
                    'expires' => 0,
                    'path' => '/',
                    'domain' => '', // null yerine '' kullan - PHP 8.1+ uyumluluğu için
                    'secure' => $is_https ? 1 : 0,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            // ===== KOZMOS_PATCH: session cookie fix after login (end) =====
        }

        $this->residentUserModel->update($resident['id'], [
            'last_login_at' => date('Y-m-d H:i:s')
        ]);
        $this->residentUserModel->resetOtpState((int)$resident['id']);

        ActivityLogger::log('resident.login', 'resident_user', $resident['id'], [
            'channel' => $channel,
        ]);
        
        // STAGE 4.3: Audit log successful resident login
        if (class_exists('AuditLogger')) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            AuditLogger::getInstance()->logAuth('RESIDENT_LOGIN_SUCCESS', (int)$resident['id'], [
                'resident_id' => (int)$resident['id'],
                'channel' => $channel,
                'ip_address' => $ipAddress
            ]);
        }

        $this->clearLoginFlow();
        
        // Session data will be written by redirect() function
        // Don't close session here as it may cause data loss
    }

    private function residentLayoutContext(string $activeKey, array $overrides = []): array
    {
        $resident = $overrides['resident'] ?? (isset($_SESSION['resident_user_id']) ? $this->residentUserModel->find($_SESSION['resident_user_id']) : null);
        $unit = $overrides['unit'] ?? (isset($_SESSION['resident_unit_id']) ? $this->unitModel->find($_SESSION['resident_unit_id']) : null);
        $building = $overrides['building'] ?? ($unit && !empty($unit['building_id']) ? $this->buildingModel->find($unit['building_id']) : null);

        $unitLabel = null;
        if ($building && $unit && !empty($unit['unit_number'])) {
            $unitLabel = sprintf('%s · %s', $building['name'] ?? 'Bina', $unit['unit_number']);
        } elseif ($unit && !empty($unit['unit_number'])) {
            $unitLabel = 'Daire ' . $unit['unit_number'];
        }

        $navigationItems = $overrides['navigationItems'] ?? $this->residentNavigationItems($activeKey);
        $roleLabel = $resident ? ResidentUser::roleLabel($resident['role'] ?? null) : ResidentUser::roleLabel(null);

        $headerOptions = [
            'resident' => [
                'name' => $resident['name'] ?? null,
                'unitLabel' => $unitLabel,
                'lastLoginAt' => $resident['last_login_at'] ?? null,
            ],
            'brand' => array_merge([
                'label' => $building['name'] ?? 'Sakin Portalı',
                'url' => base_url('/resident/dashboard'),
                'logo' => Utils::asset('img/logokureapp.png'),
                'logo_path' => 'img/logokureapp.png',
                'logo_fallback' => Utils::asset('img/logokureapp.png'),
                'logo_fallback_path' => 'img/logokureapp.png',
            ], $overrides['brand'] ?? []),
            'navGradient' => 'from-[#5c83ff] via-[#68cfff] to-[#60f5c3]',
            'navGradientStyle' => 'background-image: linear-gradient(115deg, #5c83ff 0%, #68cfff 50%, #60f5c3 100%);',
            'navigationItems' => $navigationItems,
            'quickActions' => [],
            'activeKey' => $activeKey,
            'user' => array_merge([
                'isAuthenticated' => isset($_SESSION['resident_user_id']),
                'username' => $resident['name'] ?? null,
                'logoutUrl' => base_url('/resident/logout'),
                'loginUrl' => base_url('/resident/login'),
                'chipMeta' => array_filter([$unitLabel, $roleLabel]),
            ], $overrides['user'] ?? []),
            'ui' => array_merge([
                'showQuickActions' => false,
            ], $overrides['ui'] ?? []),
        ];

        return [
            'headerVariant' => 'resident',
            'residentHeaderOptions' => $headerOptions,
        ];
    }

    private function residentNavigationItems(string $activeKey): array
    {
        $nav = [
            ['key' => 'resident-dashboard', 'label' => 'Ana Sayfa', 'icon' => 'fa-home', 'url' => '/resident/dashboard'],
            ['key' => 'resident-fees', 'label' => 'Aidatlar', 'icon' => 'fa-credit-card', 'url' => '/resident/fees'],
            ['key' => 'resident-requests', 'label' => 'Taleplerim', 'icon' => 'fa-screwdriver-wrench', 'url' => '/resident/requests'],
            ['key' => 'resident-announcements', 'label' => 'Duyurular', 'icon' => 'fa-bullhorn', 'url' => '/resident/announcements'],
            ['key' => 'resident-meetings', 'label' => 'Toplantılar', 'icon' => 'fa-people-group', 'url' => '/resident/meetings'],
            ['key' => 'resident-profile', 'label' => 'Profil', 'icon' => 'fa-user-circle', 'url' => '/resident/profile'],
        ];

        foreach ($nav as &$item) {
            $item['active'] = ($item['key'] === $activeKey);
        }

        return $nav;
    }

    private function assertNormalizedPhone(string $normalizedPhone): void
    {
        require_once __DIR__ . '/../Constants/AppConstants.php';
        $digits = preg_replace('/\D+/', '', $normalizedPhone);
        // Phase 4.2: Use constant for phone minimum length
        if (strlen($digits) < AppConstants::PHONE_MIN_LENGTH) {
            Utils::flash('error', ResidentContactVerificationService::ERROR_PHONE_LENGTH);
            redirect(base_url('/resident/profile'));
        }
        if (!str_starts_with($normalizedPhone, '+')) {
            Utils::flash('error', ResidentContactVerificationService::ERROR_PHONE_COUNTRY);
            redirect(base_url('/resident/profile'));
        }
    }
}

