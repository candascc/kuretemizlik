<?php

/**
 * Notification Controller
 * Handles full page notifications view
 */
class NotificationController
{
    /**
     * Show all notifications page
     */
    public function index()
    {
        Auth::require();
        
        try {
            $notifications = class_exists('NotificationService') ? NotificationService::getHeaderNotifications(50) : [];
            $prefs = class_exists('NotificationService') ? NotificationService::getPrefs() : ['critical'=>0,'ops'=>0,'system'=>0];
            $stats = [
                'total' => count($notifications),
                'unread' => count(array_filter($notifications, function($n) { return empty($n['read']); })),
                'critical' => count(array_filter($notifications, function($n) { return ($n['type'] ?? '') === 'critical'; })),
                'ops' => count(array_filter($notifications, function($n) { return ($n['type'] ?? '') === 'ops'; })),
                'system' => count(array_filter($notifications, function($n) { return ($n['type'] ?? '') === 'system'; }))
            ];
            
            echo View::renderWithLayout('notifications/index', [
                'title' => 'Bildirimler',
                'notifications' => $notifications,
                'prefs' => $prefs,
                'stats' => $stats,
                'flash' => Utils::getFlash()
            ]);
        } catch (Exception $e) {
            error_log("NotificationController::index error: " . $e->getMessage());
            View::error('Bildirimler yüklenemedi', 500);
        }
    }
}

