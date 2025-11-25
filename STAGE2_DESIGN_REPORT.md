# AŞAMA 2 RAPORU - HİZMET BAZLI SÖZLEŞME ŞABLONU TASARIMI

## 1. VERİTABANI TASARIMI

### contract_templates Tablosuna Eklenecek Alan

```sql
service_key TEXT NULL
```

**Açıklama**:
- **Tip**: `TEXT NULL` (NULL değer alabilir)
- **Amaç**: Hizmet tipine özgü template'leri işaretlemek
- **NULL değer**: Genel template'ler için (fallback)
- **Dolu değer**: Service-specific template'ler için

**Index**: 
```sql
CREATE INDEX IF NOT EXISTS idx_contract_templates_service_key 
ON contract_templates(service_key);
```

**Composite Index** (performans için):
```sql
CREATE INDEX IF NOT EXISTS idx_contract_templates_type_service_active 
ON contract_templates(type, service_key, is_active);
```

### Service Key Değerleri (Enum/Config)

**Önerilen service_key değerleri**:

```php
// src/Config/ServiceKeys.php (yeni dosya)
class ServiceKeys
{
    const HOUSE_CLEANING = 'house_cleaning';
    const OFFICE_CLEANING = 'office_cleaning';
    const WINDOW_CLEANING = 'window_cleaning';
    const POST_CONSTRUCTION = 'post_construction';
    const STORE_CLEANING = 'store_cleaning';
    const SITE_COMMON_AREAS = 'site_common_areas';
    const MANAGEMENT_SERVICE = 'management_service';
    
    // Service name → service_key mapping
    public static function normalizeServiceName(string $serviceName): ?string
    {
        $mapping = [
            'ev temizliği' => self::HOUSE_CLEANING,
            'ev temizlik' => self::HOUSE_CLEANING,
            'ofis temizliği' => self::OFFICE_CLEANING,
            'ofis temizlik' => self::OFFICE_CLEANING,
            'iş yeri temizliği' => self::OFFICE_CLEANING,
            'cam temizliği' => self::WINDOW_CLEANING,
            'pencere temizliği' => self::WINDOW_CLEANING,
            'cam temizlik' => self::WINDOW_CLEANING,
            'inşaat sonrası temizlik' => self::POST_CONSTRUCTION,
            'inşaat sonrası' => self::POST_CONSTRUCTION,
            'taşınma sonrası temizlik' => self::POST_CONSTRUCTION,
            'mağaza temizliği' => self::STORE_CLEANING,
            'mağaza temizlik' => self::STORE_CLEANING,
            'site temizliği' => self::SITE_COMMON_AREAS,
            'ortak alan temizliği' => self::SITE_COMMON_AREAS,
            'site yönetimi' => self::MANAGEMENT_SERVICE,
            'apartman yönetimi' => self::MANAGEMENT_SERVICE,
            'yönetim hizmeti' => self::MANAGEMENT_SERVICE,
        ];
        
        $normalized = mb_strtolower(trim($serviceName), 'UTF-8');
        return $mapping[$normalized] ?? null;
    }
}
```

**Alternatif**: Bu mapping'i `ContractTemplateService` içinde private method olarak da tutabiliriz.

## 2. TEMPLATE SEÇİM ALGORİTMASI

### Algoritma Adımları (Detaylı)

```
FUNCTION getTemplateForJob(job, customer):
    
    // 1. Job'dan service bilgisini al
    service_id = job['service_id']
    IF service_id IS NULL:
        RETURN getDefaultCleaningJobTemplate()  // Service yoksa genel template
    
    // 2. Service kaydını getir
    service = Service::find(service_id)
    IF service IS NULL:
        RETURN getDefaultCleaningJobTemplate()  // Service bulunamazsa genel template
    
    // 3. Service name'den service_key türet
    service_key = ServiceKeys::normalizeServiceName(service['name'])
    IF service_key IS NULL:
        // Service name mapping'de yok, genel template'e düş
        RETURN getDefaultCleaningJobTemplate()
    
    // 4. Service-specific template ara
    template = ContractTemplate::findByTypeAndServiceKey(
        type: 'cleaning_job',
        service_key: service_key,
        is_active: true
    )
    
    IF template IS NOT NULL:
        RETURN template
    
    // 5. Fallback: Genel template (service_key IS NULL)
    RETURN getDefaultCleaningJobTemplate()
    
END FUNCTION
```

