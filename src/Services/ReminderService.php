<?php

class ReminderService
{
    /**
     * Dispatch upcoming reminders within next N minutes.
     */
    public static function dispatch(int $windowMinutes = 5): array
    {
        $db = Database::getInstance();
        // Fetch jobs starting within window and not started yet
        $rows = $db->fetchAll(
            "SELECT j.*, c.name AS customer_name, u.username AS owner_name
             FROM jobs j
             LEFT JOIN customers c ON c.id=j.customer_id
             LEFT JOIN users u ON u.id=j.assigned_user_id
             WHERE datetime(j.start_at) BETWEEN datetime('now') AND datetime('now', :win)",
            [':win' => "+{$windowMinutes} minutes"]
        );
        $sent = 0; $errors = 0;
        foreach ($rows as $job) {
            $prefs = $db->fetch("SELECT * FROM notification_prefs WHERE user_id = ?", [(int)($job['assigned_user_id'] ?? 0)] ) ?: [];
            $emailOn = (int)($prefs['calendar_reminders_email'] ?? 1) === 1;
            $smsOn = (int)($prefs['calendar_reminders_sms'] ?? 0) === 1;
            $subject = '[Hatırlatma] ' . ($job['service_name'] ?? 'İş') . ' - ' . ($job['customer_name'] ?? '');
            $body = sprintf("%s\nBaşlangıç: %s\nBitiş: %s\nNot: %s",
                $subject,
                $job['start_at'] ?? '-',
                $job['end_at'] ?? '-',
                $job['notes'] ?? '-'
            );
            try {
                if ($emailOn && class_exists('EmailService')) {
                    EmailService::queue($job['owner_email'] ?? ($_ENV['DEFAULT_NOTIFY_EMAIL'] ?? null), $subject, nl2br(htmlentities($body)), []);
                    $sent++;
                }
                if ($smsOn && class_exists('SMSQueue')) {
                    $phone = $job['owner_phone'] ?? null;
                    if ($phone) { SMSQueue::push($phone, $subject); $sent++; }
                }
            } catch (Exception $e) { $errors++; }
        }
        return ['found' => count($rows), 'sent' => $sent, 'errors' => $errors];
    }
}


