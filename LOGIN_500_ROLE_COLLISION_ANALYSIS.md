# LOGIN 500 - ROLE COLLISION ANALYSIS (STAGE 0)

**Tarih**: 2024-12-XX  
**Görev**: PATH B - Admin Rolleri Çakışma Analizi  
**Durum**: READ-ONLY ANALİZ (Kod Değişikliği YOK)

---

## 1. TESPİT EDİLEN TÜM ROL TİPLERİ

### 1.1. Sistem Rolleri (config/roles.php)

| Rol Adı | Hierarchy | Scope | Açıklama |
|---------|-----------|-------|----------|
| `SUPERADMIN` | 100 | staff | Sistem Yöneticisi - Tam yetki |
| `ADMIN` | 90 | staff | Operasyon Yöneticisi - Operasyon ve yönetim modüllerinde tam yetki |
| `OPERATOR` | 70 | staff | Operasyon Uzmanı - Saha planlama ve iş yönetimi |
| `SITE_MANAGER` | 65 | staff | Site Yöneticisi - Apartman/site finansı |
| `FINANCE` | 60 | staff | Finans Uzmanı - Tahsilat ve finansal raporlama |
| `SUPPORT` | 55 | staff | Destek Uzmanı - Saha ve sakin iletişimi |
| `MANAGEMENT` | 50 | staff | Yönetim Analisti - Salt-okunur erişim |
| `OPERATOR_READONLY` | - | staff | Operatör salt-okunur (kod içinde kullanılıyor, config'de yok) |

**NOT**: Tüm rol isimleri **BÜYÜK HARF** ile tanımlı (`ADMIN`, `SUPERADMIN`, `OPERATOR`).

---

## 2. ROL KONTROL NOKTALARI VE KULLANIM ŞEKİLLERİ

### 2.1. Auth.php - Core Authentication

#### `Auth::hasRole(string $role): bool`
- **Kullanım**: `$user['role'] === 'SUPERADMIN'`, `$user['role'] === 'ADMIN'`, `$user['role'] === $role`
- **Satır**: 600-644
- **Özellikler**:
  - SUPERADMIN her zaman true döner
  - ADMIN her zaman true döner (company scope başka yerde kontrol ediliyor)
  - Direkt role match kontrolü
  - Roles tablosundan hierarchy kontrolü (opsiyonel)

#### `Auth::requireRole($role): void`
- **Kullanım**: Array veya string kabul eder: `['ADMIN', 'SUPERADMIN']` veya `'ADMIN'`
- **Satır**: 688-736
- **Özellikler**:
  - `in_array($currentRole, $roles, true)` ile kontrol
  - SUPERADMIN her zaman geçer
  - Roles class hierarchy kontrolü (opsiyonel)
  - Role::canManage() kontrolü (opsiyonel)

#### `Auth::requireAdmin(): void`
- **Kullanım**: `self::requireRole(['ADMIN', 'SUPERADMIN'])`
- **Satır**: 738-743
- **Özellikler**: Admin ve SuperAdmin için ortak kontrol

#### `Auth::isSuperAdmin(): bool`
- **Kullanım**: `Auth::isSuperAdmin()`
- **Satır**: 745-756
- **ÖZEL DURUM**: 
  ```php
  if (isset($_SESSION['username']) && $_SESSION['username'] === 'candas') {
      return true;
  }
  return self::role() === 'SUPERADMIN';
  ```
  - **HARD-CODED USERNAME CHECK**: `username === 'candas'` durumunda otomatik SUPERADMIN
  - Bu, rol sisteminden bağımsız bir bypass mekanizması

#### `Auth::canAccess(string $resource): bool`
- **Kullanım**: `$user['role'] === 'ADMIN'` kontrolü
- **Satır**: 758-785
- **Özellikler**: ADMIN her şeye erişebilir

#### `Auth::completeLogin(array $user): bool`
- **Kullanım**: `$_SESSION['role'] = $user['role'];`
- **Satır**: 312
- **ÖNEMLİ**: Role değeri direkt olarak session'a atanıyor, **normalize edilmiyor**
  - Eğer DB'de `role` küçük harf (`admin`) veya karışık (`Admin`) ise, session'a da öyle kaydedilir
  - Bu, case-sensitive kontrollerde sorun yaratabilir

### 2.2. AuthMiddleware.php

#### `AuthMiddleware::requireAdmin(): callable`
- **Kullanım**: `in_array($user['role'], ['ADMIN', 'SUPERADMIN'], true)`
- **Satır**: 40
- **Özellikler**: Strict comparison (`true` parametresi)

#### `AuthMiddleware::requireOperatorReadOnly(): callable`
- **Kullanım**: `in_array($user['role'], ['ADMIN', 'OPERATOR', 'OPERATOR_READONLY'])`
- **Satır**: 64
- **Özellikler**: ADMIN, OPERATOR ve OPERATOR_READONLY erişebilir

### 2.3. Header Context (header-context.php)

#### `build_app_header_context()`
- **Kullanım**: 
  - `HeaderManager::getCurrentRole()` (opsiyonel)
  - `Auth::role() === 'SUPERADMIN'` (fallback)
- **Satır**: 115-124, 258-263
- **Özellikler**:
  - HeaderManager varsa ondan role alıyor
  - Yoksa `Auth::role()` ile kontrol ediyor
  - `Auth::role()` → `$_SESSION['role']` döndürüyor

### 2.4. Dashboard Views

#### `dashboard/today.php`
- **Kullanım**: `Auth::role() !== 'OPERATOR'`
- **Satır**: 9, 153, 218, 274
- **Özellikler**: OPERATOR rolü için UI elementleri gizleniyor

#### `layout/header.php`
- **Kullanım**: `Auth::role() !== 'OPERATOR'`
- **Satır**: 632
- **Özellikler**: Floating action button OPERATOR için gizleniyor

### 2.5. Root Route (index.php)

#### `/` Route Handler
- **Kullanım**: `HeaderManager::getCurrentMode()` → dashboard seçimi
- **Satır**: 777-825
- **Özellikler**:
  - Management mode ise `/management/dashboard`'a yönlendiriyor
  - Değilse `DashboardController::today()` çağırıyor
  - **Role kontrolü yok** - sadece mode kontrolü var

---

## 3. POTANSİYEL ÇAKIŞMA VE TUTARSIZLIK NOKTALARI

### 3.1. KRİTİK: Hard-Coded Username Bypass

**Konum**: `Auth::isSuperAdmin()` (Auth.php:751-753)

```php
if (isset($_SESSION['username']) && $_SESSION['username'] === 'candas') {
    return true;
}
```

**Sorun**:
- `username === 'candas'` olan kullanıcı, **rolü ne olursa olsun** SUPERADMIN yetkisine sahip
- Bu, rol sisteminden bağımsız bir bypass mekanizması
- `admin` ve `test_admin` kullanıcıları bu kontrolden geçmiyor (username farklı)

**Etki**: 
- `candas` kullanıcısı için özel durum var
- Diğer admin kullanıcıları için bu bypass çalışmıyor

---

### 3.2. KRİTİK: Role Normalization Eksikliği

**Konum**: `Auth::completeLogin()` (Auth.php:312)

```php
$_SESSION['role'] = $user['role'];
```

**Sorun**:
- Role değeri DB'den geldiği gibi session'a atanıyor
- Eğer DB'de `role` küçük harf (`admin`) veya karışık (`Admin`) ise, session'a da öyle kaydedilir
- Ancak tüm kontroller **BÜYÜK HARF** (`ADMIN`, `SUPERADMIN`) bekliyor

**Etki**:
- DB'de `role = 'admin'` (küçük harf) olan kullanıcı için:
  - `$user['role'] === 'ADMIN'` → **false** (case-sensitive)
  - `in_array($user['role'], ['ADMIN', 'SUPERADMIN'], true)` → **false** (strict comparison)
  - Bu kullanıcı admin yetkilerine erişemez

**Test Senaryosu**:
- `admin` kullanıcısının DB'de `role = 'admin'` (küçük harf) olması durumunda
- Login sonrası `$_SESSION['role'] = 'admin'` (küçük harf)
- `Auth::hasRole('ADMIN')` → false
- `AuthMiddleware::requireAdmin()` → redirect to `/`

---

### 3.3. ORTA: HeaderManager Role Dependency

**Konum**: `build_app_header_context()` (header-context.php:115-124)

```php
$currentRole = $options['role'] ?? null;
if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['role'])) {
    try {
        $currentRole = HeaderManager::getCurrentRole();
    } catch (Throwable $e) {
        // Ignore - $currentRole remains null
    }
}
```

**Sorun**:
- HeaderManager yoksa veya exception fırlatırsa, `$currentRole = null` kalıyor
- Bu role, navigation items ve quick actions için kullanılıyor
- `null` role ile HeaderManager metodları çağrılırsa ne olur?

**Etki**:
- HeaderManager başarısız olursa, role bilgisi kaybolabilir
- Navigation items ve quick actions yanlış gösterilebilir

---

### 3.4. ORTA: isSuperAdmin() vs role() === 'SUPERADMIN' Tutarsızlığı

**Konum**: 
- `Auth::isSuperAdmin()` → username check + role check
- `header-context.php:262` → sadece `Auth::role() === 'SUPERADMIN'`

**Sorun**:
- `isSuperAdmin()` hem username hem role kontrolü yapıyor
- `header-context.php` sadece role kontrolü yapıyor
- `candas` kullanıcısı için `isSuperAdmin()` true, ama `role() === 'SUPERADMIN'` false olabilir

**Etki**:
- `candas` kullanıcısı bazı yerlerde SUPERADMIN, bazı yerlerde değil gibi görünebilir

---

### 3.5. DÜŞÜK: OPERATOR_READONLY Rol Tanımı Eksik

**Konum**: 
- `AuthMiddleware::requireOperatorReadOnly()` → `['ADMIN', 'OPERATOR', 'OPERATOR_READONLY']`
- `config/roles.php` → OPERATOR_READONLY tanımı yok

**Sorun**:
- Kod içinde `OPERATOR_READONLY` kullanılıyor ama config'de tanımlı değil
- Bu rol DB'de var mı? Yoksa sadece kod içinde mi kullanılıyor?

**Etki**:
- OPERATOR_READONLY rolüne sahip kullanıcılar için navigation/quick actions doğru çalışmayabilir

---

## 4. ADMIN/TEST_ADMIN KULLANICILARI İÇİN ÖZEL DURUMLAR

### 4.1. Username-Based Hard-Coded Checks

**Tespit Edilen**:
- `Auth::isSuperAdmin()` → `username === 'candas'` check
- **`admin` ve `test_admin` için özel username check YOK**

### 4.2. Role-Based Checks

**Tespit Edilen**:
- Tüm admin kontrolleri `role === 'ADMIN'` veya `role === 'SUPERADMIN'` üzerinden yapılıyor
- Username bazlı özel case yok (`admin` veya `test_admin` için)

### 4.3. Dashboard Route Flow

**Akış**:
1. Login → `AuthController::processLogin()` → `redirect(base_url('/'))`
2. `/` route → `HeaderManager::getCurrentMode()` → mode kontrolü
3. Mode = 'operations' → `DashboardController::today()`
4. `DashboardController::today()` → `Auth::require()` → auth check
5. View render → `build_app_header_context()` → role check

**Role Kontrol Noktaları**:
- `Auth::require()` → sadece auth check, role check yok
- `DashboardController::today()` → role check yok
- `build_app_header_context()` → role check var (navigation/quick actions için)

---

## 5. ÖNERİLER (STAGE 1 ve STAGE 2 için)

### 5.1. STAGE 1 - Log Enstrümantasyonu

**Eklenecek Log Alanları**:
- `user_role` → `$user['role'] ?? null`
- `session_role` → `$_SESSION['role'] ?? null`
- `role_normalized` → `strtoupper(trim($role ?? ''))`
- `is_admin_like` → `in_array($role, ['ADMIN', 'SUPERADMIN'], true)`
- `username` → `$user['username'] ?? null` (hard-coded check için)

**Log Noktaları**:
1. `AuthController::processLogin()` - Login başarılı olduğunda
2. `Auth::completeLogin()` - Session set edildikten sonra
3. `Auth::regenerateSession()` - Session regenerate edildikten sonra
4. `DashboardController::today()` - İlk request'te
5. `build_app_header_context()` - Header context build sırasında

### 5.2. STAGE 2 - Fix Önerileri

**Öncelik 1 - Role Normalization**:
- `Auth::completeLogin()` içinde role'ü normalize et:
  ```php
  $_SESSION['role'] = strtoupper(trim($user['role'] ?? ''));
  ```

**Öncelik 2 - isAdminLike() Helper**:
- `Auth::isAdminLike($user)` helper fonksiyonu ekle:
  ```php
  public static function isAdminLike($user): bool {
      $role = strtoupper(trim($user['role'] ?? ''));
      return in_array($role, ['ADMIN', 'SUPERADMIN'], true);
  }
  ```

**Öncelik 3 - Header Context Role Fallback**:
- `build_app_header_context()` içinde HeaderManager başarısız olursa:
  ```php
  if ($currentRole === null && class_exists('Auth') && Auth::check()) {
      $currentRole = Auth::role();
  }
  ```

---

## 6. SONUÇ

### 6.1. Tespit Edilen Ana Sorunlar

1. **Role Normalization Eksikliği**: DB'den gelen role değeri normalize edilmeden session'a atanıyor
2. **Hard-Coded Username Bypass**: `candas` kullanıcısı için özel durum var, diğer admin'ler için yok
3. **HeaderManager Dependency**: Role bilgisi HeaderManager'a bağımlı, fallback eksik

### 6.2. admin ve test_admin Kullanıcıları İçin

- Her iki kullanıcı da **aynı role değerine** sahip (kullanıcı bildirdi)
- Sorun muhtemelen **role normalization** veya **header context role fallback** ile ilgili
- İlk login'de role bilgisi eksik/null olabilir, F5 sonrası düzeliyor olabilir

### 6.3. Sonraki Adımlar

- **STAGE 1**: Role bilgili log enstrümantasyonu ekle
- **STAGE 2**: Role normalization ve isAdminLike() helper ekle
- **STAGE 2**: Header context role fallback iyileştir

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE 1 - Role Bilgili Enstrümantasyon

