<?php
/**
 * Lightweight task endpoints for schedulers (cron/Task Scheduler)
 * Secured with a simple token (set TASK_TOKEN in env.local or config)
 */

class TasksController
{
    private function checkToken(): void
    {
        $token = $_GET['token'] ?? $_SERVER['HTTP_X_TASK_TOKEN'] ?? '';
        $expected = $_ENV['TASK_TOKEN'] ?? getenv('TASK_TOKEN') ?? null;
        if (!$expected && defined('APP_DEBUG') && APP_DEBUG) {
            return; // allow in debug if token not set
        }
        if (!$expected || !hash_equals($expected, (string)$token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

    public function generateFees()
    {
        header('Content-Type: application/json');
        $this->checkToken();

        try {
            $buildingId = (int)($_GET['building_id'] ?? 0);
            $period = $_GET['period'] ?? date('Y-m');
            if ($buildingId <= 0) {
                echo json_encode(['success' => false, 'error' => 'building_id required']);
                return;
            }

            $model = new ManagementFee();
            $count = $model->generateForPeriod($buildingId, $period);
            echo json_encode(['success' => true, 'generated' => $count, 'period' => $period]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function calculateLateFees()
    {
        header('Content-Type: application/json');
        $this->checkToken();
        try {
            $buildingId = !empty($_GET['building_id']) ? (int)$_GET['building_id'] : null;
            $model = new ManagementFee();
            $updated = $model->calculateLateFees($buildingId);
            echo json_encode(['success' => true, 'updated' => $updated]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}


