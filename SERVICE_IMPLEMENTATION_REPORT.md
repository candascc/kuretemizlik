# SERVICE IMPLEMENTATION RAPORU

**Tarih:** 2025-01-XX  
**Aşama:** Service Katmanı Implementasyonu  
**Durum:** ✅ Tamamlandı

---

## ÖZET

Bu aşamada, iş bazlı sözleşme sistemi için 2 yeni service sınıfı oluşturuldu:

1. **ContractTemplateService** - Şablon render ve job contract oluşturma
2. **ContractOtpService** - OTP üretme, gönderme ve doğrulama

---

## OLUŞTURULAN DOSYALAR

### 1. `src/Services/ContractTemplateService.php`

**Amaç:** Sözleşme şablonu işlemleri ve job contract oluşturma

**Public Metotlar:**

#### `getDefaultCleaningJobTemplate(): ?array`
- Varsayılan temizlik işi şablonunu getirir
- `contract_templates` tablosundan `type='cleaning_job'`, `is_active=1`, `is_default=1` olan kaydı döndürür
- Bulunamazsa `null` döndürür (exception fırlatmaz, controller seviyesinde karar verilebilir)

**Kullanılan Model Metodları:**
- `ContractTemplate::getDefault('cleaning_job')`

#### `renderCleaningJobContractText(array $template, array $job, ?array $customer = null): string`
- Şablon metnindeki placeholder'ları doldurur
- **Placeholder'lar:**
  - `{customer_name}`, `{customer_phone}`, `{customer_email}`
  - `{job_id}`, `{job_date}`, `{job_time}`, `{job_datetime}`
  - `{job_address}`, `{job_price}`, `{job_amount}`, `{job_total_amount}`
  - `{service_type}`, `{service_name}`, `{job_description}`, `{job_status}`
- Basit `str_replace` ile placeholder replacement yapılır
- TODO: İleride daha gelişmiş placeholder engine'e evrilebilir (Twig, Mustache vb.)

**Kullanılan Yardımcılar:**
- `Utils::formatDateTime()` - Tarih formatlama
- `Utils::formatMoney()` - Para formatlama
- `htmlspecialchars()` - XSS koruması

#### `createJobContractForJob($job, ?array $customer = null): array`
- Bir iş için sözleşme oluşturur (veya mevcut olanı döndürür)
- İşlemler:
  1. Job kaydını kontrol eder
  2. Mevcut sözleşme var mı kontrol eder (`JobContract::findByJobId()`)
  3. Yoksa varsayılan şablonu çeker (`getDefaultCleaningJobTemplate()`)
  4. Sözleşme metnini render eder (`renderCleaningJobContractText()`)
  5. Contract hash hesaplar (SHA256)
  6. JobContract kaydı oluşturur (`JobContract::create()`)
- Başarısız olursa exception fırlatır

**Kullanılan Model Metodları:**
- `Job::find($jobId)`
- `Customer::find($customerId)`
- `JobContract::findByJobId($jobId)`
- `JobContract::create($data)`
- `JobContract::find($contractId)`

---

### 2. `src/Services/ContractOtpService.php`

**Amaç:** Sözleşme onayı için OTP üretme, gönderme ve doğrulama

