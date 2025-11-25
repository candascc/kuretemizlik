# Kapsamlı Test Hata Raporu
Tarih: 2025-11-24 20:55:29
Toplam Test Dosyası: 50
Başarılı: 33
Başarısız: 3
Hata: 3

## Kategori Bazında Hatalar

### Database Hataları

- **tests/unit/ResidentUserLookupTest.php**: Veritabanı sorgusu başarısız: SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: resident_users.email
- **tests/unit/ResidentUserLookupTest.php**: Veritabanı sorgusu başarısız: SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: resident_users.email
- **tests/functional/ApiFeatureTest.php**: Veritabanı sorgusu başarısız: SQLSTATE[HY000]: General error: 1 no such column: j.company_id

### Other Hataları

- **tests/unit/TransactionRollbackTest.php**: Invalid table name: test_transaction2
- **tests/functional/RbacAccessTest.php**: Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\src\Lib\Permission.php:162
- **tests/functional/RbacAccessTest.php**: Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\vendor\phpunit\phpunit\src\TextUI\Command.php:101
- **tests/functional/RbacAccessTest.php**: Uncaught Error: Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\src\Lib\Permission.php:162
- **tests/functional/ApiFeatureTest.php**: Call to undefined function redirect()

## Test Bazında Detaylı Rapor

### SessionHelperTest
- Dosya: tests/unit/SessionHelperTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 6
- Assertion Sayısı: 15

### ArrayAccessSafetyTest
- Dosya: tests/unit/ArrayAccessSafetyTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 6
- Assertion Sayısı: 16

### ErrorHandlingTest
- Dosya: tests/unit/ErrorHandlingTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 4

### ExceptionHandlerTest
- Dosya: tests/unit/ExceptionHandlerTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 4

### ViewExtractSafetyTest
- Dosya: tests/unit/ViewExtractSafetyTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 4
- Assertion Sayısı: 8

### RecurringOccurrenceMigrationTest
- Dosya: tests/unit/RecurringOccurrenceMigrationTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 4

### SessionManagementTest
- Dosya: tests/integration/SessionManagementTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 5
- Assertion Sayısı: 10

### RecurringJobGenerationTest
- Dosya: tests/integration/RecurringJobGenerationTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 2
- Assertion Sayısı: 2

### SessionCookiePathTest
- Dosya: tests/integration/SessionCookiePathTest.php
- Kategori: Phase 1
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 3

### ValidatorSecurityTest
- Dosya: tests/unit/ValidatorSecurityTest.php
- Kategori: Phase 2
- Durum: PASS
- Test Sayısı: 9
- Assertion Sayısı: 28

### XssPreventionTest
- Dosya: tests/unit/XssPreventionTest.php
- Kategori: Phase 2
- Durum: PASS
- Test Sayısı: 7
- Assertion Sayısı: 36

### TransactionRollbackTest
- Dosya: tests/unit/TransactionRollbackTest.php
- Kategori: Phase 2
- Durum: ERROR
- Test Sayısı: 6
- Assertion Sayısı: 8
- Başarısız: 0
- Hatalar: 1
- Hata Mesajları:
  - Invalid table name: test_transaction2

### RateLimitingTest
- Dosya: tests/unit/RateLimitingTest.php
- Kategori: Other
- Durum: PASS
- Test Sayısı: 5
- Assertion Sayısı: 19

### FileUploadValidationTest
- Dosya: tests/unit/FileUploadValidationTest.php
- Kategori: Other
- Durum: PASS
- Test Sayısı: 7
- Assertion Sayısı: 20

### CsrfMiddlewareTest
- Dosya: tests/unit/CsrfMiddlewareTest.php
- Kategori: Other
- Durum: PASS
- Test Sayısı: 7
- Assertion Sayısı: 26

### PasswordResetSecurityTest
- Dosya: tests/unit/PasswordResetSecurityTest.php
- Kategori: Other
- Durum: PASS
- Test Sayısı: 6
- Assertion Sayısı: 15

### ControllerTraitTest
- Dosya: tests/unit/ControllerTraitTest.php
- Kategori: Phase 4
- Durum: PASS
- Test Sayısı: 10
- Assertion Sayısı: 30

### AppConstantsTest
- Dosya: tests/unit/AppConstantsTest.php
- Kategori: Phase 4
- Durum: PASS
- Test Sayısı: 11
- Assertion Sayısı: 83

### ResidentLoginControllerTest
- Dosya: tests/unit/ResidentLoginControllerTest.php
- Kategori: Unit Tests (Other)
- Durum: FAIL
- Test Sayısı: 2
- Assertion Sayısı: 7
- Başarısız: 1
- Hatalar: 0
- Hata Mesajları:

### PortalLoginControllerTest
- Dosya: tests/unit/PortalLoginControllerTest.php
- Kategori: Unit Tests (Other)
- Durum: PASS
- Test Sayısı: 2
- Assertion Sayısı: 10

### InputSanitizerTest
- Dosya: tests/unit/InputSanitizerTest.php
- Kategori: Unit Tests (Other)
- Durum: PASS
- Test Sayısı: 12
- Assertion Sayısı: 14

### ControllerHelperTest
- Dosya: tests/unit/ControllerHelperTest.php
- Kategori: Unit Tests (Other)
- Durum: PASS
- Test Sayısı: 12
- Assertion Sayısı: 29

