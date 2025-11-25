<?php
/**
 * Export Service
 * Handles data export to Excel, CSV, and PDF formats
 */
class ExportService
{
    /**
     * Export jobs to Excel/CSV
     */
    public static function exportJobs(array $filters = [], string $format = 'csv'): string
    {
        $db = Database::getInstance();
        
        // Build query
        $sql = "
            SELECT 
                j.id,
                j.start_at,
                j.end_at,
                j.status,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                j.note,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                s.name as service_name,
                a.line as address_line,
                a.city as address_city,
                j.created_at
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND j.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['customer'])) {
            $sql .= " AND c.name LIKE ?";
            $params[] = '%' . $filters['customer'] . '%';
        }
        
        if (isset($filters['company_id'])) {
            $sql .= " AND j.company_id = ?";
            $params[] = (int)$filters['company_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(j.start_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(j.start_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY j.start_at DESC";
        
        // For large datasets, use chunked processing
        $jobs = $db->fetchAll($sql, $params);
        
        // Limit export to prevent memory issues (can be increased for production)
        if (count($jobs) > 10000) {
            $jobs = array_slice($jobs, 0, 10000);
        }
        
        if ($format === 'csv') {
            // Use comma for better Excel compatibility
            return self::generateCSV($jobs, [
                'ID',
                'Başlangıç',
                'Bitiş',
                'Durum',
                'Toplam Tutar',
                'Ödenen',
                'Ödeme Durumu',
                'Not',
                'Müşteri',
                'Telefon',
                'Email',
                'Hizmet',
                'Adres',
                'Şehir',
                'Oluşturulma'
            ]);
        }
        
        return self::generateExcel($jobs);
    }
    
    /**
     * Export customers to CSV
     */
    public static function exportCustomers(array $filters = []): string
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.phone,
                c.email,
                c.notes,
                COUNT(DISTINCT j.id) as total_jobs,
                SUM(j.total_amount) as total_revenue,
                c.created_at
            FROM customers c
            LEFT JOIN jobs j ON c.id = j.customer_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (isset($filters['company_id'])) {
            $sql .= " AND c.company_id = ?";
            $params[] = (int)$filters['company_id'];
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.name";
        
        $customers = $db->fetchAll($sql, $params);
        
        // Limit export to prevent memory issues
        if (count($customers) > 10000) {
            $customers = array_slice($customers, 0, 10000);
        }
        
        return self::generateCSV($customers, [
            'ID',
            'Ad',
            'Telefon',
            'Email',
            'Notlar',
            'Toplam İş',
            'Toplam Gelir',
            'Kayıt Tarihi'
        ]);
    }
    
    /**
     * Generate CSV content
     */
    public static function generateCSV(array $data, array $headers, string $delimiter = ','): string
    {
        $output = [];
        
        // BOM for UTF-8 Excel compatibility
        $output[] = "\xEF\xBB\xBF";
        
        // Headers
        $output[] = implode($delimiter, array_map(function($val) use ($delimiter) {
            return self::escapeCSV($val, $delimiter);
        }, $headers));
        
        // Data rows - use streaming for large datasets
        $rowCount = 0;
        foreach ($data as $row) {
            $values = [];
            foreach ($row as $value) {
                $values[] = self::escapeCSV($value ?? '', $delimiter);
            }
            $output[] = implode($delimiter, $values);
            
            // Memory optimization: flush every 1000 rows for large datasets
            $rowCount++;
            if ($rowCount % 1000 === 0) {
                // In production, you'd want to flush to file here
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Escape CSV value
     */
    public static function escapeCSV($value, string $delimiter = ','): string
    {
        $value = (string)$value;
        // Check if value contains delimiter, quotes, or newlines
        if (strpos($value, $delimiter) !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false || strpos($value, "\r") !== false) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
    
    /**
     * Generate Excel/HTML table (basic Excel-compatible format)
     */
    public static function generateExcel(array $data, array $headers = []): string
    {
        // For now, return HTML table that Excel can open
        // In production, use PhpSpreadsheet library
        $html = '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background-color:#f2f2f2}</style></head><body><table>';
        
        if (!empty($data)) {
            // Headers
            $html .= '<tr>';
            $headerKeys = !empty($headers) ? array_keys($headers) : array_keys($data[0]);
            $headerValues = !empty($headers) ? array_values($headers) : $headerKeys;
            
            foreach ($headerValues as $header) {
                $html .= '<th>' . htmlspecialchars((string)$header) . '</th>';
            }
            $html .= '</tr>';
            
            // Data
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($headerKeys as $key) {
                    $html .= '<td>' . htmlspecialchars($row[$key] ?? '') . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table></body></html>';
        
        return $html;
    }
    
    /**
     * Export management fees to Excel/CSV
     */
    public static function exportManagementFees(array $filters = [], string $format = 'excel'): string
    {
        $db = Database::getInstance();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) {
            $where[] = 'mf.building_id = ?';
            $params[] = $filters['building_id'];
        }
        
        if (!empty($filters['unit_id'])) {
            $where[] = 'mf.unit_id = ?';
            $params[] = $filters['unit_id'];
        }
        
        if (!empty($filters['period'])) {
            $where[] = 'mf.period = ?';
            $params[] = $filters['period'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'mf.status = ?';
            $params[] = $filters['status'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $fees = $db->fetchAll("
            SELECT 
                mf.id,
                mf.period,
                mf.fee_name,
                b.name as building_name,
                u.unit_number,
                u.owner_name as unit_owner,
                mf.base_amount,
                mf.discount_amount,
                mf.late_fee,
                mf.total_amount,
                mf.paid_amount,
                mf.status,
                mf.due_date,
                mf.payment_date,
                mf.payment_method,
                mf.receipt_number,
                mf.notes
            FROM management_fees mf
            LEFT JOIN buildings b ON mf.building_id = b.id
            LEFT JOIN units u ON mf.unit_id = u.id
            {$whereSql}
            ORDER BY mf.due_date DESC, mf.id DESC
            LIMIT 10000
        ", $params);
        
        $headers = [
            'id' => 'ID',
            'period' => 'Dönem',
            'fee_name' => 'Aidat Adı',
            'building_name' => 'Bina',
            'unit_number' => 'Daire No',
            'unit_owner' => 'Mal Sahibi',
            'base_amount' => 'Tutar',
            'discount_amount' => 'İndirim',
            'late_fee' => 'Gecikme',
            'total_amount' => 'Toplam',
            'paid_amount' => 'Ödenen',
            'status' => 'Durum',
            'due_date' => 'Vade Tarihi',
            'payment_date' => 'Ödeme Tarihi',
            'payment_method' => 'Ödeme Yöntemi',
            'receipt_number' => 'Makbuz No',
            'notes' => 'Notlar'
        ];
        
        if ($format === 'csv') {
            return self::generateCSV($fees, array_values($headers));
        }
        
        return self::generateExcel($fees, $headers);
    }
    
    /**
     * Export building expenses to Excel/CSV
     */
    public static function exportBuildingExpenses(array $filters = [], string $format = 'excel'): string
    {
        $db = Database::getInstance();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) {
            $where[] = 'be.building_id = ?';
            $params[] = $filters['building_id'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = 'be.category = ?';
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['approval_status'])) {
            $where[] = 'be.approval_status = ?';
            $params[] = $filters['approval_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(be.expense_date) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(be.expense_date) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $expenses = $db->fetchAll("
            SELECT 
                be.id,
                be.expense_date,
                be.description,
                be.category,
                be.amount,
                be.vendor_name,
                be.invoice_number,
                be.approval_status,
                u.username as approved_by_user,
                b.name as building_name,
                be.notes,
                be.created_at
            FROM building_expenses be
            LEFT JOIN buildings b ON be.building_id = b.id
            LEFT JOIN users u ON be.approved_by = u.id
            {$whereSql}
            ORDER BY be.expense_date DESC, be.id DESC
            LIMIT 10000
        ", $params);
        
        $headers = [
            'id' => 'ID',
            'expense_date' => 'Gider Tarihi',
            'description' => 'Açıklama',
            'category' => 'Kategori',
            'amount' => 'Tutar',
            'vendor_name' => 'Firma',
            'invoice_number' => 'Fatura No',
            'approval_status' => 'Onay Durumu',
            'approved_by_user' => 'Onaylayan',
            'building_name' => 'Bina',
            'notes' => 'Notlar',
            'created_at' => 'Oluşturulma'
        ];
        
        if ($format === 'csv') {
            return self::generateCSV($expenses, array_values($headers));
        }
        
        return self::generateExcel($expenses, $headers);
    }
    
    /**
     * Generate PDF report (basic HTML-based)
     */
    public static function generatePDF(array $data, array $headers, string $title = 'Rapor'): string
    {
        $html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #333;
        }
        h1 {
            font-size: 18px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .meta {
            margin-bottom: 20px;
            color: #7f8c8d;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #95a5a6;
        }
        .summary {
            margin: 20px 0;
            padding: 15px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .summary-row strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($title) . '</h1>
    <div class="meta">
        Oluşturulma Tarihi: ' . date('d.m.Y H:i') . '
    </div>';
        
        if (!empty($data)) {
            $html .= '<table>
                <thead>
                    <tr>';
            
            $headerValues = array_values($headers);
            foreach ($headerValues as $header) {
                $html .= '<th>' . htmlspecialchars((string)$header) . '</th>';
            }
            
            $html .= '</tr>
                </thead>
                <tbody>';
            
            $headerKeys = array_keys($headers);
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($headerKeys as $key) {
                    $value = $row[$key] ?? '';
                    // Format numeric values
                    if (is_numeric($value) && (strpos($key, 'amount') !== false || strpos($key, 'total') !== false)) {
                        $value = number_format((float)$value, 2) . ' ₺';
                    }
                    $html .= '<td>' . htmlspecialchars((string)$value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody>
            </table>';
        } else {
            $html .= '<p>Henüz veri bulunmamaktadır.</p>';
        }
        
        $html .= '<div class="footer">
            Bu rapor otomatik olarak oluşturulmuştur. ' . htmlspecialchars($title) . '
        </div>
</body>
</html>';
        
        return $html;
    }
}

