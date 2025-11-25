<?php
/**
 * Email Service
 * Handles email notifications
 */
class EmailService
{
    private static $config = null;
    
    /**
     * Get email configuration (public for queue processor)
     */
    public static function getConfig(): array
    {
        if (self::$config === null) {
            self::$config = [
                'enabled' => $_ENV['EMAIL_ENABLED'] ?? true,
                'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
                'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
                'smtp_user' => $_ENV['SMTP_USER'] ?? '',
                'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
                'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@example.com',
                'from_name' => $_ENV['FROM_NAME'] ?? 'Temizlik Takip Sistemi'
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Send email (with queue support)
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true, array $options = []): bool
    {
        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Logger::warning('Invalid email address', ['to' => $to]);
            return false;
        }
        
        $config = self::getConfig();
        
        if (!$config['enabled']) {
            Logger::info('Email sending disabled', ['to' => $to, 'subject' => $subject]);
            return false;
        }
        
        // Queue email if async option is set or queue table exists
        $queue = $options['queue'] ?? false;
        if ($queue || self::hasQueueTable()) {
            return self::queueEmail($to, $subject, $body, $isHtml, $options);
        }
        
        // Send immediately
        return self::sendImmediate($to, $subject, $body, $isHtml, $options);
    }
    
    /**
     * Send email immediately (no queue)
     */
    private static function sendImmediate(string $to, string $subject, string $body, bool $isHtml = true, array $options = []): bool
    {
        $config = self::getConfig();
        
        try {
            $headers = [];
            $headers[] = "From: {$config['from_name']} <{$config['from_email']}>";
            $headers[] = "Reply-To: {$config['from_email']}";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            $result = mail($to, $subject, $body, implode("\r\n", $headers));
            
            $status = $result ? 'sent' : 'failed';
            $errorMessage = $result ? null : 'PHP mail() function returned false';
            
            // Log email
            self::logEmail($options['job_id'] ?? null, $options['customer_id'] ?? null, $to, $subject, $options['type'] ?? 'general', $status, $errorMessage);
            
            if ($result) {
                Logger::info('Email sent successfully', ['to' => $to, 'subject' => $subject]);
            } else {
                Logger::warning('Email sending failed', ['to' => $to, 'subject' => $subject]);
            }
            
            return $result;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            self::logEmail($options['job_id'] ?? null, $options['customer_id'] ?? null, $to, $subject, $options['type'] ?? 'general', 'failed', $errorMessage);
            Logger::error('Email sending error', ['to' => $to, 'error' => $errorMessage]);
            return false;
        }
    }
    
    /**
     * Queue email for later sending
     */
    private static function queueEmail(string $to, string $subject, string $body, bool $isHtml = true, array $options = []): bool
    {
        try {
            $db = Database::getInstance();
            $db->insert('email_queue', [
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'type' => $options['type'] ?? 'general',
                'status' => 'pending',
                'max_retries' => $options['max_retries'] ?? 3,
                'created_at' => date('Y-m-d H:i:s'),
                'job_id' => $options['job_id'] ?? null,
                'customer_id' => $options['customer_id'] ?? null
            ]);
            
            // Also log to email_logs immediately as 'queued' status
            try {
                self::logEmail($options['job_id'] ?? null, $options['customer_id'] ?? null, $to, $subject, $options['type'] ?? 'general', 'pending', null);
            } catch (Exception $e) {
                // Silent fail
            }
            
            Logger::info('Email queued', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (Exception $e) {
            Logger::error('Failed to queue email', ['to' => $to, 'error' => $e->getMessage()]);
            // Fallback to immediate send
            return self::sendImmediate($to, $subject, $body, $isHtml, $options);
        }
    }
    
    /**
     * Check if email_queue table exists
     */
    private static function hasQueueTable(): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1 FROM email_queue LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log email to email_logs table
     */
    private static function logEmail(?int $jobId, ?int $customerId, string $to, string $subject, string $type, string $status, ?string $errorMessage = null): void
    {
        try {
            $db = Database::getInstance();
            
            // Check if table exists first
            try {
                $db->query("SELECT 1 FROM email_logs LIMIT 1");
            } catch (Exception $e) {
                // Table doesn't exist - log this
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Email log table does not exist: " . $e->getMessage());
                }
                return;
            }
            
            $db->insert('email_logs', [
                'job_id' => $jobId,
                'customer_id' => $customerId,
                'to_email' => $to,
                'subject' => $subject,
                'type' => $type,
                'status' => $status,
                'error_message' => $errorMessage,
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Email logged: job_id=$jobId, to=$to, status=$status");
            }
        } catch (Exception $e) {
            // Log error instead of silent fail
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Email logging failed: " . $e->getMessage());
            }
            Logger::error('Email logging failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId,
                'to' => $to
            ]);
        }
    }
    
    /**
     * Send job notification
     */
    public static function sendJobNotification(int $jobId, string $type, array $data = []): bool
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("EmailService::sendJobNotification called for job_id=$jobId, type=$type");
        }
        
