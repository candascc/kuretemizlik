<?php

class ResidentNotificationPreferenceService
{
    private const CATEGORIES = [
        'fees' => [
            'label' => 'Aidat Hatırlatmaları',
            'description' => 'Vadesi yaklaşan aidat ve ödeme hatırlatmaları.',
            'default_email' => true,
            'default_sms' => true,
            'supports_sms' => true,
        ],
        'meetings' => [
            'label' => 'Toplantı Duyuruları',
            'description' => 'Apartman/bina toplantı daveti ve güncellemeleri.',
            'default_email' => true,
            'default_sms' => false,
            'supports_sms' => true,
        ],
        'announcements' => [
            'label' => 'Genel Duyurular',
            'description' => 'Yönetim tarafından paylaşılan haber ve bilgilendirmeler.',
            'default_email' => true,
            'default_sms' => false,
            'supports_sms' => true,
        ],
        'alerts' => [
            'label' => 'Acil Durum Uyarıları',
            'description' => 'Acil durum, bakım ve güvenlik duyuruları.',
            'default_email' => true,
            'default_sms' => true,
            'supports_sms' => true,
        ],
        'requests' => [
            'label' => 'Talep Yanıtları',
            'description' => 'İletmiş olduğunuz talepler için yanıt ve durum güncellemeleri.',
            'default_email' => true,
            'default_sms' => false,
            'supports_sms' => true,
        ],
        'payments' => [
            'label' => 'Ödeme Onayları',
            'description' => 'Gerçekleştirdiğiniz ödemelerin alındı bilgisi.',
            'default_email' => true,
            'default_sms' => false,
            'supports_sms' => false,
        ],
    ];

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getCategories(): array
    {
        return self::CATEGORIES;
    }

    public function getResidentPreferences(int $residentId): array
    {
        $this->ensureDefaults($residentId);

        $rows = $this->db->fetchAll(
            "SELECT category, notify_email, notify_sms 
             FROM resident_notification_preferences 
             WHERE resident_user_id = ?",
            [$residentId]
        ) ?: [];

        $preferences = [];
        foreach ($rows as $row) {
            $preferences[$row['category']] = [
                'email' => (bool)$row['notify_email'],
                'sms' => (bool)$row['notify_sms'],
            ];
        }

        foreach (self::CATEGORIES as $key => $meta) {
            if (!isset($preferences[$key])) {
                $preferences[$key] = [
                    'email' => (bool)$meta['default_email'],
                    'sms' => (bool)$meta['default_sms'],
                ];
            }
        }

        return $preferences;
    }

