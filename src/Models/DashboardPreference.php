<?php
/**
 * Dashboard Preference Model - UX-MED-002
 * 
 * Manages user dashboard customization preferences
 * Stores widget layout, visibility, and settings
 */

class DashboardPreference
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTableExists();
    }
    
    /**
     * Ensure dashboard_preferences table exists
     */
    private function ensureTableExists()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dashboard_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                widget_config TEXT NOT NULL,
                layout_type VARCHAR(50) DEFAULT 'grid',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Create index for fast lookup
        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_dashboard_prefs_user 
            ON dashboard_preferences(user_id)
        ");
    }
    
    /**
     * Get user's dashboard preferences
     */
    public function getPreferences($userId)
    {
        $pref = $this->db->fetch(
            "SELECT * FROM dashboard_preferences WHERE user_id = ?",
            [$userId]
        );
        
        if ($pref) {
            $pref['widget_config'] = json_decode($pref['widget_config'], true);
            return $pref;
        }
        
        // Return defaults if no preferences saved
        return $this->getDefaultPreferences($userId);
    }
    
    /**
     * Save user preferences
     */
    public function savePreferences($userId, $widgetConfig, $layoutType = 'grid')
    {
        $existing = $this->db->fetch(
            "SELECT id FROM dashboard_preferences WHERE user_id = ?",
            [$userId]
        );
        
        $configJson = json_encode($widgetConfig);
        
        if ($existing) {
            // Update
            return $this->db->update('dashboard_preferences', [
                'widget_config' => $configJson,
                'layout_type' => $layoutType,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', [$userId]);
        } else {
            // Insert
            return $this->db->insert('dashboard_preferences', [
                'user_id' => $userId,
                'widget_config' => $configJson,
                'layout_type' => $layoutType,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Reset to defaults
     */
    public function resetToDefaults($userId)
    {
        return $this->db->delete('dashboard_preferences', 'user_id = ?', [$userId]);
    }
    
    /**
     * Get default preferences based on user role
     */
    private function getDefaultPreferences($userId)
    {
        $user = $this->db->fetch("SELECT role FROM users WHERE id = ?", [$userId]);
        $role = $user['role'] ?? 'STAFF';
        
        $defaults = [
            'SUPERADMIN' => [
                ['id' => 'stats', 'order' => 1, 'visible' => true, 'size' => 'full'],
                ['id' => 'revenue-chart', 'order' => 2, 'visible' => true, 'size' => 'half'],
                ['id' => 'recent-jobs', 'order' => 3, 'visible' => true, 'size' => 'half'],
                ['id' => 'staff-performance', 'order' => 4, 'visible' => true, 'size' => 'full'],
                ['id' => 'customer-activity', 'order' => 5, 'visible' => true, 'size' => 'half']
            ],
            'ADMIN' => [
                ['id' => 'today-jobs', 'order' => 1, 'visible' => true, 'size' => 'full'],
                ['id' => 'stats', 'order' => 2, 'visible' => true, 'size' => 'full'],
                ['id' => 'recent-payments', 'order' => 3, 'visible' => true, 'size' => 'half'],
                ['id' => 'quick-actions', 'order' => 4, 'visible' => true, 'size' => 'half']
            ],
            'STAFF' => [
                ['id' => 'today-jobs', 'order' => 1, 'visible' => true, 'size' => 'full'],
                ['id' => 'quick-actions', 'order' => 2, 'visible' => true, 'size' => 'full'],
                ['id' => 'calendar-preview', 'order' => 3, 'visible' => true, 'size' => 'full']
            ],
            'OPERATOR' => [
                ['id' => 'today-jobs', 'order' => 1, 'visible' => true, 'size' => 'full'],
                ['id' => 'upcoming-recurring', 'order' => 2, 'visible' => true, 'size' => 'full'],
                ['id' => 'calendar-preview', 'order' => 3, 'visible' => true, 'size' => 'full']
            ]
        ];
        
        $widgets = $defaults[$role] ?? $defaults['STAFF'];
        
        return [
            'user_id' => $userId,
            'widget_config' => $widgets,
            'layout_type' => 'grid'
        ];
    }
    
    /**
     * Get available widgets
     */
    public function getAvailableWidgets()
    {
        return [
            [
                'id' => 'stats',
                'name' => 'İstatistikler',
                'icon' => 'fa-chart-bar',
                'description' => 'Günlük/haftalık özet istatistikler',
                'sizes' => ['full', 'half']
            ],
            [
                'id' => 'today-jobs',
                'name' => 'Bugünün İşleri',
                'icon' => 'fa-tasks',
                'description' => 'Bugün için planlanmış işler',
                'sizes' => ['full']
            ],
            [
                'id' => 'revenue-chart',
                'name' => 'Gelir Grafiği',
                'icon' => 'fa-chart-line',
                'description' => 'Aylık gelir trendi',
                'sizes' => ['full', 'half']
            ],
            [
                'id' => 'recent-payments',
                'name' => 'Son Ödemeler',
                'icon' => 'fa-money-bill-wave',
                'description' => 'En son alınan ödemeler',
                'sizes' => ['full', 'half']
            ],
            [
                'id' => 'upcoming-recurring',
                'name' => 'Periyodik İşler',
                'icon' => 'fa-repeat',
                'description' => 'Yaklaşan periyodik işler',
                'sizes' => ['full', 'half']
            ],
            [
                'id' => 'staff-performance',
                'name' => 'Personel Performansı',
                'icon' => 'fa-user-check',
                'description' => 'Personel iş tamamlama istatistikleri',
                'sizes' => ['full']
            ],
            [
                'id' => 'customer-activity',
                'name' => 'Müşteri Aktivitesi',
                'icon' => 'fa-users',
                'description' => 'Son müşteri hareketleri',
                'sizes' => ['half']
            ],
            [
                'id' => 'quick-actions',
                'name' => 'Hızlı İşlemler',
                'icon' => 'fa-bolt',
                'description' => 'Sık kullanılan işlemler',
                'sizes' => ['full', 'half']
            ],
            [
                'id' => 'notifications-feed',
                'name' => 'Bildirimler',
                'icon' => 'fa-bell',
                'description' => 'Son bildirimler',
                'sizes' => ['half']
            ],
            [
                'id' => 'calendar-preview',
                'name' => 'Takvim Önizleme',
                'icon' => 'fa-calendar',
                'description' => 'Haftalık takvim görünümü',
                'sizes' => ['full']
            ]
        ];
    }
}

