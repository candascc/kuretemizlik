<?php
/**
 * Building Report Controller
 * Apartman/Site yönetimi - Rapor kontrolcüsü
 */

class BuildingReportController
{
    private $buildingModel;
    private $feeModel;
    private $expenseModel;

    public function __construct()
    {
        $this->buildingModel = new Building();
        $this->feeModel = new ManagementFee();
        $this->expenseModel = new BuildingExpense();
    }

    /**
     * Finansal raporlar
     */
    public function financial()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = $_GET['month'] ?? null;

        $buildings = $this->buildingModel->active();

        if ($buildingId) {
            $building = $this->buildingModel->find($buildingId);
            
            // İstatistikler
            $stats = $this->buildingModel->getStatistics($buildingId);

            // Aylık özet
            $monthlyExpenses = $this->expenseModel->getMonthlySummary($buildingId, $year);
            
            echo View::renderWithLayout('building-reports/financial', [
                'title' => 'Finansal Rapor',
                'building' => $building,
                'stats' => $stats,
                'monthlyExpenses' => $monthlyExpenses,
                'year' => $year,
                'buildings' => $buildings
            ]);
        } else {
            echo View::renderWithLayout('building-reports/financial', [
                'title' => 'Finansal Raporlar',
                'buildings' => $buildings
            ]);
        }
    }

    /**
     * Tahsilat raporu
     */
    public function collection()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        
        if (!$buildingId) {
            $buildings = $this->buildingModel->active();
            echo View::renderWithLayout('building-reports/collection', [
                'title' => 'Tahsilat Raporu',
                'buildings' => $buildings
            ]);
            return;
        }

        $building = $this->buildingModel->find($buildingId);
        $overdueFees = $this->feeModel->all(['building_id' => $buildingId, 'status' => 'overdue']);

        echo View::renderWithLayout('building-reports/collection', [
            'title' => 'Tahsilat Raporu',
            'building' => $building,
            'overdueFees' => $overdueFees
        ]);
    }
    
    /**
     * Export fees to Excel/CSV/PDF
     */
    public function exportFees()
    {
        Auth::require();
        
        $format = $_GET['format'] ?? 'excel';
        $buildingId = $_GET['building_id'] ?? null;
        
        if (!in_array($format, ['excel', 'csv', 'pdf'])) {
            $format = 'excel';
        }
        
        $filters = ['building_id' => $buildingId];
        if (!empty($_GET['period'])) {
            $filters['period'] = $_GET['period'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if ($format === 'pdf') {
            $data = $this->feeModel->all($filters, 10000, 0);
            $headers = [
                'id' => 'ID',
                'period' => 'Dönem',
                'fee_name' => 'Aidat Adı',
                'building_name' => 'Bina',
                'unit_number' => 'Daire No',
                'unit_owner' => 'Mal Sahibi',
                'total_amount' => 'Toplam',
                'paid_amount' => 'Ödenen',
                'status' => 'Durum',
                'due_date' => 'Vade Tarihi'
            ];
            $content = ExportService::generatePDF($data, $headers, 'Aidat Raporu');
            
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="aidat_raporu_' . date('Y-m-d') . '.html"');
            echo $content;
            exit;
        }
        
        $content = ExportService::exportManagementFees($filters, $format);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="aidatlar_' . date('Y-m-d') . '.csv"');
        } else {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="aidatlar_' . date('Y-m-d') . '.xls"');
        }
        
        echo $content;
        exit;
    }
    
    /**
     * Export expenses to Excel/CSV/PDF
     */
    public function exportExpenses()
    {
        Auth::require();
        
        $format = $_GET['format'] ?? 'excel';
        $buildingId = $_GET['building_id'] ?? null;
        
        if (!in_array($format, ['excel', 'csv', 'pdf'])) {
            $format = 'excel';
        }
        
        $filters = ['building_id' => $buildingId];
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        if (!empty($_GET['approval_status'])) {
            $filters['approval_status'] = $_GET['approval_status'];
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if ($format === 'pdf') {
            $data = $this->expenseModel->all($filters, 10000, 0);
            $headers = [
                'id' => 'ID',
                'expense_date' => 'Gider Tarihi',
                'description' => 'Açıklama',
                'category' => 'Kategori',
                'amount' => 'Tutar',
                'vendor_name' => 'Firma',
                'approval_status' => 'Onay Durumu',
                'building_name' => 'Bina'
            ];
            $content = ExportService::generatePDF($data, $headers, 'Gider Raporu');
            
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="gider_raporu_' . date('Y-m-d') . '.html"');
            echo $content;
            exit;
        }
        
        $content = ExportService::exportBuildingExpenses($filters, $format);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="giderler_' . date('Y-m-d') . '.csv"');
        } else {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="giderler_' . date('Y-m-d') . '.xls"');
        }
        
        echo $content;
        exit;
    }
}

