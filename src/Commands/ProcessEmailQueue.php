<?php
/**
 * Command: Process Email Queue
 * Processes pending emails from queue with retry mechanism
 */

class ProcessEmailQueue
{
    public static function execute(int $limit = 50): void
    {
        $db = Database::getInstance();
        
        // Get pending emails
        $emails = $db->fetchAll("
            SELECT * FROM email_queue 
            WHERE status IN ('pending', 'failed')
            AND retry_count < max_retries
            ORDER BY created_at ASC
            LIMIT ?
        ", [$limit]);
        
        $processed = 0;
        $sent = 0;
        $failed = 0;
        
        foreach ($emails as $email) {
            $processed++;
            
            // Update status to 'sending'
            $db->update('email_queue', 
                ['status' => 'sending'],
                'id = ?',
                [$email['id']]
            );
            
            try {
                // Send email
                $config = EmailService::getConfig();
                $headers = [];
                $headers[] = "From: {$config['from_name']} <{$config['from_email']}>";
                $headers[] = "Reply-To: {$config['from_email']}";
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: text/html; charset=UTF-8";
                $headers[] = "X-Mailer: PHP/" . phpversion();
                
                $result = mail($email['to_email'], $email['subject'], $email['body'], implode("\r\n", $headers));
                
                if ($result) {
                    // Mark as sent
                    $db->update('email_queue', 
                        [
                            'status' => 'sent',
                            'sent_at' => date('Y-m-d H:i:s')
                        ],
                        'id = ?',
                        [$email['id']]
                    );
                    
                    // Log to email_logs (update pending log or create new)
                    try {
                        // Try to update existing pending log
                        $existing = $db->fetch(
                            "SELECT id FROM email_logs WHERE to_email = ? AND subject = ? AND status = 'pending' LIMIT 1",
                            [$email['to_email'], $email['subject']]
                        );
                        
                        if ($existing) {
                            $db->update('email_logs', 
                                [
                                    'status' => 'sent',
                                    'sent_at' => date('Y-m-d H:i:s')
                                ],
                                'id = ?',
                                [$existing['id']]
                            );
                        } else {
                            // Create new log entry
                            $db->insert('email_logs', [
                                'job_id' => $email['job_id'] ?? null,
                                'customer_id' => $email['customer_id'] ?? null,
                                'to_email' => $email['to_email'],
                                'subject' => $email['subject'],
                                'type' => $email['type'],
                                'status' => 'sent',
                                'sent_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    } catch (Exception $logError) {
                        // Silent fail - logging is not critical
                    }
                    
                    $sent++;
                    Logger::info('Email sent from queue', ['id' => $email['id'], 'to' => $email['to_email']]);
                } else {
                    throw new Exception('PHP mail() returned false');
                }
            } catch (Exception $e) {
                $retryCount = $email['retry_count'] + 1;
                $status = ($retryCount >= $email['max_retries']) ? 'failed' : 'failed';
                
                $db->update('email_queue', 
                    [
                        'status' => $status,
                        'retry_count' => $retryCount,
                        'error_message' => $e->getMessage()
                    ],
                    'id = ?',
                    [$email['id']]
                );
                
                $failed++;
                Logger::warning('Email failed from queue', [
                    'id' => $email['id'], 
                    'to' => $email['to_email'],
                    'retry' => $retryCount,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        echo "Processed: {$processed}, Sent: {$sent}, Failed: {$failed}\n";
    }
}

