<?php
/**
 * Create crawl test users
 * 
 * This script creates test users for crawl testing.
 * These users are used by the crawl system to test different roles.
 * 
 * Usage: php scripts/create_crawl_test_users.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();

// Get password from environment or use default for development
$password = getenv('CRAWL_TEST_PASSWORD');
if (empty($password)) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $password = '12dream21'; // Default for development only
        echo "WARNING: Using default password '12dream21' for development.\n";
        echo "Set CRAWL_TEST_PASSWORD environment variable in production.\n\n";
    } else {
        die("ERROR: CRAWL_TEST_PASSWORD environment variable is not set.\n");
    }
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

// Test users for crawl testing
$testUsers = [
    ['username' => 'test_superadmin', 'role' => 'SUPERADMIN', 'email' => 'test_superadmin@crawl.test'],
    ['username' => 'test_admin', 'role' => 'ADMIN', 'email' => 'test_admin@crawl.test'],
    ['username' => 'test_operator', 'role' => 'OPERATOR', 'email' => 'test_operator@crawl.test'],
    ['username' => 'test_site_manager', 'role' => 'SITE_MANAGER', 'email' => 'test_site_manager@crawl.test'],
    ['username' => 'test_finance', 'role' => 'FINANCE', 'email' => 'test_finance@crawl.test'],
    ['username' => 'test_support', 'role' => 'SUPPORT', 'email' => 'test_support@crawl.test'],
];

echo "Creating crawl test users...\n";
echo str_repeat('=', 60) . "\n\n";

$results = [];

foreach ($testUsers as $userData) {
    $username = $userData['username'];
    $role = $userData['role'];
    $email = $userData['email'];
    
    // Check if user exists
    $existing = $db->fetch('SELECT id, is_active FROM users WHERE username = ?', [$username]);
    
    if ($existing) {
        // Update existing user
        $db->update('users', [
            'password_hash' => $passwordHash,
            'role' => $role,
            'email' => $email,
            'is_active' => 1,
            'updated_at' => $now,
        ], 'id = ?', [$existing['id']]);
        
        $status = $existing['is_active'] == 1 ? 'updated (was active)' : 'updated and activated';
        $results[] = ['username' => $username, 'role' => $role, 'status' => $status];
        echo "✓ {$username} ({$role}): {$status}\n";
    } else {
        // Create new user
        $userId = $db->insert('users', [
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
            'email' => $email,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $results[] = ['username' => $username, 'role' => $role, 'status' => 'created'];
        echo "✓ {$username} ({$role}): created\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Summary:\n";
echo "  Total users: " . count($results) . "\n";
echo "  Created: " . count(array_filter($results, fn($r) => $r['status'] === 'created')) . "\n";
echo "  Updated: " . count(array_filter($results, fn($r) => $r['status'] !== 'created')) . "\n";
echo "\nAll test users are ready for crawl testing.\n";
echo "Password: " . (defined('APP_DEBUG') && APP_DEBUG ? $password : '***') . "\n";