    public function updatePreferences(int $residentId, array $preferences): void
    {
        $this->ensureDefaults($residentId);
        $now = date('Y-m-d H:i:s');

        foreach (self::CATEGORIES as $key => $meta) {
            $pref = $preferences[$key] ?? [];
            $notifyEmail = !empty($pref['email']) ? 1 : 0;
            $notifySms = ($meta['supports_sms'] ?? false) && !empty($pref['sms']) ? 1 : 0;

            $existing = $this->db->fetch(
                "SELECT id FROM resident_notification_preferences 
                 WHERE resident_user_id = ? AND category = ?",
                [$residentId, $key]
            );

            if ($existing) {
                $this->db->update(
                    'resident_notification_preferences',
                    [
                        'notify_email' => $notifyEmail,
                        'notify_sms' => $notifySms,
                        'updated_at' => $now,
                    ],
                    'id = :id',
                    [':id' => $existing['id']]
                );
            } else {
                $this->db->insert('resident_notification_preferences', [
                    'resident_user_id' => $residentId,
                    'category' => $key,
                    'notify_email' => $notifyEmail,
                    'notify_sms' => $notifySms,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function resolveChannels(array $resident, string $category, array $requestedChannels): array
    {
        $residentId = (int)($resident['id'] ?? 0);
        if ($residentId <= 0) {
            return [];
        }

        if (!isset(self::CATEGORIES[$category])) {
            $category = 'announcements';
        }

        $preferences = $this->getResidentPreferences($residentId);
        $categoryPrefs = $preferences[$category] ?? [
            'email' => self::CATEGORIES[$category]['default_email'] ?? true,
            'sms' => self::CATEGORIES[$category]['default_sms'] ?? false,
        ];

        $channels = [];

        foreach ($requestedChannels as $channel) {
            if ($channel === 'email' && !empty($resident['notify_email']) && !empty($categoryPrefs['email'])) {
                $channels[] = 'email';
            }
            if ($channel === 'sms' && !empty($resident['notify_sms']) && !empty($categoryPrefs['sms'])) {
                $channels[] = 'sms';
            }
        }

        return $channels;
    }

    public function getCategoryStats(): array
    {
        $totalActive = (int)($this->db->fetch(
            "SELECT COUNT(*) AS total FROM resident_users WHERE is_active = 1"
        )['total'] ?? 0);

        $emailEligible = (int)($this->db->fetch(
            "SELECT COUNT(*) AS total FROM resident_users WHERE is_active = 1 AND notify_email = 1"
        )['total'] ?? 0);

        $smsEligible = (int)($this->db->fetch(
            "SELECT COUNT(*) AS total FROM resident_users WHERE is_active = 1 AND notify_sms = 1"
        )['total'] ?? 0);

        $stats = [];

        foreach (self::CATEGORIES as $key => $meta) {
            $defaults = [
                'email' => $meta['default_email'] ?? true,
                'sms' => $meta['default_sms'] ?? false,
            ];

            $record = $this->db->fetch(
                "SELECT 
                    COUNT(*) AS total_records,
                    SUM(CASE WHEN notify_email = 1 THEN 1 ELSE 0 END) AS email_on,
                    SUM(CASE WHEN notify_email = 0 THEN 1 ELSE 0 END) AS email_off,
                    SUM(CASE WHEN notify_sms = 1 THEN 1 ELSE 0 END) AS sms_on
                 FROM resident_notification_preferences
                 WHERE category = ?",
                [$key]
            ) ?: [
                'total_records' => 0,
                'email_on' => 0,
                'email_off' => 0,
                'sms_on' => 0,
            ];

            $records = (int)$record['total_records'];
            $emailOn = (int)$record['email_on'];
            $smsOn = (int)$record['sms_on'];
            $emailOff = (int)$record['email_off'];

            if ($defaults['email']) {
                $missing = max(0, $emailEligible - $records);
                $emailOn += $missing;
            }

            if ($defaults['sms']) {
                $missing = max(0, $smsEligible - $records);
                $smsOn += $missing;
            }

            $stats[$key] = [
                'label' => $meta['label'],
                'description' => $meta['description'],
                'supports_sms' => $meta['supports_sms'],
                'default_email' => $defaults['email'],
                'default_sms' => $defaults['sms'],
                'email_enabled' => min($emailOn, $emailEligible),
                'email_eligible' => $emailEligible,
                'sms_enabled' => min($smsOn, $smsEligible),
                'sms_eligible' => $smsEligible,
                'total_residents' => $totalActive,
                'explicit_email_off' => $emailOff,
            ];
        }

        return $stats;
    }

    private function ensureDefaults(int $residentId): void
    {
        $existing = $this->db->fetchAll(
            "SELECT category FROM resident_notification_preferences WHERE resident_user_id = ?",
            [$residentId]
        ) ?: [];

        $existingKeys = array_map(fn($row) => $row['category'], $existing);
        $now = date('Y-m-d H:i:s');

        foreach (self::CATEGORIES as $key => $meta) {
            if (in_array($key, $existingKeys, true)) {
                continue;
            }

            try {
                $this->db->insert('resident_notification_preferences', [
                    'resident_user_id' => $residentId,
                    'category' => $key,
                    'notify_email' => $meta['default_email'] ? 1 : 0,
                    'notify_sms' => $meta['supports_sms'] && ($meta['default_sms'] ?? false) ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (Exception $e) {
                // Ignore duplicates due to race conditions
            }
        }
    }
}