**Sabitler:**
- `OTP_LENGTH = 6`
- `EXPIRY_INTERVAL = '+10 minutes'` (Login OTP'den farklı: 10 dakika)
- `MAX_ATTEMPTS = 5`
- `RESEND_COOLDOWN_SECONDS = 60`

**Public Metotlar:**

#### `createAndSendOtp(array $contract, array $customer, string $phone): array`
- OTP oluşturur ve SMS ile gönderir
- **İşlemler:**
  1. Telefon numarası validasyonu (`Utils::normalizePhone()`)
  2. Cooldown kontrolü (60 saniye içinde tekrar gönderilmesini engeller)
  3. Rate limit kontrolü (saat başına maksimum 10 OTP)
  4. 6 haneli OTP üretir (`generateOtpCode()`)
  5. OTP'yi hashler (`password_hash()`)
  6. `ContractOtpToken` kaydı oluşturur
  7. SMS mesajı hazırlar ve `SMSQueue` ile gönderir
  8. `JobContract::incrementSmsCount()` çağırır
- Başarısız olursa exception fırlatır

**Kullanılan Model Metodları:**
- `ContractOtpToken::findActiveByJobContract($contractId)`
- `ContractOtpToken::create($data)`
- `ContractOtpToken::find($tokenId)`
- `JobContract::incrementSmsCount($contractId, $tokenId)`

**Kullanılan Servisler:**
- `SMSQueue::add()` - SMS kuyruğa ekleme
- `SMSQueue::process(1)` - SMS'i hemen işleme

**SMS Mesaj Formatı:**
```
Küre Temizlik – {job_date} tarihli temizlik hizmeti sözleşmenizi onaylamak için doğrulama kodunuz: {code}. Kod 10 dakika geçerlidir.
```

#### `verifyOtp(array $contract, string $rawCode, ?string $ip = null, ?string $userAgent = null): array`
- OTP'yi doğrular ve başarılıysa sözleşmeyi onaylar
- **İşlemler:**
  1. Aktif token bulur (`findActiveByJobContract()`)
  2. Süre kontrolü (expired check)
  3. Attempt kontrolü (max attempts check)
  4. OTP doğrulama (`password_verify()`)
  5. Yanlışsa `incrementAttempts()` çağırır
  6. Doğruysa:
     - Token'ı verified olarak işaretler (`update()`)
     - Sözleşmeyi onaylar (`JobContract::approve()`)
     - Activity log kaydeder (varsa)
- Return format: `['success' => bool, 'reason' => string|null, 'message' => string, 'attempts_remaining' => int|null]`

**Kullanılan Model Metodları:**
- `ContractOtpToken::findActiveByJobContract($contractId)`
- `ContractOtpToken::incrementAttempts($tokenId)`
- `ContractOtpToken::update($tokenId, $data)`
- `JobContract::approve($contractId, $phone, $customerId, $ip, $userAgent)`

#### `hasActiveOtp(array $contract): bool`
- Bu sözleşme için aktif bir OTP var mı kontrol eder
- `findActiveByJobContract()` kullanır

**Private Metotlar:**
- `generateOtpCode(): string` - 6 haneli random OTP üretir (0-999999 arası)

---

## ÖNEMLİ TASARIM KARARLARI

### 1. Exception Handling
- **ContractTemplateService:**
  - `getDefaultCleaningJobTemplate()`: `null` döndürür (exception fırlatmaz)
  - Diğer metotlar: Exception fırlatır (job/customer bulunamazsa, şablon boşsa)
- **ContractOtpService:**
  - Validation hatalarında exception fırlatır
  - `verifyOtp()`: Exception fırlatmaz, `['success' => false, 'reason' => ...]` formatında döner (proje pattern'ine uygun)

### 2. OTP Politikası
- **Süre:** 10 dakika (Login OTP'den farklı: 5 dakika)
- **Cooldown:** 60 saniye (tekrar gönderme engelleme)
- **Rate Limit:** Saat başına maksimum 10 OTP
- **Max Attempts:** 5 hatalı deneme hakkı

### 3. Token Yönetimi
- Eski aktif token'lar expire edilmez (kullanıcı eski veya yeni kodu deneyebilir)
- Ancak cooldown kontrolü yapılır (spam önleme)
- Her OTP ayrı kayıt olarak saklanır (audit için)

### 4. Placeholder Rendering
- Basit `str_replace` kullanılır
- XSS koruması için `htmlspecialchars()` uygulanır
- İleride gelişmiş template engine'e (Twig, Mustache) evrilebilir
- TODO yorumu eklendi

### 5. Activity Logging
- `verifyOtp()` içinde `ActivityLogger::log()` kullanılır (eğer class varsa)
- Hata durumunda sessizce geçer (kritik değil)

---

## KULLANILAN MODEL METODLARI ÖZETİ

### JobContract Model:
- ✅ `findByJobId($jobId)` - Job ID ile sözleşme bulma
- ✅ `create($data)` - Yeni sözleşme oluşturma
- ✅ `find($id)` - ID ile sözleşme getirme
- ✅ `incrementSmsCount($id, $tokenId = null)` - SMS sayısını artırma
- ✅ `approve($id, $phone, $customerId, $ipAddress, $userAgent)` - Sözleşme onaylama

### ContractOtpToken Model:
- ✅ `findActiveByJobContract($jobContractId)` - Aktif token bulma
- ✅ `create($data)` - Yeni token oluşturma
- ✅ `find($id)` - ID ile token getirme
- ✅ `incrementAttempts($id)` - Deneme sayısını artırma
- ✅ `update($id, $data)` - Token güncelleme

### ContractTemplate Model:
- ✅ `getDefault($type)` - Varsayılan şablon getirme

### Job Model:
- ✅ `find($id)` - Job getirme

### Customer Model:
- ✅ `find($id)` - Customer getirme

---

## MEVCUT SERVİSLERLE UYUM

✅ **CustomerOtpService Pattern'ine Uyum:**
- Aynı constructor pattern (Database, SMSQueue instantiation)
- Aynı exception handling yaklaşımı
- Aynı OTP generation mantığı (`random_int`, `str_pad`)
- Aynı password hash/verify kullanımı

✅ **SMSQueue Entegrasyonu:**
- `SMSQueue::add()` ile SMS kuyruğa ekleme
- `SMSQueue::process(1)` ile hemen işleme (OTP için kritik)

✅ **Logger Kullanımı:**
- ActivityLogger kullanımı (eğer class varsa)
- Error logging (exception catch bloklarında)

---

## SONRAKİ ADIMLAR

Service katmanı tamamlandı. Sonraki aşamalarda:

1. **Controller Güncellemeleri:**
   - `JobController::show()` - Sözleşme durumu gösterimi
   - `JobController::sendContractSms()` - SMS gönderme endpoint'i
   - `PublicContractController` (yeni) - Public sözleşme görüntüleme ve onay

2. **View Dosyaları:**
   - `jobs/show.php` güncellemeleri (sözleşme durumu butonu)
   - `contracts/view.php` (yeni) - Public sözleşme onay ekranı

3. **Routing:**
   - `/jobs/{id}/contract/send-sms` (POST)
   - `/contract/{token}` (GET) - Public sözleşme görüntüleme
   - `/contract/{token}/approve` (POST) - OTP ile onaylama

---

## TEST ÖNERİLERİ

1. **ContractTemplateService Test Senaryoları:**
   - Şablon bulunamazsa null dönüyor mu?
   - Placeholder replacement doğru çalışıyor mu?
   - Mevcut sözleşme varsa yeniden oluşturmuyor mu?

2. **ContractOtpService Test Senaryoları:**
   - Cooldown kontrolü çalışıyor mu?
   - Rate limit çalışıyor mu?
   - OTP doğrulama başarılı/başarısız durumları
   - Max attempts sonrası token kilitleniyor mu?

---

**Rapor Hazırlayan:** AI Pair Programmer (Cursor)  
**Tarih:** 2025-01-XX  
**Durum:** ✅ Service implementasyonu tamamlandı

