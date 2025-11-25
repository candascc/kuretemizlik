<?php
/**
 * Cache Management Controller
 * Handles cache operations, analytics, and monitoring
 */

class CacheController
{
    private $cacheManager;
    
    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
    }
    
    /**
     * Show cache dashboard
     */
    public function index()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $stats = $this->cacheManager->getStats();
        $analytics = $this->cacheManager->getAnalytics();
        
        $data = [
            'title' => 'Önbellek Yönetimi',
            'stats' => $stats,
            'analytics' => $analytics,
            'drivers' => $this->cacheManager->getDrivers()
        ];
        
        echo View::renderWithLayout('admin/cache/index', $data);
    }
    
    /**
     * Show cache statistics
     */
    public function stats()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $stats = $this->cacheManager->getStats();
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Show cache analytics
     */
    public function analytics()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $analytics = $this->cacheManager->getAnalytics();
        
        header('Content-Type: application/json');
        echo json_encode($analytics);
    }
    
    /**
     * Clear cache
     */
    public function clear()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/cache');
        }
        
        $driver = $_POST['driver'] ?? null;
        $result = $this->cacheManager->clear($driver);
        
        if ($result) {
            set_flash('success', 'Cache cleared successfully.');
        } else {
            set_flash('error', 'Failed to clear cache.');
        }
        
        redirect('/admin/cache');
    }
    
    /**
     * Warm cache
     */
    public function warm()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/cache');
        }
        
        $result = $this->cacheManager->warm();
        
        if ($result) {
            set_flash('success', 'Cache warmed successfully.');
        } else {
            set_flash('error', 'Failed to warm cache.');
        }
        
        redirect('/admin/cache');
    }
    
    /**
     * Test cache connection
     */
    public function test()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $driver = $_GET['driver'] ?? null;
        $result = $this->cacheManager->testConnection($driver);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'driver' => $driver ?? 'default'
        ]);
    }
    
    /**
     * Get cache keys
     */
    public function keys()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $driver = $_GET['driver'] ?? null;
        $pattern = $_GET['pattern'] ?? '*';
        
        $cache = $this->cacheManager->driver($driver);
        
        if (method_exists($cache, 'keys')) {
            $keys = $cache->keys($pattern);
        } else {
            $keys = [];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'keys' => $keys,
            'count' => count($keys),
            'pattern' => $pattern
        ]);
    }
    
    /**
     * Get cache value
     */
    public function get()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $key = $_GET['key'] ?? '';
        $driver = $_GET['driver'] ?? null;
        
        if (empty($key)) {
            http_response_code(400);
            echo json_encode(['error' => 'Key is required']);
            return;
        }
        
        $value = $this->cacheManager->get($key, null, $driver);
        
        header('Content-Type: application/json');
        echo json_encode([
            'key' => $key,
            'value' => $value,
            'exists' => $value !== null
        ]);
    }
    
    /**
     * Set cache value
     */
    public function set()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        $ttl = (int)($_POST['ttl'] ?? AppConstants::CACHE_TTL_MEDIUM);
        $driver = $_POST['driver'] ?? null;
        
        if (empty($key)) {
            http_response_code(400);
            echo json_encode(['error' => 'Key is required']);
            return;
        }
        
        $result = $this->cacheManager->set($key, $value, $ttl, $driver);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'key' => $key
        ]);
    }
    
    /**
     * Delete cache key
     */
    public function delete()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $key = $_POST['key'] ?? '';
            $driver = $_POST['driver'] ?? null;
            
            if (empty($key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Key is required']);
                return;
            }
            
            $result = $this->cacheManager->delete($key, $driver);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'key' => $key
            ]);
        } catch (Exception $e) {
            error_log("CacheController::delete() error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Cache silinirken bir hata oluştu'
            ]);
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Invalidate by tag
     */
    public function invalidateTag()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $tag = $_POST['tag'] ?? '';
        $driver = $_POST['driver'] ?? null;
        
        if (empty($tag)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag is required']);
            return;
        }
        
        $result = $this->cacheManager->forgetTag($tag, $driver);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'tag' => $tag
        ]);
    }
    
    /**
     * Invalidate by pattern
     */
    public function invalidatePattern()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $pattern = $_POST['pattern'] ?? '';
        $driver = $_POST['driver'] ?? null;
        
        if (empty($pattern)) {
            http_response_code(400);
            echo json_encode(['error' => 'Pattern is required']);
            return;
        }
        
        $result = $this->cacheManager->forgetPattern($pattern, $driver);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'pattern' => $pattern
        ]);
    }
    
    /**
     * Run cache cleanup
     */
    public function cleanup()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/cache');
        }
        
        $result = $this->cacheManager->cleanup();
        
        if ($result) {
            set_flash('success', 'Cache cleanup completed successfully.');
        } else {
            set_flash('error', 'Cache cleanup failed.');
        }
        
        redirect('/admin/cache');
    }
    
    /**
     * Export cache configuration
     */
    public function export()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $config = $this->cacheManager->getConfig();
        $stats = $this->cacheManager->getStats();
        
        $data = [
            'config' => $config,
            'stats' => $stats,
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => Auth::user()['username']
        ];
        
        $format = $_GET['format'] ?? 'json';
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="cache_config_' . date('Y-m-d') . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            // CSV export
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="cache_config_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Setting', 'Value']);
            
            foreach ($config as $key => $value) {
                if (is_array($value)) {
                    fputcsv($output, [$key, json_encode($value)]);
                } else {
                    fputcsv($output, [$key, $value]);
                }
            }
            
            fclose($output);
        }
        
        exit;
    }
}
