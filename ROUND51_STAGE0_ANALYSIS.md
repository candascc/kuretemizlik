# ROUND 51 - STAGE 0: Context & Log Analysis

## AUTH MODEL HARİTASI

### Login → Session → Auth::check → Middleware → /app/ Flow

1. **GET /login**
   - `AuthController::login()` çağrılır
   - Line 23-41: Session başlatma (eğer yoksa)
   - `Auth::check()` → zaten login ise redirect('/')
   - Login form gösterilir

2. **POST /login**
   - `AuthController::processLogin()` çağrılır
   - Line 73-91: Session başlatma (eğer yoksa)
   - CSRF kontrolü
   - `Auth::login($username, $password)` çağrılır
   - `Auth::login()` içinde:
     - Line 184: `ensureSessionStarted()` çağrılır
     - Line 189-190: `session_name()` ayarlanır (eğer farklıysa)
     - Kullanıcı doğrulanır
     - `Auth::completeLogin($user)` çağrılır
   - `Auth::completeLogin()` içinde:
     - Line 327: `ensureSessionStarted()` çağrılır
     - Line 332-336: `$_SESSION['user_id']`, `$_SESSION['username']`, vb. set edilir
     - Line 339: `Auth::regenerateSession()` çağrılır
   - `Auth::regenerateSession()` içinde:
     - Line 278-319: Session ID regenerate edilir
     - Line 307: `session_write_close()` + `session_start()` (reopen)
   - Redirect to `/` (dashboard)

3. **GET /**
   - `index.php` line 802-874: Root route handler
   - Line 803-828: Session başlatma (eğer yoksa)
   - `Auth::check()` çağrılır
   - `Auth::check()` içinde:
     - Line 92: `ensureSessionStarted()` çağrılır
     - Line 97: `$_SESSION['user_id']` ve `$_SESSION['login_time']` kontrol edilir
     - Yoksa `checkRememberMe()` çağrılır
   - Eğer auth yoksa → `SimpleAuthController::showLoginForm()`
   - Eğer auth varsa → `DashboardController::today()`

4. **Middleware (AuthMiddleware)**
   - `requireAuth()` middleware:
     - Line 15-38: Session başlatma (eğer yoksa)
     - Line 41: `Auth::check()` çağrılır
     - Eğer false → redirect('/login')

## PROBLEM NOKTALARI

### 1. Session Başlatma Dağınıklığı

**Nerede hâlâ session_set_cookie_params / ini_set çağrısı var?**

#### Auth.php:
- **Line 44-52**: `ensureSessionStarted()` içinde `session_set_cookie_params()` ve `ini_set('session.cookie_path')` var
- **Line 189-190**: `Auth::login()` içinde `session_name()` ayarlanıyor

#### index.php:
- **Line 35-38**: Pre-config `ini_set('session.*')` çağrıları
- **Line 119-130**: `get_flash()` helper içinde session başlatma + cookie params
- **Line 310-332**: Ana session bootstrap (config yüklendikten sonra) - **BU DOĞRU YER**
- **Line 803-828**: Root route handler içinde session başlatma
- **Line 880-899**: Dashboard route handler içinde session başlatma

#### AuthMiddleware.php:
- **Line 15-38**: `requireAuth()` içinde session başlatma + cookie params
- **Line 56-79**: `requireAdmin()` içinde session başlatma + cookie params
- **Line 103-126**: `requireOperatorReadOnly()` içinde session başlatma + cookie params

#### AuthController.php:
- **Line 23-41**: `login()` metodunda session başlatma + cookie params
- **Line 73-91**: `processLogin()` metodunda session başlatma + cookie params

### 2. Login Sonrası Kullanıcı Kaybolma

**Login sonrası hangi adımda kullanıcı kayboluyor?**

Potansiyel problemler:
1. **Session ID regenerate sonrası cookie kaybolması:**
   - `Auth::regenerateSession()` içinde `session_write_close()` + `session_start()` yapılıyor
   - Eğer cookie path yanlışsa, yeni session ID cookie olarak gönderilmeyebilir
   - Line 307-308: `session_write_close()` + `session_start()` - cookie path kontrolü yok

2. **Redirect sonrası session kaybolması:**
   - Login başarılı → `redirect(base_url('/'))` (line 306)
   - Yeni request'te `Auth::check()` çağrılıyor
   - Eğer cookie gönderilmemişse, `$_SESSION['user_id']` yok

3. **Cookie path mismatch:**
   - Login sırasında cookie path `/app` olarak ayarlanıyor
   - Redirect sonrası yeni request'te cookie path farklı olabilir
   - `index.php` line 803-828'de tekrar session başlatma yapılıyor, cookie path tekrar ayarlanıyor

### 3. Auth::check() / Auth::require() Davranışı

**Auth::check() neye bakıyor, ne zaman false dönüyor?**

- Line 92: `ensureSessionStarted()` çağrılır
- Line 97: `$_SESSION['user_id']` ve `$_SESSION['login_time']` kontrol edilir
- Yoksa → `checkRememberMe()` çağrılır
- `checkRememberMe()` başarısızsa → `false` döner

**Auth::require() ne yapıyor?**

- Line 711-720: `ensureSessionStarted()` çağrılır
- Line 722: `Auth::check()` çağrılır
- Eğer false → redirect('/login')

## LOG ANALİZİ (Beklenen Davranış)

### auth_session_warn.log
- **Spam pattern**: `Session already active, skipping initialization. uri=/app/login`
- **Sebep**: `ensureSessionStarted()` çağrıldığında session zaten aktif
- **Lokasyon**: `Auth.php` line 22-32

### error.log
- **Warning pattern**: `session_set_cookie_params(): Session cookie parameters cannot be changed when a session is active`
- **Sebep**: Session aktifken cookie params değiştirme denemesi
- **Lokasyon**: `Auth.php` line 44-52 (eğer session zaten aktifse)

### app_2025-11-23.log
- **Login sonrası pattern**: `security_regeneration` log'ları
- **Lokasyon**: `Auth::regenerateSession()` line 311-315
- **Beklenen**: Login sonrası her request'te `user_id` set edilmiş olmalı

## ÖZET

**Ana Problemler:**
1. ✅ **Session başlatma çok fazla yerde yapılıyor** - 8+ farklı yerde
2. ✅ **Cookie params her yerde ayarlanıyor** - Session aktifken warning üretiyor
3. ✅ **Session ID regenerate sonrası cookie kaybolma riski** - Cookie path mismatch
4. ✅ **Login sonrası redirect'te session kaybolma riski** - Cookie gönderilmemiş olabilir

**Çözüm Stratejisi:**
1. Tek giriş noktası: `index.php` line 310-332 (ana session bootstrap)
2. `Auth::ensureSessionStarted()` sadece `session_start()` yapacak, cookie params YOK
3. Tüm diğer session başlatma kodları kaldırılacak
4. Cookie params sadece `index.php` bootstrap'ta ayarlanacak

