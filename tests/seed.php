<?php
/**
 * Test Data Seeding Endpoint
 * 
 * This endpoint is for TEST ENVIRONMENT ONLY.
 * It provides API-based test data creation for faster E2E test execution.
 * 
 * SECURITY: This endpoint should be disabled in production.
 * Add authentication/authorization checks as needed.
 */

// Only allow in test/development environment
if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(403);
    echo json_encode(['error' => 'Test seeding endpoint is disabled in production']);
    exit;
}

// Only allow in test environment
if (($_ENV['APP_ENV'] ?? 'production') !== 'test') {
    http_response_code(403);
    echo json_encode(['error' => 'Test seeding endpoint is only available in test environment']);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $options = $input['options'] ?? [];
    
    try {
        switch ($type) {
            case 'building':
                $name = $options['name'] ?? 'Test Building ' . time();
                $address = $options['address'] ?? 'Test Address';
                
                $buildingId = $db->insert('buildings', [
                    'name' => $name,
                    'address' => $address,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                echo json_encode(['success' => true, 'id' => $buildingId, 'type' => 'building']);
                break;
                
            case 'unit':
                $buildingId = $options['building_id'] ?? null;
                $unitNumber = $options['unit_number'] ?? 'Unit-' . time();
                
                if (!$buildingId) {
                    // Get first available building
                    $building = $db->fetch("SELECT id FROM buildings LIMIT 1");
                    $buildingId = $building['id'] ?? null;
                }
                
                if (!$buildingId) {
                    throw new Exception('No building available for unit creation');
                }
                
                $unitId = $db->insert('units', [
                    'building_id' => $buildingId,
                    'unit_number' => $unitNumber,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                echo json_encode(['success' => true, 'id' => $unitId, 'type' => 'unit']);
                break;
                
            case 'job':
                $unitId = $options['unit_id'] ?? null;
                $title = $options['title'] ?? 'Test Job ' . time();
                
                if (!$unitId) {
                    // Get first available unit
                    $unit = $db->fetch("SELECT id FROM units LIMIT 1");
                    $unitId = $unit['id'] ?? null;
                }
                
                if (!$unitId) {
                    throw new Exception('No unit available for job creation');
                }
                
                $jobId = $db->insert('jobs', [
                    'unit_id' => $unitId,
                    'title' => $title,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                echo json_encode(['success' => true, 'id' => $jobId, 'type' => 'job']);
                break;
                
            case 'fee':
                $unitId = $options['unit_id'] ?? null;
                $amount = $options['amount'] ?? 100;
                
                if (!$unitId) {
                    // Get first available unit
                    $unit = $db->fetch("SELECT id FROM units LIMIT 1");
                    $unitId = $unit['id'] ?? null;
                }
                
                if (!$unitId) {
                    throw new Exception('No unit available for fee creation');
                }
                
                $feeId = $db->insert('management_fees', [
                    'unit_id' => $unitId,
                    'amount' => $amount,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                echo json_encode(['success' => true, 'id' => $feeId, 'type' => 'fee']);
                break;
                
            default:
                throw new Exception("Unknown data type: {$type}");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // GET request - return endpoint info
    echo json_encode([
        'endpoint' => '/tests/seed',
        'method' => 'POST',
        'available_types' => ['building', 'unit', 'job', 'fee'],
        'note' => 'This endpoint is for test environment only'
    ]);
}

