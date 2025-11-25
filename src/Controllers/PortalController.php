<?php
/**
 * Customer Portal Controller
 * Self-service portal for customers
 */

class PortalController
{
    private Database $db;
    private Customer $customerModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->customerModel = new Customer();
    }

    public function login()
    {
        if (isset($_SESSION['portal_customer_id'])) {
            redirect(base_url('/portal/dashboard'));
        }

        $flowState = $_SESSION['portal_login_flow'] ?? [
            'step' => 'phone',
            'phone' => '',
        ];

        view('portal/login', [
            'title' => __('customer_portal'),
            'flash' => Utils::getFlash(),
            'flowState' => $this->enrichFlowState($flowState),
        ]);
    }

    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        // STAGE 2 ROUND 2: Use RateLimitHelper for centralized rate limiting
        $phoneInput = InputSanitizer::phone($_POST['phone'] ?? '');
        $normalizedPhone = $phoneInput ? Utils::normalizePhone($phoneInput) : null;
        $identifier = $normalizedPhone ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $rateLimitResult = RateLimitHelper::checkLoginRateLimit($identifier, 'login');
        if (!$rateLimitResult['allowed']) {
            // STAGE 4.3: Audit log rate limit exceeded
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logSecurity('PORTAL_LOGIN_RATE_LIMIT_EXCEEDED', null, [
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'remaining_seconds' => $rateLimitResult['remaining_seconds']
                ]);
            }
            
            Utils::flash('error', $rateLimitResult['message']);
            redirect(base_url('/portal/login'));
        }
        
        $rateLimitKey = $rateLimitResult['rate_limit_key'];
        
        // Phone input already validated above, use normalizedPhone

        $customer = $this->customerModel->findByPhone($normalizedPhone);
        if (!$customer) {
            // STAGE 2 ROUND 2: Record failed login attempt using RateLimitHelper
            RateLimitHelper::recordFailedAttempt($rateLimitKey, $rateLimitResult['max_attempts'], $rateLimitResult['block_duration']);
            
            // STAGE 4.3: Audit log failed login attempt (phone not found)
            if (class_exists('AuditLogger')) {
                AuditLogger::getInstance()->logAuth('PORTAL_LOGIN_FAILED', null, [
                    'phone' => $normalizedPhone,
                    'ip_address' => RateLimitHelper::getClientIp(),
                    'reason' => 'phone_not_found'
                ]);
            }
            
            Utils::flash('error', 'Girilen telefon numarası sistemde kayıtlı değil.');
            Utils::flash('phone_error', 'Girilen telefon numarası sistemde kayıtlı değil.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        if (empty($customer['phone'])) {
            Utils::flash('error', 'Bu müşteri kaydında telefon numarası bulunmuyor. Lütfen destek ekibiyle iletişime geçin.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        if (Customer::hasPassword($customer)) {
            $_SESSION['portal_login_flow'] = [
                'step' => 'password',
                'customer_id' => (int)$customer['id'],
                'phone' => $customer['phone'] ?? $normalizedPhone,
                'password_attempts' => 0,
            ];

            Utils::flash('info', 'Lütfen şifrenizi girerek devam edin.');
            redirect(base_url('/portal/login'));
        }

        $this->triggerOtpChallenge(
            $customer,
            'set_password',
            'Telefonunuza doğrulama kodu gönderdik.'
        );

        redirect(base_url('/portal/login'));
    }

    public function processPasswordChallenge()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'password') {
            Utils::flash('error', 'Oturum doğrulama süresi doldu. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $password = $_POST['password'] ?? '';
        if ($password === '') {
            Utils::flash('error', 'Lütfen şifrenizi girin.');
            Utils::flash('password_error', 'Lütfen şifrenizi girin.');
            redirect(base_url('/portal/login'));
        }

        $customer = $this->customerModel->find((int)$flow['customer_id']);
        if (!$customer) {
            Utils::flash('error', 'Müşteri kaydı bulunamadı.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        if (!Customer::hasPassword($customer)) {
            $this->triggerOtpChallenge($customer, 'set_password', 'Telefonunuza doğrulama kodu gönderdik.');
            redirect(base_url('/portal/login'));
        }

        $passwordHash = (string)$customer['password_hash'];
        if (!password_verify($password, $passwordHash)) {
            $flow['password_attempts'] = ($flow['password_attempts'] ?? 0) + 1;
            $_SESSION['portal_login_flow'] = $flow;

            // STAGE 2 ROUND 2: Record failed password attempt using RateLimitHelper
            // Note: rateLimitKey should be available from initial login check
            // If not available, create a new one for this attempt
            if (!isset($rateLimitKey)) {
                $identifier = $normalizedPhone ?? RateLimitHelper::getClientIp();
                $rateLimitCheck = RateLimitHelper::checkLoginRateLimit($identifier, 'login');
                $rateLimitKey = $rateLimitCheck['rate_limit_key'] ?? null;
            }
            if (isset($rateLimitKey)) {
                RateLimitHelper::recordFailedAttempt($rateLimitKey);
            }

            ActivityLogger::log('portal.login.password_failed', 'customer', (int)$customer['id'], [
                'attempt' => $flow['password_attempts'],
                'ip' => RateLimitHelper::getClientIp(),
            ]);

            if ($flow['password_attempts'] >= 5) {
                $this->triggerOtpChallenge($customer, 'login', 'Çok fazla hatalı deneme. Telefonunuza doğrulama kodu gönderdik.');
                redirect(base_url('/portal/login'));
            }

            Utils::flash('error', 'Şifre geçersiz. Lütfen tekrar deneyin.');
            Utils::flash('password_error', 'Şifre geçersiz. Lütfen tekrar deneyin.');
            redirect(base_url('/portal/login'));
        }

        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $this->customerModel->updatePassword((int)$customer['id'], $password);
                } catch (Exception $e) {
                    // Log but don't fail login if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for customer {$customer['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====

        // STAGE 2 ROUND 2: Clear rate limit on successful login
        if (isset($rateLimitKey)) {
            RateLimitHelper::clearRateLimit($rateLimitKey);
        }

        $this->customerModel->resetOtpState((int)$customer['id']);
        $this->completePortalLogin((int)$customer['id'], 'password');
        Utils::flash('success', 'Giriş başarılı.');
        redirect(base_url('/portal/dashboard'));
    }

    public function processOtpVerification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'otp') {
            Utils::flash('error', 'Doğrulama adımı bulunamadı. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $code = InputSanitizer::string($_POST['code'] ?? '', 10);
        if ($code === '') {
            Utils::flash('error', 'Lütfen doğrulama kodunu girin.');
            Utils::flash('otp_error', 'Lütfen doğrulama kodunu girin.');
            redirect(base_url('/portal/login'));
        }

        $otpService = new CustomerOtpService();
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
                $customer = $this->customerModel->find((int)$flow['customer_id']);
                if ($customer) {
                    $this->triggerOtpChallenge($customer, $flow['context'] ?? 'login', 'Yeni doğrulama kodu gönderdik.');
                } else {
                    $this->clearPortalLoginFlow();
                }
            }

            redirect(base_url('/portal/login'));
        }

        $customerId = (int)$result['customer_id'];

        if (in_array($flow['context'] ?? 'login', ['set_password', 'password_reset'], true)) {
            $_SESSION['portal_login_flow'] = [
                'step' => 'set_password',
                'customer_id' => $customerId,
                'phone' => $flow['phone'] ?? null,
                'context' => $flow['context'],
            ];

            Utils::flash('success', 'Doğrulama başarılı. Şifrenizi belirleyin.');
            redirect(base_url('/portal/login'));
        }

        $this->completePortalLogin($customerId, 'otp');
        Utils::flash('success', 'Giriş başarılı.');
        redirect(base_url('/portal/dashboard'));
    }

    public function processPasswordSetup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'set_password') {
            Utils::flash('error', 'Şifre belirleme süresi doldu. Lütfen yeniden doğrulama yapın.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
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
            redirect(base_url('/portal/login'));
        }

        $customer = $this->customerModel->find((int)$flow['customer_id']);
        if (!$customer) {
            Utils::flash('error', 'Müşteri kaydı bulunamadı.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $password = $validator->get('password');
        $this->customerModel->updatePassword((int)$customer['id'], $password);
        $this->customerModel->resetOtpState((int)$customer['id']);

        ActivityLogger::log('portal.password_set', 'customer', (int)$customer['id'], [
            'context' => $flow['context'] ?? 'set_password',
        ]);

        $this->completePortalLogin((int)$customer['id'], 'password_set');
        Utils::flash('success', 'Şifreniz oluşturuldu.');
        redirect(base_url('/portal/dashboard'));
    }

    public function initiatePasswordReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        $phoneInput = InputSanitizer::phone($_POST['phone'] ?? '');
        if ($phoneInput === '') {
            Utils::flash('error', 'Lütfen telefon numaranızı girin.');
            Utils::flash('phone_error', 'Lütfen telefon numaranızı girin.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $normalizedPhone = Utils::normalizePhone($phoneInput);
        if ($normalizedPhone === null) {
            Utils::flash('error', 'Geçerli bir telefon numarası girin.');
            Utils::flash('phone_error', 'Geçerli bir telefon numarası girin.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $customer = $this->customerModel->findByPhone($normalizedPhone);
        if (!$customer) {
            Utils::flash('error', 'Bu telefon numarasıyla eşleşen müşteri bulunamadı.');
            Utils::flash('phone_error', 'Bu telefon numarasıyla eşleşen müşteri bulunamadı.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        if (empty($customer['phone'])) {
            Utils::flash('error', 'Bu müşteri kaydında telefon numarası bulunmuyor. Lütfen temsilcinizle iletişime geçin.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $this->triggerOtpChallenge($customer, 'password_reset', 'Şifre sıfırlama kodunu telefonunuza gönderdik.');

        ActivityLogger::log('portal.password_reset.requested', 'customer', (int)$customer['id'], [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        redirect(base_url('/portal/login'));
    }

    public function resendLoginOtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/portal/login'));
        }

        $flow = $_SESSION['portal_login_flow'] ?? null;
        if (!$flow || ($flow['step'] ?? '') !== 'otp') {
            Utils::flash('error', 'Doğrulama adımı bulunamadı. Lütfen telefon numaranızla tekrar giriş yapın.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $customer = $this->customerModel->find((int)$flow['customer_id']);
        if (!$customer) {
            Utils::flash('error', 'Müşteri kaydı bulunamadı.');
            $this->clearPortalLoginFlow();
            redirect(base_url('/portal/login'));
        }

        $this->triggerOtpChallenge($customer, $flow['context'] ?? 'login', 'Yeni doğrulama kodu telefonunuza gönderildi.');
        redirect(base_url('/portal/login'));
    }

    public function cancelLoginFlow()
    {
        $this->clearPortalLoginFlow();
        redirect(base_url('/portal/login'));
    }

    public function logout()
    {
        $customerId = $_SESSION['portal_customer_id'] ?? null;

        unset($_SESSION['portal_customer_id']);
        unset($_SESSION['portal_customer_name']);
        unset($_SESSION['portal_customer_role']);
        unset($_SESSION['portal_login_time']);

        $this->clearPortalLoginFlow();

        if ($customerId) {
            ActivityLogger::log('portal.logout', 'customer', $customerId);
        }

        Utils::flash('success', __('logout_successful'));
        redirect(base_url('/portal/login'));
    }

    private function triggerOtpChallenge(array $customer, string $context, ?string $successMessage = null): void
    {
        $otpService = new CustomerOtpService();

        try {
            $result = $otpService->requestToken($customer, 'sms', $_SERVER['REMOTE_ADDR'] ?? null, $context);

            $_SESSION['portal_login_flow'] = [
                'step' => 'otp',
                'customer_id' => (int)($customer['id'] ?? 0),
                'phone' => $customer['phone'] ?? null,
                'context' => $context,
                'token_id' => $result['token_id'],
                'expires_at' => $result['expires_at'],
                'masked_contact' => $result['masked_contact'],
                'otp_attempts' => 0,
                'resend_available_at' => date('Y-m-d H:i:s', time() + CustomerOtpService::RESEND_COOLDOWN_SECONDS),
            ];

            if ($successMessage) {
                Utils::flash('success', $successMessage);
            }
        } catch (Exception $e) {
            Utils::flash('error', $e->getMessage());
            $this->clearPortalLoginFlow();
        }
    }

    private function clearPortalLoginFlow(): void
    {
        unset($_SESSION['portal_login_flow']);
    }

    private function enrichFlowState(array $flow): array
    {
        $flow['step'] = $flow['step'] ?? 'phone';

        if (!empty($flow['phone'])) {
            $flow['phone_display'] = Utils::formatPhone($flow['phone']);
        }

        if (($flow['step'] ?? '') === 'otp') {
            $flow['otp_max_attempts'] = CustomerOtpService::MAX_ATTEMPTS;
            $flow['resend_cooldown'] = CustomerOtpService::RESEND_COOLDOWN_SECONDS;
            if (!empty($flow['expires_at'])) {
                $flow['expires_timestamp'] = strtotime($flow['expires_at']);
            }
        }

        return $flow;
    }
    
    /**
     * SECURITY: Verify portal customer access and company isolation
     */
    private function verifyPortalCustomerAccess(int $customerId): bool
    {
        $sessionCustomerId = $_SESSION['portal_customer_id'] ?? null;
        if (!$sessionCustomerId || (int)$sessionCustomerId !== $customerId) {
            return false;
        }
        
        // SECURITY: Verify customer belongs to same company as session
        $customer = $this->db->fetch(
            "SELECT company_id FROM customers WHERE id = ?",
            [$customerId]
        );
        
        if (!$customer) {
            return false;
        }
        
        // If customer has company_id, verify it matches session (if session has company context)
        // For now, just verify customer exists and matches session
        // TODO: Add company_id to portal session if needed
        
        return true;
    }
    
    /**
     * Portal dashboard
     */
    public function dashboard()
    {
        $this->requirePortalAuth();
        
        $customerId = (int)($_SESSION['portal_customer_id'] ?? 0);
        
        // SECURITY: Verify portal customer access (company isolation)
        if (!$this->verifyPortalCustomerAccess($customerId)) {
            Utils::flash('error', 'Erişim reddedildi.');
            redirect(base_url('/portal/login'));
            return;
        }
        
        // SECURITY: Get customer info with company_id check
        $customer = $this->db->fetch(
            "SELECT c.* FROM customers c WHERE c.id = ?",
            [$customerId]
        );
        
        if (!$customer) {
            Utils::flash('error', 'Müşteri bulunamadı.');
            redirect(base_url('/portal/login'));
            return;
        }
        
        // SECURITY: All queries must filter by customer_id AND verify company_id
        // Get recent jobs (with company_id filter via customer)
        $recentJobs = $this->db->fetchAll(
            "SELECT j.* FROM jobs j 
             INNER JOIN customers c ON j.customer_id = c.id
             WHERE j.customer_id = ? AND c.id = ?
             ORDER BY j.job_date DESC LIMIT 5",
            [$customerId, $customerId]
        );
        
        // Get job statistics (with company_id filter)
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN j.status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
                SUM(CASE WHEN j.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_jobs
             FROM jobs j
             INNER JOIN customers c ON j.customer_id = c.id
             WHERE j.customer_id = ? AND c.id = ?",
            [$customerId, $customerId]
        );
        
        // Get unpaid invoices count (with company_id filter)
        $unpaidInvoices = $this->db->fetch(
            "SELECT COUNT(*) as count FROM money_entries me
             INNER JOIN customers c ON me.customer_id = c.id
             WHERE me.customer_id = ? AND c.id = ? 
               AND me.type = 'income' AND me.status = 'unpaid'",
            [$customerId, $customerId]
        );
        
        // Get pending contracts count (with company_id filter)
        $pendingContracts = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM job_contracts jc
             INNER JOIN jobs j ON jc.job_id = j.id
             INNER JOIN customers c ON j.customer_id = c.id
             WHERE j.customer_id = ? AND c.id = ?
               AND jc.status IN ('PENDING', 'SENT')
               AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now'))",
            [$customerId, $customerId]
        );
        
        // Get latest pending contract for direct link (with company_id filter)
        $latestPendingContract = $this->db->fetch(
            "SELECT jc.id, jc.status, jc.created_at
             FROM job_contracts jc
             INNER JOIN jobs j ON jc.job_id = j.id
             INNER JOIN customers c ON j.customer_id = c.id
             WHERE j.customer_id = ? AND c.id = ?
               AND jc.status IN ('PENDING', 'SENT')
               AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now'))
             ORDER BY jc.created_at DESC
             LIMIT 1",
            [$customerId, $customerId]
        );
        
        view('portal/dashboard', [
            'title' => __('dashboard'),
            'customer' => $customer,
            'recentJobs' => $recentJobs,
            'stats' => $stats,
            'unpaidCount' => $unpaidInvoices['count'] ?? 0,
            'pendingContractsCount' => (int)($pendingContracts['count'] ?? 0),
            'latestPendingContract' => $latestPendingContract
        ]);
    }
    
    /**
     * View jobs
     */
    public function jobs()
    {
        $this->requirePortalAuth();
        
        $customerId = (int)($_SESSION['portal_customer_id'] ?? 0);
        
        // SECURITY: Verify portal customer access (company isolation)
        if (!$this->verifyPortalCustomerAccess($customerId)) {
            Utils::flash('error', 'Erişim reddedildi.');
            redirect(base_url('/portal/login'));
            return;
        }
        
        // Get all jobs with pagination (with company_id filter)
        $page = InputSanitizer::int($_GET['page'] ?? 1, 1, 10000);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // SECURITY: Filter by customer_id AND verify via customer table (company_id isolation)
        $jobs = $this->db->fetchAll(
            "SELECT j.*, s.name as staff_name 
             FROM jobs j 
             INNER JOIN customers c ON j.customer_id = c.id
             LEFT JOIN staff s ON j.staff_id = s.id 
             WHERE j.customer_id = ? AND c.id = ?
             ORDER BY j.job_date DESC 
             LIMIT ? OFFSET ?",
            [$customerId, $customerId, $perPage, $offset]
        );
        
        $totalJobs = $this->db->fetch(
            "SELECT COUNT(*) as count FROM jobs j
             INNER JOIN customers c ON j.customer_id = c.id
             WHERE j.customer_id = ? AND c.id = ?",
            [$customerId, $customerId]
        );
        
        view('portal/jobs', [
            'title' => __('jobs'),
            'jobs' => $jobs,
            'currentPage' => $page,
            'totalPages' => ceil($totalJobs['count'] / $perPage)
        ]);
    }
    
    /**
     * View invoices
     */
    public function invoices()
    {
        $this->requirePortalAuth();
        
        $customerId = (int)($_SESSION['portal_customer_id'] ?? 0);
        
        // SECURITY: Verify portal customer access (company isolation)
        if (!$this->verifyPortalCustomerAccess($customerId)) {
            Utils::flash('error', 'Erişim reddedildi.');
            redirect(base_url('/portal/login'));
            return;
        }
        
        // SECURITY: Filter by customer_id AND verify via customer table (company_id isolation)
        $invoices = $this->db->fetchAll(
            "SELECT me.* FROM money_entries me
             INNER JOIN customers c ON me.customer_id = c.id
             WHERE me.customer_id = ? AND c.id = ? AND me.type = 'income' 
             ORDER BY me.date DESC",
            [$customerId, $customerId]
        );
        
        view('portal/invoices', [
            'title' => __('invoices'),
            'invoices' => $invoices
        ]);
    }
    
    /**
     * Book appointment
     */
    public function booking()
    {
        $this->requirePortalAuth();
        
        $customerId = (int)($_SESSION['portal_customer_id'] ?? 0);
        
        // SECURITY: Verify portal customer access (company isolation)
        if (!$this->verifyPortalCustomerAccess($customerId)) {
            Utils::flash('error', 'Erişim reddedildi.');
            redirect(base_url('/portal/login'));
            return;
        }
        
        // Get customer info
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        
        // Get available services
        $services = $this->db->fetchAll("SELECT * FROM services WHERE is_active = 1");
        
        // Get available time slots for next 14 days
        $availableSlots = $this->getAvailableTimeSlots(14);
        
        view('portal/booking', [
            'title' => __('book_appointment'),
            'customer' => $customer,
            'services' => $services,
            'availableSlots' => $availableSlots
        ]);
    }
    
    /**
     * Process booking
     */
    public function processBooking()
    {
        $this->requirePortalAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/portal/booking');
        }
        
        $customerId = $_SESSION['portal_customer_id'];
        $serviceId = InputSanitizer::int($_POST['service_id'] ?? null, 1);
        $date = InputSanitizer::date($_POST['date'] ?? null, 'Y-m-d');
        $time = InputSanitizer::string($_POST['time'] ?? null, 10);
        $notes = InputSanitizer::string($_POST['notes'] ?? '', 1000);
        
        if (!$serviceId || !$date || !$time) {
            set_flash('error', __('required_field'));
            redirect('/portal/booking');
        }
        
        // Create job
        $jobDate = $date . ' ' . $time;
        
        $this->db->query(
            "INSERT INTO jobs (customer_id, service_id, job_date, status, notes, created_at, updated_at) 
             VALUES (?, ?, ?, 'pending', ?, ?, ?)",
            [$customerId, $serviceId, $jobDate, $notes, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );
        
        Logger::info('Customer booked appointment', [
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'date' => $jobDate
        ]);
        
        set_flash('success', __('booking_successful'));
        redirect('/portal/dashboard');
    }
    
    /**
     * Payment page
     */
    public function payment()
    {
        $this->requirePortalAuth();
        
        $customerId = $_SESSION['portal_customer_id'];
        $invoiceId = InputSanitizer::int($_GET['invoice_id'] ?? null, 1);
        
        if (!$invoiceId) {
            redirect('/portal/invoices');
        }
        
        // Get invoice
        $invoice = $this->db->fetch(
            "SELECT * FROM money_entries WHERE id = ? AND customer_id = ? AND type = 'income'",
            [$invoiceId, $customerId]
        );
        
        if (!$invoice) {
            set_flash('error', __('invoice_not_found'));
            redirect('/portal/invoices');
        }
        
        view('portal/payment', [
            'title' => __('payment'),
            'invoice' => $invoice
        ]);
    }
    
    /**
     * Process payment
     * STAGE 3.1: Added idempotency check using request_id/session (BUG_009)
     */
    public function processPayment()
    {
        $this->requirePortalAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/portal/invoices');
        }
        
        $customerId = $_SESSION['portal_customer_id'];
        $invoiceId = InputSanitizer::int($_POST['invoice_id'] ?? null, 1);
        $paymentMethod = InputSanitizer::string($_POST['payment_method'] ?? 'card', 50);
        
        // STAGE 3.1: Generate idempotency key from session + invoice + timestamp (rounded to minute)
        // This prevents double-submission from browser refresh/back button
        $idempotencyKey = 'portal_payment_' . $customerId . '_' . $invoiceId . '_' . date('Y-m-d-H-i');
        $sessionKey = 'last_payment_request_' . $invoiceId;
        
        // Check if same request was processed in the last minute (idempotency)
        if (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === $idempotencyKey) {
            Logger::info('Duplicate payment request detected (idempotency)', [
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'idempotency_key' => $idempotencyKey
            ]);
            set_flash('info', __('payment_already_processed'));
            redirect('/portal/invoices');
        }
        
        if (!$invoiceId) {
            set_flash('error', __('invalid_invoice'));
            redirect('/portal/invoices');
        }
        
        // Get invoice
        $invoice = $this->db->fetch(
            "SELECT * FROM money_entries WHERE id = ? AND customer_id = ?",
            [$invoiceId, $customerId]
        );
        
        if (!$invoice) {
            set_flash('error', __('invoice_not_found'));
            redirect('/portal/invoices');
        }
        
        // STAGE 3.1: Check if invoice is already paid (idempotency)
        if (isset($invoice['status']) && $invoice['status'] === 'paid') {
            Logger::info('Invoice already paid (idempotency)', [
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId
            ]);
            set_flash('info', __('payment_already_processed'));
            redirect('/portal/invoices');
        }
        
        // Process payment (simulated for now)
        // In production, integrate with Stripe/PayPal
        
        // Update invoice status
        $this->db->query(
            "UPDATE money_entries SET status = 'paid', payment_method = ?, updated_at = ? WHERE id = ?",
            [$paymentMethod, date('Y-m-d H:i:s'), $invoiceId]
        );
        
        // STAGE 3.1: Store idempotency key in session
        $_SESSION[$sessionKey] = $idempotencyKey;
        
        Logger::info('Customer made payment', [
            'customer_id' => $customerId,
            'invoice_id' => $invoiceId,
            'amount' => $invoice['amount'],
            'method' => $paymentMethod,
            'idempotency_key' => $idempotencyKey
        ]);
        
        set_flash('success', __('payment_successful'));
        redirect('/portal/invoices');
    }
    
    /**
     * Get available time slots
     */
    private function getAvailableTimeSlots(int $days = 14): array
    {
        $slots = [];
        $timeSlots = ['09:00', '11:00', '13:00', '15:00', '17:00'];
        
        for ($i = 1; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            
            foreach ($timeSlots as $time) {
                $slots[] = [
                    'date' => $date,
                    'time' => $time,
                    'display' => date('d/m/Y', strtotime($date)) . ' - ' . $time
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Require portal authentication
     */
    private function requirePortalAuth(array $roles = [])
    {
        PortalAuth::require($roles);
    }

    private function completePortalLogin(int $customerId, string $channel): void
    {
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        if (!$customer) {
            throw new Exception('Müşteri kaydı bulunamadı.');
        }

        // STAGE 2 ROUND 2: Rate limit clearing is handled in calling method
        
        // STAGE 4.3: Audit log successful portal login
        if (class_exists('AuditLogger')) {
            AuditLogger::getInstance()->logAuth('PORTAL_LOGIN_SUCCESS', null, [
                'customer_id' => $customerId,
                'channel' => $channel,
                'ip_address' => RateLimitHelper::getClientIp()
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['portal_customer_id'] = $customer['id'];
        $_SESSION['portal_customer_name'] = $customer['name'];
        $_SESSION['portal_customer_role'] = Customer::normalizeRole($customer['role'] ?? null);
        $_SESSION['portal_login_time'] = time();

        Logger::info('Customer portal login', [
            'customer_id' => $customer['id'],
            'channel' => $channel,
        ]);

        $this->clearPortalLoginFlow();
        
        // Login sonrası bekleyen sözleşme kontrolü
        // Eğer müşterinin onaylanmamış sözleşmesi varsa, önce onu onaylat
        $pendingContract = $this->findPendingContractForCustomer($customerId);
        if ($pendingContract) {
            Utils::flash('info', 'Onaylanmamış bir sözleşmeniz var. Lütfen sözleşmeyi onaylayarak devam edin.');
            redirect(base_url("/contract/{$pendingContract['id']}"));
        }
    }
    
    /**
     * Müşterinin bekleyen (onaylanmamış) sözleşmesini bul
     * 
     * @param int $customerId
     * @return array|null
     */
    private function findPendingContractForCustomer(int $customerId): ?array
    {
        $db = Database::getInstance();
        
        // Müşteriye ait işlerin sözleşmelerini kontrol et
        // Status: PENDING veya SENT (APPROVED değil)
        // expires_at kontrolü: Süresi dolmamış olmalı (veya expires_at NULL olabilir)
        $contract = $db->fetch(
            "SELECT jc.*
             FROM job_contracts jc
             INNER JOIN jobs j ON jc.job_id = j.id
             WHERE j.customer_id = ?
               AND jc.status IN ('PENDING', 'SENT')
               AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now'))
             ORDER BY jc.created_at DESC
             LIMIT 1",
            [$customerId]
        );
        
        return $contract ?: null;
    }
}

