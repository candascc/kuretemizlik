<?php

/**
 * Email Queue Service
 * E-posta kuyruğu yönetimi
 */
class EmailQueue
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add email to queue
     */
    public function add($emailData)
    {
        $data = [
            'to_email' => $emailData['to'],
            'subject' => $emailData['subject'],
            'message' => $emailData['message'],
            'template' => $emailData['template'] ?? 'default',
            'data' => json_encode($emailData['data'] ?? []),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('email_queue', $data);
    }

    /**
     * Process email queue
     */
    public function process($limit = 10)
    {
        $emails = $this->db->fetchAll(
            "SELECT * FROM email_queue 
             WHERE status = 'pending' 
             AND scheduled_at <= datetime('now') 
             AND attempts < max_attempts 
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );

        $processed = 0;
        foreach ($emails as $email) {
            if ($this->sendEmail($email)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Send individual email
     */
    private function sendEmail($email)
    {
        try {
            // Update attempt count
            $this->db->update('email_queue', [
                'attempts' => $email['attempts'] + 1,
                'last_attempt_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$email['id']]);

            // Send email using PHP mail function (can be replaced with SMTP)
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->getFromEmail(),
                'Reply-To: ' . $this->getReplyToEmail(),
                'X-Mailer: Apartman Yönetim Sistemi'
            ];

            $success = mail(
                $email['to_email'],
                $email['subject'],
                $email['message'],
                implode("\r\n", $headers)
            );

            if ($success) {
                $this->db->update('email_queue', [
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$email['id']]);
            } else {
                $this->db->update('email_queue', [
                    'status' => 'failed',
                    'error_message' => 'Mail function failed'
                ], 'id = ?', [$email['id']]);
            }

            return $success;

        } catch (Exception $e) {
            $this->db->update('email_queue', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = ?', [$email['id']]);

            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function getStats()
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
             FROM email_queue"
        );

        return $stats ?: [
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }

    /**
     * Clean old emails
     */
    public function clean($days = 30)
    {
        return $this->db->delete(
            'email_queue',
            "status IN ('sent', 'failed') AND created_at < datetime('now', '-{$days} days')"
        );
    }

    /**
     * Retry failed emails
     */
    public function retryFailed()
    {
        return $this->db->update(
            'email_queue',
            [
                'status' => 'pending',
                'attempts' => 0,
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ],
            "status = 'failed' AND attempts < max_attempts"
        );
    }

    private function getFromEmail()
    {
        return $_ENV['MAIL_FROM'] ?? 'noreply@apartman.com';
    }

    private function getReplyToEmail()
    {
        return $_ENV['MAIL_REPLY_TO'] ?? 'info@apartman.com';
    }
}
