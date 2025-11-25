<?php
/**
 * Email Management Controller
 * View email logs, queue status, and manage email settings
 */

class EmailController
{
    /**
     * Email logs list
     */
    public function logs()
    {
        Auth::requireAdmin();
        
        $page = (int)($_GET['page'] ?? 1);
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $db = Database::getInstance();
        $where = [];
        $params = [];
        
        if ($type) {
            $where[] = "el.type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $where[] = "el.status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        try {
            $total = $db->fetch("SELECT COUNT(*) as count FROM email_logs el $whereClause", $params)['count'];
            
            // Get logs
            $logs = $db->fetchAll("
                SELECT 
                    el.*,
                    j.id as job_id_exists,
                    c.name as customer_name
                FROM email_logs el
                LEFT JOIN jobs j ON el.job_id = j.id
                LEFT JOIN customers c ON el.customer_id = c.id
                $whereClause
                ORDER BY el.sent_at DESC
                LIMIT ? OFFSET ?
            ", array_merge($params, [$limit, $offset]));
        } catch (Exception $e) {
            // Table might not exist yet
            $total = 0;
            $logs = [];
        }
        
        // Get statistics
        try {
            $stats = $db->fetch("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    COUNT(DISTINCT DATE(sent_at)) as days_active
                FROM email_logs
            ");
        } catch (Exception $e) {
            // Table might not exist yet, return empty stats
            $stats = ['total' => 0, 'sent' => 0, 'failed' => 0, 'days_active' => 0];
        }
        
        // Pagination helper
        $totalPages = ceil($total / $limit);
        $pagination = [
            'current' => $page,
            'total' => $totalPages,
            'total_items' => $total,
            'per_page' => $limit,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
        
        echo View::renderWithLayout('admin/emails/logs', [
            'logs' => $logs,
            'stats' => $stats,
            'pagination' => $pagination,
            'filters' => [
                'type' => $type,
                'status' => $status
            ],
            'flash' => Utils::getFlash()
        ]);
    }
    
    /**
     * Email queue status
     */
    public function queue()
    {
        Auth::requireAdmin();
        
        $db = Database::getInstance();
        
        // Get queue statistics
        try {
            $stats = $db->fetch("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sending' THEN 1 ELSE 0 END) as sending,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM email_queue
            ");
        } catch (Exception $e) {
            $stats = ['total' => 0, 'pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0];
        }
        
        // Get pending emails
        try {
            $pending = $db->fetchAll("
                SELECT * FROM email_queue
                WHERE status IN ('pending', 'sending', 'failed')
                ORDER BY created_at ASC
                LIMIT 100
            ");
        } catch (Exception $e) {
            $pending = [];
        }
        
        // Get recently sent
        try {
            $recentlySent = $db->fetchAll("
                SELECT * FROM email_queue
                WHERE status = 'sent'
                ORDER BY sent_at DESC
                LIMIT 20
            ");
        } catch (Exception $e) {
            $recentlySent = [];
        }
        
        echo View::renderWithLayout('admin/emails/queue', [
            'stats' => $stats,
            'pending' => $pending,
            'recentlySent' => $recentlySent,
            'flash' => Utils::getFlash()
        ]);
    }
    
    /**
     * Retry failed email
     */
    public function retry()
    {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/admin/emails/queue'));
        }
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/admin/emails/queue'));
        }
        
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            Utils::flash('error', 'Geçersiz email ID.');
            redirect(base_url('/admin/emails/queue'));
        }
        
        $db = Database::getInstance();
        
        try {
            $db->update('email_queue', 
                [
                    'status' => 'pending',
                    'retry_count' => 0,
                    'error_message' => null
                ],
                'id = ?',
                [$id]
            );
            
            Utils::flash('success', 'Email yeniden kuyruğa eklendi.');
        } catch (Exception $e) {
            Utils::flash('error', 'Hata: ' . Utils::safeExceptionMessage($e));
        }
        
        redirect(base_url('/admin/emails/queue'));
    }
    
    /**
     * Clear old logs
     */
    public function clearLogs()
    {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/admin/emails/logs'));
        }
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/admin/emails/logs'));
        }
        
        $days = (int)($_POST['days'] ?? 90);
        
        $db = Database::getInstance();
        
        try {
            $result = $db->query(
                "DELETE FROM email_logs WHERE sent_at < datetime('now', '-' || ? || ' days')",
                [$days]
            );
            
            Utils::flash('success', "{$days} günden eski email logları temizlendi.");
        } catch (Exception $e) {
            Utils::flash('error', 'Hata: ' . Utils::safeExceptionMessage($e));
        }
        
        redirect(base_url('/admin/emails/logs'));
    }
}