### ContractTemplateSelectionTest
- Dosya: tests/unit/ContractTemplateSelectionTest.php
- Kategori: Unit Tests (Other)
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### JobContractFlowTest
- Dosya: tests/unit/JobContractFlowTest.php
- Kategori: Unit Tests (Other)
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### ResidentUserLookupTest
- Dosya: tests/unit/ResidentUserLookupTest.php
- Kategori: Unit Tests (Other)
- Durum: ERROR
- Test Sayısı: 2
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 2
- Hata Mesajları:
  - Veritabanı sorgusu başarısız: SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: resident_users.email
  - Veritabanı sorgusu başarısız: SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: resident_users.email

### ResidentAuthValidationTest
- Dosya: tests/unit/ResidentAuthValidationTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 6
- Assertion Sayısı: 11

### ResidentOtpServiceFlowTest
- Dosya: tests/unit/ResidentOtpServiceFlowTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 2
- Assertion Sayısı: 6

### ResponseFormatterTest
- Dosya: tests/unit/ResponseFormatterTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 19

### ResidentContactVerificationServiceTest
- Dosya: tests/unit/ResidentContactVerificationServiceTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 5
- Assertion Sayısı: 16

### ResidentPortalMetricsTest
- Dosya: tests/unit/ResidentPortalMetricsTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 2
- Assertion Sayısı: 7

### ResidentPortalMetricsCacheTest
- Dosya: tests/unit/ResidentPortalMetricsCacheTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 2
- Assertion Sayısı: 4

### ResidentNotificationPreferenceServiceTest
- Dosya: tests/unit/ResidentNotificationPreferenceServiceTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 12

### UtilsSanitizeTest
- Dosya: tests/unit/UtilsSanitizeTest.php
- Kategori: Database
- Durum: PASS
- Test Sayısı: 4
- Assertion Sayısı: 9

### ControllerIntegrationTest
- Dosya: tests/integration/ControllerIntegrationTest.php
- Kategori: Integration Tests (Other)
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 6

### JobCustomerFinanceFlowTest
- Dosya: tests/functional/JobCustomerFinanceFlowTest.php
- Kategori: Functional Tests
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### RbacAccessTest
- Dosya: tests/functional/RbacAccessTest.php
- Kategori: Functional Tests
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:
  - Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\src\Lib\Permission.php:162
  - Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\vendor\phpunit\phpunit\src\TextUI\Command.php:101
  - Uncaught Error: Call to undefined method Roles::getAll() in C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\src\Lib\Permission.php:162

### ApiFeatureTest
- Dosya: tests/functional/ApiFeatureTest.php
- Kategori: Other
- Durum: ERROR
- Test Sayısı: 5
- Assertion Sayısı: 20
- Başarısız: 0
- Hatalar: 2
- Hata Mesajları:
  - Call to undefined function redirect()
  - Veritabanı sorgusu başarısız: SQLSTATE[HY000]: General error: 1 no such column: j.company_id

### ResidentProfileTest
- Dosya: tests/functional/ResidentProfileTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### ResidentPaymentTest
- Dosya: tests/functional/ResidentPaymentTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### ManagementResidentsTest
- Dosya: tests/functional/ManagementResidentsTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### PaymentTransactionTest
- Dosya: tests/functional/PaymentTransactionTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### AuthSessionTest
- Dosya: tests/functional/AuthSessionTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### HeaderSecurityTest
- Dosya: tests/functional/HeaderSecurityTest.php
- Kategori: Database
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### XssPreventionTest
- Dosya: tests/security/XssPreventionTest.php
- Kategori: Security Tests
- Durum: PASS
- Test Sayısı: 6
- Assertion Sayısı: 12

### SqlInjectionTest
- Dosya: tests/security/SqlInjectionTest.php
- Kategori: Security Tests
- Durum: PASS
- Test Sayısı: 4
- Assertion Sayısı: 5

### CsrfProtectionTest
- Dosya: tests/security/CsrfProtectionTest.php
- Kategori: Security Tests
- Durum: FAIL
- Test Sayısı: 6
- Assertion Sayısı: 9
- Başarısız: 1
- Hatalar: 0
- Hata Mesajları:

### PerformanceTest
- Dosya: tests/performance/PerformanceTest.php
- Kategori: Performance Tests
- Durum: unknown
- Test Sayısı: 0
- Assertion Sayısı: 0
- Başarısız: 0
- Hatalar: 0
- Hata Mesajları:

### ResidentOtpServiceTest
- Dosya: tests/ResidentOtpServiceTest.php
- Kategori: Root Tests
- Durum: PASS
- Test Sayısı: 3
- Assertion Sayısı: 18

### CustomerOtpServiceTest
- Dosya: tests/CustomerOtpServiceTest.php
- Kategori: Root Tests
- Durum: FAIL
- Test Sayısı: 4
- Assertion Sayısı: 18
- Başarısız: 1
- Hatalar: 0
- Hata Mesajları:

### HeaderManagerTest
- Dosya: tests/HeaderManagerTest.php
- Kategori: Root Tests
- Durum: PASS
- Test Sayısı: 7
- Assertion Sayısı: 40

