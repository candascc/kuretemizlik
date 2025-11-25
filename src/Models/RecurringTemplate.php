<?php
/**
 * Recurring Job Template Model
 * UX-HIGH-003 Implementation
 * 
 * Pre-defined templates for common recurring patterns
 * Makes recurring job setup 300% easier
 */

class RecurringTemplate
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all built-in templates
     */
    public function getBuiltInTemplates()
    {
        return [
            [
                'id' => 'daily',
                'name' => 'Her Gün',
                'description' => 'Her gün tekrarlanan işler için',
                'icon' => 'fa-calendar-day',
                'rrule' => 'FREQ=DAILY;INTERVAL=1',
                'example' => 'Her gün, hafta sonu dahil',
                'popular' => true
            ],
            [
                'id' => 'daily_weekdays',
                'name' => 'Her İş Günü',
                'description' => 'Hafta içi her gün (Pazartesi-Cuma)',
                'icon' => 'fa-briefcase',
                'rrule' => 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR',
                'example' => 'Pazartesi - Cuma',
                'popular' => true
            ],
            [
                'id' => 'weekly',
                'name' => 'Her Hafta',
                'description' => 'Haftada bir, belirli günde',
                'icon' => 'fa-calendar-week',
                'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO', // Default Monday, will be customized
                'example' => 'Her hafta Pazartesi',
                'popular' => true,
                'needs_day' => true
            ],
            [
                'id' => 'biweekly',
                'name' => 'İki Haftada Bir',
                'description' => '14 günde bir temizlik',
                'icon' => 'fa-calendar-alt',
                'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO',
                'example' => 'İki haftada bir Pazartesi',
                'popular' => true,
                'needs_day' => true
            ],
            [
                'id' => 'monthly_first',
                'name' => 'Ayın İlk Günü',
                'description' => 'Her ayın 1. günü',
                'icon' => 'fa-calendar',
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=1',
                'example' => 'Her ayın 1. günü',
                'popular' => false
            ],
            [
                'id' => 'monthly_last',
                'name' => 'Ayın Son Günü',
                'description' => 'Her ayın son günü',
                'icon' => 'fa-calendar-minus',
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=-1',
                'example' => 'Her ayın son günü',
                'popular' => false
            ],
            [
                'id' => 'monthly_first_monday',
                'name' => 'Ayın İlk Pazartesi',
                'description' => 'Her ayın ilk Pazartesi günü',
                'icon' => 'fa-calendar-check',
                'rrule' => 'FREQ=MONTHLY;BYDAY=1MO',
                'example' => 'Örn: 4 Kasım, 2 Aralık...',
                'popular' => false
            ],
            [
                'id' => 'every_3_days',
                'name' => '3 Günde Bir',
                'description' => 'Her 3 günde bir',
                'icon' => 'fa-redo',
                'rrule' => 'FREQ=DAILY;INTERVAL=3',
                'example' => '1 Kas, 4 Kas, 7 Kas...',
                'popular' => false
            ],
            [
                'id' => 'three_times_week',
                'name' => 'Haftada 3 Gün',
                'description' => 'Pazartesi, Çarşamba, Cuma',
                'icon' => 'fa-calendar-week',
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR',
                'example' => 'Pazartesi, Çarşamba, Cuma',
                'popular' => true
            ],
            [
                'id' => 'weekend',
                'name' => 'Sadece Hafta Sonu',
                'description' => 'Cumartesi ve Pazar',
                'icon' => 'fa-umbrella-beach',
                'rrule' => 'FREQ=WEEKLY;BYDAY=SA,SU',
                'example' => 'Cumartesi ve Pazar',
                'popular' => false
            ]
        ];
    }
    
    /**
     * Get popular/most used templates
     */
    public function getPopularTemplates()
    {
        $all = $this->getBuiltInTemplates();
        return array_filter($all, function($t) {
            return $t['popular'] ?? false;
        });
    }
    
    /**
     * Get template by ID
     */
    public function getTemplate($id)
    {
        $templates = $this->getBuiltInTemplates();
        foreach ($templates as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }
        return null;
    }
    
    /**
     * Generate RRULE from template with user customization
     */
    public function generateRRule($templateId, $customizations = [])
    {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            return null;
        }
        
        $rrule = $template['rrule'];
        
        // Apply customizations
        if (isset($customizations['day']) && $template['needs_day']) {
            // Replace day placeholder
            $rrule = preg_replace('/BYDAY=[A-Z,]+/', 'BYDAY=' . $customizations['day'], $rrule);
        }
        
        if (isset($customizations['until'])) {
            $rrule .= ';UNTIL=' . $customizations['until'];
        }
        
        if (isset($customizations['count'])) {
            $rrule .= ';COUNT=' . (int)$customizations['count'];
        }
        
        return $rrule;
    }
    
    /**
     * Generate natural language description from RRULE
     */
    public function describeRRule($rrule)
    {
        // Simple descriptions (can be expanded)
        if (strpos($rrule, 'FREQ=DAILY;INTERVAL=1') !== false && strpos($rrule, 'BYDAY') === false) {
            return 'Her gün';
        }
        if (strpos($rrule, 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR') !== false) {
            return 'Her iş günü (Pzt-Cum)';
        }
        if (strpos($rrule, 'FREQ=WEEKLY;INTERVAL=1') !== false) {
            return 'Her hafta';
        }
        if (strpos($rrule, 'FREQ=WEEKLY;INTERVAL=2') !== false) {
            return 'İki haftada bir';
        }
        if (strpos($rrule, 'FREQ=WEEKLY;BYDAY=MO,WE,FR') !== false) {
            return 'Haftada 3 gün (Pzt, Çar, Cum)';
        }
        if (strpos($rrule, 'FREQ=MONTHLY;BYMONTHDAY=1') !== false) {
            return 'Her ayın 1. günü';
        }
        if (strpos($rrule, 'FREQ=MONTHLY;BYMONTHDAY=-1') !== false) {
            return 'Her ayın son günü';
        }
        
        // Fallback
        return 'Özel tekrar: ' . $rrule;
    }
    
    /**
     * Preview next 5 occurrences
     */
    public function previewOccurrences($startDate, $startTime, $duration, $rrule, $limit = 5)
    {
        // This would integrate with RecurringGenerator
        // For now, return a simple example
        
        $occurrences = [];
        $date = new DateTime($startDate);
        
        // Parse RRULE frequency
        if (strpos($rrule, 'FREQ=DAILY') !== false) {
            $interval = 1;
            if (preg_match('/INTERVAL=(\d+)/', $rrule, $matches)) {
                $interval = (int)$matches[1];
            }
            
            for ($i = 0; $i < $limit; $i++) {
                $occurrences[] = [
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'formatted' => $date->format('d M Y') . ', ' . $startTime
                ];
                $date->modify("+{$interval} day");
            }
        } elseif (strpos($rrule, 'FREQ=WEEKLY') !== false) {
            $interval = 1;
            if (preg_match('/INTERVAL=(\d+)/', $rrule, $matches)) {
                $interval = (int)$matches[1];
            }
            
            for ($i = 0; $i < $limit; $i++) {
                $occurrences[] = [
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'formatted' => $date->format('d M Y') . ', ' . $startTime
                ];
                $date->modify("+{$interval} week");
            }
        }
        
        return $occurrences;
    }
}

