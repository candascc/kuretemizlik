# Standalone Scripts - Detaylı Hata Analizi

Tarih: 2025-11-24

## Hata 1: JobContractFlowTest.php - Contract Creation Failure

### Hata Detayları
- **Dosya**: `tests/unit/JobContractFlowTest.php`
- **Test**: `testCreateJobContract()`
- **Hata**: `Exception: Sözleşme oluşturulurken hata oluştu.`
- **Lokasyon**: `src/Services/ContractTemplateService.php:359`
- **Exit Code**: 1

### Stack Trace
```
ContractTemplateService.php:359 -> Exception thrown
JobContractFlowTest.php -> testCreateJobContract() -> Contract creation attempt
```

### Olası Nedenler
1. **Database Constraint Violation**
   - Foreign key constraint hatası
   - NOT NULL constraint hatası
   - Unique constraint hatası

2. **Required Field Missing**
   - Contract oluşturma için gerekli field'lar eksik
   - Default değerler set edilmemiş

3. **Service Logic Error**
   - ContractTemplateService'te validation hatası
   - Business logic hatası

### Etkilenen Fonksiyonellik
- Job contract oluşturma
- Contract template service
- OTP token generation (skip ediliyor çünkü contract yok)

### Önerilen İnceleme
- `ContractTemplateService.php:359` satırını incele
- Exception'ın gerçek nedenini log'la
- Database schema'yı kontrol et
- Required field'ları doğrula

---

## Hata 2: RbacAccessTest.php - Roles::getAll() Method Not Found

### Hata Detayları
- **Dosya**: `tests/functional/RbacAccessTest.php`
- **Hata**: `Fatal error: Call to undefined method Roles::getAll()`
- **Lokasyon**: `src/Lib/Permission.php:162`
- **Exit Code**: 255
- **Kritiklik**: 🔴 KRİTİK - Sistem çökmesi

### Stack Trace
```
Permission.php:162 -> Roles::getAll() called
Permission.php:45 -> Permission::getUserPermissions(89)
Auth.php:893 -> Permission::has('jobs.create')
Auth.php:917 -> Auth::hasPermission('jobs.create')
RbacAccessTest.php:100 -> Auth::can('jobs.create')
RbacAccessTest.php:223 -> testOperatorRoleAccess()
RbacAccessTest.php:235 -> runAll()
```

### Sorun Analizi
1. **Roles Class'ında getAll() Method Yok**
   - `Roles` class'ı tanımlı ama `getAll()` method'u yok
   - Alternatif method adı kullanılıyor olabilir
   - Method silinmiş veya rename edilmiş olabilir

2. **Permission System Bağımlılığı**
   - Tüm permission kontrolleri `Roles::getAll()` kullanıyor
   - Bu method olmadan RBAC sistemi çalışmıyor

3. **Etkilenen Sistemler**
   - Tüm role-based access kontrolleri
   - Permission checks
   - Auth::can() ve Auth::hasPermission() methodları
   - User role management

### Kritik Etki
- **Production Risk**: Yüksek
- **Güvenlik Risk**: Kritik
- **Sistem Durumu**: RBAC sistemi tamamen çalışmıyor

### Önerilen Düzeltme
1. `Roles` class'ını bul ve incele
2. `getAll()` method'unu ekle veya
3. `Permission::getUserPermissions()` metodunu `Roles::getAll()` kullanmayacak şekilde düzelt
4. Alternatif method adını kullan (örn: `all()`, `list()`, `getRoles()`)

### İnceleme Gereken Dosyalar
- `src/Models/Roles.php` (varsa)
- `src/Lib/Permission.php:162`
- `src/Lib/Permission.php:45` (getUserPermissions method)
- Database'de `roles` tablosu

---

## Hata 3: PerformanceTest.php - PHPUnit TestCase Not Found

### Hata Detayları
- **Dosya**: `tests/performance/PerformanceTest.php`
- **Hata**: `Fatal error: Class "PHPUnit\Framework\TestCase" not found`
- **Lokasyon**: `tests/performance/PerformanceTest.php:16`
- **Exit Code**: 255

### Sorun Analizi
1. **PHPUnit Dependency**
   - Dosya `extends TestCase` kullanıyor
   - PHPUnit autoload/bootstrap yok
   - Standalone çalıştırılamıyor

2. **Dosya Tipi Uyumsuzluğu**
   - PHPUnit test class'ı olarak yazılmış
   - Standalone script olarak çalıştırılmaya çalışılıyor

### Çözüm Seçenekleri
1. **PHPUnit Bootstrap Eklemek**
   ```php
   require_once __DIR__ . '/../../vendor/autoload.php';
   require_once __DIR__ . '/../bootstrap.php';
   ```

2. **Standalone Script'e Dönüştürmek**
   - `extends TestCase` kaldır
   - PHPUnit assertion'ları yerine custom assertion'lar kullan
   - Standalone çalıştırılabilir hale getir

3. **PHPUnit ile Çalıştırmak**
   - Standalone değil, PHPUnit test suite'ine dahil et
   - `php vendor/bin/phpunit tests/performance/PerformanceTest.php`

### Önerilen Yaklaşım
- Dosya zaten PHPUnit test class'ı olarak yazılmış
- PHPUnit ile çalıştırılmalı, standalone değil
- Veya standalone script'e dönüştürülmeli

---

## Özet Tablo

| Script | Durum | Exit Code | Kritiklik | Etki |
|--------|-------|-----------|-----------|------|
| ContractTemplateSelectionTest.php | ✅ PASS | 0 | - | - |
| JobContractFlowTest.php | ❌ FAIL | 1 | 🟡 Yüksek | Contract creation |
| JobCustomerFinanceFlowTest.php | ✅ PASS | 0 | - | - |
| RbacAccessTest.php | ❌ FATAL | 255 | 🔴 Kritik | RBAC sistemi |
| ResidentProfileTest.php | ✅ PASS | 0 | - | - |
| ResidentPaymentTest.php | ✅ PASS | 0 | - | - |
| ManagementResidentsTest.php | ✅ PASS | 0 | - | - |
| PaymentTransactionTest.php | ✅ PASS | 0 | - | - |
| AuthSessionTest.php | ✅ PASS | 0 | - | - |
| HeaderSecurityTest.php | ✅ PASS | 0 | - | - |
| PerformanceTest.php | ❌ FATAL | 255 | 🟢 Orta | Dependency |

## Öncelik Sırası

### 🔴 Acil (Sistem Çökmesi)
1. **RbacAccessTest.php** - RBAC sistemi tamamen çalışmıyor
   - Tüm permission kontrolleri başarısız
   - Production'da ciddi güvenlik sorunu
   - **Acil düzeltme gerekli**

### 🟡 Yüksek (Fonksiyonellik Hatası)
2. **JobContractFlowTest.php** - Contract oluşturma başarısız
   - Job contract flow çalışmıyor
   - OTP gönderme skip ediliyor
   - **İncelenmeli ve düzeltilmeli**

### 🟢 Orta (Dependency Sorunu)
3. **PerformanceTest.php** - PHPUnit dependency eksik
   - Standalone çalıştırılamıyor
   - PHPUnit ile çalıştırılmalı veya standalone'a dönüştürülmeli
   - **Düşük öncelik**

## Sonuç

- **Toplam**: 11 script
- **Başarılı**: 8 (73%)
- **Başarısız**: 3 (27%)
  - 1 fonksiyonellik hatası
  - 2 fatal error (1 kritik, 1 dependency)

**En kritik sorun**: RBAC sistemi tamamen çalışmıyor. Acil düzeltme gerekiyor.