### ContractTemplate Model'e Eklenecek Metod

```php
/**
 * Type ve service_key'ye göre template bul
 * 
 * @param string $type Template type (örn: 'cleaning_job')
 * @param string|null $serviceKey Service key (null = genel template)
 * @param bool $activeOnly Sadece aktif template'ler
 * @return array|null
 */
public function findByTypeAndServiceKey(
    string $type, 
    ?string $serviceKey = null, 
    bool $activeOnly = true
): ?array {
    $sql = "SELECT * FROM contract_templates 
            WHERE type = ?";
    $params = [$type];
    
    if ($serviceKey !== null) {
        $sql .= " AND service_key = ?";
        $params[] = $serviceKey;
    } else {
        $sql .= " AND service_key IS NULL";
    }
    
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    
    $sql .= " ORDER BY is_default DESC, version DESC LIMIT 1";
    
    return $this->db->fetch($sql, $params);
}
```

## 3. CONTRACTTEMPLATESERVICE GÜNCELLEMELERİ

### Yeni Metodlar

#### 3.1. `getCleaningJobTemplateForService(string $serviceKey): ?array`

```php
/**
 * Belirli bir service_key için temizlik işi şablonu getir
 * 
 * @param string $serviceKey Service key (örn: 'house_cleaning')
 * @return array|null Şablon kaydı veya null
 */
public function getCleaningJobTemplateForService(string $serviceKey): ?array
{
    return $this->templateModel->findByTypeAndServiceKey(
        'cleaning_job',
        $serviceKey,
        true
    );
}
```

#### 3.2. `getTemplateForJob(array $job, ?array $customer = null): ?array`

```php
/**
 * Bir iş için uygun sözleşme şablonunu getir
 * 
 * Seçim sırası:
 * 1. Job'ın service_id'sine göre service-specific template
 * 2. Fallback: Genel default template (service_key IS NULL)
 * 3. Fallback: Herhangi bir aktif cleaning_job template
 * 
 * @param array $job Job kaydı (service_id, service_name içermeli)
 * @param array|null $customer Customer kaydı (opsiyonel)
 * @return array|null Şablon kaydı veya null
 */
public function getTemplateForJob(array $job, ?array $customer = null): ?array
{
    // 1. Service kontrolü
    if (empty($job['service_id'])) {
        // Service yok, genel template'e düş
        return $this->getDefaultCleaningJobTemplate();
    }
    
    // 2. Service kaydını getir
    $serviceModel = new Service();
    $service = $serviceModel->find($job['service_id']);
    
    if (!$service) {
        // Service bulunamadı, genel template'e düş
        error_log("Service not found for job {$job['id']}, service_id: {$job['service_id']}");
        return $this->getDefaultCleaningJobTemplate();
    }
    
    // 3. Service name'den service_key türet
    $serviceKey = $this->normalizeServiceName($service['name']);
    
    if (!$serviceKey) {
        // Service name mapping'de yok, genel template'e düş
        error_log("Service name '{$service['name']}' not mapped to service_key for job {$job['id']}");
        return $this->getDefaultCleaningJobTemplate();
    }
    
    // 4. Service-specific template ara
    $template = $this->getCleaningJobTemplateForService($serviceKey);
    
    if ($template) {
        return $template;
    }
    
    // 5. Fallback: Genel default template
    return $this->getDefaultCleaningJobTemplate();
}
```

