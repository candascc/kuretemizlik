# MIGRATION VE MODEL IMPLEMENTASYON RAPORU

**Tarih:** 2025-01-XX  
**Aşama:** AŞAMA 3 - Migration + Model Implementasyonu  
**Durum:** ✅ Tamamlandı

---

## ÖZET

Bu aşamada, `JOB_CONTRACT_ANALYSIS_REPORT.md` dosyasında tasarlanan veri modeli hayata geçirildi. 3 yeni migration dosyası ve 3 yeni model sınıfı oluşturuldu.

---

## 1. MIGRATION DOSYALARI

### Oluşturulan Migration Dosyaları

#### 1.1. `db/migrations/034_contract_templates.sql`
- **Amaç:** Sözleşme şablonları tablosu
- **Tablo:** `contract_templates`
- **Ana Alanlar:**
  - `id`, `type`, `name`, `version`, `description`
  - `template_text`, `template_variables` (JSON), `pdf_template_path`
  - `is_active`, `is_default`
  - `content_hash`, `created_by` (FK -> users)
  - `created_at`, `updated_at`
- **İndeksler:**
  - `idx_contract_templates_type` (type)
  - `idx_contract_templates_active` (is_active)
  - `idx_contract_templates_default` (is_default)
- **Özellikler:**
  - `type` CHECK constraint: 'cleaning_job', 'maintenance_job', 'recurring_cleaning'
  - `is_default` ile varsayılan şablon yönetimi

#### 1.2. `db/migrations/035_contract_otp_tokens.sql`
- **Amaç:** Sözleşme onayı için OTP kodları tablosu
- **Tablo:** `contract_otp_tokens`
- **Ana Alanlar:**
  - `id`, `job_contract_id` (FK -> job_contracts), `customer_id` (FK -> customers)
  - `token` (hashlenmiş OTP), `phone`, `channel` ('sms' | 'email')
  - `expires_at`, `sent_at`, `verified_at`
  - `attempts`, `max_attempts`
  - `ip_address`, `user_agent`, `meta` (JSON)
  - `created_at`, `updated_at`
- **İndeksler:**
  - `idx_contract_otp_tokens_job_contract` (job_contract_id)
  - `idx_contract_otp_tokens_customer` (customer_id)
  - `idx_contract_otp_tokens_token` (token)
  - `idx_contract_otp_tokens_expires` (expires_at)
  - `idx_contract_otp_tokens_verified` (verified_at)
- **Özellikler:**
  - `channel` CHECK constraint: 'sms', 'email'
  - `verified_at` NULL ise henüz doğrulanmamış
  - Güvenlik: token hashlenmiş, attempt tracking

**NOT:** Bu migration, `036_job_contracts.sql`'den SONRA çalıştırılmalıdır (FK bağımlılığı nedeniyle).

#### 1.3. `db/migrations/036_job_contracts.sql`
- **Amaç:** İş bazlı sözleşmeler tablosu
- **Tablo:** `job_contracts`
- **Ana Alanlar:**
  - `id`, `job_id` (UNIQUE, FK -> jobs)
  - `template_id` (FK -> contract_templates)
  - `status` ('PENDING' | 'SENT' | 'APPROVED' | 'EXPIRED' | 'REJECTED')
  - `approval_method` ('SMS_OTP' | 'EMAIL_OTP' | 'MANUAL')
  - Onay bilgileri: `approved_at`, `approved_phone`, `approved_ip`, `approved_user_agent`, `approved_customer_id`
  - SMS bilgileri: `sms_sent_at`, `sms_sent_count`, `last_sms_token_id` (referans, FK yok)
  - İçerik: `contract_text`, `contract_pdf_path`, `contract_hash`
  - `metadata` (JSON), `expires_at`
  - `created_at`, `updated_at`
- **İndeksler:**
  - `idx_job_contracts_job_id` (job_id) - UNIQUE constraint ile birlikte
  - `idx_job_contracts_status` (status)
  - `idx_job_contracts_expires_at` (expires_at)
- **Özellikler:**
  - `job_id UNIQUE` constraint ile her iş için tek sözleşme garantisi
  - `last_sms_token_id` için FK tanımlanmadı (circular dependency önlemek için)

### Migration Sırası

