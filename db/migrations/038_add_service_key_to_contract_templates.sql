-- Migration: 038_add_service_key_to_contract_templates
-- contract_templates tablosuna service_key alanı ve indexler ekleme
-- Tarih: 2025-01-XX
-- Açıklama: Hizmet bazlı sözleşme şablonu desteği için service_key alanı ekleniyor

-- service_key alanını ekle (NULL olabilir - genel template'ler için)
ALTER TABLE contract_templates ADD COLUMN service_key TEXT NULL;

-- service_key için index
CREATE INDEX IF NOT EXISTS idx_contract_templates_service_key 
ON contract_templates(service_key);

-- Composite index (type + service_key + is_active) - performans için
CREATE INDEX IF NOT EXISTS idx_contract_templates_type_service_active 
ON contract_templates(type, service_key, is_active);

-- Mevcut kayıtlar için service_key NULL kalacak (genel template olarak)
-- Bu migration geriye dönük uyumludur, mevcut kayıtları etkilemez

