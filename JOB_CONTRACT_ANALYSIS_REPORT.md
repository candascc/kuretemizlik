# İŞ BAZLI SÖZLEŞME SİSTEMİ - KOD KEŞFİ VE VERİ MODELİ TASLAĞI

**Tarih:** 2025-01-XX  
**Amaç:** Her temizlik işi için ayrı SMS/OTP doğrulamalı sözleşme onay sistemi tasarımı  
**Durum:** Analiz ve Tasarım Aşaması (Kod Değişikliği Yapılmadı)

---

## AŞAMA 1: KOD KEŞFİ RAPORU

### Backend – Modeller

#### 1. Temizlik İşi (Job) Modeli
- **Dosya:** `src/Models/Job.php`
- **Veritabanı Tablosu:** `jobs`
- **Ana Alanlar:**
  - `id` (PRIMARY KEY)
  - `customer_id` (NOT NULL, FK -> customers)
  - `service_id` (FK -> services)
  - `address_id` (FK -> addresses)
  - `start_at`, `end_at` (TEXT, NOT NULL)
  - `status` (CHECK: 'SCHEDULED', 'DONE', 'CANCELLED')
  - `total_amount`, `amount_paid`, `payment_status`
  - `assigned_to`, `note`
  - `company_id` (NOT NULL DEFAULT 1, FK -> companies)
  - `recurring_job_id`, `occurrence_id` (periyodik iş bağlantıları)
  - `created_at`, `updated_at`
- **İlişkiler:**
  - `customers` (1:N)
  - `services` (N:1)
  - `addresses` (N:1)
  - `companies` (N:1)
