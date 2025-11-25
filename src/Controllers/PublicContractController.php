<?php
/**
 * Public Contract Controller
 * Müşteriler için public sözleşme görüntüleme ve onay akışı
 */

require_once __DIR__ . '/../Services/ContractOtpService.php';

class PublicContractController
{
    private $contractModel;
    private $jobModel;
    private $customerModel;

    public function __construct()
    {
        $this->contractModel = new JobContract();
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
    }

    /**
     * Show contract page for customer approval
     * 
     * TODO: İleride public_token (UUID) eklenip linkler daha güvenli hale getirilecek.
     */
    public function show($id)
    {
        try {
            // Validate ID
            $contractId = (int)$id;
            if ($contractId <= 0) {
                View::notFound('Geçersiz sözleşme ID.');
            }
            
            // Find contract by ID
            $contract = $this->contractModel->find($contractId);
            if (!$contract) {
                if (class_exists('Logger')) {
                    Logger::warning('Contract not found', ['contract_id' => $contractId]);
                }
                View::notFound('Sözleşme bulunamadı.');
            }
            
            // Get related job and customer
            $job = $this->jobModel->find($contract['job_id']);
            if (!$job) {
                if (class_exists('Logger')) {
                    Logger::warning('Job not found for contract', [
                        'contract_id' => $contractId,
                        'job_id' => $contract['job_id'] ?? null,
                    ]);
                }
                View::notFound('İş bulunamadı.');
            }
            
            $customer = $this->customerModel->find($job['customer_id']);
            if (!$customer) {
                if (class_exists('Logger')) {
                    Logger::warning('Customer not found for contract', [
                        'contract_id' => $contractId,
                        'job_id' => $contract['job_id'],
                        'customer_id' => $job['customer_id'] ?? null,
                    ]);
                }
                View::notFound('Müşteri bulunamadı.');
            }
        
        // Prepare status information for view
        $statusInfo = [
            'label' => __('contracts.panel.status.none'),
            'class' => 'bg-gray-100 text-gray-800',
            'can_approve' => false,
            'message' => null
        ];
        
        switch ($contract['status']) {
            case 'APPROVED':
                $statusInfo['label'] = __('contracts.panel.status.APPROVED');
                $statusInfo['class'] = 'bg-green-100 text-green-800';
                $statusInfo['can_approve'] = false;
                $statusInfo['message'] = __('contracts.public.messages.already_approved');
                break;
            case 'PENDING':
            case 'SENT':
                $statusInfo['label'] = __('contracts.panel.status.PENDING');
                $statusInfo['class'] = 'bg-yellow-100 text-yellow-800';
                $statusInfo['can_approve'] = true;
                break;
            case 'EXPIRED':
                $statusInfo['label'] = __('contracts.panel.status.EXPIRED');
                $statusInfo['class'] = 'bg-red-100 text-red-800';
                $statusInfo['can_approve'] = false;
                $statusInfo['message'] = __('contracts.public.messages.contract_expired');
                break;
            case 'REJECTED':
                $statusInfo['label'] = __('contracts.panel.status.REJECTED');
                $statusInfo['class'] = 'bg-red-100 text-red-800';
                $statusInfo['can_approve'] = false;
                $statusInfo['message'] = __('contracts.public.messages.generic_error');
                break;
        }
        
        // Check if contract has expired (expires_at check)
        if ($contract['expires_at']) {
            $expiresAt = new DateTime($contract['expires_at']);
            $now = new DateTime();
            if ($expiresAt < $now && $contract['status'] !== 'APPROVED') {
                $statusInfo['label'] = __('contracts.panel.status.EXPIRED');
                $statusInfo['class'] = 'bg-red-100 text-red-800';
                $statusInfo['can_approve'] = false;
                $statusInfo['message'] = __('contracts.public.messages.contract_expired');
            }
        }
        
            $flash = Utils::getFlash();
            
            // Activity log (view tracking)
            if (class_exists('ActivityLogger')) {
                try {
                    ActivityLogger::log('contract.viewed', 'job_contract', $contractId, [
                        'job_id' => $contract['job_id'],
                        'customer_id' => $job['customer_id'] ?? null,
                        'status' => $contract['status'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'is_logged_in' => isset($_SESSION['portal_customer_id']),
                    ]);
                } catch (Exception $e) {
                    // Activity log hatası kritik değil
                    error_log("Activity log error: " . $e->getMessage());
                }
            }
            
            echo View::renderWithLayout('contracts/public_show', [
                'contract' => $contract,
                'job' => $job,
                'customer' => $customer,
                'statusInfo' => $statusInfo,
                'flash' => $flash,
                'title' => __('contracts.public.title')
            ]);
            
        } catch (Exception $e) {
            error_log("Error in PublicContractController::show(): " . $e->getMessage());
            if (class_exists('Logger')) {
                Logger::error('Contract view error', [
                    'contract_id' => $id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            View::notFound('Sözleşme görüntülenirken bir hata oluştu.');
        }
    }

    /**
     * Process contract approval with OTP verification
     */
    public function approve($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contract/{$id}"));
        }
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contract/{$id}"));
        }
        
        // Find contract
        $contract = $this->contractModel->find($id);
        if (!$contract) {
            View::notFound('Sözleşme bulunamadı.');
        }
        
        // Check if already approved
        if ($contract['status'] === 'APPROVED') {
            Utils::flash('info', __('contracts.public.messages.already_approved'));
            redirect(base_url("/contract/{$id}"));
        }
        
        // Validate accept_terms checkbox
        $acceptTerms = isset($_POST['accept_terms']) && $_POST['accept_terms'] === '1';
        if (!$acceptTerms) {
            Utils::flash('error', __('contracts.public.messages.must_accept'));
            redirect(base_url("/contract/{$id}"));
        }
        
        // Validate OTP code
        $otpCode = trim($_POST['otp_code'] ?? '');
        if (empty($otpCode) || !preg_match('/^\d{6}$/', $otpCode)) {
            Utils::flash('error', __('contracts.public.messages.otp_required'));
            redirect(base_url("/contract/{$id}"));
        }
        
        // Get IP and user agent
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $contractOtpService = new ContractOtpService();
            $now = new DateTime();
            
            // Verify OTP
            $result = $contractOtpService->verifyOtp($contract, $otpCode, $ip, $userAgent, $now);
            
            if ($result['success']) {
                Utils::flash('success', __('contracts.public.messages.success'));
                
                // Activity logging
                if (class_exists('ActivityLogger')) {
                    try {
                        ActivityLogger::log('contract.approved', 'job_contract', (int)$contract['id'], [
                            'job_id' => $contract['job_id'],
                            'customer_id' => $contract['job_customer_id'] ?? null,
                            'method' => 'SMS_OTP',
                            'ip' => $ip,
                        ]);
                    } catch (Exception $e) {
                        // Activity log hatası kritik değil
                        error_log("Activity log error: " . $e->getMessage());
                    }
                }
                
                // Check if user is logged into portal, redirect to dashboard
                if (isset($_SESSION['portal_customer_id'])) {
                    $customerId = (int)$_SESSION['portal_customer_id'];
                    
                    // Check if there are more pending contracts (excluding current one)
                    $pendingCount = $this->countPendingContractsForCustomer($customerId, (int)$contract['id']);
                    if ($pendingCount > 0) {
                        Utils::flash('info', "Sözleşme onaylandı. {$pendingCount} adet bekleyen sözleşmeniz daha var.");
                    } else {
                        Utils::flash('success', __('contracts.public.messages.success'));
                    }
                    redirect(base_url('/portal/dashboard'));
                }
            } else {
                // Set error message based on reason
                $errorType = $result['error_type'] ?? 'invalid';
                $attemptsRemaining = $result['attempts_remaining'] ?? 0;
                
                switch ($errorType) {
                    case 'expired':
                        $message = __('contracts.public.messages.otp_expired');
                        break;
                    case 'blocked':
                        $message = __('contracts.public.messages.otp_blocked');
                        break;
                    case 'invalid':
                    default:
                        if ($attemptsRemaining > 0) {
                            $message = __('contracts.public.messages.otp_invalid', ['remaining' => $attemptsRemaining]);
                        } else {
                            $message = __('contracts.public.messages.otp_blocked');
                        }
                        break;
                }
                
                Utils::flash('error', $message);
            }
            
        } catch (Exception $e) {
            error_log("Contract approval failed for contract {$id}: " . $e->getMessage());
            if (class_exists('Logger')) {
                Logger::error('Contract approval exception', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            Utils::flash('error', __('contracts.public.messages.generic_error'));
        }
        
        redirect(base_url("/contract/{$id}"));
    }
    
    /**
     * Count pending contracts for a customer (excluding given contract ID)
     * 
     * @param int $customerId
     * @param int|null $excludeContractId Contract ID to exclude from count
     * @return int
     */
    private function countPendingContractsForCustomer(int $customerId, ?int $excludeContractId = null): int
    {
        $db = Database::getInstance();
        
        // Count pending contracts
        $sql = "SELECT COUNT(*) as count
                FROM job_contracts jc
                INNER JOIN jobs j ON jc.job_id = j.id
                WHERE j.customer_id = ?
                  AND jc.status IN ('PENDING', 'SENT')
                  AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now'))";
        $params = [$customerId];
        
        if ($excludeContractId) {
            $sql .= " AND jc.id != ?";
            $params[] = $excludeContractId;
        }
        
        $result = $db->fetch($sql, $params);
        
        return (int)($result['count'] ?? 0);
    }
}

