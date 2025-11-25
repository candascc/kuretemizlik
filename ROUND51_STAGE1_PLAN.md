# ROUND 51 - STAGE 1: Session & Cookie Modelini Sadeleştirme PLAN

## PRENSİPLER

### 1. Tek Giriş Noktası: Session Başlatma Sadece 1 Yerde

**Hedef:**
- Session başlatma sadece `index.php` içinde, config yüklendikten sonra, router'dan önce
- Tüm diğer yerlerde session başlatma kaldırılacak
- `Auth::ensureSessionStarted()` sadece `session_start()` yapacak, cookie params YOK

### 2. Session Config Sadece Start'tan Önce

**Hedef:**
- `session_name()`, `session_set_cookie_params()`, `ini_set('session.*')` çağrıları sadece `index.php` bootstrap'ta
- Session başladıktan sonra hiçbir yerde cookie params değiştirilmeyecek
- PHP 8 warning'leri önlenecek

### 3. Auth::ensureSessionStarted() Minimal

**Hedef:**
```php
private static function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}
```

**Kaldırılacaklar:**
- `session_set_cookie_params()` çağrısı
- `ini_set('session.cookie_path')` çağrısı
- `session_name()` çağrısı
- Logging (opsiyonel, sadece debug mode'da)
- Try/catch (opsiyonel, sadece kritik durumlarda)

### 4. Auth::login() ve Auth::regenerateSession() Temiz

**Auth::login():**
- Başta `ensureSessionStarted()` çağrılır
- `session_name()` çağrısı KALDIRILACAK (index.php'de zaten ayarlanmış)
- Login başarılı → `$_SESSION['user_id']` set edilir
- `Auth::completeLogin()` çağrılır

**Auth::regenerateSession():**
- `session_set_cookie_params()` KALDIRILACAK
- `ini_set('session.cookie_path')` KALDIRILACAK
- `session_name()` KALDIRILACAK
- Sadece:
  - `self::ensureSessionStarted()`
  - `session_regenerate_id(true)`
  - Session değişkenlerini koru (zaten korunuyor)

## MEVCUT KODLA KARŞILAŞTIRMA

### Auth.php İçinde Kaldırılacaklar

#### 1. `ensureSessionStarted()` - Line 17-87
**Kaldırılacaklar:**
- Line 38-52: `$cookiePath`, `$isHttps` hesaplama
- Line 44-51: `session_set_cookie_params()` çağrısı
- Line 52: `ini_set('session.cookie_path')` çağrısı
- Line 24-32: Session already active warning log (opsiyonel, sadece debug)
- Line 59-67: Session success log (opsiyonel, sadece debug)
- Line 69-82: Try/catch bloğu (opsiyonel, sadece kritik durumlarda)

**Kalacaklar:**
- Line 19: `session_status()` kontrolü
- Line 22: `PHP_SESSION_ACTIVE` kontrolü
- Line 37: `PHP_SESSION_NONE` kontrolü
- Line 55: `session_start()` çağrısı

#### 2. `Auth::login()` - Line 180-272
**Kaldırılacaklar:**
- Line 189-190: `session_name()` kontrolü ve ayarlama

**Kalacaklar:**
- Line 184: `ensureSessionStarted()` çağrısı
- Line 198-271: Login logic (değişmeyecek)

#### 3. `Auth::regenerateSession()` - Line 278-319
**Kaldırılacaklar:**
- Line 286-303: Cookie params ayarlama ve `setcookie()` çağrısı (tamamen kaldırılacak)

**Kalacaklar:**
- Line 302: `session_status() === PHP_SESSION_ACTIVE` kontrolü
- Line 304: `session_regenerate_id(true)` çağrısı
- Line 307-308: `session_write_close()` + `session_start()` (reopen)
- Line 311-316: Logger çağrısı (değişmeyecek)

### index.php İçinde İyileştirmeler

#### 1. Pre-Config ini_set - Line 35-38
**Durum:** Bu kısım kalabilir, çünkü session başlatılmadan önce yapılıyor
**Aksiyon:** Değişiklik yok

#### 2. Ana Session Bootstrap - Line 310-332
**Durum:** Bu doğru yer, burada kalacak
**Aksiyon:** 
- Mevcut kod iyi, sadece temizlik yapılabilir
- Cookie path kontrolü ve warning log'ları kalabilir (opsiyonel)

#### 3. get_flash() Helper - Line 112-138
**Kaldırılacaklar:**
- Line 113-137: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 140-145: Flash message logic (değişmeyecek)

#### 4. Root Route Handler - Line 803-828
**Kaldırılacaklar:**
- Line 803-828: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 830-874: Route logic (değişmeyecek)

#### 5. Dashboard Route Handler - Line 880-899
**Kaldırılacaklar:**
- Line 880-899: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 901+: Route logic (değişmeyecek)

### AuthMiddleware.php İçinde Kaldırılacaklar

#### 1. `requireAuth()` - Line 11-47
**Kaldırılacaklar:**
- Line 15-38: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 41: `Auth::check()` çağrısı
- Line 42-44: Redirect logic (değişmeyecek)

#### 2. `requireAdmin()` - Line 52-94
**Kaldırılacaklar:**
- Line 56-79: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 82-91: Auth ve role check logic (değişmeyecek)

#### 3. `requireOperatorReadOnly()` - Line 99-142
**Kaldırılacaklar:**
- Line 103-126: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 129-139: Auth ve role check logic (değişmeyecek)

### AuthController.php İçinde Kaldırılacaklar

#### 1. `login()` - Line 11-57
**Kaldırılacaklar:**
- Line 23-41: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 45: CSRF token
- Line 47-50: Auth check ve redirect
- Line 52-56: View render (değişmeyecek)

#### 2. `processLogin()` - Line 62-316
**Kaldırılacaklar:**
- Line 73-91: Session başlatma kodu (tamamen kaldırılacak)
- `ensureSessionStarted()` çağrılacak (Auth sınıfından)

**Kalacaklar:**
- Line 95-316: Login logic (değişmeyecek)

## ÖZET: Kaldırılacak Kod Blokları

### Toplam Kaldırılacak Satır Sayısı: ~200+ satır

1. **Auth.php:**
   - `ensureSessionStarted()` içinde ~50 satır (cookie params, ini_set, logging)
   - `Auth::login()` içinde ~2 satır (session_name)
   - `Auth::regenerateSession()` içinde ~20 satır (cookie params, setcookie)

2. **index.php:**
   - `get_flash()` içinde ~25 satır
   - Root route içinde ~25 satır
   - Dashboard route içinde ~20 satır

3. **AuthMiddleware.php:**
   - `requireAuth()` içinde ~25 satır
   - `requireAdmin()` içinde ~25 satır
   - `requireOperatorReadOnly()` içinde ~25 satır

4. **AuthController.php:**
   - `login()` içinde ~20 satır
   - `processLogin()` içinde ~20 satır

## GÜVENLİK KONTROLÜ

**Mevcut Cookie Param Değerleri:**
- `lifetime`: 0 (session cookie)
- `path`: `/app` (APP_BASE)
- `domain`: null
- `secure`: HTTPS kontrolüne göre
- `httponly`: true
- `samesite`: 'Lax'

**Bu değerler `index.php` bootstrap'ta korunacak, sadece tek bir yerde ayarlanacak.**

## SONUÇ

**Hedef Model:**
1. ✅ Session başlatma sadece `index.php` bootstrap'ta
2. ✅ Cookie params sadece `index.php` bootstrap'ta
3. ✅ `Auth::ensureSessionStarted()` minimal (sadece `session_start()`)
4. ✅ Tüm diğer session başlatma kodları kaldırılacak
5. ✅ Session security modeli korunacak (cookie params değerleri aynı kalacak)

**Beklenen Sonuç:**
- ✅ PHP 8 warning'leri ortadan kalkacak
- ✅ Session başlatma tek tip olacak
- ✅ Login loop'ları önlenecek
- ✅ Cookie path mismatch sorunları çözülecek

