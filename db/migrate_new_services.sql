-- Migration: Add new services
-- Date: 2025-01-27
-- Description: Add Mağaza Temizliği, İnşaat Sonrası Temizlik, and Site Yönetimi services

-- Insert new services
INSERT INTO services (name, duration_min, default_fee, is_active, created_at) VALUES
('Mağaza Temizliği', 150, 180.00, 1, datetime('now')),
('İnşaat Sonrası Temizlik', 240, 300.00, 1, datetime('now')),
('Site Yönetimi', 60, 120.00, 1, datetime('now'));

-- Verify the insertion
SELECT 'Migration completed successfully' as status;
SELECT COUNT(*) as total_services FROM services;
SELECT name, duration_min, default_fee FROM services WHERE name IN ('Mağaza Temizliği', 'İnşaat Sonrası Temizlik', 'Site Yönetimi');
