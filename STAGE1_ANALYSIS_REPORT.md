# AŞAMA 1 RAPORU - HİZMET TİPLERİ ANALİZİ

## 1. HİZMET TİPLERİ TESPİTİ

### Services Tablosu Yapısı
- **Tablo adı**: `services`
- **Sütunlar**:
  - `id` (INTEGER PRIMARY KEY)
  - `name` (TEXT NOT NULL) - Hizmet adı (örn: "Ev Temizliği", "Ofis Temizliği")
  - `company_id` (INTEGER NOT NULL DEFAULT 1) - Multi-tenancy desteği
  - `duration_min` (INTEGER) - Tahmini süre (dakika)
  - `default_fee` (REAL) - Varsayılan ücret
  - `is_active` (INTEGER NOT NULL DEFAULT 1) - Aktiflik durumu
  - `created_at` (TEXT NOT NULL DEFAULT datetime('now'))
  - `updated_at` (TEXT) - Güncelleme zamanı

### Mevcut Hizmet Tipleri (Tespit Edilen)
Migration dosyalarından ve kod yapısından tespit edilen hizmet tipleri:

1. **Ev Temizliği** - Muhtemelen `name = 'Ev Temizliği'`
2. **Ofis / İş Yeri Temizliği** - Muhtemelen `name = 'Ofis Temizliği'` veya benzeri
3. **Cam Temizliği** - Muhtemelen `name = 'Cam Temizliği'` veya `'Pencere Temizliği'`
4. **İnşaat Sonrası Temizlik** - `migrate_new_services.sql` dosyasında görüldü: `'İnşaat Sonrası Temizlik'`
5. **Mağaza Temizliği** - `migrate_new_services.sql` dosyasında görüldü: `'Mağaza Temizliği'`
6. **Site Yönetimi** - `migrate_new_services.sql` dosyasında görüldü: `'Site Yönetimi'`

**NOT**: Services tablosunda `service_key` veya `code` gibi bir alan **YOK**. Sadece `name` alanı var.

### Service Model Yapısı
- **Model**: `src/Models/Service.php`
- **CompanyScope trait** kullanıyor (multi-tenancy)
- **Metodlar**:
  - `all()` - Tüm hizmetleri getirir
  - `getActive()` - Sadece aktif hizmetleri getirir
  - `find($id)` - ID ile hizmet getirir
  - `create($data)`, `update($id, $data)`, `delete($id)`
  - `getStats()`, `getUsageStats()` - İstatistikler

## 2. JOB ↔ HİZMET İLİŞKİSİ

### Jobs Tablosu Yapısı
- **Tablo adı**: `jobs`
- **Hizmet bağlantısı**: `service_id INTEGER` (FOREIGN KEY → `services(id)`)
- **İlişki**: `jobs.service_id` → `services.id` (ON DELETE SET NULL)

### Job Model Yapısı
- **Model**: `src/Models/Job.php`
- **CompanyScope trait** kullanıyor
- **JOIN yapısı**: Job sorgularında `services` tablosu LEFT JOIN ile birleştiriliyor
- **Service bilgisi**: `s.name as service_name` olarak job kayıtlarına ekleniyor

### Hizmet Tipi Belirleme
- Job kaydında hizmet tipi **sadece `service_id`** üzerinden belirleniyor
- `Job::find($id)` metodu şu JOIN'i yapıyor:
  ```sql
  LEFT JOIN services s ON j.service_id = s.id
  ```
- Sonuç olarak `job['service_name']` alanında hizmet adı geliyor (örn: "Ev Temizliği")

**ÖNEMLİ**: Job kaydında `service_type`, `category` gibi ek alanlar **YOK**. Sadece `service_id` var.

## 3. CONTRACT_TEMPLATES MEVCUT DURUMU

### Tablo Yapısı
- **Tablo adı**: `contract_templates`
- **Sütunlar**:
  - `id` (INTEGER PRIMARY KEY)
  - `type` (TEXT NOT NULL DEFAULT 'cleaning_job') - CHECK constraint: `('cleaning_job', 'maintenance_job', 'recurring_cleaning')`
  - `name` (TEXT NOT NULL) - Şablon adı
  - `version` (TEXT NOT NULL DEFAULT '1.0')
  - `description` (TEXT)
  - `template_text` (TEXT NOT NULL) - Sözleşme metni
  - `template_variables` (TEXT) - JSON formatında değişkenler
  - `pdf_template_path` (TEXT)
  - `is_active` (INTEGER NOT NULL DEFAULT 1)
  - `is_default` (INTEGER NOT NULL DEFAULT 0)
  - `content_hash` (TEXT)
  - `created_by` (INTEGER) - FOREIGN KEY → `users(id)`
  - `created_at`, `updated_at`

**ÖNEMLİ**: `contract_templates` tablosunda **`service_id` veya `service_key` alanı YOK**.

### Mevcut Kullanım
- **ContractTemplateService::getDefaultCleaningJobTemplate()**:
  - `type = 'cleaning_job'`
  - `is_default = 1`
  - `is_active = 1`
  - Bu kriterlere uyan **tek bir şablon** arıyor

- **ContractTemplateService::createJobContractForJob()**:
  - Her zaman `getDefaultCleaningJobTemplate()` çağırıyor
  - Hizmet tipine göre ayrım **YOK**

### Placeholder Sistemi
- **Mevcut placeholder'lar** (`renderCleaningJobContractText` içinde):
  - `{customer_name}`, `{customer_phone}`, `{customer_email}`
  - `{job_id}`, `{job_date}`, `{job_time}`, `{job_datetime}`
  - `{job_address}`, `{job_price}`, `{job_amount}`, `{job_total_amount}`
  - `{service_type}`, `{service_name}` - **Bu zaten var!**
  - `{job_description}`, `{job_status}`