#### 3.3. `normalizeServiceName(string $serviceName): ?string` (Private Helper)

```php
/**
 * Service name'den service_key türet
 * 
 * @param string $serviceName Service adı (örn: "Ev Temizliği")
 * @return string|null Service key (örn: "house_cleaning") veya null
 */
private function normalizeServiceName(string $serviceName): ?string
{
    // ServiceKeys::normalizeServiceName() kullanılabilir
    // VEYA burada inline mapping
    $mapping = [
        'ev temizliği' => 'house_cleaning',
        'ev temizlik' => 'house_cleaning',
        'ofis temizliği' => 'office_cleaning',
        'ofis temizlik' => 'office_cleaning',
        'iş yeri temizliği' => 'office_cleaning',
        'cam temizliği' => 'window_cleaning',
        'pencere temizliği' => 'window_cleaning',
        'cam temizlik' => 'window_cleaning',
        'inşaat sonrası temizlik' => 'post_construction',
        'inşaat sonrası' => 'post_construction',
        'taşınma sonrası temizlik' => 'post_construction',
        'mağaza temizliği' => 'store_cleaning',
        'mağaza temizlik' => 'store_cleaning',
        'site temizliği' => 'site_common_areas',
        'ortak alan temizliği' => 'site_common_areas',
        'site yönetimi' => 'management_service',
        'apartman yönetimi' => 'management_service',
        'yönetim hizmeti' => 'management_service',
    ];
    
    $normalized = mb_strtolower(trim($serviceName), 'UTF-8');
    return $mapping[$normalized] ?? null;
}
```

#### 3.4. `createJobContractForJob()` Güncellemesi

**Mevcut kod**:
```php
// Varsayılan şablonu getir
$template = $this->getDefaultCleaningJobTemplate();
```

**Yeni kod**:
```php
// İş için uygun şablonu getir (service-specific veya genel)
$template = $this->getTemplateForJob($jobRecord, $customer);
```

**Geriye uyumluluk**: `getDefaultCleaningJobTemplate()` metodu korunur, sadece `createJobContractForJob()` içinde `getTemplateForJob()` kullanılır.

## 4. FALLBACK STRATEJİSİ

### Fallback Sırası (Öncelik)

1. **Service-specific template** (service_key = 'house_cleaning' gibi)
   - `type = 'cleaning_job'`
   - `service_key = [türetilen_key]`
   - `is_active = 1`
   - `ORDER BY is_default DESC, version DESC`

2. **Genel default template** (service_key IS NULL)
   - `type = 'cleaning_job'`
   - `service_key IS NULL`
   - `is_default = 1`
   - `is_active = 1`

3. **Herhangi bir aktif template** (son çare)
   - `type = 'cleaning_job'`
   - `is_active = 1`
   - `ORDER BY is_default DESC, version DESC, created_at DESC`

