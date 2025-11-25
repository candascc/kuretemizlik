CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    scope TEXT NOT NULL DEFAULT 'staff',
    hierarchy_level INTEGER NOT NULL DEFAULT 0,
    parent_role TEXT,
    is_system_role INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    category TEXT NOT NULL DEFAULT 'general',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

CREATE TABLE IF NOT EXISTS user_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(user_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_roles_scope ON roles(scope);
CREATE INDEX IF NOT EXISTS idx_permissions_category ON permissions(category);
CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions(permission_id);
CREATE INDEX IF NOT EXISTS idx_user_permissions_user ON user_permissions(user_id);

-- Seed core roles based on current configuration
INSERT OR IGNORE INTO roles (name, description, scope, hierarchy_level, is_system_role)
VALUES 
    ('SUPERADMIN', 'Sistem Yöneticisi', 'staff', 100, 1),
    ('ADMIN', 'Operasyon Yöneticisi', 'staff', 90, 1),
    ('OPERATOR', 'Operasyon Uzmanı', 'staff', 70, 1),
    ('SITE_MANAGER', 'Site Yöneticisi', 'staff', 60, 1),
    ('FINANCE', 'Finans Uzmanı', 'staff', 60, 1),
    ('SUPPORT', 'Destek Uzmanı', 'staff', 50, 1),
    ('MANAGEMENT', 'Yönetim Analisti', 'staff', 40, 1),
    ('RESIDENT_OWNER', 'Kat Maliki', 'resident_portal', 30, 1),
    ('RESIDENT_TENANT', 'Kiracı', 'resident_portal', 20, 1),
    ('RESIDENT_BOARD', 'Yönetim Kurulu', 'resident_portal', 35, 1),
    ('RESIDENT_GUEST', 'Misafir', 'resident_portal', 10, 1),
    ('CUSTOMER_STANDARD', 'Standart Müşteri', 'customer_portal', 20, 1),
    ('CUSTOMER_VIP', 'VIP Müşteri', 'customer_portal', 30, 1),
    ('CUSTOMER_CORPORATE', 'Kurumsal Müşteri', 'customer_portal', 40, 1);

-- Seed high-level permissions (can be extended via UI)
INSERT OR IGNORE INTO permissions (name, description, category)
VALUES
    ('dashboard.view', 'Dashboard ekranlarını görüntüleme', 'core'),
    ('jobs.manage', 'İş kayıtlarını oluşturma ve güncelleme', 'operations'),
    ('customers.manage', 'Müşteri kayıtlarını düzenleme', 'operations'),
    ('finance.collect', 'Ödemeleri görüntüleme ve tahsilat yapma', 'finance'),
    ('reports.view', 'Raporları görüntüleme', 'analytics'),
    ('portal.support', 'Portal destek taleplerini yanıtlama', 'support');

