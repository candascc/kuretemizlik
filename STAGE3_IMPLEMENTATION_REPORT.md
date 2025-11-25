# AŞAMA 3 RAPORU - MİGRATION VE SERVİS GÜNCELLEMELERİ

## 1. MİGRATION DOSYASI

### Dosya Yolu
`db/migrations/038_add_service_key_to_contract_templates.sql`

### İçerik
```sql
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
```

### Özellikler
- ✅ Geriye dönük uyumlu (mevcut kayıtlar etkilenmez, service_key NULL kalır)
- ✅ Index'ler performans için eklendi
- ✅ Composite index ile type+service_key+is_active sorguları optimize edildi

## 2. MODEL GÜNCELLEMELERİ

### ContractTemplate Model

**Dosya**: `src/Models/ContractTemplate.php`

#### Eklenen Metod
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
): ?array
```

#### Güncellenen Metodlar
- **`create($data)`**: `service_key` alanı eklendi
- **`update($id, $data)`**: `service_key` alanı `$allowed` listesine eklendi
- **`all($filters)`**: `service_key` filtresi desteği eklendi

## 3. SERVİS GÜNCELLEMELERİ

### ContractTemplateService

**Dosya**: `src/Services/ContractTemplateService.php`

#### 3.1. normalizeServiceName() - Yeni Metod

**Amaç**: Service name'den service_key türetir (TEK KAYNAK - mapping burada)

**İmza**:
```php
public function normalizeServiceName(string $serviceName): ?string
```

**Mapping** (TEK YERDE TANIMLI):
- "Ev Temizliği" → `'house_cleaning'`
- "Ofis Temizliği" → `'office_cleaning'`
- "Cam Temizliği" → `'window_cleaning'`
- "İnşaat Sonrası Temizlik" → `'post_construction'`
- "Mağaza Temizliği" → `'store_cleaning'`
- "Site Temizliği" → `'site_common_areas'`
- "Site Yönetimi" → `'management_service'`

**Özellikler**:
- Case-insensitive (küçük harfe çevirir)
- Türkçe karakter desteği (UTF-8)
- Trim yapar
- Mapping'de yoksa `null` döndürür

#### 3.2. getCleaningJobTemplateForService() - Yeni Metod

**Amaç**: Belirli bir service_key için template getirir

**İmza**:
```php
public function getCleaningJobTemplateForService(string $serviceKey): ?array
```

**Kullanım**:
```php
$template = $service->getCleaningJobTemplateForService('house_cleaning');
```

#### 3.3. getTemplateForJob() - Yeni Metod

**Amaç**: Bir iş için uygun template'i bulur (fallback mantığı ile)

**İmza**:
```php
public function getTemplateForJob(array $job, ?array $customer = null): ?array
```

**Fallback Sırası**:
1. Service-specific template (service_key = 'house_cleaning')
2. Genel default template (service_key IS NULL, is_default = 1)
3. Herhangi bir aktif cleaning_job template (son çare)
4. NULL (kritik durum, loglanır)

**Loglama**:
- Her fallback adımı loglanır
- Kritik durumlar (hiç template yok) Logger::error ile kaydedilir

#### 3.4. createJobContractForJob() - Güncellenen Metod

**Değişiklik**:
```php
// ESKİ:
$template = $this->getDefaultCleaningJobTemplate();

// YENİ:
$template = $this->getTemplateForJob($jobRecord, $customer);
```

**Sonuç**: Artık service-specific template kullanılıyor, yoksa genel template'e fallback yapılıyor.

## 4. ÖRNEK AKIŞ - JOB İÇİN TEMPLATE SEÇİMİ

### Senaryo 1: Ev Temizliği İşi (Service-specific template var)

```
1. Job oluşturuldu:
   - job_id = 123
   - service_id = 5
   - service_name = "Ev Temizliği"

2. createJobContractForJob($job) çağrıldı

3. getTemplateForJob($job) içinde:
   a) service_id = 5 kontrolü → VAR
   b) Service::find(5) → service = {id: 5, name: "Ev Temizliği"}
   c) normalizeServiceName("Ev Temizliği") → "house_cleaning"
   d) getCleaningJobTemplateForService("house_cleaning") → 
      ContractTemplate::findByTypeAndServiceKey('cleaning_job', 'house_cleaning')
      → template = {id: 10, name: "Ev Temizliği Hizmet Sözleşmesi", service_key: "house_cleaning"}

4. Template bulundu! ✅
   - renderCleaningJobContractText() ile metin render edilir
   - {service_name} → "Ev Temizliği" olarak doldurulur
   - JobContract kaydı oluşturulur
