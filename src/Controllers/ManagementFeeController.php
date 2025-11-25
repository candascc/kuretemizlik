<?php
/**
 * Management Fee Controller
 * Apartman/Site yönetimi - Aidat kontrolcüsü
 */

class ManagementFeeController
{
    private $feeModel;
    private $feeDefModel;
    private $buildingModel;
    private $unitModel;

    public function __construct()
    {
        $this->feeModel = new ManagementFee();
        $this->feeDefModel = new ManagementFeeDefinition();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
    }

    /**
     * Aidat oluşturma önizleme (AJAX)
     */
    public function preview()
    {
        Auth::require();
        header('Content-Type: application/json');
        if (!CSRF::verifyRequest()) {
            echo json_encode(['success' => false, 'error' => 'CSRF doğrulaması başarısız']);
            return;
        }

        try {
            $generateType = $_POST['generate_type'] ?? 'all';
            $buildingId = (int)($_POST['building_id'] ?? 0);
            $period = $_POST['period'] ?? date('Y-m');

            $buildings = ($generateType === 'single' && $buildingId) ? [$this->buildingModel->find($buildingId)] : $this->buildingModel->active();
            $unitsCount = 0;
            $totalAmount = 0.0;

            foreach ($buildings as $b) {
                if (!$b) { continue; }
                $units = $this->unitModel->getByBuilding($b['id']);
                $unitsCount += is_array($units) ? count($units) : 0;
                // Varsayılan birim tutarı (tanım yoksa) 0 kabul edilir; gerçek hesap model içinde yapılır
            }

            echo json_encode([
                'success' => true,
                'buildings_count' => is_array($buildings) ? count($buildings) : 0,
                'units_count' => $unitsCount,
                'total_amount' => $totalAmount,
                'period' => $period
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Aidat listesi
     */
    public function index()
    {
        Auth::require();

        $page = (int)($_GET['page'] ?? 1);
        $buildingId = $_GET['building_id'] ?? null;
        $unitId = $_GET['unit_id'] ?? null;
        $status = $_GET['status'] ?? '';
        $period = $_GET['period'] ?? '';

        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($unitId) $filters['unit_id'] = $unitId;
        if ($status && $status !== 'all') $filters['status'] = $status;
        if ($period) $filters['period'] = $period;

        $result = $this->feeModel->paginate($filters, $limit, $offset);
        $fees = $result['data'];
        $total = $result['total'];
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('management-fees/index', [
                'title' => 'Aidat Yönetimi',
                'fees' => $fees ?: [],
                'pagination' => $pagination,
                'filters' => $filters,
                'buildings' => $buildings ?: []
            ]);
        } catch (Exception $e) {
            error_log("ManagementFeeController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Aidat detay
     */
    public function show($id)
    {
        Auth::require();

        $fee = $this->feeModel->find($id);
        if (!$fee) {
            Utils::flash('error', 'Aidat kaydı bulunamadı');
            redirect(base_url('/management-fees'));
        }

        echo View::renderWithLayout('management-fees/show', [
            'title' => 'Aidat Detay',
            'fee' => $fee
        ]);
    }

    /**
     * Aylık aidat oluşturma
     */
    public function generate()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        if ($buildingId) {
            $building = $this->buildingModel->find($buildingId);
        }

        echo View::renderWithLayout('management-fees/generate', [
            'title' => 'Aidat Oluştur',
            'buildings' => $buildings,
            'buildingId' => $buildingId ?? null
        ]);
    }

    /**
     * Aidat oluşturma işlemi
     */
    public function generateProcess()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/management-fees/generate'));
        }

        try {
            $buildingId = (int)($_POST['building_id'] ?? 0);
            $period = $_POST['period'] ?? '';

            if (!$buildingId) {
                throw new Exception('Bina seçilmelidir');
            }

            if (!$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
                throw new Exception('Geçerli bir dönem formatı girin (YYYY-MM)');
            }

            $count = $this->feeModel->generateForPeriod($buildingId, $period);

            ActivityLogger::log('fee.bulk_generated', 'building', $buildingId, ['period' => $period, 'count' => $count]);
            Utils::flash('success', "{$count} adet aidat kaydı oluşturuldu");
            redirect(base_url("/management-fees?building_id={$buildingId}&period={$period}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/management-fees/generate'));
        }
    }

    /**
     * Ödeme kaydetme formu
     */
    public function paymentForm($id)
    {
        Auth::require();

        $fee = $this->feeModel->find($id);
        if (!$fee) {
            Utils::flash('error', 'Aidat kaydı bulunamadı');
            redirect(base_url('/management-fees'));
        }

        echo View::renderWithLayout('management-fees/payment-form', [
            'title' => 'Ödeme Kaydet',
            'fee' => $fee
        ]);
    }

    /**
     * Ödeme kaydetme işlemi
     */
    public function recordPayment($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/management-fees/{$id}/payment"));
        }

        try {
            $amount = (float)($_POST['amount'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $notes = $_POST['notes'] ?? '';

            if ($amount <= 0) {
                throw new Exception('Ödeme tutarı 0\'dan büyük olmalıdır');
            }

            $fee = $this->feeModel->find($id);
            if (!$fee) {
                throw new Exception('Aidat kaydı bulunamadı');
            }

            $remaining = $fee['total_amount'] - $fee['paid_amount'];
            if ($amount > $remaining) {
                throw new Exception("Kalan borç tutarından fazla ödeme yapılamaz (Kalan: {$remaining} TL)");
            }

            $this->feeModel->recordPayment($id, $amount, $paymentMethod, $notes);

            ActivityLogger::log('fee.payment_recorded', 'management_fee', $id, ['amount' => $amount]);
            Utils::flash('success', 'Ödeme başarıyla kaydedildi');
            redirect(base_url("/management-fees/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/management-fees/{$id}/payment"));
        }
    }

    /**
     * Geciken aidatlar
     */
    public function overdue()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $filters = ['overdue_only' => true];
        if ($buildingId) $filters['building_id'] = $buildingId;

        $fees = $this->feeModel->all($filters);

        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('management-fees/overdue', [
            'title' => 'Geciken Aidatlar',
            'fees' => $fees,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Geçikme ücreti hesapla
     */
    public function calculateLateFees()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/management-fees/overdue'));
        }


        try {
            $buildingId = !empty($_POST['building_id']) ? (int)$_POST['building_id'] : null;
            $updated = $this->feeModel->calculateLateFees($buildingId);

            ActivityLogger::log('fee.late_fees_calculated', 'system', null, ['count' => $updated]);
            Utils::flash('success', "{$updated} adet aidat için geçikme ücreti hesaplandı");
            redirect(base_url('/management-fees/overdue'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/management-fees/overdue'));
        }
    }
}

