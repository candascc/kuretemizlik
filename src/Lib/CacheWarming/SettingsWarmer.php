<?php
/**
 * Settings Cache Warmer
 * Pre-loads application settings into cache
 */

class SettingsWarmer
{
    private $cacheManager;
    
    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
    }
    
    /**
     * Warm settings cache
     */
    public function warm(): void
    {
        Logger::info('Starting settings cache warming');
        
        try {
            // Warm application settings
            $this->warmApplicationSettings();
            
            // Warm system settings
            $this->warmSystemSettings();
            
            // Warm audit settings
            $this->warmAuditSettings();
            
            Logger::info('Settings cache warming completed');
            
        } catch (Exception $e) {
            Logger::error('Settings cache warming failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Warm application settings
     */
    private function warmApplicationSettings(): void
    {
        $settings = [
            'app_name' => APP_NAME,
            'app_version' => APP_VERSION,
            'app_url' => APP_URL,
            'timezone' => date_default_timezone_get(),
            'locale' => 'tr_TR',
            'currency' => 'TRY',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd.m.Y H:i'
        ];
        
        $this->cacheManager->set('settings:app', $settings, 7200);
        
        Logger::info('Application settings cached');
    }
    
    /**
     * Warm system settings
     */
    private function warmSystemSettings(): void
    {
        $db = Database::getInstance();
        
        // Get system settings from database
        $systemSettings = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings");
        
        $settings = [];
        foreach ($systemSettings as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        $this->cacheManager->set('settings:system', $settings, 7200);
        
        Logger::info('System settings cached', [
            'count' => count($settings)
        ]);
    }
    
    /**
     * Warm audit settings
     */
    private function warmAuditSettings(): void
    {
        $db = Database::getInstance();
        
        // Get audit settings
        $auditSettings = $db->fetchAll("SELECT setting_key, setting_value FROM audit_settings");
        
        $settings = [];
        foreach ($auditSettings as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        $this->cacheManager->set('settings:audit', $settings, 7200);
        
        Logger::info('Audit settings cached', [
            'count' => count($settings)
        ]);
    }
}
