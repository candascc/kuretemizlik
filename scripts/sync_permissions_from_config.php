<?php
/**
 * Permission Sync Script
 * Syncs capabilities from config/roles.php to permissions table
 * 
 * Usage: 
 *   php scripts/sync_permissions_from_config.php [--dry-run]
 * 
 * Options:
 *   --dry-run    Show what would be done without making changes
 */

require_once __DIR__ . '/../config/config.php';

$dryRun = in_array('--dry-run', $argv ?? []);

// Load roles config
$configPath = __DIR__ . '/../config/roles.php';
if (!file_exists($configPath)) {
    die("ERROR: config/roles.php not found\n");
}

$rolesConfig = require $configPath;
$roles = $rolesConfig['roles'] ?? [];

if (empty($roles)) {
    die("ERROR: No roles found in config\n");
}

$db = Database::getInstance();

// Check if permissions table exists
try {
    $db->query("SELECT 1 FROM permissions LIMIT 1");
} catch (Exception $e) {
    die("ERROR: permissions table does not exist. Please run migration 028_role_management_schema.sql first.\n");
}

// Collect all unique capabilities
$allCapabilities = [];
$capabilityCategories = [];

foreach ($roles as $roleName => $roleDef) {
    $capabilities = $roleDef['capabilities'] ?? [];
    
    foreach ($capabilities as $capability) {
        // Skip wildcard capabilities (they are handled differently)
        if (strpos($capability, '.*') !== false) {
            continue;
        }
        
        if (!in_array($capability, $allCapabilities, true)) {
            $allCapabilities[] = $capability;
            
            // Determine category from capability name
            $parts = explode('.', $capability);
            $category = $parts[0] ?? 'general';
            
            // Map common prefixes to categories
            $categoryMap = [
                'system' => 'core',
                'operations' => 'operations',
                'management' => 'management',
                'finance' => 'finance',
                'security' => 'security',
                'api' => 'api',
                'tenant' => 'core',
                'customers' => 'operations',
                'jobs' => 'operations',
                'reports' => 'analytics',
                'analytics' => 'analytics',
                'resident' => 'resident',
                'portal' => 'portal',
                'communications' => 'support',
                'residents' => 'support',
            ];
            
            $category = $categoryMap[$category] ?? 'general';
            $capabilityCategories[$capability] = $category;
        }
    }
}

echo "Found " . count($allCapabilities) . " unique capabilities\n";

// Insert capabilities into permissions table
$inserted = 0;
$skipped = 0;
$errors = 0;

foreach ($allCapabilities as $capability) {
    $category = $capabilityCategories[$capability] ?? 'general';
    
    // Generate description from capability name
    $description = ucfirst(str_replace('.', ' ', $capability));
    
        try {
            // Check if permission already exists
            $existing = $db->fetch("SELECT id FROM permissions WHERE name = ?", [$capability]);
            
            if ($existing) {
                // Update category if different
                if (!$dryRun) {
                    $db->update('permissions', [
                        'category' => $category,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'name = ?', [$capability]);
                }
                $skipped++;
                if ($dryRun) {
                    echo "  ~ Would update: {$capability} (category: {$category})\n";
                }
                continue;
            }
            
            // Insert new permission
            if ($dryRun) {
                echo "  + Would add: {$capability} (category: {$category})\n";
            } else {
                $db->query(
                    "INSERT INTO permissions (name, description, category, is_system_permission, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $capability,
                        $description,
                        $category,
                        1, // System permission
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s')
                    ]
                );
            }
            $inserted++;
            if (!$dryRun) {
                echo "  + Added: {$capability} (category: {$category})\n";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "  ERROR: Failed to insert {$capability}: " . $e->getMessage() . "\n";
        }
}

// Clear permission cache
if (!$dryRun && class_exists('Permission')) {
    Permission::clearCache();
}

echo "\n";
if ($dryRun) {
    echo "=== DRY RUN MODE - No changes were made ===\n";
}
echo "Summary:\n";
echo "  " . ($dryRun ? "Would insert" : "Inserted") . ": {$inserted}\n";
echo "  Skipped (already exists): {$skipped}\n";
echo "  Errors: {$errors}\n";
echo "\n";
if (!$dryRun) {
    echo "Next step: Run scripts/assign_permissions_to_roles.php to assign permissions to roles\n";
} else {
    echo "Run without --dry-run to apply changes\n";
}