**ÖNEMLİ**: `{service_name}` placeholder'ı zaten mevcut ve `job['service_name']` değerini kullanıyor.

## 4. SERVICE-SPECIFIC TEMPLATE SEÇİMİ İÇİN ÖNERİ

### Seçenek 1: `service_key` Alanı Ekle (ÖNERİLEN)
**Avantajlar**:
- Services tablosuna dokunmadan çalışır
- Contract_templates tablosunda string-based lookup yapılabilir
- Service name değişse bile service_key sabit kalır
- Migration kolay (sadece contract_templates'e alan eklenir)

**Dezavantajlar**:
- Service → service_key mapping'i manuel yapılmalı
- Service name'den service_key türetme mantığı gerekir

### Seçenek 2: `service_id` Foreign Key Ekle
**Avantajlar**:
- Doğrudan ilişki (FOREIGN KEY)
- Service silinirse template de etkilenir (CASCADE veya SET NULL)
- Daha güçlü referential integrity

**Dezavantajlar**:
- Multi-tenancy: Her company için aynı service_id farklı service'leri temsil edebilir
- Service silinirse template'in durumu belirsizleşir
- Company scope kontrolü gerekir

### Seçenek 3: Service Name Normalization (ÖNERİLMİYOR)
**Avantajlar**:
- Yeni alan gerekmez

**Dezavantajlar**:
- Service name değişirse template eşleşmesi bozulur
- Case-sensitive, boşluk, özel karakter sorunları
- Çok kırılgan

## 5. ÖNERİLEN TASARIM

### contract_templates Tablosuna Eklenecek Alan
```sql
service_key TEXT NULL
```

**Açıklama**:
- `NULL` olabilir (genel template'ler için)
- Service-specific template'ler için dolu olacak
- Örnek değerler: `'house_cleaning'`, `'office_cleaning'`, `'window_cleaning'`, `'post_construction'`, `'site_common_areas'`, `'management_service'`

### Service → service_key Mapping Stratejisi
**Yaklaşım**: Service name'den normalize edilmiş bir key üretme fonksiyonu

**Örnek mapping**:
- "Ev Temizliği" → `'house_cleaning'`
- "Ofis Temizliği" → `'office_cleaning'`
- "Cam Temizliği" → `'window_cleaning'`
- "İnşaat Sonrası Temizlik" → `'post_construction'`
- "Mağaza Temizliği" → `'store_cleaning'`
- "Site Yönetimi" → `'management_service'`

**Alternatif**: Service tablosuna `code` veya `key` alanı eklenebilir (ileride), ama şimdilik name-based mapping yeterli.

### Template Seçim Algoritması (Pseudo-code)
```
1. Job'dan service_id al
2. Service kaydını getir (service['name'])
3. Service name'den service_key türet (normalize)
4. contract_templates'te ara:
   WHERE type = 'cleaning_job'
     AND service_key = [türetilen_key]
     AND is_active = 1
   ORDER BY is_default DESC, version DESC
   LIMIT 1
5. Bulunamazsa:
   WHERE type = 'cleaning_job'
     AND service_key IS NULL  -- Genel template
     AND is_default = 1
     AND is_active = 1
   LIMIT 1
6. Hala bulunamazsa: NULL veya Exception
```

## 6. HİZMETLER SAYFASI ENTEGRASYONU

### Mevcut Yapı
- **Controller**: `src/Controllers/ServiceController.php`
- **View'lar**:
  - `src/Views/services/list.php` - Hizmet listesi
  - `src/Views/services/form.php` - Hizmet ekleme/düzenleme formu

### Hizmet Detay Sayfası
**DURUM**: Şu an **ayrı bir detay sayfası YOK**. Sadece `edit()` metodu var ve form view'ı kullanılıyor.

**ÖNERİ**: 
- `ServiceController::show($id)` metodu eklenebilir (opsiyonel)
- VEYA `edit()` sayfasına sözleşme şablonu bölümü eklenir (daha pratik)

### Hizmet → Template Eşleşmesi
**Yaklaşım**: `service_key` üzerinden lookup
- Service name'den service_key türet
- `contract_templates` tablosunda `service_key = [türetilen_key]` ile ara
- Bulunursa göster, bulunamazsa "Genel template kullanılacak" mesajı

## 7. ÖZET VE SONRAKI ADIMLAR

### Tespit Edilenler
1. ✅ Services tablosu mevcut, `name` alanı var, `service_key` yok
2. ✅ Jobs tablosu `service_id` ile services'e bağlı
3. ✅ Contract_templates tablosu mevcut, ama service-specific ayrım yok
4. ✅ Placeholder sistemi mevcut, `{service_name}` zaten var
5. ✅ ServiceController ve view'lar mevcut

### Eksikler
1. ❌ `contract_templates.service_key` alanı yok
2. ❌ Service name → service_key normalization fonksiyonu yok
3. ❌ Service-specific template seçim mantığı yok
4. ❌ Hizmetler sayfasında sözleşme şablonu yönetimi yok

### Önerilen Çözüm
1. **Migration**: `contract_templates` tablosuna `service_key TEXT NULL` ekle
2. **Helper Fonksiyon**: Service name'den service_key türeten fonksiyon
3. **ContractTemplateService**: Service-specific template seçim metodları
4. **ServiceController**: Sözleşme şablonu görüntüleme/düzenleme
5. **View**: Hizmet form/detay sayfasına sözleşme şablonu bölümü

---

**Rapor Tarihi**: 2025-01-XX
**Hazırlayan**: AI Assistant
**Durum**: Analiz Tamamlandı ✅