1. **034_contract_templates.sql** (ilk)
2. **036_job_contracts.sql** (ikinci - contract_templates'e bağımlı)
3. **035_contract_otp_tokens.sql** (üçüncü - job_contracts'e bağımlı)

**Not:** Dosya isimlendirmesi alfabetik sıraya göre, ancak çalıştırma sırası yukarıdaki gibi olmalıdır.

---

## 2. MODEL SINIFLARI

### Oluşturulan Model Dosyaları

#### 2.1. `src/Models/ContractTemplate.php`
- **Sınıf:** `ContractTemplate`
- **Tablo:** `contract_templates`
- **CompanyScope:** ❌ Kullanılmıyor (global şablonlar)
- **Temel Metodlar:**
  - `all($filters = [])` - Tüm şablonları getir (type, is_active, is_default filtreleri)
  - `find($id)` - ID ile şablon getir
  - `getDefault($type = 'cleaning_job')` - Varsayılan şablonu getir
  - `getActive($type = null)` - Aktif şablonları getir
  - `create($data)` - Yeni şablon oluştur (is_default=1 ise aynı type'daki diğer default'ları kaldırır)
  - `update($id, $data)` - Şablon güncelle
  - `delete($id)` - Şablon sil
  - `count($filters = [])` - Şablon sayısı
- **İlişkiler:**
  - `jobContracts($templateId)` - Bu şablonu kullanan iş sözleşmeleri
  - `createdBy($templateId)` - Şablonu oluşturan kullanıcı (User modeli)

#### 2.2. `src/Models/ContractOtpToken.php`
- **Sınıf:** `ContractOtpToken`
- **Tablo:** `contract_otp_tokens`
- **CompanyScope:** ❌ Kullanılmıyor (OTP token'lar job_contract üzerinden scope edilir)
- **Temel Metodlar:**
  - `find($id)` - ID ile token getir
  - `findActiveByJobContract($jobContractId)` - Job contract için aktif token getir
  - `findByToken($tokenHash)` - Token hash ile token getir
  - `create($data)` - Yeni OTP token oluştur
  - `update($id, $data)` - Token güncelle
  - `incrementAttempts($id)` - Deneme sayısını artır
  - `markAsVerified($id)` - Token'ı doğrulanmış olarak işaretle
  - `cleanupExpired()` - Süresi dolmuş token'ları temizle
- **İlişkiler:**
  - `jobContract($tokenId)` - Bu token'ın ait olduğu iş sözleşmesi (JobContract modeli)
  - `customer($tokenId)` - Bu token'ın ait olduğu müşteri (Customer modeli)

#### 2.3. `src/Models/JobContract.php`
- **Sınıf:** `JobContract`
- **Tablo:** `job_contracts`
- **CompanyScope:** ✅ Kullanılıyor (Job üzerinden company scope)
- **Temel Metodlar:**
  - `all($filters = [], $limit = null, $offset = 0)` - Tüm sözleşmeleri getir (JOIN ile job, customer)
  - `find($id)` - ID ile sözleşme getir (JOIN ile job, customer)
  - `findByJobId($jobId)` - Job ID ile sözleşme getir
  - `create($data)` - Yeni sözleşme oluştur (UNIQUE kontrolü yapar)
  - `update($id, $data)` - Sözleşme güncelle
  - `delete($id)` - Sözleşme sil
  - `updateStatus($id, $status)` - Durum güncelle
  - `incrementSmsCount($id, $tokenId = null)` - SMS gönderim sayısını artır
  - `approve($id, $phone, $customerId = null, $ipAddress = null, $userAgent = null)` - Sözleşme onayla
  - `markExpired()` - Süresi dolmuş sözleşmeleri işaretle
  - `count($filters = [])` - Sözleşme sayısı
- **İlişkiler:**
  - `job($contractId)` - Bu sözleşmenin ait olduğu iş (Job modeli)
  - `template($contractId)` - Bu sözleşmenin şablonu (ContractTemplate modeli)
  - `approvedCustomer($contractId)` - Bu sözleşmeyi onaylayan müşteri (Customer modeli)
  - `lastOtpToken($contractId)` - Son gönderilen OTP token (ContractOtpToken modeli)
  - `otpTokens($contractId)` - Bu sözleşme için tüm OTP token'ları

### Model Özellikleri

1. **CompanyScope Kullanımı:**
   - `JobContract`: ✅ Kullanıyor (Job üzerinden company scope)
   - `ContractTemplate`: ❌ Kullanmıyor (global şablonlar)
   - `ContractOtpToken`: ❌ Kullanmıyor (JobContract üzerinden scope edilir)

2. **İlişki Metodları:**
   - Tüm modellerde ilişkiler manuel JOIN'lerle veya helper metotlarla yapılıyor
   - ORM-style relation metotları yok (proje standardına uygun)

3. **JSON Alanlar:**
   - `template_variables`, `metadata`, `meta` alanları JSON olarak saklanıyor
   - Model metodlarında otomatik encode/decode yapılıyor

4. **Güvenlik:**
   - OTP token'lar hashlenmiş olarak saklanıyor
   - Attempt tracking mevcut
   - IP ve user agent loglama

---

## 3. UYUM VE KONTROL

### Mevcut Codebase ile Uyum

✅ **Migration Formatı:**
- SQLite syntax kullanıldı
- Mevcut migration dosyalarıyla aynı format (`CREATE TABLE IF NOT EXISTS`, `CREATE INDEX IF NOT EXISTS`)
- CHECK constraints ve FOREIGN KEY'ler mevcut pattern'e uygun

✅ **Model Yapısı:**
- Basit PHP sınıfları (ORM yok)
- `Database::getInstance()` kullanımı
- `CompanyScope` trait'i uygun modellerde kullanıldı
- İlişkiler manuel JOIN'lerle yapılıyor

✅ **Dosya Yapısı:**
- Migration dosyaları: `db/migrations/XXX_description.sql`
- Model dosyaları: `src/Models/ClassName.php`
- Autoload mekanizması mevcut (diğer modeller gibi)

### Önemli Notlar

1. **Circular Dependency Çözümü:**
   - `job_contracts.last_sms_token_id` için FK tanımlanmadı
   - Sadece referans alanı olarak kullanılıyor
   - İlişki model metodunda (`lastOtpToken()`) kontrol ediliyor

2. **Migration Sırası:**
   - Dosya isimleri alfabetik: 034, 035, 036
   - Ancak çalıştırma sırası: 034 → 036 → 035 (FK bağımlılıkları nedeniyle)
   - Migration runner'ın bu sırayı takip etmesi gerekiyor

3. **CompanyScope:**
   - `JobContract` modeli `CompanyScope` kullanıyor
   - Job üzerinden company scope yapılıyor (JOIN ile)
   - `ContractTemplate` ve `ContractOtpToken` global scope'da

---

## 4. SONRAKİ ADIMLAR

Bu aşamada sadece migration ve model dosyaları oluşturuldu. Sonraki aşamalarda:

1. **Service Sınıfları:**
   - `ContractOtpService` (OTP üretme/gönderme/doğrulama)
   - `ContractTemplateService` (Şablon render, placeholder replacement)

2. **Controller Sınıfları:**
   - `JobController` güncellemeleri (sözleşme durumu, SMS gönderme)
   - `PublicContractController` (public sözleşme görüntüleme ve onay)

3. **View Dosyaları:**
   - `jobs/show.php` güncellemeleri (sözleşme durumu gösterimi)
   - `contracts/view.php` (public sözleşme onay ekranı)

4. **Routing:**
   - `/jobs/{id}/contract/send-sms` (POST)
   - `/contract/{token}` (GET)
   - `/contract/{token}/approve` (POST)

---

## 5. ÖZET

### Oluşturulan Dosyalar

**Migration Dosyaları:**
1. `db/migrations/034_contract_templates.sql`
2. `db/migrations/035_contract_otp_tokens.sql`
3. `db/migrations/036_job_contracts.sql`

**Model Dosyaları:**
1. `src/Models/ContractTemplate.php`
2. `src/Models/ContractOtpToken.php`
3. `src/Models/JobContract.php`

### Model İlişkileri

**ContractTemplate:**
- `jobContracts($templateId)` → JobContract[] (hasMany)
- `createdBy($templateId)` → User (belongsTo)

**ContractOtpToken:**
- `jobContract($tokenId)` → JobContract (belongsTo)
- `customer($tokenId)` → Customer (belongsTo)

**JobContract:**
- `job($contractId)` → Job (belongsTo)
- `template($contractId)` → ContractTemplate (belongsTo)
- `approvedCustomer($contractId)` → Customer (belongsTo)
- `lastOtpToken($contractId)` → ContractOtpToken (belongsTo)
- `otpTokens($contractId)` → ContractOtpToken[] (hasMany)

---

**Rapor Hazırlayan:** AI Pair Programmer (Cursor)  
**Tarih:** 2025-01-XX  
**Durum:** ✅ Migration ve Model implementasyonu tamamlandı

