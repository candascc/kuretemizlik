<?php
/**
 * Building Expense Controller
 * Apartman/Site yönetimi - Bina gideri kontrolcüsü
 */

class BuildingExpenseController
{
    private $expenseModel;
    private $buildingModel;

    public function __construct()
    {
        $this->expenseModel = new BuildingExpense();
        $this->buildingModel = new Building();
    }

    /**
     * Gider listesi
     */
    public function index()
    {
        Auth::require();

        $page = (int)($_GET['page'] ?? 1);
        $buildingId = $_GET['building_id'] ?? null;
        $category = $_GET['category'] ?? '';
        $status = $_GET['approval_status'] ?? '';

        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($category) $filters['category'] = $category;
        if ($status) $filters['approval_status'] = $status;

        $expenses = $this->expenseModel->all($filters, $limit, $offset);
        $total = count($expenses);
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('expenses/index', [
                'title' => 'Bina Giderleri',
                'expenses' => $expenses ?: [],
                'pagination' => $pagination,
                'filters' => $filters,
                'buildings' => $buildings ?: []
            ]);
        } catch (Exception $e) {
            error_log("BuildingExpenseController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Gider detay
     */
    public function show($id)
    {
        Auth::require();

        $expense = $this->expenseModel->find($id);
        if (!$expense) {
            Utils::flash('error', 'Gider kaydı bulunamadı');
            redirect(base_url('/expenses'));
        }

        echo View::renderWithLayout('expenses/show', [
            'title' => 'Gider Detay',
            'expense' => $expense
        ]);
    }

    /**
     * Yeni gider formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('expenses/form', [
            'title' => 'Yeni Gider Ekle',
            'expense' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Gider kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/expenses'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'category' => $_POST['category'] ?? 'diger',
                'subcategory' => $_POST['subcategory'] ?? '',
                'amount' => (float)($_POST['amount'] ?? 0),
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'invoice_number' => $_POST['invoice_number'] ?? '',
                'vendor_name' => $_POST['vendor_name'] ?? '',
                'vendor_tax_number' => $_POST['vendor_tax_number'] ?? '',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
                'description' => $_POST['description'] ?? '',
                'approval_status' => 'pending'
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if ($data['amount'] <= 0) {
                throw new Exception('Tutar 0\'dan büyük olmalıdır');
            }

            $id = $this->expenseModel->create($data);

            ActivityLogger::log('expense.created', 'building_expense', $id, ['amount' => $data['amount']]);
            Utils::flash('success', 'Gider başarıyla kaydedildi');
            redirect(base_url("/expenses/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/expenses/new'));
        }
    }

    /**
     * Gider düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();

        $expense = $this->expenseModel->find($id);
        if (!$expense) {
            Utils::flash('error', 'Gider kaydı bulunamadı');
            redirect(base_url('/expenses'));
        }

        if ($expense['approval_status'] === 'approved') {
            Utils::flash('error', 'Onaylanmış giderler düzenlenemez');
            redirect(base_url("/expenses/{$id}"));
        }

        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('expenses/form', [
            'title' => 'Gider Düzenle',
            'expense' => $expense,
            'buildings' => $buildings,
            'buildingId' => $expense['building_id']
        ]);
    }

    /**
     * Gider güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/expenses'));
        }

        try {
            $expense = $this->expenseModel->find($id);
            if (!$expense) {
                throw new Exception('Gider kaydı bulunamadı');
            }

            if ($expense['approval_status'] === 'approved') {
                throw new Exception('Onaylanmış giderler düzenlenemez');
            }

            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'category' => $_POST['category'] ?? 'diger',
                'subcategory' => $_POST['subcategory'] ?? '',
                'amount' => (float)($_POST['amount'] ?? 0),
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'invoice_number' => $_POST['invoice_number'] ?? '',
                'vendor_name' => $_POST['vendor_name'] ?? '',
                'vendor_tax_number' => $_POST['vendor_tax_number'] ?? '',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
                'description' => $_POST['description'] ?? ''
            ];

            if ($data['amount'] <= 0) {
                throw new Exception('Tutar 0\'dan büyük olmalıdır');
            }

            $this->expenseModel->update($id, $data);

            ActivityLogger::log('expense.updated', 'building_expense', $id);
            Utils::flash('success', 'Gider başarıyla güncellendi');
            redirect(base_url("/expenses/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/expenses/{$id}/edit"));
        }
    }

    /**
     * Gider sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/expenses'));
        }

        try {
            $expense = $this->expenseModel->find($id);
            if (!$expense) {
                throw new Exception('Gider kaydı bulunamadı');
            }

            $this->expenseModel->delete($id);

            ActivityLogger::log('expense.deleted', 'building_expense', $id);
            Utils::flash('success', 'Gider başarıyla silindi');
            redirect(base_url('/expenses'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/expenses/{$id}"));
        }
    }

    /**
     * Onay bekleyen giderler
     */
    public function approvalQueue()
    {
        Auth::require();

        $expenses = $this->expenseModel->all(['approval_status' => 'pending']);

        echo View::renderWithLayout('expenses/approval-queue', [
            'title' => 'Onay Bekleyen Giderler',
            'expenses' => $expenses
        ]);
    }

    /**
     * Gider onayla
     */
    public function approve($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/expenses/approval-queue'));
        }

        try {
            $this->expenseModel->approve($id, Auth::id());

            ActivityLogger::log('expense.approved', 'building_expense', $id);
            Utils::flash('success', 'Gider onaylandı');
            redirect(base_url('/expenses/approval-queue'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/expenses/approval-queue'));
        }
    }

    /**
     * Gider reddet
     */
    public function reject($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/expenses/approval-queue'));
        }

        try {
            $this->expenseModel->reject($id, Auth::id());

            ActivityLogger::log('expense.rejected', 'building_expense', $id);
            Utils::flash('success', 'Gider reddedildi');
            redirect(base_url('/expenses/approval-queue'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/expenses/approval-queue'));
        }
    }
}

