<?php
/**
 * Export Controller
 */

class ExportController
{
    public function index()
    {
        Auth::require();
        
        echo View::renderWithLayout('export/index', [
            'title' => 'Veri Dışa Aktarma'
        ]);
    }
    
    public function customers()
    {
        Auth::require();
        
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $customerModel = new Customer();
        $customers = $customerModel->all();
        
        if ($format === 'csv') {
            $this->exportCustomersCsv($customers);
        } elseif ($format === 'excel') {
            $this->exportCustomersExcel($customers);
        } else {
            View::notFound('Desteklenmeyen format');
        }
    }

    public function exportCustomers()
    {
        return $this->customers();
    }
    
    public function jobs()
    {
        Auth::require();
        
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $jobModel = new Job();
        $jobs = $jobModel->all();
        
        if ($format === 'csv') {
            $this->exportJobsCsv($jobs);
        } elseif ($format === 'excel') {
            $this->exportJobsExcel($jobs);
        } else {
            View::notFound('Desteklenmeyen format');
        }
    }

    public function exportJobs()
    {
        return $this->jobs();
    }
    
    public function finance()
    {
        Auth::require();
        
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $moneyModel = new MoneyEntry();
        $entries = $moneyModel->all();
        
        if ($format === 'csv') {
            $this->exportFinanceCsv($entries);
        } elseif ($format === 'excel') {
            $this->exportFinanceExcel($entries);
        } else {
            View::notFound('Desteklenmeyen format');
        }
    }

    public function exportFinance()
    {
        return $this->finance();
    }
    
    private function exportCustomersCsv($customers)
    {
        $filename = 'musteriler_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID',
            'Ad',
            'Telefon',
            'E-posta',
            'Notlar',
            'Oluşturulma Tarihi',
            'Güncellenme Tarihi'
        ]);
        
        // Data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['name'],
                $customer['phone'],
                $customer['email'],
                $customer['notes'],
                $customer['created_at'],
                $customer['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportJobsCsv($jobs)
    {
        $filename = 'isler_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID',
            'Müşteri',
            'Hizmet',
            'Başlangıç Tarihi',
            'Bitiş Tarihi',
            'Durum',
            'Toplam Tutar',
            'Ödenen Tutar',
            'Ödeme Durumu'
        ]);
        
        // Data
        foreach ($jobs as $job) {
            fputcsv($output, [
                $job['id'],
                $job['customer_name'] ?? '',
                $job['service_name'] ?? '',
                $job['start_at'],
                $job['end_at'],
                $job['status'],
                $job['total_amount'],
                $job['amount_paid'],
                $job['payment_status']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportFinanceCsv($entries)
    {
        $filename = 'finans_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID',
            'Tür',
            'Kategori',
            'Tutar',
            'Açıklama',
            'Tarih',
            'Oluşturulma Tarihi'
        ]);
        
        // Data
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry['id'],
                $entry['kind'],
                $entry['category'],
                $entry['amount'],
                $entry['description'],
                $entry['date'],
                $entry['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportCustomersExcel($customers)
    {
        // Simple Excel export using HTML table
        $filename = 'musteriler_' . date('Y-m-d_H-i-s') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<table border="1">';
        echo '<tr><th>ID</th><th>Ad</th><th>Telefon</th><th>E-posta</th><th>Notlar</th><th>Oluşturulma Tarihi</th><th>Güncellenme Tarihi</th></tr>';
        
        foreach ($customers as $customer) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($customer['id']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['name']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['phone']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['email']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['notes']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($customer['updated_at']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    }
    
    private function exportJobsExcel($jobs)
    {
        $filename = 'isler_' . date('Y-m-d_H-i-s') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<table border="1">';
        echo '<tr><th>ID</th><th>Müşteri</th><th>Hizmet</th><th>Başlangıç Tarihi</th><th>Bitiş Tarihi</th><th>Durum</th><th>Toplam Tutar</th><th>Ödenen Tutar</th><th>Ödeme Durumu</th></tr>';
        
        foreach ($jobs as $job) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($job['id']) . '</td>';
            echo '<td>' . htmlspecialchars($job['customer_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($job['service_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($job['start_at']) . '</td>';
            echo '<td>' . htmlspecialchars($job['end_at']) . '</td>';
            echo '<td>' . htmlspecialchars($job['status']) . '</td>';
            echo '<td>' . htmlspecialchars($job['total_amount']) . '</td>';
            echo '<td>' . htmlspecialchars($job['amount_paid']) . '</td>';
            echo '<td>' . htmlspecialchars($job['payment_status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    }
    
    private function exportFinanceExcel($entries)
    {
        $filename = 'finans_' . date('Y-m-d_H-i-s') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<table border="1">';
        echo '<tr><th>ID</th><th>Tür</th><th>Kategori</th><th>Tutar</th><th>Açıklama</th><th>Tarih</th><th>Oluşturulma Tarihi</th></tr>';
        
        foreach ($entries as $entry) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($entry['id']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['kind']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['category']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['amount']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['description']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['date']) . '</td>';
            echo '<td>' . htmlspecialchars($entry['created_at']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    }
}
