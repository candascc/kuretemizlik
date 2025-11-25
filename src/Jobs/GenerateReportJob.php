<?php
/**
 * Generate Report Job
 * Handles asynchronous report generation
 */

class GenerateReportJob extends Job
{
    private $reportType;
    private $parameters;
    private $userId;
    private $format;
    private $outputPath;
    
    public function __construct(array $payload = [])
    {
        parent::__construct($payload);
        
        $this->reportType = $payload['report_type'] ?? '';
        $this->parameters = $payload['parameters'] ?? [];
        $this->userId = $payload['user_id'] ?? null;
        $this->format = $payload['format'] ?? 'pdf';
        $this->outputPath = $payload['output_path'] ?? null;
    }
    
    /**
     * Execute the job
     */
    public function handle(): void
    {
        if (empty($this->reportType)) {
            throw new Exception('Report type is required');
        }
        
        $this->generateReport();
    }
    
    /**
     * Generate report
     */
    private function generateReport(): void
    {
        $reportData = $this->fetchReportData();
        $filePath = $this->generateReportFile($reportData);
        
        // Store report metadata
        $this->storeReportMetadata($filePath);
        
        // Send notification if user ID provided
        if ($this->userId) {
            $this->sendNotification();
        }
        
        Logger::info('Report generated successfully', [
            'report_type' => $this->reportType,
            'file_path' => $filePath,
            'user_id' => $this->userId
        ]);
    }
    
    /**
     * Fetch report data
     */
    private function fetchReportData(): array
    {
        $db = Database::getInstance();
        
        switch ($this->reportType) {
            case 'financial':
                return $this->fetchFinancialData($db);
            case 'customer':
                return $this->fetchCustomerData($db);
            case 'job':
                return $this->fetchJobData($db);
            case 'staff':
                return $this->fetchStaffData($db);
            default:
                throw new Exception("Unknown report type: {$this->reportType}");
        }
    }
    