```

### Senaryo 2: Yeni Hizmet Tipi (Service-specific template yok)

```
1. Job oluşturuldu:
   - job_id = 124
   - service_id = 10
   - service_name = "Balkon Temizliği" (yeni hizmet, mapping'de yok)

2. createJobContractForJob($job) çağrıldı

3. getTemplateForJob($job) içinde:
   a) service_id = 10 kontrolü → VAR
   b) Service::find(10) → service = {id: 10, name: "Balkon Temizliği"}
   c) normalizeServiceName("Balkon Temizliği") → null (mapping'de yok)
   d) Log: "Service name 'Balkon Temizliği' not mapped to service_key"
   e) getDefaultCleaningJobTemplate() → 
      ContractTemplate::getDefault('cleaning_job')
      → template = {id: 1, name: "Genel Temizlik Sözleşmesi", service_key: NULL}

4. Genel template kullanıldı ✅
   - {service_name} → "Balkon Temizliği" olarak doldurulur (placeholder zaten var)
   - JobContract kaydı oluşturulur
```

### Senaryo 3: Service ID Yok (Eski işler)

```
1. Job oluşturuldu (eski kayıt):
   - job_id = 50
   - service_id = NULL

2. createJobContractForJob($job) çağrıldı

3. getTemplateForJob($job) içinde:
   a) service_id kontrolü → NULL
   b) Log: "Job 50 has no service_id, using default template"
   c) getDefaultCleaningJobTemplate() → genel template

4. Genel template kullanıldı ✅
```

## 5. SERVICE NAME → SERVICE_KEY MAPPING

### Mapping Listesi (TEK KAYNAK)

**Dosya**: `src/Services/ContractTemplateService.php` → `normalizeServiceName()` metodu

| Service Name (Normalize Edilmiş) | service_key |
|----------------------------------|-------------|
| ev temizliği, ev temizlik | house_cleaning |
| ofis temizliği, ofis temizlik, iş yeri temizliği | office_cleaning |
| cam temizliği, pencere temizliği, cam temizlik | window_cleaning |
| inşaat sonrası temizlik, taşınma sonrası temizlik | post_construction |
| mağaza temizliği, mağaza temizlik | store_cleaning |
| site temizliği, ortak alan temizliği | site_common_areas |
| site yönetimi, apartman yönetimi, yönetim hizmeti | management_service |

**Önemli**: Bu mapping **TEK BİR YERDE** tanımlıdır. Yeni hizmet tipleri eklemek için sadece bu metodu güncellemek yeterlidir.

## 6. GERİYE UYUMLULUK

### Mevcut Kod Etkilenmedi
- ✅ `getDefaultCleaningJobTemplate()` metodu korundu (geriye uyumluluk)
- ✅ Mevcut contract_templates kayıtları etkilenmedi (service_key NULL)
- ✅ Eski işler (service_id NULL) için genel template kullanılıyor
- ✅ Mapping'de olmayan service name'ler için genel template kullanılıyor

### Yeni Özellikler
- ✅ Service-specific template seçimi
- ✅ Fallback mantığı (service-specific → genel → son çare)
- ✅ Detaylı loglama

## 7. ÖZET

### Oluşturulan/Güncellenen Dosyalar

1. **Migration**: `db/migrations/038_add_service_key_to_contract_templates.sql`
2. **Model**: `src/Models/ContractTemplate.php`
   - `findByTypeAndServiceKey()` metodu eklendi
   - `create()` ve `update()` metodları `service_key` desteği eklendi
   - `all()` metodu `service_key` filtresi desteği eklendi
3. **Service**: `src/Services/ContractTemplateService.php`
   - `normalizeServiceName()` metodu eklendi (TEK KAYNAK - mapping)
   - `getCleaningJobTemplateForService()` metodu eklendi
   - `getTemplateForJob()` metodu eklendi (fallback mantığı ile)
   - `createJobContractForJob()` güncellendi (artık `getTemplateForJob()` kullanıyor)

### Template Seçim Akışı

```
Job → service_id → Service → service_name 
  → normalizeServiceName() → service_key 
  → getCleaningJobTemplateForService() 
  → Service-specific Template ✅
  
  ↓ (bulunamazsa)
  
  → getDefaultCleaningJobTemplate() 
  → Genel Template ✅
```

### Sonraki Adımlar

- **AŞAMA 4**: Seed/CLI script ile örnek service-specific template'ler oluşturulacak
- **AŞAMA 5**: Smoke test'ler yazılacak
- **AŞAMA 6**: Hizmetler sayfasına sözleşme şablonu yönetimi eklenecek

---

**Rapor Tarihi**: 2025-01-XX
**Hazırlayan**: AI Assistant
**Durum**: AŞAMA 3 Tamamlandı ✅

