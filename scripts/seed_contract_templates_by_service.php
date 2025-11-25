<?php
/**
 * Seed Contract Templates by Service
 * 
 * Bu script, services tablosundaki aktif hizmetler için
 * service-specific contract template'ler oluşturur.
 * 
 * İdempotent: Birden fazla kez çalıştırılabilir, sadece eksik template'ler oluşturulur.
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';
require __DIR__ . '/../src/Models/Service.php';
require __DIR__ . '/../src/Models/ContractTemplate.php';
require __DIR__ . '/../src/Services/ContractTemplateService.php';

$db = Database::getInstance();

echo "=== Contract Templates Seed Script ===\n\n";

// 1. Genel default template'i bul (template_text için)
$templateService = new ContractTemplateService();
$defaultTemplate = $templateService->getDefaultCleaningJobTemplate();

if (!$defaultTemplate) {
    echo "ERROR: Genel default template bulunamadı!\n";
    echo "Lütfen önce bir default cleaning_job template oluşturun.\n";
    exit(1);
}

echo "✓ Genel default template bulundu (ID: {$defaultTemplate['id']}, Name: {$defaultTemplate['name']})\n";
echo "  Template text uzunluğu: " . strlen($defaultTemplate['template_text'] ?? '') . " karakter\n\n";

// 2. Aktif hizmetleri getir (tüm company'ler için)
echo "Aktif hizmetler sorgulanıyor...\n";
$services = $db->fetchAll(
    "SELECT id, name, company_id, is_active 
     FROM services 
     WHERE is_active = 1 
     ORDER BY company_id, name"
);

if (empty($services)) {
    echo "WARNING: Hiç aktif hizmet bulunamadı!\n";
    exit(0);
}

echo "✓ " . count($services) . " aktif hizmet bulundu\n\n";

// 3. Her hizmet için işlem yap
$templateModel = new ContractTemplate();
$created = 0;
$skipped = 0;
$unmapped = [];

echo "Hizmetler işleniyor...\n";
echo str_repeat("=", 80) . "\n";

foreach ($services as $service) {
    $serviceId = $service['id'];
    $serviceName = $service['name'];
    $companyId = $service['company_id'];
    
    // Service name'den service_key türet
    $serviceKey = $templateService->normalizeServiceName($serviceName);
    
    if (!$serviceKey) {
        // Mapping'de yok
        $unmapped[] = [
            'service_id' => $serviceId,
            'service_name' => $serviceName,
            'company_id' => $companyId,
        ];
        echo sprintf(
            "⚠ [%d] %s (Company: %d) → service_key mapping'de yok (genel template kullanılacak)\n",
            $serviceId,
            $serviceName,
            $companyId
        );
        continue;
    }
    
    // Bu service_key için template var mı kontrol et
    $existing = $templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);
    
    if ($existing) {
        echo sprintf(
            "✓ [%d] %s → service_key: %s → Template zaten var (ID: %d, Name: %s)\n",
            $serviceId,
            $serviceName,
            $serviceKey,
            $existing['id'],
            $existing['name']
        );
        $skipped++;
        continue;
    }
    
    // Yeni template oluştur
    $templateName = $serviceName . ' Hizmet Sözleşmesi';
    
    try {
        $templateId = $templateModel->create([
            'type' => 'cleaning_job',
            'name' => $templateName,
            'version' => '1.0',
            'description' => sprintf('%s için özel sözleşme şablonu', $serviceName),
            'template_text' => $defaultTemplate['template_text'], // Genel template'in metnini kullan
            'template_variables' => $defaultTemplate['template_variables'], // Placeholder'ları koru
            'service_key' => $serviceKey,
            'is_active' => 1,
            'is_default' => 0, // Service-specific template'ler default olmamalı
            'content_hash' => null, // İleride hesaplanabilir
            'created_by' => null, // CLI script
        ]);
        
        if ($templateId) {
            echo sprintf(
                "✓ [%d] %s → service_key: %s → YENİ Template oluşturuldu (ID: %d, Name: %s)\n",
                $serviceId,
                $serviceName,
                $serviceKey,
                $templateId,
                $templateName
            );
            $created++;
        } else {
            echo sprintf(
                "✗ [%d] %s → service_key: %s → Template oluşturulamadı!\n",
                $serviceId,
                $serviceName,
                $serviceKey
            );
        }
        
    } catch (Exception $e) {
        echo sprintf(
            "✗ [%d] %s → service_key: %s → HATA: %s\n",
            $serviceId,
            $serviceName,
            $serviceKey,
            $e->getMessage()
        );
    }
}

echo str_repeat("=", 80) . "\n\n";

// 4. Özet rapor
echo "=== ÖZET ===\n";
echo "Toplam işlenen hizmet: " . count($services) . "\n";
echo "Yeni oluşturulan template: {$created}\n";
echo "Zaten mevcut template: {$skipped}\n";
echo "Mapping'de olmayan hizmet: " . count($unmapped) . "\n\n";

if (!empty($unmapped)) {
    echo "=== Mapping'de Olmayan Hizmetler ===\n";
    echo "Bu hizmetler için genel template kullanılacak.\n";
    echo "İleride normalizeServiceName() metoduna eklenebilir:\n\n";
    
    foreach ($unmapped as $item) {
        echo sprintf(
            "  - Service ID: %d, Name: '%s' (Company: %d)\n",
            $item['service_id'],
            $item['service_name'],
            $item['company_id']
        );
    }
    echo "\n";
}

// 5. Oluşturulan template'lerin listesi
if ($created > 0) {
    echo "=== Oluşturulan Template'ler ===\n";
    $newTemplates = $db->fetchAll(
        "SELECT id, name, service_key, created_at 
         FROM contract_templates 
         WHERE type = 'cleaning_job' 
           AND service_key IS NOT NULL 
           AND created_at >= datetime('now', '-1 minute')
         ORDER BY created_at DESC"
    );
    
    foreach ($newTemplates as $template) {
        echo sprintf(
            "  - Template ID: %d, Name: %s, service_key: %s\n",
            $template['id'],
            $template['name'],
            $template['service_key']
        );
    }
    echo "\n";
}

echo "✓ Seed işlemi tamamlandı!\n";
exit(0);

