# Test Kategorileri ve Bağımlılıkları

Tarih: 2025-11-24

## Kategori Tanımları

### Phase 1 Tests (9 dosya)
Temel sistem testleri - Session, Error Handling, View Safety

### Phase 2 Tests (7 dosya)
Güvenlik testleri - Validator, XSS, Transaction, Rate Limiting, File Upload, CSRF, Password Reset

### Phase 4 Tests (2 dosya)
Code Quality testleri - ControllerTrait, AppConstants

### Unit Tests (Other) (15 dosya)
Diğer unit testler - Controller, Service, Model testleri

### Integration Tests (Other) (1 dosya)
Controller integration testleri

### Functional Tests (9 dosya)
End-to-end fonksiyonel testler - API, Payment, Auth, RBAC

### Security Tests (3 dosya)
Güvenlik odaklı testler - XSS, SQL Injection, CSRF

### Performance Tests (1 dosya)
Performans testleri

### Root Tests (3 dosya)
Root klasöründeki testler

## Test Bağımlılıkları

### Bootstrap Kullanımı

#### Bootstrap.php Kullanan Testler
- tests/unit/SessionHelperTest.php
- tests/unit/ExceptionHandlerTest.php
- tests/unit/ErrorHandlingTest.php
- tests/integration/SessionManagementTest.php
- tests/integration/SessionCookiePathTest.php
- tests/unit/ControllerTraitTest.php
- tests/unit/XssPreventionTest.php
- tests/unit/TransactionRollbackTest.php
- tests/unit/PasswordResetSecurityTest.php

#### Manuel Require Kullanan Testler
- tests/unit/ResidentLoginControllerTest.php - TestHelper.php, config.php, Database.php, ResidentController.php, ResidentUser.php
- tests/unit/PortalLoginControllerTest.php - TestHelper.php, config.php, Database.php, PortalController.php
- tests/ResidentOtpServiceTest.php - config.php, ResidentOtpService.php
- tests/unit/ResidentAuthValidationTest.php - config.php, Validator.php
- tests/unit/ResidentOtpServiceFlowTest.php - config.php, Database.php, Utils.php, ResidentUser.php, ResidentOtpService.php, EmailQueue.php, SMSQueue.php
- tests/unit/ResidentUserLookupTest.php - config.php, Database.php, ResidentUser.php
- tests/functional/ApiFeatureTest.php - autoload.php, config.php, Database.php, ResponseFormatter.php, InputSanitizer.php, Validator.php, JWTAuth.php, Auth.php, JobController.php, FileUploadController.php

### Database Bağımlılıkları

#### Transaction Kullanan Testler
- tests/unit/ResidentLoginControllerTest.php
- tests/unit/ResidentOtpServiceFlowTest.php
- tests/unit/ResidentUserLookupTest.php
- tests/ResidentOtpServiceTest.php
- tests/unit/PasswordResetSecurityTest.php
- tests/functional/ApiFeatureTest.php

#### Schema Setup Gerektiren Testler
- tests/unit/ResidentUserLookupTest.php - resident_users schema
- tests/unit/ResidentOtpServiceFlowTest.php - resident_users, resident_login_tokens
- tests/ResidentOtpServiceTest.php - resident_users, resident_login_tokens
- tests/unit/PasswordResetSecurityTest.php - resident_users, resident_login_tokens, buildings, units

### Session Bağımlılıkları

#### Session Kullanan Testler
- tests/unit/SessionHelperTest.php
- tests/integration/SessionManagementTest.php
- tests/integration/SessionCookiePathTest.php
- tests/unit/ResidentLoginControllerTest.php
- tests/unit/PortalLoginControllerTest.php
- tests/unit/CsrfMiddlewareTest.php

### Auth/Login Gereksinimleri

#### Login Gerektiren Testler
- tests/functional/RbacAccessTest.php - User login + roles
- tests/functional/ApiFeatureTest.php - JWT token
- tests/functional/AuthSessionTest.php - Session auth

### Redirect Function Gereksinimleri

#### Redirect Kullanan Testler
- tests/unit/ResidentLoginControllerTest.php - redirect() function
- tests/unit/PortalLoginControllerTest.php - redirect() function
- tests/functional/ApiFeatureTest.php - redirect() function

## Test Durumları

### Başarılı Testler (33 dosya)
- Phase 1: 9/9
- Phase 2: 6/7 (TransactionRollbackTest hata veriyor)
- Phase 4: 2/2
- Unit Tests (Other): 11/15
- Integration Tests: 1/1
- Security Tests: 2/3
- Root Tests: 2/3

### Başarısız Testler (3 dosya)
- tests/unit/ResidentLoginControllerTest.php - 1 failure
- tests/security/CsrfProtectionTest.php - 1 failure
- tests/CustomerOtpServiceTest.php - 1 failure

### Hata Veren Testler (3 dosya)
- tests/unit/TransactionRollbackTest.php - Invalid table name validation
- tests/unit/ResidentUserLookupTest.php - NOT NULL constraint failed: resident_users.email
- tests/functional/ApiFeatureTest.php - redirect() function not found, no such column: j.company_id

### Test Çalıştırmayan Dosyalar (11 dosya)
- tests/unit/ContractTemplateSelectionTest.php - PHPUnit test class değil
- tests/unit/JobContractFlowTest.php - PHPUnit test class değil
- tests/functional/JobCustomerFinanceFlowTest.php - PHPUnit test class değil
- tests/functional/RbacAccessTest.php - PHPUnit test class değil (standalone script)
- tests/functional/ResidentProfileTest.php - PHPUnit test class değil
- tests/functional/ResidentPaymentTest.php - PHPUnit test class değil
- tests/functional/ManagementResidentsTest.php - PHPUnit test class değil
- tests/functional/PaymentTransactionTest.php - PHPUnit test class değil
- tests/functional/AuthSessionTest.php - PHPUnit test class değil
- tests/functional/HeaderSecurityTest.php - PHPUnit test class değil
- tests/performance/PerformanceTest.php - PHPUnit test class değil

