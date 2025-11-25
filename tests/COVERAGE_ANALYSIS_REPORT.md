# Test Coverage Analizi - Detaylı Rapor

**Oluşturulma Tarihi:** 2025-11-25

## Özet

Bu rapor, tüm test suite'lerinin kod kapsamını (coverage) detaylı olarak analiz eder.

## Genel Kapsam İstatistikleri

| Metrik | Toplam | Kapsanan | Kapsam % |
|--------|--------|----------|----------|
| Dosyalar | - | - | - |
| Satırlar | 36,733 | 342 | 0.93% |
| Sınıflar | 247 | 0 | 0% |
| Metotlar | 2,530 | 33 | 1.3% |

## Suite Bazında Kapsam Analizi

### Phase 1 Suite
- **Dosyalar:** Çeşitli
- **Satır Kapsamı:** 0.15%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 0.28%
- **Test Dosyaları:**
  - SessionHelperTest.php
  - ArrayAccessSafetyTest.php
  - ErrorHandlingTest.php
  - ExceptionHandlerTest.php
  - ViewExtractSafetyTest.php
  - RecurringOccurrenceMigrationTest.php
  - SessionManagementTest.php
  - RecurringJobGenerationTest.php
  - SessionCookiePathTest.php

### Phase 2 Suite
- **Dosyalar:** Çeşitli
- **Satır Kapsamı:** 1.01%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 1.26%
- **Test Dosyaları:**
  - ValidatorSecurityTest.php
  - XssPreventionTest.php
  - TransactionRollbackTest.php
  - RateLimitingTest.php
  - FileUploadValidationTest.php
  - CsrfMiddlewareTest.php
  - PasswordResetSecurityTest.php

### Phase 4 Suite
- **Dosyalar:** Çeşitli
- **Satır Kapsamı:** 0.08%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 0.28%
- **Test Dosyaları:**
  - ControllerTraitTest.php
  - AppConstantsTest.php

### Fast Suite
- **Dosyalar:** Tüm unit testler
- **Satır Kapsamı:** 2.53%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 3.4%
- **Kapsam:** Tüm `tests/unit/` klasöründeki testler

### Slow Suite
- **Dosyalar:** Integration ve functional testler
- **Satır Kapsamı:** 1.45%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 2.02%
- **Kapsam:** `tests/integration/` ve `tests/functional/` klasörlerindeki testler

### Stress Suite
- **Durum:** Coverage verisi bulunamadı
- **Not:** Stress testleri genellikle performans testleri olduğu için coverage analizi yapılmamış olabilir

### Load Suite
- **Dosyalar:** Load testleri
- **Satır Kapsamı:** 0.38%
- **Sınıf Kapsamı:** 0%
- **Metot Kapsamı:** 0.59%
- **Kapsam:** `tests/load/` klasöründeki testler

## Detaylı Dosya Bazında Kapsam

### Yüksek Kapsamlı Dosyalar

1. **SessionHelper.php**
   - Metot Kapsamı: 25% (1/4)
   - Satır Kapsamı: 60.29% (41/68)

2. **ResidentOtpService.php**
   - Metot Kapsamı: 22.22% (2/9)
   - Satır Kapsamı: 67.13% (96/143)

3. **SMSQueue.php**
   - Metot Kapsamı: 25% (3/12)
   - Satır Kapsamı: 47.69% (93/195)

### Düşük Kapsamlı Dosyalar

1. **RateLimitHelper.php**
   - Metot Kapsamı: 0% (0/5)
   - Satır Kapsamı: 23.08% (12/52)

2. **ResidentUser.php**
   - Metot Kapsamı: 13.64% (3/22)
   - Satır Kapsamı: 6.45% (12/186)

3. **Validator.php**
   - Metot Kapsamı: 8.89% (4/45)
   - Satır Kapsamı: 23.56% (82/348)

## Öneriler

1. **Kritik Dosyalar İçin Test Artırımı:**
   - Validator.php için daha fazla test case eklenmeli
   - ResidentUser.php için model testleri genişletilmeli
   - RateLimitHelper.php için test coverage artırılmalı

2. **Integration Testleri:**
   - Controller'lar için integration testleri eklenmeli
   - Service sınıfları için end-to-end testler yazılmalı

3. **Coverage Hedefleri:**
   - Minimum satır kapsamı: %80
   - Minimum metot kapsamı: %75
   - Kritik dosyalar için: %90+

## Coverage Raporlarına Erişim

- **Phase 1:** `tests/coverage/phase_1/index.html`
- **Phase 2:** `tests/coverage/phase_2/index.html`
- **Phase 4:** `tests/coverage/phase_4/index.html`
- **Fast:** `tests/coverage/fast/index.html`
- **Slow:** `tests/coverage/slow/index.html`
- **Load:** `tests/coverage/load/index.html`

## Notlar

- Coverage analizi `phpdbg` kullanılarak yapılmıştır
- Tüm test suite'leri ayrı ayrı analiz edilmiştir
- HTML raporları detaylı dosya bazında kapsam bilgisi içerir
- Clover XML formatı CI/CD entegrasyonu için mevcuttur




