<?php
/**
 * Test Data Cleanup Endpoint
 * 
 * This endpoint is for TEST ENVIRONMENT ONLY.
 * It provides API-based test data cleanup for E2E tests.
 * 
 * SECURITY: This endpoint should be disabled in production.
 */

// Only allow in test/development environment
if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(403);
    echo json_encode(['error' => 'Test cleanup endpoint is disabled in production']);
    exit;
}

// Only allow in test environment
if (($_ENV['APP_ENV'] ?? 'production') !== 'test') {
    http_response_code(403);
    echo json_encode(['error' => 'Test cleanup endpoint is only available in test environment']);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $id = $input['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID is required']);
        exit;
    }
    
    try {
        $tableMap = [
            'building' => 'buildings',
            'unit' => 'units',
            'job' => 'jobs',
            'fee' => 'management_fees'
        ];
        
        $table = $tableMap[$type] ?? null;
        
        if (!$table) {
            throw new Exception("Unknown data type: {$type}");
        }
        
        // Delete the record
        $deleted = $db->delete($table, ['id' => $id]);
        
        if ($deleted) {
            echo json_encode(['success' => true, 'deleted' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Record not found']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // GET request - return endpoint info
    echo json_encode([
        'endpoint' => '/tests/cleanup',
        'method' => 'POST',
        'available_types' => ['building', 'unit', 'job', 'fee'],
        'note' => 'This endpoint is for test environment only'
    ]);
}