- **Kritik Metodlar:**
  - `create($data)` - Yeni iş oluşturma
  - `update($id, $data)` - İş güncelleme
  - `find($id)` - İş detayını getirme (JOIN ile müşteri, servis, adres)
  - `delete($id)` - İş silme (FK constraint'lere dikkat)

#### 2. Müşteri (Customer) Modeli
- **Dosya:** `src/Models/Customer.php`
- **Veritabanı Tablosu:** `customers`
- **Ana Alanlar:**
  - `id` (PRIMARY KEY)
  - `name` (NOT NULL)
  - `phone` (TEXT, nullable)
  - `email` (TEXT, nullable)
  - `company_id` (NOT NULL DEFAULT 1, FK -> companies)
  - `notes`
  - `password_hash` (TEXT, nullable) - Portal login için
  - `password_set_at` (TEXT, nullable)
  - `last_otp_sent_at` (TEXT, nullable)
  - `otp_context` (TEXT, nullable) - 'login', 'set_password' vs.
  - `otp_attempts` (INTEGER DEFAULT 0)
  - `created_at`, `updated_at`
- **OTP İlgili Metodlar:**
  - `hasPassword(?array $customer): bool` - Şifre var mı kontrolü
  - `updatePassword(int $customerId, string $password): bool` - Şifre güncelleme
  - `markOtpIssued(int $customerId, string $context): void` - OTP gönderimini kaydetme
  - `incrementOtpAttempt(int $customerId): void` - Hatalı deneme sayısını artırma
  - `resetOtpState(int $customerId): void` - OTP durumunu sıfırlama
  - `findByPhone(string $phone): ?array` - Telefon ile müşteri bulma

#### 3. OTP Token Modeli (Mevcut Login İçin)
- **Veritabanı Tablosu:** `customer_login_tokens`
- **Şema:**
  ```sql
  CREATE TABLE customer_login_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    token TEXT NOT NULL,  -- password_hash ile hashlenmiş kod
    channel TEXT NOT NULL CHECK(channel IN ('email','sms')),
    expires_at TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    meta TEXT,  -- JSON: ip, user_agent, context
    consumed_at TEXT,  -- NULL ise henüz kullanılmamış
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
  );
  ```
- **İndeksler:**
  - `idx_customer_login_tokens_customer` (customer_id)
  - `idx_customer_login_tokens_token` (token)
  - `idx_customer_login_tokens_expires` (expires_at)

### Backend – Servis / Controller

#### 1. İş Operasyonları
- **Dosya:** `src/Controllers/JobController.php`
- **Kritik Metodlar:**
  - `index()` - İş listesi (filtreleme, sayfalama)
  - `show($id)` - İş detay sayfası
  - `create()` - Yeni iş oluşturma formu
  - `store()` - İş kaydetme (POST)
  - `edit($id)` - İş düzenleme formu
  - `update($id)` - İş güncelleme (POST)
  - `delete($id)` - İş silme (POST)
- **Routing:** `index.php` içinde `/jobs`, `/jobs/show/{id}`, `/jobs/create` vb.

#### 2. Müşteri Portal Login / OTP
- **Dosya:** `src/Controllers/PortalController.php`
- **Kritik Metodlar:**
  - `login()` - Login formu gösterimi (phone/password/otp/set_password adımları)
  - `processLogin()` - Telefon numarası işleme (ilk giriş/şifre kontrolü)
  - `processPassword()` - Şifre ile giriş
  - `processOtpVerification()` - OTP doğrulama
  - `processSetPassword()` - İlk girişte şifre belirleme
  - `triggerOtpChallenge()` - OTP gönderme tetikleme
  - `completePortalLogin()` - Giriş tamamlama
- **Akış:**
  1. Kullanıcı telefon numarası girer
  2. Müşteri kaydı kontrol edilir (`Customer::findByPhone()`)
  3. Eğer şifre yoksa (`!Customer::hasPassword()`) → OTP gönderilir (`context='set_password'`)
  4. Eğer şifre varsa → Şifre ekranı gösterilir
  5. Şifre yanlışsa veya 5 deneme sonrası → OTP gönderilir (`context='login'`)
  6. OTP doğrulanınca → Login tamamlanır veya şifre belirleme ekranına gidilir

#### 3. OTP Servisi
- **Dosya:** `src/Services/CustomerOtpService.php`
- **Sınıf:** `CustomerOtpService`
- **Sabitler:**
  - `OTP_LENGTH = 6`
  - `EXPIRY_INTERVAL = '+5 minutes'`
  - `RESEND_COOLDOWN_SECONDS = 60`
  - `MAX_GENERATE_PER_HOUR = 10`
  - `MAX_ATTEMPTS = 5`
- **Kritik Metodlar:**
  - `requestToken(array $customer, string $channel, ?string $ipAddress = null, string $context = 'login'): array`
    - OTP kod üretir
    - `customer_login_tokens` tablosuna kaydeder (hashlenmiş)
    - SMS veya email gönderir (`SMSQueue` / `EmailQueue`)
    - Rate limiting kontrolü yapar
    - `context` parametresi ile kullanım amacını ayırır ('login', 'set_password')
  - `verifyToken(int $tokenId, string $code): array`
    - Token'ı kontrol eder (expiry, consumed, attempts)
    - `password_verify()` ile kodu doğrular
    - Başarılıysa `consumed_at` işaretler
  - `maskPhone()`, `maskEmail()` - Hassas bilgi gizleme

#### 4. SMS Servisi
- **Dosya:** `src/Services/SMSQueue.php`
- **Sınıf:** `SMSQueue`
- **Veritabanı Tablosu:** `sms_queue`
- **Şema:**
  ```sql
  CREATE TABLE sms_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    to_phone TEXT NOT NULL,
    message TEXT NOT NULL,
    data TEXT,  -- JSON metadata
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')),
    attempts INTEGER DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    scheduled_at TEXT DEFAULT (datetime('now')),
    last_attempt_at TEXT,
    sent_at TEXT,
    error_message TEXT,
    created_at TEXT DEFAULT (datetime('now'))
  );
  ```
- **Kritik Metodlar:**
  - `add($smsData)` - SMS kuyruğa ekleme
  - `process($limit = 10)` - Kuyruktan işleme
  - `sendViaProvider($sms)` - Provider'a gönderme (NetGSM/Twilio/Mock)
- **Provider Entegrasyonları:**
  - NetGSM: `sendViaNetgsm()` - API endpoint: `https://api.netgsm.com.tr/sms/send/get`
  - Twilio: `sendViaTwilio()` - Henüz implement edilmemiş
  - Mock: `sendViaMock()` - Development için log'a yazma
- **Environment Değişkenleri:**
  - `SMS_ENABLED` (true/false)
  - `SMS_PROVIDER` (netgsm/twilio/mock)
  - `NETGSM_USERNAME`, `NETGSM_PASSWORD`, `NETGSM_BRAND_CODE`, `NETGSM_SENDER`

### Frontend – Operasyon Ekranları

#### 1. İş Listesi
- **Dosya:** `src/Views/jobs/list.php`
- **Özellikler:**
  - Filtreleme (durum, müşteri, tarih aralığı, şirket)
  - Sayfalama (20 kayıt/sayfa)
  - Tablo görünümü (desktop) ve kart görünümü (mobile)
  - Durum badge'leri (SCHEDULED/DONE/CANCELLED)
  - Bulk operations (seçili işler için toplu işlemler)
- **Kritik Bilgiler:**
  - Her satırda: Tarih, Müşteri, Hizmet, Durum, Ödeme Durumu, İşlemler
  - Detay linki: `/jobs/show/{id}`

#### 2. İş Detay Sayfası
- **Dosya:** `src/Views/jobs/show.php`
- **Özellikler:**
  - İş bilgileri (tarih, müşteri, adres, servis, durum, ödeme)
  - Ödeme geçmişi (job_payments tablosu)
  - Yorumlar ve notlar
  - İşlem butonları (Düzenle, Sil, Periyodiğe Dönüştür)
  - Breadcrumb navigasyon
- **Önemli Not:** Şu an sözleşme durumu veya sözleşme gönderme butonu yok.

### Frontend – Mevcut Onay / OTP Akışları

#### 1. Müşteri Portal Login
- **Dosya:** `src/Views/portal/login.php`
- **Akış Adımları:**
  1. **Step: 'phone'** - Telefon numarası girme
  2. **Step: 'password'** - Şifre girme (eğer şifre varsa)
  3. **Step: 'otp'** - OTP kodu girme (ilk giriş veya şifre unutma)
  4. **Step: 'set_password'** - Şifre belirleme (ilk giriş sonrası)
- **OTP Ekranı Özellikleri:**
  - 6 haneli kod input'u
  - Maskelenmiş telefon numarası gösterimi
  - Kod son kullanma zamanı gösterimi
  - "Kodu yeniden gönder" butonu (cooldown ile)
  - Hata mesajları (yanlış kod, expired, attempts exceeded)
- **JavaScript Özellikleri:**
  - OTP input'u için numeric-only validation
  - Auto-focus
  - Resend countdown timer
  - Client-side phone validation

#### 2. Route Yapısı (Portal)
- `/portal/login` - Login ana sayfası
- `/portal/login` (POST) - Telefon işleme
- `/portal/login/password` (POST) - Şifre ile giriş
- `/portal/login/otp` (POST) - OTP doğrulama
- `/portal/login/resend` (POST) - OTP yeniden gönderme
- `/portal/login/set-password` (POST) - Şifre belirleme
- `/portal/login/forgot` (POST) - Şifremi unuttum
- `/portal/login/cancel` (POST) - Akışı iptal etme

---

## AŞAMA 2: VERİ MODELİ TASLAĞI

### Genel Tasarım Prensipleri

1. **İş Bazlı Sözleşme:** Her `jobs` kaydı için 1 adet `job_contracts` kaydı olacak.
2. **OTP Yeniden Kullanım:** Mevcut `customer_login_tokens` tablosunu genişletmek yerine, yeni bir `usage` alanı ekleyerek hem LOGIN hem CONTRACT OTP ihtiyaçlarını karşılayabiliriz. Ancak daha temiz bir yaklaşım için ayrı bir `contract_otp_tokens` tablosu öneriyorum (tekrar kullanılabilirlik açısından).
3. **Şablon Sistemi:** Sözleşme şablonları için `contract_templates` tablosu oluşturulacak.
4. **Onay Loglama:** IP, user agent, telefon, onay zamanı gibi bilgiler `job_contracts` tablosunda tutulacak.

### Önerilen Veri Modeli

#### 1. `job_contracts` Tablosu

Her iş için bir sözleşme kaydı.

```sql
CREATE TABLE job_contracts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER NOT NULL UNIQUE,  -- Her iş için tek sözleşme
  template_id INTEGER,  -- FK -> contract_templates
  status TEXT NOT NULL CHECK(status IN ('PENDING', 'SENT', 'APPROVED', 'EXPIRED', 'REJECTED')) DEFAULT 'PENDING',
  approval_method TEXT NOT NULL DEFAULT 'SMS_OTP' CHECK(approval_method IN ('SMS_OTP', 'EMAIL_OTP', 'MANUAL')),
  
  -- Onay bilgileri (approved ise dolu olur)
  approved_at TEXT,
  approved_phone TEXT,  -- Onaylayan telefon
  approved_ip TEXT,  -- Onaylayan IP adresi
  approved_user_agent TEXT,  -- Onaylayan tarayıcı
  approved_customer_id INTEGER,  -- FK -> customers (doğrulama için)
  
  -- SMS/OTP gönderim bilgileri
  sms_sent_at TEXT,  -- İlk SMS gönderim zamanı
  sms_sent_count INTEGER DEFAULT 0,  -- Kaç kez SMS gönderildi
  last_sms_token_id INTEGER,  -- Son gönderilen OTP token ID (FK -> contract_otp_tokens)
  
  -- Sözleşme metni/PDF referansı
  contract_text TEXT,  -- Metin formatında sözleşme içeriği (şablondan türetilmiş)
  contract_pdf_path TEXT,  -- PDF dosya yolu (generated)
  contract_hash TEXT,  -- Sözleşme içeriği hash (değişiklik takibi için)
  
  -- Metadata
  metadata TEXT,  -- JSON: ek bilgiler (admin notları, red sebebi vs.)
  expires_at TEXT,  -- Sözleşme geçerlilik süresi (varsayılan: job başlangıç tarihi)
  
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  
  FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY(template_id) REFERENCES contract_templates(id) ON DELETE SET NULL,
  FOREIGN KEY(approved_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY(last_sms_token_id) REFERENCES contract_otp_tokens(id) ON DELETE SET NULL
);

CREATE INDEX idx_job_contracts_job_id ON job_contracts(job_id);
CREATE INDEX idx_job_contracts_status ON job_contracts(status);
CREATE INDEX idx_job_contracts_expires_at ON job_contracts(expires_at);
```

**Notlar:**
- `job_id` UNIQUE constraint ile her iş için tek sözleşme garantisi.
- `status` değerleri:
  - `PENDING`: Henüz SMS gönderilmedi
  - `SENT`: SMS gönderildi, onay bekleniyor
  - `APPROVED`: Onaylandı
  - `EXPIRED`: Süresi doldu
  - `REJECTED`: Reddedildi (manuel olarak)

#### 2. `contract_templates` Tablosu

Sözleşme şablonları (hukuki metinler, PDF referansları).

```sql
CREATE TABLE contract_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL DEFAULT 'cleaning_job' CHECK(type IN ('cleaning_job', 'maintenance_job', 'recurring_cleaning')),
  name TEXT NOT NULL,  -- Şablon adı (örn: "Temizlik İşi Sözleşmesi v1.0")
  version TEXT NOT NULL DEFAULT '1.0',  -- Versiyon numarası
  description TEXT,  -- Şablon açıklaması
  
  -- İçerik
  template_text TEXT NOT NULL,  -- Şablon metni (placeholders: {customer_name}, {job_date}, {amount} vs.)
  template_variables TEXT,  -- JSON: Kullanılabilir placeholder'lar ve açıklamaları
  pdf_template_path TEXT,  -- PDF şablon dosya yolu (opsiyonel)
  
  -- Aktiflik
  is_active INTEGER NOT NULL DEFAULT 1,  -- 1 = aktif, 0 = pasif
  is_default INTEGER NOT NULL DEFAULT 0,  -- Varsayılan şablon mu?
  
  -- Metadata
  content_hash TEXT,  -- Şablon içeriği hash (değişiklik takibi)
  created_by INTEGER,  -- FK -> users (admin)
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_contract_templates_type ON contract_templates(type);
CREATE INDEX idx_contract_templates_active ON contract_templates(is_active);
CREATE INDEX idx_contract_templates_default ON contract_templates(is_default);
```

**Notlar:**
- Şablon metninde placeholder'lar kullanılabilir: `{customer_name}`, `{job_id}`, `{job_date}`, `{job_amount}`, `{service_name}`, `{address}`, vb.
- İleride PDF template desteği eklenebilir.

#### 3. `contract_otp_tokens` Tablosu

Sözleşme onayı için OTP kodları. Login OTP'den ayrı tutulması önerilir (separation of concerns).

```sql
CREATE TABLE contract_otp_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_contract_id INTEGER NOT NULL,  -- FK -> job_contracts
  customer_id INTEGER NOT NULL,  -- FK -> customers (onaylayacak müşteri)
  token TEXT NOT NULL,  -- password_hash ile hashlenmiş kod
  phone TEXT NOT NULL,  -- Gönderilen telefon numarası
  channel TEXT NOT NULL DEFAULT 'sms' CHECK(channel IN ('sms', 'email')),
  
  -- Zamanlama
  expires_at TEXT NOT NULL,  -- Kod son kullanma zamanı
  sent_at TEXT NOT NULL DEFAULT (datetime('now')),  -- Gönderim zamanı
  verified_at TEXT,  -- NULL ise henüz doğrulanmamış
  
  -- Güvenlik
  attempts INTEGER NOT NULL DEFAULT 0,  -- Hatalı deneme sayısı
  max_attempts INTEGER NOT NULL DEFAULT 5,
  ip_address TEXT,  -- Gönderim yapan IP
  user_agent TEXT,  -- Gönderim yapan tarayıcı
  
  -- Metadata
  meta TEXT,  -- JSON: ek bilgiler
  
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  
  FOREIGN KEY(job_contract_id) REFERENCES job_contracts(id) ON DELETE CASCADE,
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE INDEX idx_contract_otp_tokens_job_contract ON contract_otp_tokens(job_contract_id);
CREATE INDEX idx_contract_otp_tokens_customer ON contract_otp_tokens(customer_id);
CREATE INDEX idx_contract_otp_tokens_token ON contract_otp_tokens(token);
CREATE INDEX idx_contract_otp_tokens_expires ON contract_otp_tokens(expires_at);
CREATE INDEX idx_contract_otp_tokens_verified ON contract_otp_tokens(verified_at);
```

**Notlar:**
- `token` alanı `password_hash()` ile hashlenmiş OTP kodu içerir (plain text saklanmaz).
- `verified_at` NULL ise kod henüz kullanılmamış, dolu ise doğrulanmış.
- `channel` şu an sadece 'sms' kullanılacak, ileride email desteği eklenebilir.

### Alternatif Yaklaşım: `customer_login_tokens` Genişletme

Eğer kod tekrarını azaltmak isterseniz, mevcut `customer_login_tokens` tablosunu genişletebilirsiniz:

```sql
-- Mevcut tabloya eklenecek alanlar:
ALTER TABLE customer_login_tokens ADD COLUMN usage_type TEXT DEFAULT 'LOGIN' CHECK(usage_type IN ('LOGIN', 'CONTRACT', 'PASSWORD_RESET'));
ALTER TABLE customer_login_tokens ADD COLUMN related_entity_type TEXT;  -- 'job_contract' gibi
ALTER TABLE customer_login_tokens ADD COLUMN related_entity_id INTEGER;  -- job_contract_id gibi

CREATE INDEX idx_customer_login_tokens_usage ON customer_login_tokens(usage_type);
CREATE INDEX idx_customer_login_tokens_entity ON customer_login_tokens(related_entity_type, related_entity_id);
```

**Artıları:**
- Tek bir OTP servisi kullanılabilir
- Kod tekrarı azalır

**Eksileri:**
- Tablo amacı karışır (login + contract)
- İleride başka modüller için genişletme zorlaşır
- Query'ler daha karmaşık hale gelir

**Öneri:** Ayrı tablo yaklaşımını tercih ediyoruz (ayrışma ve bakım kolaylığı).

---

## ÖZET VE SONRAKİ ADIMLAR

### Tespit Edilen Mevcut Sistem Özellikleri

1. ✅ **Job Modeli:** Mevcut ve çalışıyor
2. ✅ **Customer Modeli:** Telefon, email, şifre yönetimi mevcut
3. ✅ **OTP Altyapısı:** `CustomerOtpService` ile login OTP sistemi aktif
4. ✅ **SMS Servisi:** `SMSQueue` ile NetGSM entegrasyonu mevcut
5. ✅ **Frontend Operasyon Ekranları:** İş listesi ve detay sayfaları mevcut
6. ✅ **Portal Login Akışı:** OTP + şifre belirleme flow'u çalışıyor

### Önerilen Veri Modeli Özeti

1. **`job_contracts`** - Her iş için sözleşme kaydı
2. **`contract_templates`** - Sözleşme şablonları
3. **`contract_otp_tokens`** - Sözleşme onay OTP kodları (login OTP'den ayrı)

### Tasarım Kararları

1. ✅ Login OTP ile Contract OTP ayrı tablolarda tutulacak (separation of concerns)
2. ✅ Her iş için tek sözleşme garantisi (UNIQUE constraint)
3. ✅ Şablon sistemi ile ileride kolay genişletme
4. ✅ Onay loglama (IP, user agent, telefon, zaman) `job_contracts` içinde
5. ✅ SMS gönderim sayısı takibi (`sms_sent_count`, `last_sms_token_id`)

### Sonraki Adımlar (İmplementasyon İçin)

1. **Migration Dosyaları Oluşturma:**
   - `db/migrations/XXX_job_contracts.sql`
   - `db/migrations/XXX_contract_templates.sql`
   - `db/migrations/XXX_contract_otp_tokens.sql`

2. **Model Sınıfları:**
   - `src/Models/JobContract.php`
   - `src/Models/ContractTemplate.php`
   - `src/Models/ContractOtpToken.php` (veya `ContractOtpService` içinde)

3. **Servis Sınıfları:**
   - `src/Services/ContractOtpService.php` (veya `JobContractOtpService.php`)
   - `src/Services/ContractTemplateService.php` (şablon render için)

4. **Controller Güncellemeleri:**
   - `JobController::show()` - Sözleşme durumu gösterimi ve SMS gönderme butonu
   - `JobController::sendContractSms()` - SMS gönderme endpoint'i
   - `PublicContractController` (yeni) - Public sözleşme görüntüleme ve onay ekranı

5. **Frontend Güncellemeleri:**
   - `src/Views/jobs/show.php` - Sözleşme durumu ve buton eklenecek
   - `src/Views/contracts/view.php` (yeni) - Public sözleşme onay ekranı
   - `src/Views/contracts/approved.php` (yeni) - Onay başarılı ekranı

6. **Routing:**
   - `/jobs/{id}/contract/send-sms` (POST) - SMS gönderme
   - `/contract/{token}` (GET) - Public sözleşme görüntüleme (token ile güvenli link)
   - `/contract/{token}/approve` (POST) - Sözleşme onaylama

---

**Rapor Hazırlayan:** AI Pair Programmer (Cursor)  
**Tarih:** 2025-01-XX  
**Not:** Bu rapor sadece analiz ve tasarım aşamasıdır. Henüz kod değişikliği yapılmamıştır.