4. **NULL / Exception** (hiçbiri yoksa)
   - Loglama yapılır
   - Exception fırlatılır veya null döndürülür (proje pattern'ine göre)

### Loglama Stratejisi

```php
// Service-specific template bulunamadığında
error_log("Service-specific template not found: service_key={$serviceKey}, job_id={$job['id']}");

// Genel template bulunamadığında (kritik)
error_log("CRITICAL: No default cleaning_job template found! job_id={$job['id']}");
if (class_exists('Logger')) {
    Logger::error('No contract template available', [
        'job_id' => $job['id'],
        'service_id' => $job['service_id'],
        'service_name' => $service['name'] ?? null,
        'service_key' => $serviceKey ?? null,
    ]);
}
```

## 5. HİZMETLER SAYFASI ENTEGRASYONU

### Service → Template Eşleşmesi

**Yaklaşım**: Service name'den service_key türet, template'i bul

```php
// ServiceController::edit() veya yeni show() metodunda
$service = $this->serviceModel->find($id);

// Service name'den service_key türet
$contractTemplateService = new ContractTemplateService();
$serviceKey = $contractTemplateService->normalizeServiceName($service['name']); // Public method olmalı

// Template'i bul
$template = null;
if ($serviceKey) {
    $template = $contractTemplateService->getCleaningJobTemplateForService($serviceKey);
}

// Fallback: Genel template
if (!$template) {
    $template = $contractTemplateService->getDefaultCleaningJobTemplate();
}
```

### Hizmet Form/Detay Sayfasına Eklenecek Bölüm

**Konum**: `src/Views/services/form.php` (edit modunda)

**Yeni Bölüm**: "Hizmet Sözleşme Şablonu" (Temel Bilgiler'den sonra)

**İçerik**:
- Template varsa: Şablon adı, versiyon, aktiflik, önizleme, "Düzenle" butonu
- Template yoksa: Bilgi mesajı, "Yeni Şablon Oluştur" butonu

### Controller Aksiyonları

#### 5.1. `ServiceController::showContractTemplate($serviceId)`

```php
/**
 * Hizmet için sözleşme şablonunu göster/düzenle
 */
public function showContractTemplate($serviceId)
{
    Auth::requireAdmin(); // Sadece admin erişebilir
    
    $service = $this->serviceModel->find($serviceId);
    if (!$service) {
        View::notFound('Hizmet bulunamadı');
    }
    
    // Template'i bul
    $contractTemplateService = new ContractTemplateService();
    $serviceKey = $contractTemplateService->normalizeServiceName($service['name']);
    $template = null;
    
    if ($serviceKey) {
        $template = $contractTemplateService->getCleaningJobTemplateForService($serviceKey);
    }
    
    // View render
    echo View::renderWithLayout('services/contract_template', [
        'service' => $service,
        'serviceKey' => $serviceKey,
        'template' => $template,
        'flash' => Utils::getFlash(),
    ]);
}
```

#### 5.2. `ServiceController::updateContractTemplate($serviceId)`

```php
/**
 * Hizmet için sözleşme şablonunu güncelle/oluştur
 */
public function updateContractTemplate($serviceId)
{
    Auth::requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(base_url("/services/edit/{$serviceId}"));
    }
    
    if (!CSRF::verifyRequest()) {
        Utils::flash('error', 'Güvenlik hatası.');
        redirect(base_url("/services/edit/{$serviceId}"));
    }
    
    $service = $this->serviceModel->find($serviceId);
    if (!$service) {
        View::notFound('Hizmet bulunamadı');
    }
    
    // Service key türet
    $contractTemplateService = new ContractTemplateService();
    $serviceKey = $contractTemplateService->normalizeServiceName($service['name']);
    
    if (!$serviceKey) {
        Utils::flash('error', 'Bu hizmet için service key türetilemedi. Lütfen hizmet adını kontrol edin.');
        redirect(base_url("/services/edit/{$serviceId}"));
    }
    
    // Validation
    $validator = new Validator($_POST);
    $validator->required('template_text', 'Sözleşme metni zorunludur');
    
    if ($validator->fails()) {
        Utils::flash('error', $validator->firstError());
        redirect(base_url("/services/contract-template/{$serviceId}"));
    }
    
    // Mevcut template var mı kontrol et
    $templateModel = new ContractTemplate();
    $existingTemplate = $contractTemplateService->getCleaningJobTemplateForService($serviceKey);
    
    $templateData = [
        'type' => 'cleaning_job',
        'name' => $service['name'] . ' Hizmet Sözleşmesi',
        'service_key' => $serviceKey,
        'template_text' => $validator->get('template_text'),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_default' => 0, // Service-specific template'ler default olamaz
        'version' => $existingTemplate ? 
            $this->incrementVersion($existingTemplate['version']) : '1.0',
    ];
    
    if ($existingTemplate) {
        // Güncelle
        $result = $templateModel->update($existingTemplate['id'], $templateData);
        $action = 'güncellendi';
    } else {
        // Yeni oluştur
        $templateData['created_by'] = Auth::id();
        $result = $templateModel->create($templateData);
        $action = 'oluşturuldu';
    }
    
    if ($result) {
        Utils::flash('success', "Sözleşme şablonu başarıyla {$action}.");
    } else {
        Utils::flash('error', 'Sözleşme şablonu kaydedilemedi.');
    }
    
    redirect(base_url("/services/edit/{$serviceId}"));
}
```

## 6. PLACEHOLDER SİSTEMİ

### Mevcut Placeholder'lar (Korunacak)

- `{customer_name}`, `{customer_phone}`, `{customer_email}`
- `{job_id}`, `{job_date}`, `{job_time}`, `{job_datetime}`
- `{job_address}`, `{job_price}`, `{job_amount}`, `{job_total_amount}`
- `{service_type}`, `{service_name}` ✅ **Zaten var**
- `{job_description}`, `{job_status}`

### İleride Eklenebilecek Service-Specific Placeholder'lar

- `{service_duration}` - Hizmet süresi (dakika)
- `{service_default_fee}` - Hizmet varsayılan ücreti
- `{service_category}` - Hizmet kategorisi (eğer eklenirse)

**Not**: Bu placeholder'lar şimdilik eklenmeyecek, sadece `template_variables` JSON alanında saklanabilir.

## 7. VERSİYONLAMA STRATEJİSİ

### Version Format

- **Format**: `MAJOR.MINOR` (örn: `1.0`, `1.1`, `2.0`)
- **Increment Logic**:
  - Template metni değiştiğinde: MINOR artar (1.0 → 1.1)
  - Önemli değişikliklerde: MAJOR artar (1.1 → 2.0)

### Version Increment Helper

```php
/**
 * Version string'i increment et
 * 
 * @param string $currentVersion Mevcut versiyon (örn: "1.0")
 * @param bool $majorIncrement Major artır mı? (default: false = minor)
 * @return string Yeni versiyon
 */
private function incrementVersion(string $currentVersion, bool $majorIncrement = false): string
{
    $parts = explode('.', $currentVersion);
    $major = (int)($parts[0] ?? 1);
    $minor = (int)($parts[1] ?? 0);
    
    if ($majorIncrement) {
        return ($major + 1) . '.0';
    } else {
        return $major . '.' . ($minor + 1);
    }
}
```

## 8. ÖZET

### Eklenen/Güncellenen Dosyalar

1. **Migration**: `db/migrations/037_add_service_key_to_contract_templates.sql`
2. **Model**: `src/Models/ContractTemplate.php` - `findByTypeAndServiceKey()` metodu
3. **Service**: `src/Services/ContractTemplateService.php` - Yeni metodlar
4. **Controller**: `src/Controllers/ServiceController.php` - Template yönetim metodları
5. **View**: `src/Views/services/contract_template.php` - Yeni view (opsiyonel)
6. **View**: `src/Views/services/form.php` - Sözleşme şablonu bölümü eklenecek

### Template Seçim Mantığı

```
Job → service_id → Service → service_name → service_key → ContractTemplate
                                                              ↓ (bulunamazsa)
                                                    Genel Template (service_key IS NULL)
```

### Fallback Senaryoları

1. ✅ Service-specific template var → Kullan
2. ✅ Service-specific yok, genel default var → Genel default kullan
3. ✅ Hiçbiri yok → Log + Exception

### Hizmetler Sayfası Entegrasyonu

- Hizmet edit sayfasına "Sözleşme Şablonu" bölümü eklenecek
- Template görüntüleme/düzenleme butonları
- Yeni template oluşturma akışı

---

**Rapor Tarihi**: 2025-01-XX
**Hazırlayan**: AI Assistant
**Durum**: Tasarım Tamamlandı ✅

