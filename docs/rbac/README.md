# RBAC System Documentation

## Overview

The Role-Based Access Control (RBAC) system provides granular permission management for the application. It uses a combination of:
- **Roles**: User roles defined in `config/roles.php` and stored in the `roles` table
- **Capabilities**: Fine-grained permissions (e.g., `jobs.create`, `finance.collect`)
- **Groups**: Logical groupings of roles for navigation and access control (e.g., `nav.operations.core`)

## Architecture

### Configuration Layer (`config/roles.php`)

Defines all roles, their capabilities, and role groups:

```php
'roles' => [
    'ADMIN' => [
        'capabilities' => [
            'jobs.view', 'jobs.create', 'jobs.edit', 'jobs.delete',
            'customers.manage', 'finance.authorize', ...
        ],
    ],
],
'groups' => [
    'nav.operations.core' => ['ADMIN', 'OPERATOR', 'SUPPORT'],
    'nav.finance.core' => ['ADMIN', 'FINANCE'],
],
```

### Database Layer

- `roles` table: Stores role definitions
- `permissions` table: Stores capability definitions
- `role_permissions` table: Maps capabilities to roles
- `user_permissions` table: Direct user-to-permission assignments (optional)

### Application Layer

- `Auth` class: Provides access control methods
- `Permission` class: Manages permission checks and assignments
- `Roles` class: Loads and normalizes role definitions from config
- `RolePermissions` class: Legacy helper (deprecated, use `Auth::can()`)

## Usage

### In Controllers

#### Require Specific Capability
```php
public function create()
{
    Auth::requireCapability('jobs.create');
    // ... rest of method
}
```

#### Require Role Group
```php
public function index()
{
    Auth::requireGroup('nav.finance.core');
    // ... rest of method
}
```

#### Require Specific Role(s)
```php
public function adminOnly()
{
    Auth::requireAdmin(); // Checks for ADMIN or SUPERADMIN
    // OR
    Auth::requireRole(['ADMIN', 'SUPERADMIN']);
    // ... rest of method
}
```

### In Views

#### Check Permission
```php
<?php require_once __DIR__ . '/../helpers/permission.php'; ?>
<?php if (can('jobs.create')): ?>
    <a href="/jobs/new">Create Job</a>
<?php endif; ?>
```

#### Check Role
```php
<?php if (hasRole('ADMIN')): ?>
    <a href="/admin/settings">Settings</a>
<?php endif; ?>
```

#### Check Group
```php
<?php if (hasGroup('nav.operations.core')): ?>
    <a href="/jobs">Jobs</a>
<?php endif; ?>
```

## Permission Sync

After updating `config/roles.php`, sync permissions to the database:

```bash
# Step 1: Sync capabilities from config to permissions table (dry-run first!)
php scripts/sync_permissions_from_config.php --dry-run
php scripts/sync_permissions_from_config.php

# Step 2: Assign permissions to roles (dry-run first!)
php scripts/assign_permissions_to_roles.php --dry-run
php scripts/assign_permissions_to_roles.php

# Or clear existing assignments first (use with caution!)
php scripts/assign_permissions_to_roles.php --clear-existing
```

See [MIGRATION.md](MIGRATION.md) for detailed migration guide.

## Role Hierarchy

Roles have hierarchy levels defined in `config/roles.php`:
- SUPERADMIN: 100
- ADMIN: 90
- OPERATOR: 70
- SITE_MANAGER: 65
- FINANCE: 60
- SUPPORT: 55
- MANAGEMENT: 50

Higher hierarchy roles can access resources requiring lower hierarchy roles. For example, ADMIN (90) can access OPERATOR (70) resources.

## Capability Naming Convention

Capabilities follow a dot-notation pattern:
- `{module}.{action}` - e.g., `jobs.create`, `finance.collect`
- `{module}.*` - Wildcard for all actions in a module (e.g., `operations.*`)

Common modules:
- `jobs.*` - Job management
- `customers.*` - Customer management
- `finance.*` - Financial operations
- `reports.*` - Reporting
- `operations.*` - Operational tasks
- `management.*` - Management module
- `api.*` - API access

## Group Naming Convention

Groups are organized by purpose:
- `nav.*` - Navigation groups (for menu visibility)
- `quick.*` - Quick action groups
- `api.*` - API access groups

## Testing

Run the RBAC test suite:

```bash
php tests/functional/RbacAccessTest.php
```

Tests verify:
- Role-specific capability access
- Group-based access control
- SUPERADMIN full access
- Unauthenticated user restrictions
- Role hierarchy inheritance

## Migration from Legacy Code

### Old Way (Deprecated)
```php
if (Auth::role() === 'ADMIN') {
    // ...
}
```

### New Way
```php
if (Auth::hasGroup('nav.settings.admin')) {
    // ...
}
// OR
if (Auth::can('settings.company.edit')) {
    // ...
}
```

## Best Practices

1. **Use Groups for Navigation**: Use `requireGroup()` for page-level access control
2. **Use Capabilities for Actions**: Use `requireCapability()` for specific operations (create, edit, delete)
3. **Avoid Hardcoded Role Checks**: Use groups or capabilities instead
4. **Keep Config in Sync**: Run sync scripts after updating `config/roles.php`
5. **Test After Changes**: Run RBAC tests after modifying permissions

## Troubleshooting

### Permission Not Working
1. Check if permission exists in `permissions` table
2. Verify permission is assigned to role in `role_permissions` table
3. Run sync scripts to update database
4. Clear permission cache (if using cache)

### Role Not Accessing Resource
1. Check role hierarchy (higher hierarchy can access lower)
2. Verify role is in the required group
3. Check if SUPERADMIN override is needed
4. Review `Auth::requireRole()` hierarchy logic

### View Permission Check Failing
1. Ensure `require_once __DIR__ . '/../helpers/permission.php';` is called
2. Verify user is authenticated (`Auth::check()`)
3. Check capability name matches exactly (case-sensitive)

