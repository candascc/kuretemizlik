<?php

class CalendarWebhookController
{
    public function google()
    {
        // Google Calendar Push Notifications validation
        // On initial subscription, Google sends a validation challenge
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $challenge = $_GET['syncToken'] ?? '';
            if (strlen($challenge) > 0) {
                http_response_code(200);
                echo $challenge;
                return;
            }
        }
        
        // Handle notification
        $raw = file_get_contents('php://input');
        $notif = json_decode($raw, true);
        
        if (!$notif || !isset($notif['channel_id'])) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'Invalid notification']);
            return;
        }
        
        // Lookup user by channel_id from calendar_sync
        $db = Database::getInstance();
        $sync = $db->fetch(
            "SELECT user_id, provider FROM calendar_sync WHERE webhook_id = ?",
            [$notif['channel_id']]
        );
        
        if (!$sync) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'Unknown channel']);
            return;
        }
        
        // Trigger background sync for this user/provider
        // In production, queue this job
        try {
            CalendarSyncService::incrementalSync((int)$sync['user_id'], $sync['provider']);
            Logger::info('Webhook-triggered sync', [
                'provider' => 'google',
                'user_id' => $sync['user_id'],
                'channel' => $notif['channel_id']
            ]);
        } catch (Exception $e) {
            Logger::error('Webhook sync failed', ['error' => $e->getMessage()]);
        }
        
        http_response_code(200);
        echo json_encode(['ok' => true, 'provider' => 'google']);
    }

    public function microsoft()
    {
        // Microsoft Graph notifications
        $raw = file_get_contents('php://input');
        $notif = json_decode($raw, true);
        
        if (!$notif) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'Invalid notification']);
            return;
        }
        
        // Handle validation request
        if (isset($notif['validationToken'])) {
            http_response_code(200);
            header('Content-Type: text/plain');
            echo $notif['validationToken'];
            return;
        }
        
        // Process notification
        $subscriptionId = $notif['subscriptionId'] ?? null;
        if (!$subscriptionId) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'No subscription ID']);
            return;
        }
        
        // Lookup user by subscription ID
        $db = Database::getInstance();
        $sync = $db->fetch(
            "SELECT user_id, provider FROM calendar_sync WHERE webhook_id = ?",
            [$subscriptionId]
        );
        
        if (!$sync) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'Unknown subscription']);
            return;
        }
        
        // Trigger sync
        try {
            CalendarSyncService::incrementalSync((int)$sync['user_id'], $sync['provider']);
            Logger::info('Webhook-triggered sync', [
                'provider' => 'microsoft',
                'user_id' => $sync['user_id'],
                'subscription' => $subscriptionId
            ]);
        } catch (Exception $e) {
            Logger::error('Webhook sync failed', ['error' => $e->getMessage()]);
        }
        
        http_response_code(200);
        echo json_encode(['ok' => true, 'provider' => 'microsoft']);
    }
}


