<?php
/**
 * Command: Send Job Reminders
 * Sends email reminders for upcoming jobs (24 hours before)
 */

class SendJobReminders
{
    public static function execute(): void
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $next24h = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Get jobs scheduled for next 24 hours
        $jobs = $db->fetchAll("
            SELECT 
                j.id,
                j.start_at,
                j.reminder_sent
            FROM jobs j
            WHERE j.start_at BETWEEN ? AND ?
            AND j.status = 'SCHEDULED'
            AND (j.reminder_sent IS NULL OR j.reminder_sent = 0)
            ORDER BY j.start_at ASC
        ", [$now, $next24h]);
        
        $sent = 0;
        foreach ($jobs as $job) {
            try {
                if (class_exists('EmailService')) {
                    EmailService::sendJobReminder($job['id']);
                    $db->update('jobs', ['reminder_sent' => 1], 'id = ?', [$job['id']]);
                    $sent++;
                }
            } catch (Exception $e) {
                error_log("Failed to send reminder for job {$job['id']}: " . $e->getMessage());
            }
        }
        
        Logger::info("Job reminders sent", ['count' => $sent]);
        echo "Sent {$sent} job reminder(s)\n";
    }
}

