<?php

/**
 * NotificationService
 * Provides lightweight header notifications from business data
 */
class NotificationServiceHeaderShim
{
    /**
     * Count header notifications quickly
     */
    public static function getNotificationCount(): int
    {
        $list = self::getHeaderNotifications(6);
        if (empty($list)) { return 0; }
        $unread = 0;
        foreach ($list as $it) {
            if (empty($it['read'])) { $unread++; }
        }
        return $unread;
    }

    /**
     * Build a compact list of notifications for the header dropdown
     * @return array<int,array<string,mixed>>
     */
    public static function getHeaderNotifications(int $limit = 6): array
    {
        if (APP_DEBUG) {
            error_log("[NOTIF DEBUG] NotificationService::getHeaderNotifications called with limit=$limit");
        }
        $items = [];
        try {
            $db = Database::getInstance();
            $userId = class_exists('Auth') ? (Auth::id() ?? 0) : 0;
            if (APP_DEBUG) {
                error_log("[NOTIF DEBUG] userId=$userId");
            }
            $prefs = ['critical'=>0,'ops'=>0,'system'=>0];
            if ($userId) {
                try {
                    $row = $db->fetch("SELECT * FROM notification_prefs WHERE user_id = ?", [$userId]);
                    if ($row) { $prefs = ['critical'=>(int)$row['mute_critical'],'ops'=>(int)$row['mute_ops'],'system'=>(int)$row['mute_system']]; }
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] Prefs: " . json_encode($prefs));
                    }
                } catch (Exception $e) {
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] Prefs fetch error: " . $e->getMessage());
                    }
                }
            }

            // 1) Expiring contracts (within 14 days)
            try {
                $expiring = $db->fetchAll(
                    "SELECT id, title, end_date FROM contracts 
                     WHERE end_date IS NOT NULL 
                       AND DATE(end_date) <= DATE('now', '+14 days') 
                       AND (status IS NULL OR status != 'TERMINATED')
                     ORDER BY DATE(end_date) ASC
                     LIMIT 5"
                );
                foreach ($expiring as $c) {
                    $items[] = [
                        'type' => 'critical',
                        'icon' => 'fa-exclamation-triangle',
                        'text' => 'Sözleşme süresi yaklaşıyor: ' . ($c['title'] ?? ('#' . $c['id'])),
                        'meta' => Utils::formatDate($c['end_date'] ?? ''),
                        'href' => base_url('/contracts/' . $c['id']),
                        'key' => 'contract:' . $c['id'],
                    ];
                }
            } catch (Exception $e) {}

            // 2) Today jobs/appointments
            try {
                $jobCountRow = $db->fetch(
                    "SELECT COUNT(*) as c FROM jobs WHERE DATE(start_at) = DATE('now')"
                );
                $jobCount = (int)($jobCountRow['c'] ?? 0);
                if ($jobCount > 0) {
                    $items[] = [
                        'type' => 'ops',
                        'icon' => 'fa-calendar-check',
                        'text' => 'Bugün ' . $jobCount . ' randevu var',
                        'href' => base_url('/calendar'),
                        'key' => 'jobs:' . date('Y-m-d'),
                    ];
                }
            } catch (Exception $e) {}

            // 3) System: disk usage alert
            try {
                $total = @disk_total_space('.') ?: 0; $free = @disk_free_space('.') ?: 0; $used = max(0, $total - $free);
                $pct = ($total > 0) ? round(($used / $total) * 100, 0) : 0;
                if ($pct >= 85) {
                    $items[] = [
                        'type' => 'system',
                        'icon' => 'fa-hdd',
                        'text' => 'Disk kullanım uyarısı: %' . $pct,
                        'href' => base_url('/performance'),
                        'key' => 'disk:' . date('Y-m-d'),
                    ];
                }
            } catch (Exception $e) {}

        } catch (Exception $e) {
            if (APP_DEBUG) {
                error_log("[NOTIF DEBUG] Main exception: " . $e->getMessage());
            }
        }

        if (APP_DEBUG) {
            error_log("[NOTIF DEBUG] Items before filter: " . count($items));
        }
        // Trim list to limit
        // Apply mute prefs
        if (!empty($items)) {
            $items = array_values(array_filter($items, function($i) use ($prefs){ return !$prefs[$i['type']] ?? true; }));
        }
        if (APP_DEBUG) {
            error_log("[NOTIF DEBUG] Items after filter: " . count($items));
        }

        // Mark read flags for current user
        if ($userId && !empty($items)) {
            try {
                $keys = array_map(function($i){ return $i['key'] ?? null; }, $items);
                $keys = array_values(array_filter($keys));
                if (!empty($keys)) {
                    $placeholders = implode(',', array_fill(0, count($keys), '?'));
                    $rows = $db->fetchAll("SELECT notif_key FROM notifications_read WHERE user_id = ? AND notif_key IN ($placeholders)", array_merge([$userId], $keys));
                    $readKeys = array_map(function($r){ return $r['notif_key']; }, $rows ?: []);
                    foreach ($items as &$it) { $it['read'] = isset($it['key']) && in_array($it['key'], $readKeys, true); }
                }
            } catch (Exception $e) {}
        }

        return array_slice($items, 0, max(1, $limit));
    }

    /**
     * Clear any in-memory/ephemeral caches used by header notifications (noop-safe)
     */
    public static function clearCache(): void
    {
        // Currently no persistent cache layer; method provided to satisfy callers
        // Could be extended to clear APCu/Redis keys if integrated
        if (APP_DEBUG) {
            error_log('[NOTIF DEBUG] NotificationServiceHeaderShim::clearCache() called');
        }
    }

    public static function markAllRead(): int
    {
        $userId = class_exists('Auth') ? (Auth::id() ?? 0) : 0;
        if (!$userId) return 0;
        $db = Database::getInstance();
        $items = self::getHeaderNotifications(20);
        $count = 0;
        foreach ($items as $it) {
            if (empty($it['key'])) continue;
            try {
                $db->insert('notifications_read', [
                    'user_id' => $userId,
                    'notif_key' => $it['key'],
                    'read_at' => date('Y-m-d H:i:s')
                ]);
                $count++;
            } catch (Exception $e) { /* duplicate ok */ }
        }
        return $count;
    }

    public static function getPrefs(): array
    {
        $userId = class_exists('Auth') ? (Auth::id() ?? 0) : 0;
        if (!$userId) { return ['critical'=>0,'ops'=>0,'system'=>0]; }
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM notification_prefs WHERE user_id = ?", [$userId]);
        if (!$row) { return ['critical'=>0,'ops'=>0,'system'=>0]; }
        return ['critical'=>(int)$row['mute_critical'],'ops'=>(int)$row['mute_ops'],'system'=>(int)$row['mute_system']];
    }

    public static function setMuted(string $type, bool $muted): bool
    {
        $userId = class_exists('Auth') ? (Auth::id() ?? 0) : 0;
        if (!$userId) return false;
        $db = Database::getInstance();
        $col = $type === 'critical' ? 'mute_critical' : ($type === 'system' ? 'mute_system' : 'mute_ops');
        try {
            // upsert
            $exists = $db->fetch("SELECT user_id FROM notification_prefs WHERE user_id = ?", [$userId]);
            if ($exists) {
                $db->update('notification_prefs', [$col => $muted ? 1 : 0], 'user_id = :uid', ['uid'=>$userId]);
            } else {
                $db->insert('notification_prefs', ['user_id'=>$userId, $col => $muted ? 1 : 0]);
            }
            return true;
        } catch (Exception $e) { return false; }
    }
}

