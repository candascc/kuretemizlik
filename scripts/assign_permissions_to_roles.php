<?php
/**
 * Role-Permission Assignment Script
 * Assigns permissions from config/roles.php to roles in the database
 * 
 * Usage: 
 *   php scripts/assign_permissions_to_roles.php [--dry-run] [--clear-existing]
 * 
 * Options:
 *   --dry-run         Show what would be done without making changes
 *   --clear-existing  Remove existing role-permission assignments before assigning
 */

require_once __DIR__ . '/../config/config.php';

$dryRun = in_array('--dry-run', $argv ?? []);
$clearExisting = in_array('--clear-existing', $argv ?? []);

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

// Check if required tables exist
try {
    $db->query("SELECT 1 FROM roles LIMIT 1");
    $db->query("SELECT 1 FROM permissions LIMIT 1");
    $db->query("SELECT 1 FROM role_permissions LIMIT 1");
} catch (Exception $e) {
    die("ERROR: Required tables do not exist. Please run migration 028_role_management_schema.sql first.\n");
}

echo "Assigning permissions to roles...\n\n";

$totalAssigned = 0;
$totalSkipped = 0;
$errors = 0;

foreach ($roles as $roleName => $roleDef) {
    $capabilities = $roleDef['capabilities'] ?? [];
    
    if (empty($capabilities)) {
        echo "  Role {$roleName}: No capabilities defined, skipping\n";
        continue;
    }
    
    // Get role ID from database
    $role = $db->fetch("SELECT id FROM roles WHERE name = ?", [$roleName]);
    if (!$role) {
        echo "  Role {$roleName}: Not found in database, skipping\n";
        $errors++;
        continue;
    }
    
    $roleId = $role['id'];
    $assigned = 0;
    $skipped = 0;
    
    // Clear existing permissions for this role if requested
    if ($clearExisting && !$dryRun) {
        $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        echo "  Cleared existing permissions for {$roleName}\n";
    } elseif ($clearExisting && $dryRun) {
        echo "  Would clear existing permissions for {$roleName}\n";
    }
    
    foreach ($capabilities as $capability) {
        // Skip wildcard capabilities (they need special handling)
        if (strpos($capability, '.*') !== false) {
            // For wildcards, we need to find all permissions matching the prefix
            $prefix = str_replace('.*', '', $capability);
            $matchingPermissions = $db->fetchAll(
                "SELECT id FROM permissions WHERE name LIKE ?",
                [$prefix . '.%']
            );
            
            foreach ($matchingPermissions as $perm) {
                try {
                    // Check if already assigned
                    $existing = $db->fetch(
                        "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                        [$roleId, $perm['id']]
                    );
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }
                    
                    if ($dryRun) {
                        echo "    Would assign: {$capability} (via wildcard {$capability})\n";
                    } else {
                        $db->query(
                            "INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, ?)",
                            [$roleId, $perm['id'], date('Y-m-d H:i:s')]
                        );
                    }
                    $assigned++;
                } catch (Exception $e) {
                    // Ignore duplicate key errors
                    if (strpos($e->getMessage(), 'UNIQUE constraint') === false) {
                        echo "    ERROR assigning {$capability}: " . $e->getMessage() . "\n";
                        $errors++;
                    } else {
                        $skipped++;
                    }
                }
            }
            continue;
        }
        
        // Get permission ID
        $permission = $db->fetch("SELECT id FROM permissions WHERE name = ?", [$capability]);
        if (!$permission) {
            echo "    WARNING: Permission '{$capability}' not found in database (run sync_permissions_from_config.php first)\n";
            $errors++;
            continue;
        }
        
        $permissionId = $permission['id'];
        
        try {
            // Check if already assigned
            $existing = $db->fetch(
                "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permissionId]
            );
            
            if ($existing) {
                $skipped++;
                continue;
            }
            
            // Assign permission to role
            if ($dryRun) {
                echo "    Would assign: {$capability}\n";
            } else {
                $db->query(
                    "INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, ?)",
                    [$roleId, $permissionId, date('Y-m-d H:i:s')]
                );
            }
            $assigned++;
            
        } catch (Exception $e) {
            // Ignore duplicate key errors
            if (strpos($e->getMessage(), 'UNIQUE constraint') === false) {
                echo "    ERROR assigning {$capability} to {$roleName}: " . $e->getMessage() . "\n";
                $errors++;
            } else {
                $skipped++;
            }
        }
    }
    
    echo "  Role {$roleName}: Assigned {$assigned}, Skipped {$skipped}\n";
    $totalAssigned += $assigned;
    $totalSkipped += $skipped;
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
echo "  Total " . ($dryRun ? "would be assigned" : "assigned") . ": {$totalAssigned}\n";
echo "  Total skipped (already exists): {$totalSkipped}\n";
echo "  Errors: {$errors}\n";
echo "\n";
if (!$dryRun) {
    echo "Done! Permissions have been assigned to roles.\n";
} else {
    echo "Run without --dry-run to apply changes\n";
}

