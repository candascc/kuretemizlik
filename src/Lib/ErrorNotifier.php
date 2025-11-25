<?php
/**
 * Error Notification System
 * Sends notifications when critical errors occur
 */

class ErrorNotifier
{
    /**
     * Send error notification
     */
    public static function notify(array $errorData): void
    {
        // Log notification attempt
        if (class_exists('Logger')) {
            Logger::critical('Error notification triggered', [
                'error_id' => $errorData['id'],
                'error_type' => $errorData['type'],
                'error_message' => $errorData['message']
            ]);
        }

        // Email notification
        if (isset($_ENV['ERROR_EMAIL']) && !empty($_ENV['ERROR_EMAIL'])) {
            self::sendEmail($errorData);
        }

        // Slack notification
        if (isset($_ENV['SLACK_WEBHOOK']) && !empty($_ENV['SLACK_WEBHOOK'])) {
            self::sendSlack($errorData);
        }

        // Database notification (for admin dashboard)
        self::saveNotification($errorData);
    }

    /**
     * Send email notification
     */
    private static function sendEmail(array $errorData): void
    {
        $to = $_ENV['ERROR_EMAIL'];
        $subject = "[CRITICAL] Application Error: {$errorData['type']}";
        
        $message = "An error has been detected in the application:\n\n";
        $message .= "Error ID: {$errorData['id']}\n";
        $message .= "Type: {$errorData['type']}\n";
        $message .= "Message: {$errorData['message']}\n";
        $message .= "File: {$errorData['file']}\n";
        $message .= "Line: {$errorData['line']}\n";
        $timestamp = is_numeric($errorData['timestamp']) ? (int)$errorData['timestamp'] : time();
        $message .= "Time: " . date('Y-m-d H:i:s', $timestamp) . "\n";
        $message .= "User: " . ($errorData['user_id'] ?? 'Guest') . "\n";
        $message .= "IP: {$errorData['ip']}\n";
        $message .= "URL: {$errorData['url']}\n\n";
        $message .= "Stack Trace:\n{$errorData['trace']}\n";

        $headers = [
            'From: noreply@kuretemizlik.com',
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1 (Highest)',
            'X-MSMail-Priority: High',
            'Importance: High'
        ];

        @mail($to, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Send Slack notification
     */
    private static function sendSlack(array $errorData): void
    {
        $webhook = $_ENV['SLACK_WEBHOOK'];
        
        $payload = [
            'text' => ':rotating_light: *Application Error Detected*',
            'attachments' => [[
                'color' => 'danger',
                'fields' => [
                    ['title' => 'Error Type', 'value' => $errorData['type'], 'short' => true],
                    ['title' => 'Error ID', 'value' => $errorData['id'], 'short' => true],
                    ['title' => 'Message', 'value' => $errorData['message'], 'short' => false],
                    ['title' => 'Location', 'value' => "{$errorData['file']}:{$errorData['line']}", 'short' => false],
                    ['title' => 'URL', 'value' => $errorData['url'], 'short' => true],
                    ['title' => 'User', 'value' => $errorData['user_id'] ?? 'Guest', 'short' => true]
                ],
                'ts' => $errorData['timestamp']
            ]]
        ];

        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Save notification to database
     */
    private static function saveNotification(array $errorData): void
    {
        try {
            $db = Database::getInstance();
            
            // Check if notifications table exists
            $tableExists = $db->fetch(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='error_notifications'"
            );

            if (!$tableExists) {
                // Create table
                $db->query("
                    CREATE TABLE IF NOT EXISTS error_notifications (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        error_id TEXT NOT NULL,
                        error_type TEXT NOT NULL,
                        error_message TEXT NOT NULL,
                        error_hash TEXT NOT NULL,
                        occurred_at TEXT NOT NULL,
                        acknowledged BOOLEAN DEFAULT 0,
                        acknowledged_by INTEGER,
                        acknowledged_at TEXT,
                        created_at TEXT DEFAULT (datetime('now'))
                    )
                ");
            }

            // Ensure timestamp is integer for date() function
            $timestamp = is_numeric($errorData['timestamp']) ? (int)$errorData['timestamp'] : time();
            
            $db->insert('error_notifications', [
                'error_id' => $errorData['id'],
                'error_type' => $errorData['type'],
                'error_message' => substr($errorData['message'], 0, 500),
                'error_hash' => $errorData['hash'],
                'occurred_at' => date('Y-m-d H:i:s', $timestamp)
            ]);
        } catch (Exception $e) {
            // Silent failure - don't break application
            error_log("Failed to save error notification: " . $e->getMessage());
        }
    }

    /**
     * Get unacknowledged notifications
     */
    public static function getUnacknowledged(): array
    {
        try {
            $db = Database::getInstance();
            
            return $db->fetchAll(
                "SELECT * FROM error_notifications WHERE acknowledged = 0 ORDER BY created_at DESC LIMIT 50"
            );
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Acknowledge notification
     */
    public static function acknowledge(int $notificationId, int $userId): bool
    {
        try {
            $db = Database::getInstance();
            
            return $db->update('error_notifications', [
                'acknowledged' => 1,
                'acknowledged_by' => $userId,
                'acknowledged_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$notificationId]);
        } catch (Exception $e) {
            return false;
        }
    }
}