/**
 * Notification Service
 * E-posta ve SMS bildirimleri için servis
 */
class NotificationService
{
    /**
     * Count header notifications quickly (static)
     * Delegates to NotificationServiceHeaderShim
     */
    public static function getNotificationCount(): int
    {
        return NotificationServiceHeaderShim::getNotificationCount();
    }

    /**
     * Build compact header notifications (static)
     * Delegates to NotificationServiceHeaderShim
     */
    public static function getHeaderNotifications(int $limit = 6): array
    {
        return NotificationServiceHeaderShim::getHeaderNotifications($limit);
    }

    public static function markAllRead(): int
    {
        return NotificationServiceHeaderShim::markAllRead();
    }

    public static function getPrefs(): array
    {
        return NotificationServiceHeaderShim::getPrefs();
    }

    public static function setMuted(string $type, bool $muted): bool
    {
        return NotificationServiceHeaderShim::setMuted($type, $muted);
    }

    /**
     * Clear cached header notifications/pre-computed counts (noop-safe)
     */
    public static function clearCache(): void
    {
        NotificationServiceHeaderShim::clearCache();
    }

    public static function markRead(string $key): bool
    {
        $userId = class_exists('Auth') ? (Auth::id() ?? 0) : 0;
        if (!$userId) return false;
        $db = Database::getInstance();
        try {
            $db->insert('notifications_read', [
                'user_id' => $userId,
                'notif_key' => $key,
                'read_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private $db;
    private $emailQueue;
    private $smsQueue;
    private ResidentNotificationPreferenceService $preferenceService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailQueue = new EmailQueue();
        $this->smsQueue = new SMSQueue();
        $this->preferenceService = new ResidentNotificationPreferenceService();
    }

    /**
     * Send notification to residents
     */
    public function sendToResidents($buildingId, $type, $subject, $message, $options = [])
    {
        $residents = $this->getBuildingResidents($buildingId, $options);

        foreach ($residents as $resident) {
            $this->sendNotification($resident, $type, $subject, $message, $options);
        }
    }

    /**
     * Send notification to specific resident
     */
    public function sendToResident($residentId, $type, $subject, $message, $options = [])
    {
        $resident = $this->getResident($residentId);
        if ($resident) {
            $this->sendNotification($resident, $type, $subject, $message, $options);
        }
    }

    /**
     * Send fee reminder
     */
    public function sendFeeReminder($feeId)
    {
        $fee = $this->getManagementFee($feeId);
        if (!$fee) return false;

        $resident = $this->getResidentByUnit($fee['unit_id']);
        if (!$resident) return false;

        $subject = "Aidat Hatırlatması - {$fee['fee_name']}";
        $message = $this->buildFeeReminderMessage($fee, $resident);

        return $this->sendNotification($resident, 'email', $subject, $message, [
            'template' => 'fee_reminder',
            'fee_id' => $feeId,
            'channels' => ['email', 'sms'],
            'category' => 'fees',
        ]);
    }

    /**
     * Send meeting notification
     */
    public function sendMeetingNotification($meetingId)
    {
        $meeting = $this->getMeeting($meetingId);
        if (!$meeting) return false;

        $residents = $this->getBuildingResidents($meeting['building_id']);

        $subject = "Toplantı Duyurusu - {$meeting['title']}";
        $message = $this->buildMeetingMessage($meeting);

        foreach ($residents as $resident) {
            $this->sendNotification($resident, 'email', $subject, $message, [
                'template' => 'meeting_notification',
                'meeting_id' => $meetingId,
                'channels' => ['email', 'sms'],
                'category' => 'meetings',
            ]);
        }

        return true;
    }

    /**
     * Send announcement notification
     */
    public function sendAnnouncementNotification($announcementId)
    {
        $announcement = $this->getAnnouncement($announcementId);
        if (!$announcement) return false;

        $residents = $this->getBuildingResidents($announcement['building_id']);

        $subject = $announcement['title'];
        $message = $announcement['content'];

        foreach ($residents as $resident) {
            $this->sendNotification($resident, 'email', $subject, $message, [
                'template' => 'announcement',
                'announcement_id' => $announcementId,
                'channels' => ['email', 'sms'],
                'category' => 'announcements',
            ]);
        }

        return true;
    }

    /**
     * Send request response notification
     */
    public function sendRequestResponse($requestId)
    {
        $request = $this->getResidentRequest($requestId);
        if (!$request || !$request['resident_user_id']) return false;

        $resident = $this->getResident($request['resident_user_id']);
        if (!$resident) return false;

        $subject = "Talep Yanıtı - {$request['subject']}";
        $message = $this->buildRequestResponseMessage($request);

        return $this->sendNotification($resident, 'email', $subject, $message, [
            'template' => 'request_response',
            'request_id' => $requestId,
            'channels' => ['email'],
            'category' => 'requests',
        ]);
    }

    /**
     * Send payment confirmation
     */
    public function sendPaymentConfirmation($feeId, $amount)
    {
        $fee = $this->getManagementFee($feeId);
        if (!$fee) return false;

        $resident = $this->getResidentByUnit($fee['unit_id']);
        if (!$resident) return false;

        $subject = "Ödeme Onayı - {$fee['fee_name']}";
        $message = $this->buildPaymentConfirmationMessage($fee, $amount);

        return $this->sendNotification($resident, 'email', $subject, $message, [
            'template' => 'payment_confirmation',
            'fee_id' => $feeId,
            'channels' => ['email'],
            'category' => 'payments',
        ]);
    }

    /**
     * Core notification sending method
     */
    private function sendNotification($resident, $type, $subject, $message, $options = [])
    {
        $requestedChannels = $options['channels'] ?? ['email'];
        $category = $options['category'] ?? 'announcements';
        $channels = $this->preferenceService->resolveChannels($resident, $category, $requestedChannels);

        if (empty($channels)) {
            return false;
        }

        $options['resolved_channels'] = $channels;
        $options['category'] = $category;

        $sent = false;

        if (in_array('email', $channels, true) && !empty($resident['email'])) {
            $sent = $this->sendEmail($resident['email'], $subject, $message, $options) || $sent;
        }

        if (in_array('sms', $channels, true) && !empty($resident['phone'])) {
            $sent = $this->sendSMS($resident['phone'], $message, $options) || $sent;
        }

        // Log notification
        if ($sent) {
            $this->logNotification($resident['id'], $type, $subject, $options);
        }

        return $sent;
    }

    /**
     * Send email notification
     */
    private function sendEmail($email, $subject, $message, $options = [])
    {
        try {
            $template = $options['template'] ?? 'default';
            $htmlMessage = $this->buildEmailTemplate($template, $subject, $message, $options);

            return $this->emailQueue->add([
                'to' => $email,
                'subject' => $subject,
                'message' => $htmlMessage,
                'template' => $template,
                'data' => $options
            ]);
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSMS($phone, $message, $options = [])
    {
        try {
            return $this->smsQueue->add([
                'to' => $phone,
                'message' => $message,
                'data' => $options
            ]);
        } catch (Exception $e) {
            error_log("SMS send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email template
     */
    private function buildEmailTemplate($template, $subject, $message, $options = [])
    {
        $baseUrl = APP_BASE;
        $logoUrl = $baseUrl . '/assets/images/logo.png';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #fff; padding: 30px; border: 1px solid #dee2e6; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; color: #6c757d; }
                .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
                .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
                .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
                .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
                .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Apartman Yönetim Sistemi</h1>
                </div>
                <div class='content'>
                    <h2>{$subject}</h2>
                    <div>{$message}</div>
                </div>
                <div class='footer'>
                    <p>Bu e-posta Apartman Yönetim Sistemi tarafından gönderilmiştir.</p>
                    <p>E-posta adresinizi güncellemek için <a href='{$baseUrl}/resident/profile'>profil sayfanızı</a> ziyaret edin.</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Build fee reminder message
     */
    private function buildFeeReminderMessage($fee, $resident)
    {
        $dueDate = date('d.m.Y', strtotime($fee['due_date']));
        $remainingAmount = $fee['total_amount'] - $fee['paid_amount'];
        
        return "
        <p>Sayın {$resident['name']},</p>
        <p>Aidat ödemeniz hakkında hatırlatma:</p>
        <div class='alert alert-warning'>
            <strong>Aidat:</strong> {$fee['fee_name']}<br>
            <strong>Dönem:</strong> {$fee['period']}<br>
            <strong>Toplam Tutar:</strong> ₺" . number_format($fee['total_amount'], 2) . "<br>
            <strong>Ödenen:</strong> ₺" . number_format($fee['paid_amount'], 2) . "<br>
            <strong>Kalan:</strong> ₺" . number_format($remainingAmount, 2) . "<br>
            <strong>Vade Tarihi:</strong> {$dueDate}
        </div>
        <p>Ödeme yapmak için <a href='" . APP_BASE . "/resident/pay-fee/{$fee['id']}'>buraya tıklayın</a>.</p>
        ";
    }

    /**
     * Build meeting message
     */
    private function buildMeetingMessage($meeting)
    {
        $meetingDate = date('d.m.Y H:i', strtotime($meeting['meeting_date']));
        
        return "
        <p>Toplantı duyurusu:</p>
        <div class='alert alert-info'>
            <strong>Konu:</strong> {$meeting['title']}<br>
            <strong>Tarih:</strong> {$meetingDate}<br>
            <strong>Yer:</strong> " . ($meeting['location'] ?: 'Belirtilmemiş') . "
        </div>
        <p>{$meeting['description']}</p>
        ";
    }

    /**
     * Build request response message
     */
    private function buildRequestResponseMessage($request)
    {
        $statusText = [
            'open' => 'Açık',
            'in_progress' => 'İşlemde',
            'resolved' => 'Çözüldü',
            'closed' => 'Kapalı'
        ];

        return "
        <p>Talep yanıtı:</p>
        <div class='alert alert-info'>
            <strong>Konu:</strong> {$request['subject']}<br>
            <strong>Durum:</strong> " . ($statusText[$request['status']] ?? $request['status']) . "
        </div>
        <p><strong>Yanıt:</strong></p>
        <p>{$request['response']}</p>
        ";
    }

    /**
     * Build payment confirmation message
     */
    private function buildPaymentConfirmationMessage($fee, $amount)
    {
        return "
        <p>Ödeme onayı:</p>
        <div class='alert alert-info'>
            <strong>Aidat:</strong> {$fee['fee_name']}<br>
            <strong>Dönem:</strong> {$fee['period']}<br>
            <strong>Ödenen Tutar:</strong> ₺" . number_format($amount, 2) . "<br>
            <strong>Ödeme Tarihi:</strong> " . date('d.m.Y H:i') . "
        </div>
        <p>Ödemeniz başarıyla kaydedilmiştir. Teşekkür ederiz.</p>
        ";
    }

    /**
     * Helper methods
     */
    private function getBuildingResidents($buildingId, $options = [])
    {
        $sql = "SELECT ru.*, u.unit_number, b.name as building_name 
                FROM resident_users ru 
                JOIN units u ON ru.unit_id = u.id 
                JOIN buildings b ON u.building_id = b.id 
                WHERE u.building_id = ? AND ru.is_active = 1";
        
        $params = [$buildingId];
        
        if (!empty($options['email_verified'])) {
            $sql .= " AND ru.email_verified = 1";
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    private function getResident($residentId)
    {
        return $this->db->fetch(
            "SELECT ru.*, u.unit_number, b.name as building_name 
             FROM resident_users ru 
             JOIN units u ON ru.unit_id = u.id 
             JOIN buildings b ON u.building_id = b.id 
             WHERE ru.id = ?",
            [$residentId]
        );
    }

    private function getResidentByUnit($unitId)
    {
        return $this->db->fetch(
            "SELECT ru.*, u.unit_number, b.name as building_name 
             FROM resident_users ru 
             JOIN units u ON ru.unit_id = u.id 
             JOIN buildings b ON u.building_id = b.id 
             WHERE u.id = ? AND ru.is_active = 1 
             ORDER BY ru.is_owner DESC, ru.created_at ASC 
             LIMIT 1",
            [$unitId]
        );
    }

    private function getManagementFee($feeId)
    {
        return $this->db->fetch(
            "SELECT mf.*, u.unit_number, b.name as building_name 
             FROM management_fees mf 
             JOIN units u ON mf.unit_id = u.id 
             JOIN buildings b ON mf.building_id = b.id 
             WHERE mf.id = ?",
            [$feeId]
        );
    }

    private function getMeeting($meetingId)
    {
        return $this->db->fetch(
            "SELECT * FROM building_meetings WHERE id = ?",
            [$meetingId]
        );
    }

    private function getAnnouncement($announcementId)
    {
        return $this->db->fetch(
            "SELECT * FROM building_announcements WHERE id = ?",
            [$announcementId]
        );
    }

    private function getResidentRequest($requestId)
    {
        return $this->db->fetch(
            "SELECT * FROM resident_requests WHERE id = ?",
            [$requestId]
        );
    }

    private function logNotification($residentId, $type, $subject, $options = [])
    {
        $this->db->insert('notification_logs', [
            'resident_id' => $residentId,
            'type' => $type,
            'subject' => $subject,
            'template' => $options['template'] ?? 'default',
            'data' => json_encode($options),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}