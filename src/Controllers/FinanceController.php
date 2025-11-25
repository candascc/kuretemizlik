<?php

declare(strict_types=1);

/**
 * Finance Controller
 */

require_once __DIR__ . '/../Lib/AuditLogger.php';
require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class FinanceController
{
    use CompanyScope;

    private $moneyModel;
    private $jobModel;
    private $recurringJobModel;

    public function __construct()
    {
        $this->moneyModel = new MoneyEntry();
        $this->jobModel = new Job();
        $this->recurringJobModel = new RecurringJob();
    }

    public function index()
    {
        Auth::requireGroup('nav.finance.core');

        // ===== PRODUCTION FIX: Prevent caching of finance list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====

        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $kind = InputSanitizer::string($_GET['kind'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);
        $category = InputSanitizer::string($_GET['category'] ?? '', AppConstants::MAX_STRING_LENGTH_MEDIUM);
        $dateFrom = InputSanitizer::date($_GET['date_from'] ?? '', AppConstants::DATE_FORMAT);
        $dateTo = InputSanitizer::date($_GET['date_to'] ?? '', AppConstants::DATE_FORMAT);

        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();
        $whereClause = $this->scopeToCompany('WHERE 1=1', 'me');
        $params = [];

        if ($kind) {
            $whereClause .= " AND me.kind = ?";
            $params[] = $kind;
        }

        if ($category) {
            $whereClause .= " AND me.category = ?";
            $params[] = $category;
        }

        if ($dateFrom) {
            $whereClause .= " AND me.date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $whereClause .= " AND me.date <= ?";
            $params[] = $dateTo;
        }

        $countSql = "SELECT COUNT(*) as count FROM money_entries me $whereClause";
        $total = $db->fetch($countSql, $params)['count'];

        $sql = "
            SELECT
                me.*,
                j.id as job_id,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                c.name as customer_name,
                c.name as job_customer_name,
                u.username as created_by_name,
                rj.id as recurring_job_id,
                rj.pricing_model as recurring_pricing_model,
                rj.monthly_amount as recurring_monthly_amount,
                rj.contract_total_amount as recurring_contract_total_amount,
                rc.name as recurring_customer_name
            FROM money_entries me
            LEFT JOIN jobs j ON me.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN users u ON me.created_by = u.id
            LEFT JOIN recurring_jobs rj ON me.recurring_job_id = rj.id
            LEFT JOIN customers rc ON rj.customer_id = rc.id
            $whereClause
            ORDER BY me.date DESC, me.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $entries = $db->fetchAll($sql, $params);
        $pagination = Utils::paginate($total, $limit, $page);

        $totalIncome = $this->moneyModel->getTotalIncome($dateFrom, $dateTo);
        $totalExpense = $this->moneyModel->getTotalExpense($dateFrom, $dateTo);
        $netProfit = $totalIncome - $totalExpense;

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $summary = [
            'today' => [
                'income' => $this->moneyModel->getTotalIncome($today, $today),
                'expense' => $this->moneyModel->getTotalExpense($today, $today),
            ],
            'week' => [
                'income' => $this->moneyModel->getTotalIncome($weekStart, $weekEnd),
                'expense' => $this->moneyModel->getTotalExpense($weekStart, $weekEnd),
            ],
            'month' => [
                'income' => $this->moneyModel->getTotalIncome($monthStart, $monthEnd),
                'expense' => $this->moneyModel->getTotalExpense($monthStart, $monthEnd),
            ],
        ];
        foreach ($summary as $key => $values) {
            $summary[$key]['profit'] = $values['income'] - $values['expense'];
        }

        $categoryTotals = $this->moneyModel->getCategoryTotals($kind, $dateFrom, $dateTo);
        $companies = $this->getCompanyOptions();

        echo View::renderWithLayout('finance/list', [
            'entries' => $entries,
            'pagination' => $pagination,
            'categoryTotals' => $categoryTotals,
            'totals' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'profit' => $netProfit,
            ],
            'summary' => $summary,
            'filters' => [
                'kind' => $kind,
                'category' => $category,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'company_filter' => $_GET['company_filter'] ?? '',
            ],
            'companies' => $companies,
            'flash' => Utils::getFlash(),
        ]);
    }

    public function show($id)
    {
        Auth::requireGroup('nav.finance.core');

        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz finans kaydı ID.');
            redirect(base_url('/finance'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $entry = $this->moneyModel->find($id);
        if (!$entry) {
            Utils::flash('error', 'Kayıt bulunamadı.');
            redirect(base_url('/finance'));
            return;
        }

        $jobRemaining = null;
        if (!empty($entry['job_id'])) {
            $job = $this->jobModel->find($entry['job_id']);
            if ($job) {
                $jobRemaining = max(0, (float)($job['total_amount'] ?? 0) - (float)($job['amount_paid'] ?? 0));
            }
        }

        echo View::renderWithLayout('finance/show', [
            'entry' => $entry,
            'jobRemaining' => $jobRemaining,
            'flash' => Utils::getFlash(),
        ]);
    }

    public function create()
    {
        // ===== CRITICAL FIX: Auth::requireCapability() now handles session initialization =====
        // No need to start session here - Auth::requireCapability() handles it
        Auth::requireCapability('finance.collect');
        
        Utils::setNoCacheHeaders();

        $outstandingJobs = $this->jobModel->getOutstandingJobs(50);
        
        // Get active recurring jobs with contract-based pricing (PER_MONTH or TOTAL_CONTRACT)
        $db = Database::getInstance();
        $recurringWhere = $this->scopeToCompany("WHERE rj.status = 'ACTIVE' 
            AND rj.pricing_model IN ('PER_MONTH', 'TOTAL_CONTRACT')", 'rj');
        $recurringJobs = $db->fetchAll("
            SELECT 
                rj.*,
                c.name as customer_name,
                c.phone as customer_phone
            FROM recurring_jobs rj
            LEFT JOIN customers c ON rj.customer_id = c.id
            {$recurringWhere}
            ORDER BY rj.created_at DESC
        ");

        echo View::renderWithLayout('finance/form', [
            'entry' => null,
            'completedJobs' => $outstandingJobs,
            'recurringJobs' => $recurringJobs,
            'flash' => Utils::getFlash(),
        ]);
    }

    public function store()
    {
        // ===== CRITICAL FIX: Auth::requireCapability() now handles session initialization =====
        // No need to start session here - Auth::requireCapability() handles it
        Auth::requireCapability('finance.collect');

        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/finance')) {
            return;
        }

        if (!ControllerHelper::verifyCsrfOrRedirect('/finance/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $validator = new Validator($_POST);
        $validator->required('kind', 'Tür seçimi zorunludur')
                 ->in('kind', ['INCOME', 'EXPENSE'], 'Geçerli bir tür seçin')
                 ->required('category', 'Kategori zorunludur')
                 ->required('amount', 'Miktar zorunludur')
                 ->numeric('amount', 'Miktar sayısal olmalıdır')
                 ->required('date', 'Tarih zorunludur')
                 ->date('date', 'Geçerli bir tarih girin');

        if ($validator->fails()) {
            ControllerHelper::flashErrorAndRedirect($validator->firstError(), '/finance/new');
            return;
        }

        $kind = $validator->get('kind');
        $category = $validator->get('category');
        $amount = $this->parseMoney($validator->get('amount'));
        $date = $validator->get('date');
        $note = $validator->get('note') ?: null;
        $jobId = $validator->get('job_id') ?: null;
        $recurringJobId = $validator->get('recurring_job_id') ?: null;
        $incomeSource = $validator->get('income_source') ?? 'job';

        if ($amount <= 0) {
            ControllerHelper::flashErrorAndRedirect('Miktar sıfırdan büyük olmalıdır.', '/finance/new');
            return;
        }

        if ($kind === 'INCOME') {
            // Check if recurring job based income
            if ($incomeSource === 'recurring' && !empty($recurringJobId)) {
                // Validate recurring job exists and is active
                $recurringJob = $this->recurringJobModel->find($recurringJobId);
                if (!$recurringJob) {
                    Utils::flash('error', 'Periyodik iş bulunamadı.');
                    redirect(base_url('/finance/new'));
                }
                
                if (($recurringJob['status'] ?? '') !== 'ACTIVE') {
                    Utils::flash('error', 'Sadece aktif periyodik işler için gelir kaydı oluşturulabilir.');
                    redirect(base_url('/finance/new'));
                }
                
                // Create income entry with recurring_job_id
                $db = Database::getInstance();
                $db->beginTransaction();
                
                try {
                    $entryData = [
                        'kind' => 'INCOME',
                        'category' => $category,
                    'amount' => $amount,
                    'date' => $date,
                    'note' => $note,
                    'job_id' => null,
                    'recurring_job_id' => (int)$recurringJobId,
                    'created_by' => Auth::id(),
                ];
                
                    $entryId = $this->moneyModel->create($entryData);
                    $db->commit();
                    
                    // ===== ERR-018 FIX: Add audit logging =====
                    AuditLogger::getInstance()->logBusiness('FINANCE_INCOME_CREATED', Auth::id(), [
                        'entry_id' => $entryId,
                        'amount' => $amount,
                        'category' => $category,
                        'recurring_job_id' => $recurringJobId,
                        'date' => $date
                    ]);
                    // ===== ERR-018 FIX: End =====
                    
                    ActivityLogger::log('income_added', 'money_entry', [
                        'amount' => $amount,
                        'category' => $category,
                        'recurring_job_id' => $recurringJobId
                    ]);
                    Utils::flash('success', 'Periyodik iş bazlı gelir kaydı oluşturuldu.');
                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Finance entry creation failed: " . $e->getMessage());
                    Utils::flash('error', 'Gelir kaydı oluşturulamadı. Lütfen tekrar deneyin.');
                    redirect(base_url('/finance/new'));
                }
            } else {
                // Job-based income (existing logic)
                if (empty($jobId)) {
                    Utils::flash('error', 'Gelir kayıtlarında bir iş veya periyodik iş seçmelisiniz.');
                    redirect(base_url('/finance/new'));
                }

                $job = $this->jobModel->find($jobId);
                if (!$job) {
                    Utils::flash('error', 'İş bulunamadı.');
                    redirect(base_url('/finance/new'));
                }

                if (($job['payment_status'] ?? 'UNPAID') === 'PAID') {
                    Utils::flash('error', 'Ödemesi tamamlanmış bir iş için gelir kaydı oluşturulamaz.');
                    redirect(base_url('/finance/new'));
                }

                // ===== ERR-010 FIX: Add try-catch for error handling =====
                try {
                    $result = PaymentService::createIncomeWithPayment((int)$jobId, $amount, $date, $note, $category);
                    
                    // ===== ERR-018 FIX: Add audit logging =====
                    AuditLogger::getInstance()->logBusiness('FINANCE_INCOME_CREATED', Auth::id(), [
                        'job_id' => $jobId,
                        'amount' => $amount,
                        'category' => $category,
                        'date' => $date,
                        'payment_service_result' => $result ?? null
                    ]);
                    // ===== ERR-018 FIX: End =====
                    
                    Utils::flash('success', 'Gelir kaydı oluşturuldu.');
                } catch (Exception $e) {
                    error_log("FinanceController::store() job-based income error: " . $e->getMessage());
                    Utils::flash('error', 'Gelir kaydı oluşturulamadı: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
                }
                // ===== ERR-010 FIX: End =====
            }
        } else {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            try {
                $entryData = [
                    'kind' => 'EXPENSE',
                    'category' => $category,
                    'amount' => $amount,
                    'date' => $date,
                    'note' => $note,
                    'job_id' => $jobId ?: null,
                    'created_by' => Auth::id(),
                ];

                $entryId = $this->moneyModel->create($entryData);
                $db->commit();
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logBusiness('FINANCE_EXPENSE_CREATED', Auth::id(), [
                    'entry_id' => $entryId,
                    'amount' => $amount,
                    'category' => $category,
                    'date' => $date,
                    'job_id' => $jobId
                ]);
                // ===== ERR-018 FIX: End =====
                
                ActivityLogger::expenseAdded($entryData['amount'], $entryData['category']);
                Utils::flash('success', 'Gider kaydı oluşturuldu.');
            } catch (Exception $e) {
                $db->rollback();
                error_log("Expense creation failed: " . $e->getMessage());
                Utils::flash('error', 'Gider kaydı oluşturulamadı. Lütfen tekrar deneyin.');
            }
        }

        redirect(base_url('/finance'));
    }

    public function edit($id)
    {
        Auth::require();

        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz finans kaydı ID.');
            redirect(base_url('/finance'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $entry = $this->moneyModel->find($id);
        if (!$entry) {
            Utils::flash('error', 'Para girişi bulunamadı.');
            redirect(base_url('/finance'));
            return;
        }

        $outstandingJobs = $this->jobModel->getOutstandingJobs(50);
        if (!empty($entry['job_id'])) {
            $currentJob = $this->jobModel->find($entry['job_id']);
            if ($currentJob) {
                $alreadyListed = array_filter($outstandingJobs, function ($job) use ($entry) {
                    return $job['id'] === $entry['job_id'];
                });
                if (empty($alreadyListed)) {
                    array_unshift($outstandingJobs, $currentJob);
                }
            }
        }
        
        // Get active recurring jobs with contract-based pricing
        $db = Database::getInstance();
        $recurringWhere = $this->scopeToCompany("WHERE rj.status = 'ACTIVE' 
            AND rj.pricing_model IN ('PER_MONTH', 'TOTAL_CONTRACT')", 'rj');
        $recurringJobs = $db->fetchAll("
            SELECT 
                rj.*,
                c.name as customer_name,
                c.phone as customer_phone
            FROM recurring_jobs rj
            LEFT JOIN customers c ON rj.customer_id = c.id
            {$recurringWhere}
            ORDER BY rj.created_at DESC
        ");

        echo View::renderWithLayout('finance/form', [
            'entry' => $entry,
            'completedJobs' => $outstandingJobs,
            'recurringJobs' => $recurringJobs,
            'currentJobId' => $entry['job_id'] ?? null,
            'currentRecurringJobId' => $entry['recurring_job_id'] ?? null,
            'flash' => Utils::getFlash(),
        ]);
    }

    public function update($id)
    {
        Auth::requireCapability('finance.collect');

        $entry = $this->moneyModel->find($id);
        if (!$entry) {
            View::notFound('Para girişi bulunamadı');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/finance'));
        }

        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/finance/edit/$id"));
        }

        $validator = new Validator($_POST);
        $validator->required('kind', 'Tür seçimi zorunludur')
                 ->in('kind', ['INCOME', 'EXPENSE'], 'Geçerli bir tür seçin')
                 ->required('category', 'Kategori zorunludur')
                 ->required('amount', 'Miktar zorunludur')
                 ->numeric('amount', 'Miktar sayısal olmalıdır')
                 ->required('date', 'Tarih zorunludur')
                 ->date('date', 'Geçerli bir tarih girin');

        if ($validator->fails()) {
            ControllerHelper::flashErrorAndRedirect($validator->firstError(), "/finance/edit/$id");
            return;
        }

        $kind = $validator->get('kind');
        $category = $validator->get('category');
        $amount = $this->parseMoney($validator->get('amount'));
        $date = $validator->get('date');
        $note = $validator->get('note') ?: null;
        $jobId = $validator->get('job_id') ?: null;
        $recurringJobId = $validator->get('recurring_job_id') ?: null;
        $incomeSource = $validator->get('income_source') ?? 'job';

        if ($amount <= 0) {
            ControllerHelper::flashErrorAndRedirect('Miktar sıfırdan büyük olmalıdır.', "/finance/edit/$id");
            return;
        }

        if ($kind === 'INCOME') {
            // Check if recurring job based income
            if ($incomeSource === 'recurring' && !empty($recurringJobId)) {
                // Validate recurring job exists and is active
                $recurringJob = $this->recurringJobModel->find($recurringJobId);
                if (!$recurringJob) {
                    ControllerHelper::flashErrorAndRedirect('Periyodik iş bulunamadı.', "/finance/edit/$id");
                    return;
                }
                
                if (($recurringJob['status'] ?? '') !== 'ACTIVE') {
                    ControllerHelper::flashErrorAndRedirect('Sadece aktif periyodik işler için gelir kaydı oluşturulabilir.', "/finance/edit/$id");
                    return;
                }
                
                // Update with recurring_job_id
                $updateData = [
                    'kind' => 'INCOME',
                    'category' => $category,
                    'amount' => $amount,
                    'date' => $date,
                    'note' => $note,
                    'job_id' => null,
                    'recurring_job_id' => (int)$recurringJobId,
                ];
                
                // ===== ERR-010 FIX: Add try-catch for error handling =====
                try {
                    $oldAmount = $entry['amount'] ?? 0;
                    $oldCategory = $entry['category'] ?? '';
                    
                    $this->moneyModel->update($id, $updateData);
                    
                    // ===== ERR-018 FIX: Add audit logging =====
                    AuditLogger::getInstance()->logBusiness('FINANCE_INCOME_UPDATED', Auth::id(), [
                        'entry_id' => $id,
                        'old_amount' => $oldAmount,
                        'new_amount' => $amount,
                        'old_category' => $oldCategory,
                        'new_category' => $category,
                        'recurring_job_id' => $recurringJobId,
                        'date' => $date
                    ]);
                    // ===== ERR-018 FIX: End =====
                    
                    ControllerHelper::flashSuccessAndRedirect('Gelir kaydı güncellendi.', '/finance');
                } catch (Exception $e) {
                    ControllerHelper::handleException($e, 'FinanceController::update() recurring job income', 'Gelir kaydı güncellenemedi', "/finance/edit/$id");
                }
                // ===== ERR-010 FIX: End =====
            } else {
                // Job-based income
                if (empty($jobId)) {
                    Utils::flash('error', 'Gelir kayıtlarında bir iş veya periyodik iş seçmelisiniz.');
                    redirect(base_url("/finance/edit/$id"));
                }

                $job = $this->jobModel->find($jobId);
                if (!$job) {
                    Utils::flash('error', 'İş bulunamadı.');
                    redirect(base_url("/finance/edit/$id"));
                }

                if (($job['payment_status'] ?? 'UNPAID') === 'PAID' && (int)($entry['job_id'] ?? 0) !== (int)$jobId) {
                    Utils::flash('error', 'Ödemesi tamamlanmış bir işe gelir kaydı aktarılamaz.');
                    redirect(base_url("/finance/edit/$id"));
                }
                
                $updateData = [
                    'kind' => 'INCOME',
                    'category' => $category,
                    'amount' => $amount,
                    'date' => $date,
                    'note' => $note,
                    'job_id' => $jobId,
                    'recurring_job_id' => null
                ];
                
                // ===== ERR-010 FIX: Add try-catch for error handling =====
                try {
                    $oldAmount = $entry['amount'] ?? 0;
                    $oldCategory = $entry['category'] ?? '';
                    $oldJobId = $entry['job_id'] ?? null;
                    
                    $this->moneyModel->update($id, $updateData);
                    PaymentService::syncFinancePayment($id, (int)$jobId, $amount, $date, $note);
                    
                    // ===== ERR-018 FIX: Add audit logging =====
                    AuditLogger::getInstance()->logBusiness('FINANCE_INCOME_UPDATED', Auth::id(), [
                        'entry_id' => $id,
                        'old_amount' => $oldAmount,
                        'new_amount' => $amount,
                        'old_category' => $oldCategory,
                        'new_category' => $category,
                        'old_job_id' => $oldJobId,
                        'new_job_id' => $jobId,
                        'date' => $date
                    ]);
                    // ===== ERR-018 FIX: End =====
                    
                    Utils::flash('success', 'Gelir kaydı güncellendi.');
                } catch (Exception $e) {
                    error_log("FinanceController::update() job-based income error: " . $e->getMessage());
                    Utils::flash('error', 'Gelir kaydı güncellenemedi: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
                }
                // ===== ERR-010 FIX: End =====
            }
        } else {
            // Expense update
            // ===== ERR-010 FIX: Add try-catch for error handling =====
            try {
                $oldAmount = $entry['amount'] ?? 0;
                $oldCategory = $entry['category'] ?? '';
                $oldKind = $entry['kind'] ?? '';
                
                $updateData = [
                    'kind' => 'EXPENSE',
                    'category' => $category,
                    'amount' => $amount,
                    'date' => $date,
                    'note' => $note,
                    'job_id' => $jobId ?: null,
                    'recurring_job_id' => null
                ];
                
                $this->moneyModel->update($id, $updateData);
                
                if (($entry['kind'] ?? '') === 'INCOME') {
                    PaymentService::deleteFinancePayment((int)$id);
                }
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logBusiness('FINANCE_EXPENSE_UPDATED', Auth::id(), [
                    'entry_id' => $id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $amount,
                    'old_category' => $oldCategory,
                    'new_category' => $category,
                    'old_kind' => $oldKind,
                    'new_kind' => 'EXPENSE',
                    'date' => $date
                ]);
                // ===== ERR-018 FIX: End =====
                
                ActivityLogger::expenseAdded($amount, $category);
                Utils::flash('success', 'Gider kaydı güncellendi.');
            } catch (Exception $e) {
                error_log("FinanceController::update() expense error: " . $e->getMessage());
                Utils::flash('error', 'Gider kaydı güncellenemedi: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
            }
            // ===== ERR-010 FIX: End =====
        }

        redirect(base_url('/finance'));
    }

    public function delete($id)
    {
        Auth::requireCapability('finance.collect');

        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz finans kaydı ID.');
            redirect(base_url('/finance'));
            return;
        }
        // ===== IMPROVEMENT: End =====

        $entry = $this->moneyModel->find($id);
        if (!$entry) {
            Utils::flash('error', 'Para girişi bulunamadı.');
            redirect(base_url('/finance'));
            return;
        }

        // ===== IMPROVEMENT: Use ControllerHelper for POST and CSRF checks =====
        if (!ControllerHelper::requirePostOrRedirect('/finance')) {
            return;
        }

        if (!ControllerHelper::verifyCsrfOrRedirect('/finance')) {
            return;
        }
        // ===== IMPROVEMENT: End =====

        // ===== ERR-018 FIX: Add audit logging before deletion =====
        $entryKind = $entry['kind'] ?? '';
        $entryAmount = $entry['amount'] ?? 0;
        $entryCategory = $entry['category'] ?? '';
        // ===== ERR-018 FIX: End =====

        if (($entry['kind'] ?? '') === 'INCOME') {
            PaymentService::deleteFinancePayment((int)$id);
        }

        $this->moneyModel->delete($id);
        
        // ===== ERR-018 FIX: Add audit logging =====
        AuditLogger::getInstance()->logBusiness('FINANCE_ENTRY_DELETED', Auth::id(), [
            'entry_id' => $id,
            'kind' => $entryKind,
            'amount' => $entryAmount,
            'category' => $entryCategory,
            'job_id' => $entry['job_id'] ?? null,
            'recurring_job_id' => $entry['recurring_job_id'] ?? null
        ]);
        // ===== ERR-018 FIX: End =====

        ControllerHelper::flashSuccessAndRedirect('Kayıt silindi.', '/finance');
    }

    public function createFromJob($jobId)
    {
        Auth::require();

        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $jobId = ControllerHelper::validateId($jobId);
        if (!$jobId) {
            Utils::flash('error', 'Geçersiz iş ID.');
            redirect(base_url('/finance'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $job = $this->jobModel->find($jobId);
        if (!$job) {
            Utils::flash('error', 'İş bulunamadı.');
            redirect(base_url('/finance'));
            return;
        }

        if ($job['status'] !== 'DONE') {
            Utils::flash('error', 'Sadece tamamlanmış işlerden gelir oluşturulabilir.');
            redirect(base_url('/jobs'));
        }

        $remaining = max(0, (float)($job['total_amount'] ?? 0) - (float)($job['amount_paid'] ?? 0));
        if ($remaining <= 0) {
            Utils::flash('error', 'Bu iş için alacak bulunmuyor.');
            redirect(base_url('/jobs/show/' . $jobId));
        }

        echo View::renderWithLayout('finance/form', [
            'entry' => null,
            'job' => $job,
            'defaultAmount' => $remaining,
            'flash' => Utils::getFlash(),
        ]);
    }

    private function parseMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string)$value);
        return (float)$normalized;
    }
    
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (begin)
    public function bulkDelete()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        // CSRF protection
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
            redirect(base_url('/finance'));
            return;
        }
        
        $financeIds = InputSanitizer::array($_POST['finance_ids'] ?? [], function($id) {
            return InputSanitizer::int($id, 1);
        });
        $financeIds = array_filter($financeIds, function($id) {
            return $id !== null;
        });
        
        if (empty($financeIds) || !is_array($financeIds)) {
            Utils::flash('error', 'Lütfen en az bir kayıt seçin.');
            redirect(base_url('/finance'));
        }
        
        $db = Database::getInstance();
        $deletedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($financeIds as $financeId) {
                $entry = $this->moneyModel->find($financeId);
                if (!$entry) continue;
                
                // İlişkili iş ödemelerini güncelle
                if (!empty($entry['job_id'])) {
                    $this->jobModel->syncPayments($entry['job_id']);
                }
                
                // Finans kaydını sil
                if ($this->moneyModel->delete($financeId)) {
                    $deletedCount++;
                    ActivityLogger::log('finance_deleted', 'finance', [
                        'entry_id' => $financeId,
                        'amount' => $entry['amount'],
                        'kind' => $entry['kind']
                    ]);
                }
            }
            
            $db->commit();
            
            if ($deletedCount > 0) {
                Utils::flash('success', "{$deletedCount} finans kaydı başarıyla silindi.");
            } else {
                Utils::flash('error', 'Hiçbir kayıt silinemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'finance', [
                'action' => 'bulk_delete',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu silme sırasında hata oluştu.');
        }
        
        redirect(base_url('/finance'));
    }
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (end)
}
