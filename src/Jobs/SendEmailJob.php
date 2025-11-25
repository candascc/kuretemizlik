<?php
/**
 * Send Email Job
 * Handles asynchronous email sending
 */

class SendEmailJob extends Job
{
    private $to;
    private $subject;
    private $body;
    private $from;
    private $attachments;
    
    public function __construct(array $payload = [])
    {
        parent::__construct($payload);
        
        $this->to = $payload['to'] ?? '';
        $this->subject = $payload['subject'] ?? '';
        $this->body = $payload['body'] ?? '';
        $this->from = $payload['from'] ?? $_ENV['MAIL_FROM'] ?? 'noreply@example.com';
        $this->attachments = $payload['attachments'] ?? [];
    }
    
    /**
     * Execute the job
     */
    public function handle(): void
    {
        if (empty($this->to) || empty($this->subject) || empty($this->body)) {
            throw new Exception('Email parameters are required');
        }
        
        $this->sendEmail();
    }
    
    /**
     * Send email
     */
    private function sendEmail(): void
    {
        $headers = [
            'From: ' . $this->from,
            'Reply-To: ' . $this->from,
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Add attachments if any
        if (!empty($this->attachments)) {
            $boundary = md5(uniqid(time()));
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            
            $body = $this->buildMultipartBody($boundary);
        } else {
            $body = $this->body;
        }
        
        $success = mail($this->to, $this->subject, $body, implode("\r\n", $headers));
        
        if (!$success) {
            throw new Exception('Failed to send email');
        }
        
        Logger::info('Email sent successfully', [
            'to' => $this->to,
            'subject' => $this->subject,
            'from' => $this->from
        ]);
    }
    
    /**
     * Build multipart email body with attachments
     */
    private function buildMultipartBody(string $boundary): string
    {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $this->body . "\r\n";
        
        foreach ($this->attachments as $attachment) {
            // ===== ERR-005 FIX: Validate file path before reading =====
            if (!is_string($attachment) || empty($attachment)) {
                continue; // Skip invalid attachment paths
            }
            
            // Normalize and validate path
            $normalizedPath = InputSanitizer::filePath($attachment);
            if ($normalizedPath === null) {
                continue; // Path traversal detected, skip
            }
            
            // Ensure file exists and is readable
            if (!file_exists($attachment) || !is_readable($attachment)) {
                continue; // Cannot read, skip
            }
            
            // Ensure file is within allowed directories (storage/uploads)
            $allowedDirs = [
                realpath(__DIR__ . '/../../storage'),
                realpath(__DIR__ . '/../../uploads'),
                sys_get_temp_dir()
            ];
            $realFilePath = realpath($attachment);
            $isAllowed = false;
            foreach ($allowedDirs as $allowedDir) {
                if ($allowedDir !== false && $realFilePath !== false && strpos($realFilePath, $allowedDir) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                continue; // File outside allowed directories, skip
            }
            // ===== ERR-005 FIX: End =====
            
            if (file_exists($attachment)) {
                $content = file_get_contents($attachment);
                $filename = basename($attachment);
                $mimeType = mime_content_type($attachment);
                
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
                $body .= chunk_split(base64_encode($content)) . "\r\n";
            }
        }
        
        $body .= "--{$boundary}--\r\n";
        
        return $body;
    }
    
    /**
     * Get job timeout
     */
    public function timeout(): int
    {
        return 30; // 30 seconds for email sending
    }
    
    /**
     * Get job display name
     */
    public function getDisplayName(): string
    {
        return "Send Email to {$this->to}";
    }
    
    /**
     * Get job description
     */
    public function getDescription(): string
    {
        return "Send email '{$this->subject}' to {$this->to}";
    }
    
    /**
     * Get job tags
     */
    public function getTags(): array
    {
        return ['email', 'notification'];
    }
    
    /**
     * Get job priority
     */
    public function getPriority(): int
    {
        return 1; // High priority for emails
    }
    
    /**
     * Check if job should be unique
     */
    public function isUnique(): bool
    {
        return true; // Prevent duplicate emails
    }
    
    /**
     * Get unique key for job
     */
    public function getUniqueKey(): string
    {
        return 'email:' . md5($this->to . $this->subject . $this->body);
    }
    
    /**
     * Validate job payload
     */
    public function validate(): bool
    {
        if (empty($this->to) || !filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        if (empty($this->subject)) {
            return false;
        }
        
        if (empty($this->body)) {
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
        $stats['email_to'] = $this->to;
        $stats['email_subject'] = $this->subject;
        $stats['email_from'] = $this->from;
        $stats['attachments_count'] = count($this->attachments);
        
        return $stats;
    }
}
