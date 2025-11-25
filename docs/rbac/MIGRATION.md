# RBAC System Migration Guide

## Overview

This guide explains how to migrate from the old permission system to the new RBAC system.

## Prerequisites

1. Database migration `028_role_management_schema.sql` must be run
2. All roles must exist in the `roles` table (synced from `config/roles.php`)

## Migration Steps

### Step 1: Sync Permissions from Config

First, sync all capabilities from `config/roles.php` to the `permissions` table:

```bash
# Dry run to see what would be done
php scripts/sync_permissions_from_config.php --dry-run

# Apply changes
php scripts/sync_permissions_from_config.php
```

This will:
- Extract all unique capabilities from `config/roles.php`
- Create entries in the `permissions` table
- Categorize permissions automatically
- Skip existing permissions

### Step 2: Assign Permissions to Roles

Next, assign permissions to roles based on `config/roles.php`:

```bash
# Dry run to see what would be done
php scripts/assign_permissions_to_roles.php --dry-run

# Apply changes (keeps existing assignments)
php scripts/assign_permissions_to_roles.php

# Or clear existing assignments first (use with caution!)
php scripts/assign_permissions_to_roles.php --clear-existing
```

This will:
- Map capabilities from each role definition to permissions
- Handle wildcard capabilities (e.g., `operations.*`)
- Create `role_permissions` entries
- Clear permission cache automatically

### Step 3: Verify Permissions

Check that permissions are correctly assigned:

```sql
-- Check permission counts per role
SELECT r.name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name
ORDER BY r.name;

-- Check specific role permissions
SELECT p.name, p.category
FROM permissions p
JOIN role_permissions rp ON p.id = rp.permission_id
JOIN roles r ON rp.role_id = r.id
WHERE r.name = 'ADMIN'
ORDER BY p.category, p.name;
```

### Step 4: Test Access Control

Run the RBAC test suite:

```bash
php tests/functional/RbacAccessTest.php
```

All tests should pass. If any fail, check:
- Roles exist in database
- Permissions are assigned correctly
- User roles are set correctly

## Updating Permissions

### Adding New Capabilities

1. Add capability to `config/roles.php` in the appropriate role(s)
2. Run sync script: `php scripts/sync_permissions_from_config.php`
3. Run assignment script: `php scripts/assign_permissions_to_roles.php`
4. Update controllers to use new capability

### Removing Capabilities

1. Remove from `config/roles.php`
2. Optionally remove from database (manual SQL or script)
3. Update controllers to remove capability checks

### Modifying Role Permissions

1. Update `config/roles.php`
2. Run assignment script with `--clear-existing` to reset:
   ```bash
   php scripts/assign_permissions_to_roles.php --clear-existing
   ```

## Troubleshooting

### Permission Not Working

1. Check if permission exists:
   ```sql
   SELECT * FROM permissions WHERE name = 'your.capability';
   ```

2. Check if assigned to role:
   ```sql
   SELECT r.name, p.name
   FROM roles r
   JOIN role_permissions rp ON r.id = rp.role_id
   JOIN permissions p ON rp.permission_id = p.id
   WHERE p.name = 'your.capability';
   ```

3. Clear permission cache:
   ```php
   Permission::clearCache();
   ```

4. Check user's role:
   ```sql
   SELECT id, username, role FROM users WHERE id = ?;
   ```

### Role Not Accessing Resource

1. Verify role hierarchy (higher hierarchy can access lower):
   ```sql
   SELECT name, hierarchy_level FROM roles ORDER BY hierarchy_level DESC;
   ```

2. Check if role is in required group:
   ```php
   // In config/roles.php, check groups section
   'nav.operations.core' => ['ADMIN', 'OPERATOR', 'SUPPORT']
   ```

3. Verify SUPERADMIN override is working (should have access to everything)

### Cache Issues

If permissions seem stale:

```php
// Clear all permission caches
Permission::clearCache();

// Or clear specific user cache
unset($_SESSION['permissions']);
```

## Best Practices

1. **Always use dry-run first**: Test changes before applying
2. **Version control config**: Keep `config/roles.php` in version control
3. **Document custom permissions**: Add comments for non-standard capabilities
4. **Test after changes**: Run test suite after permission updates
5. **Monitor logs**: Check for permission-related errors in logs

## Rollback

If you need to rollback:

1. Restore `config/roles.php` from version control
2. Run assignment script with `--clear-existing`:
   ```bash
   php scripts/assign_permissions_to_roles.php --clear-existing
   ```
3. Clear permission cache
4. Test access control

## Production Deployment

Before deploying to production:

1. ✅ Run sync script in dry-run mode
2. ✅ Run assignment script in dry-run mode
3. ✅ Review changes carefully
4. ✅ Backup database
5. ✅ Run sync script (without dry-run)
6. ✅ Run assignment script (without dry-run)
7. ✅ Run test suite
8. ✅ Monitor error logs for 24 hours

