# ROUND 34 – STAGE 2: PHP 8 UYUMLULUK TESPİTİ & DÜZELTMELER

**Tarih:** 2025-11-22  
**Round:** ROUND 34

---

## PHP 8 UYUMSUZLUK SORUNLARI

### 1. SecurityStatsService::getRecentSecurityEvents()

**Sorun:**
- **Dosya:** `src/Services/SecurityStatsService.php`
- **Satır:** 233
- **Hata:** Optional parametre (`$limit = 20`) required parametreden (`$companyId`) önce geliyor
- **PHP 8 Davranışı:** Fatal error: "Optional parameter $limit declared before required parameter $companyId"

**Önceki İmza:**
```php
private function getRecentSecurityEvents(int $limit = 20, ?int $companyId): array
```

**Düzeltilmiş İmza:**
```php
private function getRecentSecurityEvents(?int $companyId = null, int $limit = 20): array
```

**Değişiklikler:**
1. Parametre sırası değiştirildi: `$companyId` önce, `$limit` sonra
2. `$companyId` optional yapıldı (`= null` default değeri eklendi)
3. Çağrı yeri güncellendi: `getRecentSecurityEvents(20, $companyId)` → `getRecentSecurityEvents($companyId, 20)`

---

## DİĞER POTANSİYEL SORUNLAR (TARAMA SONUCU)

### 2. PaymentService::createIncomeWithPayment()

**Dosya:** `src/Services/PaymentService.php`  
**Satır:** 23  
**İmza:** `public static function createIncomeWithPayment(int $jobId, float $amount, string $paidAt, ?string $note = null, ?string $category = null): int`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 3. PaymentService::createJobPayment()

**Dosya:** `src/Services/PaymentService.php`  
**Satır:** 144  
**İmza:** `public static function createJobPayment(int $jobId, float $amount, string $paidAt, ?string $note = null, ?int $financeId = null): int`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 4. PaymentService::createPaymentRequest()

**Dosya:** `src/Services/PaymentService.php`  
**Satır:** 178  
**İmza:** `public function createPaymentRequest($feeId, $amount, $method = 'card', $transactionId = null)`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 5. FileUploadService::getFiles()

**Dosya:** `src/Services/FileUploadService.php`  
**Satır:** 872  
**İmza:** `public function getFiles($filters = [], $limit = 50, $offset = 0)`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 6. ContractOtpService::verifyOtp()

**Dosya:** `src/Services/ContractOtpService.php`  
**Satır:** 195  
**İmza:** `public function verifyOtp(array $contract, string $rawCode, ?string $ip = null, ?string $userAgent = null, ?\DateTimeInterface $now = null): array`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 7. ExportService::exportJobs()

**Dosya:** `src/Services/ExportService.php`  
**Satır:** 11  
**İmza:** `public static function exportJobs(array $filters = [], string $format = 'csv'): string`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

### 8. CustomerOtpService::requestToken()

**Dosya:** `src/Services/CustomerOtpService.php`  
**Satır:** 27  
**İmza:** `public function requestToken(array $customer, string $channel, ?string $ipAddress = null, string $context = 'login'): array`  
**Durum:** ⚠️ **DİKKAT** - `$context = 'login'` required parametre ama optional gibi görünüyor, ancak `$ipAddress` optional olduğu için sorun yok

### 9. ResidentOtpService::requestToken()

**Dosya:** `src/Services/ResidentOtpService.php`  
**Satır:** 29  
**İmza:** `public function requestToken(array $resident, string $channel = 'sms', ?string $ipAddress = null, string $context = 'login'): array`  
**Durum:** ⚠️ **DİKKAT** - `$context = 'login'` required parametre ama optional gibi görünüyor, ancak `$ipAddress` optional olduğu için sorun yok

### 10. DebtCollectionService::getOverdueDebts()

**Dosya:** `src/Services/DebtCollectionService.php`  
**Satır:** 23  
**İmza:** `public function getOverdueDebts($buildingId = null, $daysOverdue = null): array`  
**Durum:** ✅ **UYUMLU** (tüm parametreler optional)

### 11. EmailService::send()

**Dosya:** `src/Services/EmailService.php`  
**Satır:** 33  
**İmza:** `public static function send(string $to, string $subject, string $body, bool $isHtml = true, array $options = []): bool`  
**Durum:** ✅ **UYUMLU** (optional parametreler sonda)

---

## ÖZET

**Toplam Tespit Edilen Sorun:** 1  
**Düzeltilen Sorun:** 1  
**Kalan Sorun:** 0

**Düzeltilen Dosya:**
- `src/Services/SecurityStatsService.php` - `getRecentSecurityEvents()` imza düzeltmesi

---

**STAGE 2 TAMAMLANDI** ✅

