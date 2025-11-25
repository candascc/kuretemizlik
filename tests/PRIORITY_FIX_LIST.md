# Öncelikli Düzeltme Listesi

Tarih: 2025-11-24

## Kritik (Sistem Çökmesine Neden Olan Hatalar)

### 1. Roles::getAll() Method Not Found
**Dosya**: `src/Lib/Permission.php:162`
**Etkilenen Testler**: 
- `tests/functional/RbacAccessTest.php`

**Hata**: 
```
Call to undefined method Roles::getAll() in Permission.php:162
```

**Çözüm**: 
- `Roles` class'ında `getAll()` method'unu ekle veya mevcut method adını kontrol et
- `Permission::getUserPermissions()` metodunu düzelt

**Öncelik**: Yüksek - RBAC sistemi çalışmıyor

---

### 2. redirect() Function Not Found
**Dosya**: `tests/functional/ApiFeatureTest.php`
**Etkilenen Testler**:
- `tests/functional/ApiFeatureTest.php`

**Hata**:
```
Call to undefined function redirect()
```

**Çözüm**:
- `tests/functional/ApiFeatureTest.php` dosyasına `require_once __DIR__ . '/../TestHelper.php';` ekle
- Veya `config.php`'deki `redirect()` function'ının yüklendiğinden emin ol

**Öncelik**: Yüksek - API testleri çalışmıyor

---

## Yüksek (Çok Sayıda Testi Etkileyen Hatalar)

### 3. Database Table Name Validation - test_transaction2
**Dosya**: `tests/unit/TransactionRollbackTest.php`
**Etkilenen Testler**:
- `tests/unit/TransactionRollbackTest.php::testTransactionCommitsOnSuccess`

**Hata**:
```
InvalidArgumentException: Invalid table name: test_transaction2
```

**Çözüm**:
- `Database::insert()` metodunun table name validation'ını kontrol et
- Test tablo isimlerini whitelist'e ekle veya validation'ı test ortamı için bypass et
- Alternatif: Test tabloları için özel bir prefix kullan (örn: `_test_`)

**Öncelik**: Orta - Sadece bir test etkileniyor ama transaction testleri önemli

---

### 4. NOT NULL Constraint Failed: resident_users.email
**Dosya**: `tests/unit/ResidentUserLookupTest.php`
**Etkilenen Testler**:
- `tests/unit/ResidentUserLookupTest.php` (2 test)

**Hata**:
```
SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: resident_users.email
```

**Çözüm**:
- `ResidentUserLookupTest::seedResident()` metodunda `email` field'ını NULL yerine geçerli bir email değeri ver
- Veya database schema'da `email` field'ını NULL'a izin verecek şekilde güncelle (test ortamı için)

**Öncelik**: Orta - Sadece bir test dosyası etkileniyor

---

### 5. No Such Column: j.company_id
**Dosya**: `tests/functional/ApiFeatureTest.php`
**Etkilenen Testler**:
- `tests/functional/ApiFeatureTest.php`

**Hata**:
```
SQLSTATE[HY000]: General error: 1 no such column: j.company_id
```

**Çözüm**:
- SQL query'de `j.company_id` kullanılıyor ama `jobs` tablosunda bu kolon yok
- Query'yi düzelt veya migration ile `company_id` kolonunu ekle
- Test data setup'ını kontrol et

**Öncelik**: Orta - Functional test etkileniyor

---

## Orta (Belirli Test Kategorilerini Etkileyen Hatalar)

### 6. ResidentLoginControllerTest - Assertion Failure
**Dosya**: `tests/unit/ResidentLoginControllerTest.php`
**Etkilenen Testler**:
- `tests/unit/ResidentLoginControllerTest.php::testProcessLoginSetsOtpStepForFirstTimeUser`

**Hata**:
```
Failed asserting that two strings are identical.
Expected: 'otp'
Actual: 'password'
```

**Çözüm**:
- Test logic'ini kontrol et - resident user'ın password'ü var mı?
- `ResidentController::processLogin()` metodunun logic'ini kontrol et
- Test setup'ında password olmadığından emin ol

**Öncelik**: Düşük - Sadece bir test assertion'ı

---

### 7. CsrfProtectionTest - Assertion Failure
**Dosya**: `tests/security/CsrfProtectionTest.php`
**Etkilenen Testler**:
- `tests/security/CsrfProtectionTest.php` (1 failure)

**Hata**: (Detaylı hata mesajı rapor edilmemiş)

**Çözüm**:
- Test'i tekrar çalıştırıp detaylı hata mesajını al
- CSRF token validation logic'ini kontrol et

**Öncelik**: Düşük - Security test ama sadece bir assertion

---

### 8. CustomerOtpServiceTest - Assertion Failure
**Dosya**: `tests/CustomerOtpServiceTest.php`
**Etkilenen Testler**:
- `tests/CustomerOtpServiceTest.php` (1 failure)

**Hata**: (Detaylı hata mesajı rapor edilmemiş)

**Çözüm**:
- Test'i tekrar çalıştırıp detaylı hata mesajını al
- Customer OTP service logic'ini kontrol et

**Öncelik**: Düşük - Sadece bir test assertion'ı

---

## Düşük (Tekil Test Hataları veya Test Çalıştırmayan Dosyalar)

### 9. PHPUnit Test Class Olmayan Dosyalar
**Dosyalar**:
- `tests/unit/ContractTemplateSelectionTest.php`
- `tests/unit/JobContractFlowTest.php`
- `tests/functional/JobCustomerFinanceFlowTest.php`
- `tests/functional/RbacAccessTest.php` (standalone script)
- `tests/functional/ResidentProfileTest.php`
- `tests/functional/ResidentPaymentTest.php`
- `tests/functional/ManagementResidentsTest.php`
- `tests/functional/PaymentTransactionTest.php`
- `tests/functional/AuthSessionTest.php`
- `tests/functional/HeaderSecurityTest.php`
- `tests/performance/PerformanceTest.php`

**Sorun**: Bu dosyalar PHPUnit `TestCase` extend etmiyor, bu yüzden PHPUnit tarafından test olarak tanınmıyor.

**Çözüm**:
- Bu dosyaları PHPUnit test class'ına dönüştür (TestCase extend et)
- Veya bu dosyaları farklı bir test runner ile çalıştır
- Veya bu dosyaları test suite'den çıkar

**Öncelik**: Düşük - Bu dosyalar zaten çalışmıyor, acil değil

---

## Özet

- **Kritik**: 2 hata (Roles::getAll(), redirect() function)
- **Yüksek**: 3 hata (Database validation, NOT NULL constraint, missing column)
- **Orta**: 3 hata (Assertion failures)
- **Düşük**: 11 dosya (PHPUnit test class değil)

**Toplam Düzeltme Gereken**: 19 sorun

**Tahmini Çalışma Süresi**:
- Kritik: 2-4 saat
- Yüksek: 3-5 saat
- Orta: 2-3 saat
- Düşük: 5-10 saat (test class'a dönüştürme)

**Toplam**: 12-22 saat
