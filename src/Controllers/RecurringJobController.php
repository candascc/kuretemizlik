<?php

declare(strict_types=1);

/**
 * RecurringJob Controller
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class RecurringJobController
{
    use CompanyScope;

    private $recurringModel;

    public function __construct()
    {
        $this->recurringModel = new RecurringJob();
    }

    public function index()
    {
        Auth::requireGroup('nav.operations.core');
        
        // ===== PRODUCTION FIX: Prevent caching of recurring job list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        $items = $this->recurringModel->allWithDetails();
        $companies = $this->getCompanyOptions();
        echo View::renderWithLayout('recurring/list', [
            'items' => $items,
            'companies' => $companies,
        ]);
    }

    public function create()
    {
        // ROUND 19: Add error handling for /recurring/new 500
        try {
            Auth::requireCapability('jobs.create');
            
            // ROUND 19: Ensure no exceptions during view rendering
            echo View::renderWithLayout('recurring/form', []);
        } catch (Throwable $e) {
            // ROUND 19: Log error and show user-friendly error page
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, ['context' => 'RecurringJobController::create()']);
            } else {
                error_log("RecurringJobController::create() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // Show error page instead of 500
            View::error('Periyodik iş formu yüklenemedi. Lütfen tekrar deneyin.', 500, $e->getMessage());
        }
    }

    public function store()
    {
        Auth::requireCapability('jobs.create');
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/recurring')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/recurring', 'Güvenlik hatası')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        // Validation
        if (empty($_POST['customer_id'])) {
            ControllerHelper::flashErrorAndRedirect('Müşteri seçimi zorunludur', '/recurring/new');
            return;
        }
        
        if (empty($_POST['start_date'])) {
            ControllerHelper::flashErrorAndRedirect('Başlangıç tarihi zorunludur', '/recurring/new');
            return;
        }
        
        if ($_POST['frequency'] === 'WEEKLY' && empty($_POST['byweekday'])) {
            ControllerHelper::flashErrorAndRedirect('Haftalık tekrar için en az bir gün seçmelisiniz', '/recurring/new');
            return;
        }
        
        if ($_POST['frequency'] === 'MONTHLY' && (empty($_POST['bymonthday']) || (int)$_POST['bymonthday'] < 1 || (int)$_POST['bymonthday'] > 31)) {
            ControllerHelper::flashErrorAndRedirect('Aylık tekrar için ayın günü 1-31 arasında olmalıdır', '/recurring/new');
            return;
        }
        
        // Clear byweekday for non-WEEKLY frequencies
        if ($_POST['frequency'] !== 'WEEKLY') {
            $_POST['byweekday'] = [];
        }
        
        // Clear bymonthday for non-MONTHLY frequencies
        if ($_POST['frequency'] !== 'MONTHLY') {
            $_POST['bymonthday'] = null;
        }
        
        // Pricing model validation
        $pricingModel = $_POST['pricing_model'] ?? 'PER_JOB';
        if (!in_array($pricingModel, ['PER_JOB', 'PER_MONTH', 'TOTAL_CONTRACT'], true)) {
            $pricingModel = 'PER_JOB';
        }
        
        $perJobAmount = Utils::normalizeMoney((string)($_POST['default_total_amount'] ?? '0'));
        $monthlyAmount = Utils::normalizeMoney((string)($_POST['monthly_amount'] ?? '0'));
        $contractAmount = Utils::normalizeMoney((string)($_POST['contract_total_amount'] ?? '0'));

        if ($pricingModel === 'PER_JOB' && $perJobAmount <= 0) {
            ControllerHelper::flashErrorAndRedirect('İş başı ücret modeli için pozitif bir tutar giriniz.', '/recurring/new');
            return;
        }

        if ($pricingModel === 'PER_MONTH') {
            if ($monthlyAmount <= 0) {
                ControllerHelper::flashErrorAndRedirect('Aylık ücret modeli için aylık tutar zorunludur', '/recurring/new');
                return;
            }
        }
        
        if ($pricingModel === 'TOTAL_CONTRACT') {
            if ($contractAmount <= 0) {
                ControllerHelper::flashErrorAndRedirect('Toplam sözleşme modeli için sözleşme tutarı zorunludur', '/recurring/new');
                return;
            }
            if (empty($_POST['end_date'])) {
                ControllerHelper::flashErrorAndRedirect('Toplam sözleşme modeli için bitiş tarihi zorunludur', '/recurring/new');
                return;
            }
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('[RecurringPricing][store] ' . json_encode([
                'pricing_model' => $pricingModel,
                'raw' => [
                    'default_total_amount' => $_POST['default_total_amount'] ?? null,
                    'monthly_amount' => $_POST['monthly_amount'] ?? null,
                    'contract_total_amount' => $_POST['contract_total_amount'] ?? null,
                ],
                'normalized' => [
                    'per_job' => $perJobAmount,
                    'monthly' => $monthlyAmount,
                    'contract' => $contractAmount,
                ],
            ]));
        }

        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'address_id' => $_POST['address_id'] ?? null,
            'service_id' => $_POST['service_id'] ?? null,
            'frequency' => $_POST['frequency'] ?? 'WEEKLY',
            'interval' => $_POST['interval'] ?? 1,
            'byweekday' => isset($_POST['byweekday']) ? (array)$_POST['byweekday'] : [],
            'bymonthday' => isset($_POST['bymonthday']) ? (int)$_POST['bymonthday'] : null,
            'byhour' => $_POST['byhour'] ?? 9,
            'byminute' => $_POST['byminute'] ?? 0,
            'duration_min' => $_POST['duration_min'] ?? 60,
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? null,
            'timezone' => $_POST['timezone'] ?? 'Europe/Istanbul',
            'status' => $_POST['status'] ?? 'ACTIVE',
            'default_total_amount' => $pricingModel === 'PER_JOB' ? $perJobAmount : null,
            'default_notes' => $_POST['default_notes'] ?? null,
            'default_assignees' => isset($_POST['default_assignees']) ? (array)$_POST['default_assignees'] : [],
            'exclusions' => isset($_POST['exclusions']) ? (array)$_POST['exclusions'] : [],
            'holiday_policy' => $_POST['holiday_policy'] ?? 'SKIP',
            'pricing_model' => $pricingModel,
            'monthly_amount' => $pricingModel === 'PER_MONTH' ? $monthlyAmount : null,
            'contract_total_amount' => $pricingModel === 'TOTAL_CONTRACT' ? $contractAmount : null,
        ];

        try {
            $id = $this->recurringModel->create($data);
            
            // Generate occurrences and materialize to jobs
            $occurrencesGenerated = 0;
            $jobsCreated = 0;
            $errorDetails = [];
            
            try {
                // Step 1: Generate occurrences
                $occurrencesGenerated = RecurringGenerator::generateForJob((int)$id, 30);
                
                // Step 2: Materialize occurrences to actual jobs
                if ($occurrencesGenerated > 0) {
                    $jobsCreated = RecurringGenerator::materializeToJobs((int)$id);
                }
                
                // Success messages based on results
                if ($occurrencesGenerated > 0 && $jobsCreated > 0) {
                    Utils::flash('success', 
                        "Periyodik iş başarıyla oluşturuldu. " . 
                        "Önümüzdeki 30 gün için {$occurrencesGenerated} adet oluşum planlandı ve " . 
                        "{$jobsCreated} adet iş oluşturuldu. " .
                        "<a href='" . base_url('/jobs') . "' class='underline font-semibold'>İşler sayfasında görüntüleyebilirsiniz.</a>"
                    );
                } elseif ($occurrencesGenerated > 0) {
                    Utils::flash('success', 
                        "Periyodik iş başarıyla oluşturuldu. " . 
                        "Önümüzdeki 30 gün için {$occurrencesGenerated} adet oluşum planlandı. " .
                        "İşler yakında otomatik olarak oluşturulacak veya " .
                        "<a href='" . base_url("/recurring/{$id}") . "' class='underline font-semibold'>manuel olarak oluşturabilirsiniz</a>."
                    );
                } else {
                    Utils::flash('info', 
                        "Periyodik iş başarıyla oluşturuldu. " .
                        "Henüz oluşum planlanmadı. Geçmiş tarihler için oluşum oluşturulmaz. " .
                        "<a href='" . base_url("/recurring/{$id}/generate-now") . "' class='underline font-semibold'>Şimdi oluşumları oluşturmak</a> için detay sayfasını ziyaret edin."
                    );
                }
                
            } catch (Exception $e) {
                error_log("Occurrence generation failed for recurring job $id: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Still show success for recurring job creation, but warn about job generation
                Utils::flash('success', 'Periyodik iş başarıyla oluşturuldu.');
                Utils::flash('warning', 
                    "Ancak otomatik işler oluşturulamadı. " .
                    "Sorun: " . htmlspecialchars($e->getMessage()) . ". " .
                    "<a href='" . base_url("/recurring/{$id}") . "' class='underline font-semibold'>Detay sayfasından</a> " .
                    "manuel olarak oluşturabilir veya tekrar deneyebilirsiniz."
                );
            }

            redirect(base_url('/recurring'));
        } catch (Exception $e) {
            error_log("Recurring job creation failed: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            Utils::flash('error', 'Periyodik iş oluşturulurken bir hata oluştu: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/recurring/new'));
        }
    }

    public function edit($id)
    {
        // Redirect to unified management page
        redirect(base_url("/recurring/{$id}"));
    }

    public function update($id)
    {
        Auth::requireCapability('jobs.edit');
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası');
            redirect(base_url('/recurring'));
        }

        // Validation
        if (empty($_POST['customer_id'])) {
            Utils::flash('error', 'Müşteri seçimi zorunludur');
            redirect(base_url("/recurring/{$id}"));
        }
        
        if (empty($_POST['start_date'])) {
            Utils::flash('error', 'Başlangıç tarihi zorunludur');
            redirect(base_url("/recurring/{$id}"));
        }
        
        if ($_POST['frequency'] === 'WEEKLY' && empty($_POST['byweekday'])) {
            Utils::flash('error', 'Haftalık tekrar için en az bir gün seçmelisiniz');
            redirect(base_url("/recurring/{$id}"));
        }
        
        if ($_POST['frequency'] === 'MONTHLY' && (empty($_POST['bymonthday']) || (int)$_POST['bymonthday'] < 1 || (int)$_POST['bymonthday'] > 31)) {
            Utils::flash('error', 'Aylık tekrar için ayın günü 1-31 arasında olmalıdır');
            redirect(base_url("/recurring/{$id}"));
        }
        
        // Clear byweekday for non-WEEKLY frequencies
        if ($_POST['frequency'] !== 'WEEKLY') {
            $_POST['byweekday'] = [];
        }
        
        // Clear bymonthday for non-MONTHLY frequencies
        if ($_POST['frequency'] !== 'MONTHLY') {
            $_POST['bymonthday'] = null;
        }
        
        // Pricing model validation
        $pricingModel = $_POST['pricing_model'] ?? 'PER_JOB';
        if (!in_array($pricingModel, ['PER_JOB', 'PER_MONTH', 'TOTAL_CONTRACT'], true)) {
            $pricingModel = 'PER_JOB';
        }
        
        $perJobAmount = Utils::normalizeMoney((string)($_POST['default_total_amount'] ?? '0'));
        $monthlyAmount = Utils::normalizeMoney((string)($_POST['monthly_amount'] ?? '0'));
        $contractAmount = Utils::normalizeMoney((string)($_POST['contract_total_amount'] ?? '0'));

        if ($pricingModel === 'PER_JOB' && $perJobAmount <= 0) {
            Utils::flash('error', 'İş başı ücret modeli için pozitif bir tutar giriniz.');
            redirect(base_url("/recurring/{$id}"));
        }

        if ($pricingModel === 'PER_MONTH') {
            if ($monthlyAmount <= 0) {
                Utils::flash('error', 'Aylık ücret modeli için aylık tutar zorunludur');
                redirect(base_url("/recurring/{$id}"));
            }
        }
        
        if ($pricingModel === 'TOTAL_CONTRACT') {
            if ($contractAmount <= 0) {
                Utils::flash('error', 'Toplam sözleşme modeli için sözleşme tutarı zorunludur');
                redirect(base_url("/recurring/{$id}"));
            }
            if (empty($_POST['end_date'])) {
                Utils::flash('error', 'Toplam sözleşme modeli için bitiş tarihi zorunludur');
                redirect(base_url("/recurring/{$id}"));
            }
        }

        $data = $_POST;
        if (isset($data['byweekday'])) { $data['byweekday'] = (array)$data['byweekday']; }
        if (isset($data['default_assignees'])) { $data['default_assignees'] = (array)$data['default_assignees']; }
        if (isset($data['exclusions'])) { $data['exclusions'] = (array)$data['exclusions']; }
        $data['default_total_amount'] = $pricingModel === 'PER_JOB' ? $perJobAmount : null;
        $data['monthly_amount'] = $pricingModel === 'PER_MONTH' ? $monthlyAmount : null;
        $data['contract_total_amount'] = $pricingModel === 'TOTAL_CONTRACT' ? $contractAmount : null;

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('[RecurringPricing][update] ' . json_encode([
                'id' => $id,
                'pricing_model' => $pricingModel,
                'raw' => [
                    'default_total_amount' => $_POST['default_total_amount'] ?? null,
                    'monthly_amount' => $_POST['monthly_amount'] ?? null,
                    'contract_total_amount' => $_POST['contract_total_amount'] ?? null,
                ],
                'normalized' => [
                    'per_job' => $perJobAmount,
                    'monthly' => $monthlyAmount,
                    'contract' => $contractAmount,
                ],
            ]));
        }

        try {
            $this->recurringModel->update($id, $data);
            // Regenerate upcoming occurrences and materialize into jobs
            try {
                RecurringGenerator::generateForJob((int)$id, 30);
                RecurringGenerator::materializeToJobs((int)$id);
            } catch (Exception $e) {
                error_log("Occurrence regeneration failed for recurring job $id: " . $e->getMessage());
                Utils::flash('warning', 'Periyodik iş güncellendi ancak otomatik iş oluşturulurken bir hata oluştu. Lütfen manuel olarak kontrol edin.');
                redirect(base_url('/recurring'));
                return;
            }
            Utils::flash('success', 'Periyodik iş başarıyla güncellendi');
            redirect(base_url('/recurring'));
        } catch (Exception $e) {
            error_log("Recurring job update failed: " . $e->getMessage());
            Utils::flash('error', 'Periyodik iş güncellenirken bir hata oluştu. Lütfen tekrar deneyin.');
            redirect(base_url("/recurring/{$id}"));
        }
    }

    public function toggle($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // ID kontrolü
        if (!$id || !is_numeric($id)) {
            Utils::flash('error', 'Geçersiz periyodik iş ID\'si.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // Periyodik işin var olup olmadığını kontrol et
        $recurringJob = $this->recurringModel->find($id);
        if (!$recurringJob) {
            Utils::flash('error', 'Periyodik iş bulunamadı.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // CANCELLED durumundaysa toggle yapılmasın
        if (($recurringJob['status'] ?? '') === 'CANCELLED') {
            Utils::flash('warning', 'İptal edilmiş periyodik işler için durum değiştirilemez.');
            redirect(base_url('/recurring'));
            return;
        }
        
        try {
            $result = $this->recurringModel->toggleStatus($id);
            if ($result > 0) {
                $newStatus = ($recurringJob['status'] ?? '') === 'ACTIVE' ? 'pasif' : 'aktif';
                Utils::flash('success', "Periyodik iş başarıyla {$newStatus} yapıldı.");
            } else {
                Utils::flash('error', 'Durum değiştirilemedi. Lütfen tekrar deneyin.');
            }
        } catch (Exception $e) {
            error_log("Recurring job toggle failed for ID $id: " . $e->getMessage());
            Utils::flash('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
        
        redirect(base_url('/recurring'));
    }

    public function show($id)
    {
        Auth::requireGroup('nav.operations.core');
        
        $recurringJob = $this->recurringModel->find($id);
        if (!$recurringJob) {
            View::notFound('Periyodik iş bulunamadı');
            return;
        }
        
        // Get customer and service names
        $customer = (new Customer())->find($recurringJob['customer_id']);
        $service = (new Service())->find($recurringJob['service_id']);
        
        $recurringJob['customer_name'] = $customer['name'] ?? 'Bilinmeyen Müşteri';
        $recurringJob['customer_phone'] = $customer['phone'] ?? '';
        $recurringJob['service_name'] = $service['name'] ?? 'Bilinmeyen Hizmet';
        
        // Get address info
        if ($recurringJob['address_id']) {
            $address = (new Address())->find($recurringJob['address_id']);
            $recurringJob['address_line'] = $address['line'] ?? '';
            $recurringJob['address_city'] = $address['city'] ?? '';
        }
        
        // Get occurrences with job details
        $occurrences = RecurringOccurrence::getByRecurringJobId($id);
        
        // Enhance occurrences with job details
        foreach ($occurrences as &$occurrence) {
            // Check if job_id exists and is not null/empty
            if (!empty($occurrence['job_id']) && isset($occurrence['job_id'])) {
                $job = (new Job())->find($occurrence['job_id']);
                if ($job) {
                    $occurrence['job_status'] = $job['status'] ?? 'UNKNOWN';
                    $occurrence['job_total_amount'] = $job['total_amount'] ?? 0;
                    $occurrence['job_amount_paid'] = $job['amount_paid'] ?? 0;
                }
            } else {
                // Ensure these fields exist even if no job_id
                $occurrence['job_id'] = null;
                $occurrence['job_status'] = null;
                $occurrence['job_total_amount'] = 0;
                $occurrence['job_amount_paid'] = 0;
            }
        }
        unset($occurrence); // Break reference
        
        // Calculate comprehensive stats
        $stats = [
            'total_occurrences' => count($occurrences),
            'completed' => count(array_filter($occurrences, fn($o) => $o['status'] === 'GENERATED')),
            'planned' => count(array_filter($occurrences, fn($o) => $o['status'] === 'PLANNED')),
            'skipped' => count(array_filter($occurrences, fn($o) => $o['status'] === 'SKIPPED')),
            'conflict' => count(array_filter($occurrences, fn($o) => $o['status'] === 'CONFLICT')),
            'total_revenue' => array_sum(array_map(fn($o) => $o['job_total_amount'] ?? 0, array_filter($occurrences, fn($o) => $o['status'] === 'GENERATED'))),
            'total_paid' => array_sum(array_map(fn($o) => $o['job_amount_paid'] ?? 0, array_filter($occurrences, fn($o) => $o['status'] === 'GENERATED'))),
        ];
        
        // Get recent occurrences (last 10)
        $recentOccurrences = array_slice(array_reverse($occurrences), 0, 10);
        
        // ===== KOZMOS_SCHEMA_COMPAT: use view columns (begin)
        // Get upcoming occurrences (next 10)
        $upcomingOccurrences = array_filter($occurrences, fn($o) => $o['status'] === 'PLANNED' && $o['scheduled_date'] >= date('Y-m-d'));
        // ===== KOZMOS_SCHEMA_COMPAT: use view columns (end)
        $upcomingOccurrences = array_slice($upcomingOccurrences, 0, 10);
        
        echo View::renderWithLayout('recurring/show', [
            'recurringJob' => $recurringJob,
            'occurrences' => $occurrences,
            'recentOccurrences' => $recentOccurrences,
            'upcomingOccurrences' => $upcomingOccurrences,
            'stats' => $stats,
            'flash' => Utils::getFlash()
        ]);
    }

    public function updateTime($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url("/recurring/{$id}"));
        }
        
        $newTime = $_POST['new_time'] ?? '';
        if (empty($newTime)) {
            Utils::flash('error', 'Yeni saat gerekli.');
            redirect(base_url("/recurring/{$id}"));
        }
        
        list($hour, $minute) = explode(':', $newTime);
        
        // Update recurring job
        RecurringJob::update($id, [
            'byhour' => (int)$hour,
            'byminute' => (int)$minute,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update future occurrences
        $generator = new RecurringGenerator();
        $generator->updateFutureOccurrences($id, (int)$hour, (int)$minute);
        
        Utils::flash('success', 'Saat başarıyla güncellendi. Gelecek oluşumlar yeni saatle planlandı.');
        redirect(base_url("/recurring/{$id}"));
    }

    public function deleteFuture($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url("/recurring/{$id}"));
        }
        
        // Delete future occurrences
        $generator = new RecurringGenerator();
        $deleted = $generator->deleteFutureOccurrences($id);
        
        Utils::flash('success', "{$deleted} gelecek oluşum silindi.");
        redirect(base_url("/recurring/{$id}"));
    }

    public function cancel($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // ID kontrolü
        if (!$id || !is_numeric($id)) {
            Utils::flash('error', 'Geçersiz periyodik iş ID\'si.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // Periyodik işin var olup olmadığını kontrol et
        $recurringJob = $this->recurringModel->find($id);
        if (!$recurringJob) {
            Utils::flash('error', 'Periyodik iş bulunamadı.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // Zaten iptal edilmişse
        if (($recurringJob['status'] ?? '') === 'CANCELLED') {
            Utils::flash('info', 'Bu periyodik iş zaten iptal edilmiş.');
            redirect(base_url('/recurring'));
            return;
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            // 1. Status'u CANCELLED yap
            $this->recurringModel->update($id, [
                'status' => 'CANCELLED',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // 2. Gelecekteki occurrence'ları ve ona bağlı job'ları sil
            $futureOccurrences = $db->fetchAll(
                "SELECT id, job_id FROM recurring_job_occurrences 
                 WHERE recurring_job_id = ? AND date >= DATE('now')",
                [$id]
            );
            
            $deletedOccurrences = 0;
            $deletedJobs = 0;
            
            foreach ($futureOccurrences as $occ) {
                // Job varsa ve silinebilir durumdaysa sil
                if (!empty($occ['job_id'])) {
                    $job = $db->fetch("SELECT status FROM jobs WHERE id = ?", [$occ['job_id']]);
                    if ($job && in_array($job['status'], ['SCHEDULED', 'PLANNED'])) {
                        // Finans kayıtlarını arşivle (varsa)
                        $db->query("UPDATE money_entries SET is_archived = 1 WHERE job_id = ? AND (is_archived IS NULL OR is_archived = 0)", [$occ['job_id']]);
                        // Job'ı sil
                        $db->delete('jobs', 'id = ?', [$occ['job_id']]);
                        $deletedJobs++;
                    }
                }
                // Occurrence'ı sil
                $db->delete('recurring_job_occurrences', 'id = ?', [$occ['id']]);
                $deletedOccurrences++;
            }
            
            $db->commit();
            
            $message = "Periyodik iş iptal edildi. ";
            if ($deletedOccurrences > 0) {
                $message .= "{$deletedOccurrences} gelecek oluşum silindi. ";
            }
            if ($deletedJobs > 0) {
                $message .= "{$deletedJobs} planlanmış iş silindi. ";
            }
            $message .= "Geçmiş tamamlanmış işler korundu.";
            
            Utils::flash('success', $message);
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollback();
            }
            error_log("Recurring job cancel failed for ID $id: " . $e->getMessage());
            Utils::flash('error', 'İptal işlemi sırasında bir hata oluştu.');
        }
        
        redirect(base_url('/recurring'));
    }

    public function delete($id)
    {
        Auth::requireCapability('jobs.delete');
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz periyodik iş ID.');
            redirect(base_url('/recurring'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/recurring')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/recurring', 'Güvenlik hatası.')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        // Periyodik işin var olup olmadığını kontrol et
        $recurringJob = $this->recurringModel->find($id);
        if (!$recurringJob) {
            Utils::flash('error', 'Periyodik iş bulunamadı.');
            redirect(base_url('/recurring'));
            return;
        }
        
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // 1. Tüm occurrence'ları bul
            $occurrences = $db->fetchAll(
                "SELECT id, job_id FROM recurring_job_occurrences WHERE recurring_job_id = ?",
                [$id]
            );
            
            $deletedOccurrences = 0;
            $deletedJobs = 0;
            $archivedFinance = 0;
            
            // 2. Her occurrence için job varsa sil (finans kayıtlarını arşivle)
            foreach ($occurrences as $occ) {
                if (!empty($occ['job_id'])) {
                    // Finans kayıtlarını arşivle
                    $financeUpdated = $db->query(
                        "UPDATE money_entries SET is_archived = 1 WHERE job_id = ? AND (is_archived IS NULL OR is_archived = 0)",
                        [$occ['job_id']]
                    );
                    if ($financeUpdated) {
                        $archivedFinance++;
                    }
                    // Job'ı sil
                    $jobDeleted = $db->delete('jobs', 'id = ?', [$occ['job_id']]);
                    if ($jobDeleted) {
                        $deletedJobs++;
                    }
                }
                // Occurrence'ı sil
                $occDeleted = $db->delete('recurring_job_occurrences', 'id = ?', [$occ['id']]);
                if ($occDeleted) {
                    $deletedOccurrences++;
                }
            }
            
            // 3. Periyodik iş tanımını sil
            $this->recurringModel->delete($id);
            
            $db->commit();
            
            $message = "Periyodik iş tamamen silindi. ";
            $message .= "{$deletedOccurrences} oluşum, {$deletedJobs} iş silindi. ";
            if ($archivedFinance > 0) {
                $message .= "{$archivedFinance} finans kaydı arşivlendi.";
            }
            
            Utils::flash('success', $message);
            
            // Activity log
            if (class_exists('ActivityLogger')) {
                ActivityLogger::log('recurring_deleted', 'recurring_job', [
                    'recurring_job_id' => $id,
                    'deleted_occurrences' => $deletedOccurrences,
                    'deleted_jobs' => $deletedJobs,
                    'archived_finance' => $archivedFinance
                ]);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Recurring job delete failed for ID $id: " . $e->getMessage());
            Utils::flash('error', 'Silme işlemi başarısız oldu.');
        }
        
        redirect(base_url('/recurring'));
    }

    public function generateSingle($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url("/recurring/{$id}"));
        }
        
        $occurrenceId = $_POST['occurrence_id'] ?? '';
        if (empty($occurrenceId)) {
            Utils::flash('error', 'Oluşum ID gerekli.');
            redirect(base_url("/recurring/{$id}"));
        }
        
        $generator = new RecurringGenerator();
        $generator->generateSingleOccurrence($id, $occurrenceId);
        
        Utils::flash('success', 'Oluşum başarıyla oluşturuldu.');
        redirect(base_url("/recurring/{$id}"));
    }

    public function generateOccurrencesNow($id)
    {
        Auth::require();
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // ID kontrolü
        if (!$id || !is_numeric($id)) {
            Utils::flash('error', 'Geçersiz periyodik iş ID\'si.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // Periyodik işin var olup olmadığını kontrol et
        $recurringJob = $this->recurringModel->find($id);
        if (!$recurringJob) {
            Utils::flash('error', 'Periyodik iş bulunamadı.');
            redirect(base_url('/recurring'));
            return;
        }
        
        // Sadece aktif işler için oluşum üret
        if (($recurringJob['status'] ?? '') !== 'ACTIVE') {
            Utils::flash('warning', 'Sadece aktif periyodik işler için oluşum üretilebilir.');
            redirect(base_url('/recurring'));
            return;
        }
        
        try {
            $generator = new RecurringGenerator();
            $generated = $generator->generateForJob((int)$id, 30); // Generate for next 30 days
            $materialized = $generator->materializeToJobs((int)$id);
            
            Utils::flash('success', "Başarıyla oluşumlar oluşturuldu ve işlere dönüştürüldü.");
        } catch (Exception $e) {
            error_log("Recurring job generation failed for ID $id: " . $e->getMessage());
            Utils::flash('error', 'Oluşumlar oluşturulurken bir hata oluştu: ' . Utils::safeExceptionMessage($e));
        }
        
        redirect(base_url("/recurring/{$id}"));
    }
}