        $db = Database::getInstance();
        $job = $db->fetch(
            "SELECT j.*, c.name as customer_name, c.email as customer_email 
             FROM jobs j 
             LEFT JOIN customers c ON j.customer_id = c.id 
             WHERE j.id = ?",
            [$jobId]
        );
        
        if (!$job) {
            Logger::warning('Job not found for email notification', ['job_id' => $jobId]);
            return false;
        }
        
        // Check if customer has email
        if (empty($job['customer_email'])) {
            // Log this attempt even if email is missing
            try {
                self::logEmail($jobId, $job['customer_id'], 'N/A', 'Yeni İş Oluşturuldu', 'job_created', 'failed', 'Müşteri email adresi bulunamadı');
            } catch (Exception $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Failed to log email (no email): " . $e->getMessage());
                }
            }
            Logger::info('Customer email not found, skipping notification', [
                'job_id' => $jobId,
                'customer_id' => $job['customer_id'],
                'customer_name' => $job['customer_name'] ?? 'Unknown'
            ]);
            return false;
        }
        
        // Validate email address
        if (!filter_var($job['customer_email'], FILTER_VALIDATE_EMAIL)) {
            // Log invalid email attempt
            try {
                self::logEmail($jobId, $job['customer_id'], $job['customer_email'], 'Yeni İş Oluşturuldu', 'job_created', 'failed', 'Geçersiz email adresi');
            } catch (Exception $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Failed to log email (invalid): " . $e->getMessage());
                }
            }
            Logger::warning('Invalid customer email address', [
                'job_id' => $jobId,
                'email' => $job['customer_email']
            ]);
            return false;
        }
        
        $subject = match($type) {
            'created' => 'Yeni İş Oluşturuldu',
            'updated' => 'İş Bilgisi Güncellendi',
            'reminder' => 'İş Hatırlatması',
            default => 'İş Bildirimi'
        };
        
        $body = self::generateJobEmailBody($job, $type);
        
        return self::send($job['customer_email'], $subject, $body, true, [
            'job_id' => $jobId,
            'customer_id' => $job['customer_id'],
            'type' => 'job_' . $type
        ]);
    }
    
    /**
     * Generate job email body
     */
    private static function generateJobEmailBody(array $job, string $type): string
    {
        $statusLabels = [
            'pending' => 'Beklemede',
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi'
        ];
        
        $status = $statusLabels[$job['status']] ?? $job['status'];
        
        $startDate = date('d.m.Y H:i', strtotime($job['start_at']));
        $endDate = isset($job['end_at']) ? date('d.m.Y H:i', strtotime($job['end_at'])) : '-';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff; }
                .header { background: #4F46E5; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #f9fafb; border-radius: 0 0 8px 8px; }
                .info-row { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
                .label { font-weight: bold; color: #4F46E5; display: inline-block; min-width: 120px; }
                .value { color: #333; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>İş {$status}</h1>
                </div>
                <div class='content'>
                    <div class='info-row'>
                        <span class='label'>Müşteri:</span>
                        <span class='value'>{$job['customer_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Başlangıç:</span>
                        <span class='value'>{$startDate}</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Bitiş:</span>
                        <span class='value'>{$endDate}</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Tutar:</span>
                        <span class='value'>" . number_format($job['total_amount'], 2) . " ₺</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Durum:</span>
                        <span class='value'>{$status}</span>
                    </div>
                </div>
                <div class='footer'>
                    <p>Temizlik İş Takip Sistemi</p>
                    <p>Bu otomatik bir bildirimdir.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send job reminder (24 hours before)
     */
    public static function sendJobReminder(int $jobId): bool
    {
        return self::sendJobNotification($jobId, 'reminder');
    }
    
    /**
     * Send payment reminder
     */
    public static function sendPaymentReminder(int $jobId, float $amount = null): bool
    {
        $db = Database::getInstance();
        $job = $db->fetch(
            "SELECT j.*, c.name as customer_name, c.email as customer_email 
             FROM jobs j 
             LEFT JOIN customers c ON j.customer_id = c.id 
             WHERE j.id = ?",
            [$jobId]
        );
        
        if (!$job || empty($job['customer_email'])) {
            return false;
        }
        
        $remaining = $amount ?? ($job['total_amount'] - ($job['amount_paid'] ?? 0));
        
        $subject = 'Ödeme Hatırlatıcısı - ' . number_format($remaining, 2) . ' ₺';
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff; }
                .header { background: #DC2626; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #fef2f2; border-radius: 0 0 8px 8px; }
                .amount { font-size: 32px; font-weight: bold; color: #DC2626; text-align: center; margin: 20px 0; }
                .info-row { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
                .label { font-weight: bold; color: #666; display: inline-block; min-width: 120px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>Ödeme Hatırlatıcısı</h1>
                </div>
                <div class='content'>
                    <p>Sayın <strong>{$job['customer_name']}</strong>,</p>
                    <p>Bekleyen ödemeniz bulunmaktadır:</p>
                    <div class='amount'>" . number_format($remaining, 2) . " ₺</div>
                    <div class='info-row'>
                        <span class='label'>İş Tarihi:</span>
                        " . date('d.m.Y H:i', strtotime($job['start_at'])) . "
                    </div>
                    <div class='info-row'>
                        <span class='label'>Toplam Tutar:</span>
                        " . number_format($job['total_amount'], 2) . " ₺
                    </div>
                    <div class='info-row'>
                        <span class='label'>Ödenen:</span>
                        " . number_format($job['amount_paid'] ?? 0, 2) . " ₺
                    </div>
                    <div class='info-row'>
                        <span class='label'>Kalan:</span>
                        <strong>" . number_format($remaining, 2) . " ₺</strong>
                    </div>
                    <p style='margin-top: 20px;'>Ödemenizi yapmak için lütfen bizimle iletişime geçin.</p>
                </div>
                <div class='footer'>
                    <p>Temizlik İş Takip Sistemi</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($job['customer_email'], $subject, $body);
    }
    
    /**
     * Send job status change notification
     */
    public static function sendJobStatusChange(int $jobId, string $oldStatus, string $newStatus): bool
    {
        $db = Database::getInstance();
        $job = $db->fetch(
            "SELECT j.*, c.name as customer_name, c.email as customer_email 
             FROM jobs j 
             LEFT JOIN customers c ON j.customer_id = c.id 
             WHERE j.id = ?",
            [$jobId]
        );
        
        if (!$job || empty($job['customer_email'])) {
            return false;
        }
        
        $statusLabels = [
            'SCHEDULED' => 'Planlandı',
            'DONE' => 'Tamamlandı',
            'CANCELLED' => 'İptal Edildi'
        ];
        
        $subject = 'İş Durumu Değişti: ' . ($statusLabels[$newStatus] ?? $newStatus);
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff; }
                .header { background: #059669; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #f0fdf4; border-radius: 0 0 8px 8px; }
                .status-change { text-align: center; margin: 20px 0; padding: 20px; background: white; border-radius: 5px; }
                .old-status { color: #666; text-decoration: line-through; }
                .new-status { color: #059669; font-size: 24px; font-weight: bold; }
                .info-row { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
                .label { font-weight: bold; color: #666; display: inline-block; min-width: 120px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>İş Durumu Güncellendi</h1>
                </div>
                <div class='content'>
                    <p>Sayın <strong>{$job['customer_name']}</strong>,</p>
                    <div class='status-change'>
                        <div class='old-status'>" . ($statusLabels[$oldStatus] ?? $oldStatus) . "</div>
                        <div style='margin: 10px 0;'>→</div>
                        <div class='new-status'>" . ($statusLabels[$newStatus] ?? $newStatus) . "</div>
                    </div>
                    <div class='info-row'>
                        <span class='label'>İş Tarihi:</span>
                        " . date('d.m.Y H:i', strtotime($job['start_at'])) . "
                    </div>
                    <div class='info-row'>
                        <span class='label'>Tutar:</span>
                        " . number_format($job['total_amount'], 2) . " ₺
                    </div>
                </div>
                <div class='footer'>
                    <p>Temizlik İş Takip Sistemi</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($job['customer_email'], $subject, $body, true, [
            'job_id' => $jobId,
            'customer_id' => $job['customer_id'],
            'type' => 'status_change'
        ]);
    }
    
    /**
     * Send monthly report
     */
    public static function sendMonthlyReport(string $email, array $data): bool
    {
        $subject = 'Aylık Rapor - ' . date('F Y');
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #f9fafb; border-radius: 0 0 8px 8px; }
                .stat-box { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; text-align: center; }
                .stat-value { font-size: 32px; font-weight: bold; color: #4F46E5; }
                .stat-label { color: #666; margin-top: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>Aylık Rapor</h1>
                    <p style='margin: 10px 0 0 0;'>" . date('F Y') . "</p>
                </div>
                <div class='content'>
                    <div class='stat-box'>
                        <div class='stat-value'>{$data['total_jobs']}</div>
                        <div class='stat-label'>Toplam İş</div>
                    </div>
                    <div class='stat-box'>
                        <div class='stat-value'>" . number_format($data['total_income'], 2) . " ₺</div>
                        <div class='stat-label'>Toplam Gelir</div>
                    </div>
                    <div class='stat-box'>
                        <div class=                'stat-value'>{$data['completed_jobs']}</div>
                        <div class='stat-label'>Tamamlanan İş</div>
                    </div>
                </div>
                <div class='footer'>
                    <p>Temizlik İş Takip Sistemi</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($email, $subject, $body, true, [
            'type' => 'monthly_report'
        ]);
    }
}