    /**
     * Fetch financial data
     */
    private function fetchFinancialData(Database $db): array
    {
        $startDate = $this->parameters['start_date'] ?? date('Y-m-01');
        $endDate = $this->parameters['end_date'] ?? date('Y-m-t');
        
        $revenue = $db->fetch(
            "SELECT SUM(amount) as total FROM payments WHERE created_at BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
        
        $expenses = $db->fetch(
            "SELECT SUM(amount) as total FROM expenses WHERE created_at BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
        
        $jobs = $db->fetchAll(
            "SELECT j.*, c.name as customer_name, s.name as service_name 
             FROM jobs j 
             JOIN customers c ON j.customer_id = c.id 
             JOIN services s ON j.service_id = s.id 
             WHERE j.created_at BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
        
        return [
            'revenue' => $revenue['total'] ?? 0,
            'expenses' => $expenses['total'] ?? 0,
            'jobs' => $jobs,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }
    
    /**
     * Fetch customer data
     */
    private function fetchCustomerData(Database $db): array
    {
        $customers = $db->fetchAll(
            "SELECT c.*, COUNT(j.id) as job_count, SUM(j.total_amount) as total_spent
             FROM customers c
             LEFT JOIN jobs j ON c.id = j.customer_id
             GROUP BY c.id
             ORDER BY total_spent DESC"
        );
        
        return [
            'customers' => $customers,
            'total_customers' => count($customers)
        ];
    }
    
    /**
     * Fetch job data
     */
    private function fetchJobData(Database $db): array
    {
        $startDate = $this->parameters['start_date'] ?? date('Y-m-01');
        $endDate = $this->parameters['end_date'] ?? date('Y-m-t');
        
        $jobs = $db->fetchAll(
            "SELECT j.*, c.name as customer_name, s.name as service_name, u.username as staff_name
             FROM jobs j
             JOIN customers c ON j.customer_id = c.id
             JOIN services s ON j.service_id = s.id
             LEFT JOIN users u ON j.assigned_to = u.id
             WHERE j.created_at BETWEEN ? AND ?
             ORDER BY j.created_at DESC",
            [$startDate, $endDate]
        );
        
        return [
            'jobs' => $jobs,
            'total_jobs' => count($jobs),
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }
    
    /**
     * Fetch staff data
     */
    private function fetchStaffData(Database $db): array
    {
        $staff = $db->fetchAll(
            "SELECT u.*, COUNT(j.id) as job_count, SUM(j.total_amount) as total_revenue
             FROM users u
             LEFT JOIN jobs j ON u.id = j.assigned_to
             WHERE u.role IN ('USER', 'MANAGER', 'SUPERVISOR')
             GROUP BY u.id
             ORDER BY total_revenue DESC"
        );
        
        return [
            'staff' => $staff,
            'total_staff' => count($staff)
        ];
    }
    
    /**
     * Generate report file
     */
    private function generateReportFile(array $data): string
    {
        $filename = $this->generateFilename();
        $filePath = $this->outputPath ?: sys_get_temp_dir() . '/' . $filename;
        
        switch ($this->format) {
            case 'pdf':
                $this->generatePdfReport($data, $filePath);
                break;
            case 'csv':
                $this->generateCsvReport($data, $filePath);
                break;
            case 'excel':
                $this->generateExcelReport($data, $filePath);
                break;
            default:
                throw new Exception("Unsupported format: {$this->format}");
        }
        
        return $filePath;
    }
    
    /**
     * Generate filename
     */
    private function generateFilename(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        return "report_{$this->reportType}_{$timestamp}.{$this->format}";
    }
    
    /**
     * Generate PDF report
     */
    private function generatePdfReport(array $data, string $filePath): void
    {
        // Simple HTML to PDF conversion (in production, use a proper PDF library)
        $html = $this->generateHtmlReport($data);
        file_put_contents($filePath, $html);
    }
    
    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $data, string $filePath): void
    {
        $output = fopen($filePath, 'w');
        
        switch ($this->reportType) {
            case 'financial':
                fputcsv($output, ['Date', 'Revenue', 'Expenses', 'Profit']);
                // Add financial data rows
                break;
            case 'customer':
                fputcsv($output, ['Name', 'Email', 'Phone', 'Job Count', 'Total Spent']);
                foreach ($data['customers'] as $customer) {
                    fputcsv($output, [
                        $customer['name'],
                        $customer['email'],
                        $customer['phone'],
                        $customer['job_count'],
                        $customer['total_spent']
                    ]);
                }
                break;
            case 'job':
                fputcsv($output, ['ID', 'Customer', 'Service', 'Status', 'Amount', 'Date']);
                foreach ($data['jobs'] as $job) {
                    fputcsv($output, [
                        $job['id'],
                        $job['customer_name'],
                        $job['service_name'],
                        $job['status'],
                        $job['total_amount'],
                        $job['created_at']
                    ]);
                }
                break;
        }
        
        fclose($output);
    }
    
    /**
     * Generate Excel report
     */
    private function generateExcelReport(array $data, string $filePath): void
    {
        // Simple CSV for now (in production, use PhpSpreadsheet)
        $this->generateCsvReport($data, $filePath);
    }
    
    /**
     * Generate HTML report
     */
    private function generateHtmlReport(array $data): string
    {
        $html = "<!DOCTYPE html><html><head><title>{$this->reportType} Report</title></head><body>";
        $html .= "<h1>" . ucfirst($this->reportType) . " Report</h1>";
        $html .= "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
        
        switch ($this->reportType) {
            case 'financial':
                $html .= "<h2>Financial Summary</h2>";
                $html .= "<p>Revenue: $" . number_format($data['revenue'], 2) . "</p>";
                $html .= "<p>Expenses: $" . number_format($data['expenses'], 2) . "</p>";
                $html .= "<p>Profit: $" . number_format($data['revenue'] - $data['expenses'], 2) . "</p>";
                break;
            case 'customer':
                $html .= "<h2>Customer Report</h2>";
                $html .= "<p>Total Customers: " . $data['total_customers'] . "</p>";
                break;
            case 'job':
                $html .= "<h2>Job Report</h2>";
                $html .= "<p>Total Jobs: " . $data['total_jobs'] . "</p>";
                break;
        }
        
        $html .= "</body></html>";
        return $html;
    }
    
    /**
     * Store report metadata
     */
    private function storeReportMetadata(string $filePath): void
    {
        $db = Database::getInstance();
        
        $db->query(
            "INSERT INTO reports (user_id, report_type, file_path, format, parameters, created_at) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $this->userId,
                $this->reportType,
                $filePath,
                $this->format,
                json_encode($this->parameters),
                date('Y-m-d H:i:s')
            ]
        );
    }
    
    /**
     * Send notification
     */
    private function sendNotification(): void
    {
        if (!$this->userId) {
            return;
        }
        
        $user = $db->fetch("SELECT email FROM users WHERE id = ?", [$this->userId]);
        if (!$user) {
            return;
        }
        
        // Queue email notification
        $emailJob = new SendEmailJob([
            'to' => $user['email'],
            'subject' => 'Report Generated - ' . ucfirst($this->reportType),
            'body' => "Your {$this->reportType} report has been generated and is ready for download.",
            'from' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com'
        ]);
        
        $emailJob->dispatch();
    }
    
    /**
     * Get job timeout
     */
    public function timeout(): int
    {
        return 300; // 5 minutes for report generation
    }
    
    /**
     * Get job display name
     */
    public function getDisplayName(): string
    {
        return "Generate {$this->reportType} Report";
    }
    
    /**
     * Get job description
     */
    public function getDescription(): string
    {
        return "Generate {$this->reportType} report in {$this->format} format";
    }
    
    /**
     * Get job tags
     */
    public function getTags(): array
    {
        return ['report', 'generation', $this->reportType];
    }
    
    /**
     * Get job priority
     */
    public function getPriority(): int
    {
        return 0; // Normal priority for reports
    }
    
    /**
     * Check if job should be unique
     */
    public function isUnique(): bool
    {
        return false; // Allow multiple report generations
    }
    
    /**
     * Validate job payload
     */
    public function validate(): bool
    {
        if (empty($this->reportType)) {
            return false;
        }
        
        $validTypes = ['financial', 'customer', 'job', 'staff'];
        if (!in_array($this->reportType, $validTypes)) {
            return false;
        }
        
        $validFormats = ['pdf', 'csv', 'excel'];
        if (!in_array($this->format, $validFormats)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get job statistics
     */
    public function getStatistics(): array
    {
        $stats = parent::getStatistics();
        $stats['report_type'] = $this->reportType;
        $stats['format'] = $this->format;
        $stats['user_id'] = $this->userId;
        
        return $stats;
    }
}
