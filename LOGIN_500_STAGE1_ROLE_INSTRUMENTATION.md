# LOGIN 500 - STAGE 1: ROLE BİLGİLİ ENSTRÜMANTASYON

**Tarih**: 2024-12-XX  
**Görev**: PATH B - STAGE 1 - Role Bilgili Log Enstrümantasyonu  
**Durum**: TAMAMLANDI

---

## 1. EKLENEN LOG ALANLARI

### 1.1. Yeni Log Alanları

Her log satırına aşağıdaki alanlar eklendi:

- `user_role` / `session_role` → `$_SESSION['role']` değeri (normalize edilmiş)
- `db_role` → `Auth::user()['role']` değeri (DB'den gelen, normalize edilmiş)
- `Auth::role()` → `Auth::role()` metodundan dönen değer (normalize edilmiş)
- `username` → `$_SESSION['username']` değeri
- `is_admin_like` → `in_array($role, ['ADMIN', 'SUPERADMIN'], true)` sonucu (1 veya 0)

**Normalizasyon**: Tüm role değerleri `strtoupper(trim($role))` ile normalize edilerek loglanıyor.

---

## 2. GÜNCELLENEN DOSYALAR VE LOG NOKTALARI

### 2.1. AuthController.php

**Dosya**: `src/Controllers/AuthController.php`  
**Metod**: `processLogin()`  
**Satır**: ~271

**Eklenen Log Alanları**:
- `user_role` → Session'daki role (normalize)
- `username` → Session'daki username
- `is_admin_like` → Admin/SuperAdmin kontrolü

**Log Formatı**:
```
[STAGE1] [AuthController::processLogin] ... user_role={$userRoleNormalized}, username={$username}, is_admin_like={0|1}
```

---

### 2.2. Auth.php - completeLogin()

**Dosya**: `src/Lib/Auth.php`  
**Metod**: `completeLogin()`  
**Satır**: ~328 (AFTER_SESSION_SET), ~340 (AFTER_REGENERATE)

**Eklenen Log Alanları**:
- `session_role` → Session'a atanan role (normalize)
- `db_role` → DB'den gelen role (normalize)
- `username` → Session'daki username
- `is_admin_like` → Admin/SuperAdmin kontrolü

**Log Formatı**:
```
[STAGE1] [Auth::completeLogin] AFTER_SESSION_SET ... session_role={$userRoleNormalized}, db_role={$dbUserRoleNormalized}, username={$username}, is_admin_like={0|1}
[STAGE1] [Auth::completeLogin] AFTER_REGENERATE ... user_role={$finalUserRoleNormalized}, username={$finalUsername}
```

**ÖNEMLİ**: Bu log, DB'den gelen role ile session'a atanan role'ün karşılaştırmasını yapmamıza olanak sağlıyor.

---

### 2.3. Auth.php - regenerateSession()

**Dosya**: `src/Lib/Auth.php`  
**Metod**: `regenerateSession()`  
**Satır**: ~258 (BEFORE), ~270 (AFTER), ~288 (AFTER_RESTART)

**Eklenen Log Alanları**:
- `user_role` → Session'daki role (normalize)
- `username` → Session'daki username

**Log Formatı**:
```
[STAGE1] [Auth::regenerateSession] BEFORE ... user_role={$userRoleNormalized}, username={$username}
[STAGE1] [Auth::regenerateSession] AFTER ... user_role={$userRoleNormalized}, username={$username}
[STAGE1] [Auth::regenerateSession] AFTER_RESTART ... user_role={$restartedUserRoleNormalized}, username={$restartedUsername}
```

**ÖNEMLİ**: Bu log, session regenerate sırasında role bilgisinin korunup korunmadığını gösteriyor.

---

### 2.4. DashboardController.php

**Dosya**: `src/Controllers/DashboardController.php`  
**Metod**: `today()`  
**Satır**: ~64 (AFTER_AUTH)

**Eklenen Log Alanları**:
- `session_role` → `$_SESSION['role']` (normalize)
- `Auth::role()` → `Auth::role()` metodundan dönen değer (normalize)
- `db_role` → `Auth::user()['role']` (normalize)
- `username` → Session'daki username
- `is_admin_like` → Admin/SuperAdmin kontrolü

**Log Formatı**:
```
[STAGE1] [DashboardController::today] AFTER_AUTH ... session_role={$sessionRoleNormalized}, Auth::role()={$authRoleNormalized}, db_role={$dbRoleNormalized}, username={$sessionUsername}, is_admin_like={0|1}
```

**ÖNEMLİ**: Bu log, dashboard'a ilk erişimde role bilgisinin durumunu gösteriyor. Üç farklı kaynaktan role bilgisi karşılaştırılıyor:
1. `$_SESSION['role']` (session)
2. `Auth::role()` (Auth helper)
3. `Auth::user()['role']` (DB)

---

### 2.5. header-context.php

**Dosya**: `src/Views/layout/partials/header-context.php`  
**Fonksiyon**: `build_app_header_context()`  
**Satır**: ~32 (START), ~471 (SUCCESS)

**Eklenen Log Alanları**:
- `session_role` → `$_SESSION['role']` (normalize)
- `Auth::role()` → `Auth::role()` metodundan dönen değer (normalize)
- `db_role` → `Auth::user()['role']` (normalize)
- `username` → Session'daki username
- `is_admin_like` → Admin/SuperAdmin kontrolü

**Log Formatı**:
```
[STAGE1] [build_app_header_context] START ... session_role={$sessionRoleNormalized}, Auth::role()={$authRoleNormalized}, db_role={$dbRoleNormalized}, username={$sessionUsername}, is_admin_like={0|1}
[STAGE1] [build_app_header_context] SUCCESS ... session_role={$finalSessionRoleNormalized}, Auth::role()={$finalAuthRoleNormalized}, db_role={$finalDbRoleNormalized}, username={$finalSessionUsername}, is_admin_like={0|1}
```

**ÖNEMLİ**: Bu log, header context build sırasında role bilgisinin durumunu gösteriyor. View rendering sırasında role bilgisi kayboluyor mu kontrol edilebilir.

---

## 3. LOG ANALİZİ İÇİN ÖNERİLER

### 3.1. Role Drift Tespiti

Log dosyasında (`logs/login_500_trace.log`) şu karşılaştırmaları yapın:

1. **DB vs Session Role**:
   - `db_role` vs `session_role` → Eğer farklıysa, role normalization sorunu var
   - Örnek: `db_role=admin` ama `session_role=ADMIN` → Normalizasyon çalışmış
   - Örnek: `db_role=admin` ama `session_role=admin` → Normalizasyon çalışmamış

2. **Session vs Auth::role()**:
   - `session_role` vs `Auth::role()` → Eğer farklıysa, Auth helper sorunu var
   - `Auth::role()` → `$_SESSION['role']` döndürüyor, bu yüzden aynı olmalı

3. **Role Kaybı**:
   - Login sonrası `session_role=ADMIN` ama dashboard'da `session_role=null` → Role kaybı var
   - Session regenerate sonrası role kayboluyor mu?

### 3.2. Admin/Test_Admin Kullanıcıları İçin

Log dosyasında şu kullanıcılar için arama yapın:

```
grep "username=admin\|username=test_admin" logs/login_500_trace.log
```

**Kontrol Edilecekler**:
1. `user_role` değeri nedir? (`ADMIN`, `admin`, `Admin`?)
2. `is_admin_like` değeri nedir? (`1` mi `0` mı?)
3. Login sonrası role kayboluyor mu?
4. Dashboard'a ilk erişimde role var mı?

### 3.3. İlk Login vs F5 Senaryosu

**İlk Login** (500 hatası):
- `[AuthController::processLogin]` → `user_role=?`
- `[Auth::completeLogin] AFTER_SESSION_SET` → `session_role=?`, `db_role=?`
- `[DashboardController::today] AFTER_AUTH` → `session_role=?`, `Auth::role()=?`, `db_role=?`
- `[build_app_header_context] START` → `session_role=?`, `Auth::role()=?`, `db_role=?`

**F5 Sonrası** (başarılı):
- `[DashboardController::today] AFTER_AUTH` → `session_role=?`, `Auth::role()=?`, `db_role=?`
- `[build_app_header_context] START` → `session_role=?`, `Auth::role()=?`, `db_role=?`

**Karşılaştırma**: İlk login'de role bilgisi eksik/null ise, F5 sonrası düzeliyor mu?

---

## 4. SONUÇ

### 4.1. Eklenen Log Noktaları

- ✅ `AuthController::processLogin()` - Login başarılı olduğunda
- ✅ `Auth::completeLogin()` - Session set edildikten sonra (2 nokta)
- ✅ `Auth::regenerateSession()` - Session regenerate edildikten sonra (3 nokta)
- ✅ `DashboardController::today()` - İlk request'te
- ✅ `build_app_header_context()` - Header context build sırasında (2 nokta)

### 4.2. Toplam Log Noktası

**9 farklı log noktasına** role bilgisi eklendi.

### 4.3. Sonraki Adım

**STAGE 2**: Log analizi sonuçlarına göre role tutarsızlığına nokta atışı fix yapılacak.

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE 2 - Role Tutarsızlığına Nokta Atışı Fix

