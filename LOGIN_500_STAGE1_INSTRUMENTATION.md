# LOGIN 500 STAGE 1: Login Akışı Enstrümantasyonu Raporu

**Tarih**: 2025-11-23  
**Aşama**: STAGE 1 - Login Akışı Enstrümantasyonu

## Özet

Login akışının kritik noktalarına detaylı loglar eklendi. Bu loglar, session state, auth state ve cookie durumunu takip edecek.

---

## Eklenen Log Noktaları

### 1. AuthController::processLogin()

**Konum**: Login başarılı olduğunda, redirect'ten önce

**Log Edilen Bilgiler**:
- URI
- IP adresi
- Session ID
- Session status
- Cookie name
- Cookie varlığı (yes/no)
- Cookie value (ilk 12 karakter)
- User ID
- Login time
- Cookie params (path, domain, secure, httponly, samesite)

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [AuthController::processLogin] uri=..., ip=..., session_id=..., session_status=..., cookie_name=..., cookie_exists=..., cookie_value=..., user_id=..., login_time=..., cookie_path=..., cookie_domain=..., cookie_secure=..., cookie_httponly=..., cookie_samesite=...
```

---

### 2. Auth::completeLogin()

**Konum**: Session set edildikten sonra ve regenerate edildikten sonra

**Log Edilen Bilgiler**:
- URI
- Session ID (before/after regenerate)
- Session status
- User ID
- Login time
- Cookie params

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::completeLogin] AFTER_SESSION_SET uri=..., session_id=..., session_status=..., user_id=..., login_time=..., cookie_path=..., cookie_domain=..., cookie_secure=..., cookie_httponly=..., cookie_samesite=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::completeLogin] AFTER_REGENERATE uri=..., session_id=..., session_status=..., user_id=..., login_time=...
```

---

### 3. Auth::regenerateSession()

**Konum**: Session regenerate işleminin öncesi, sonrası, write_close sonrası ve restart sonrası

**Log Edilen Bilgiler**:
- URI
- Old session ID
- New session ID
- Session status
- Cookie name
- Cookie varlığı (yes/no)
- Cookie value (ilk 12 karakter)
- User ID

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::regenerateSession] BEFORE uri=..., old_session_id=..., user_id=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::regenerateSession] AFTER uri=..., new_session_id=..., session_status=..., cookie_name=..., cookie_exists=..., cookie_value=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::regenerateSession] AFTER_WRITE_CLOSE uri=..., session_id=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [Auth::regenerateSession] AFTER_RESTART uri=..., session_id=..., session_status=..., user_id=...
```

---

### 4. DashboardController::today()

**Konum**: Auth::require() çağrısından önce ve sonra

**Log Edilen Bilgiler**:
- URI
- IP adresi
- Session ID (before/after)
- Session status (before/after)
- Cookie name
- Cookie varlığı (before/after)
- Cookie value (before/after)
- User ID (before/after)
- Login time (after)
- Auth::check() sonucu (after)
- Auth::id() sonucu (after)

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [DashboardController::today] BEFORE_AUTH uri=..., ip=..., session_id=..., session_status=..., cookie_name=..., cookie_exists=..., cookie_value=..., user_id=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [DashboardController::today] AFTER_AUTH uri=..., session_id=..., session_status=..., cookie_exists=..., cookie_value=..., user_id=..., login_time=..., Auth::check()=..., Auth::id()=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [DashboardController::today] AUTH_ERROR uri=..., error=..., file=..., line=...
```

---

### 5. build_app_header_context()

**Konum**: Fonksiyon başında, exception catch bloğunda ve success durumunda

**Log Edilen Bilgiler**:
- URI
- Session ID
- Session status
- User ID
- Auth::check() sonucu
- Exception bilgileri (class, message, file, line)

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [build_app_header_context] START uri=..., session_id=..., session_status=..., user_id=..., Auth::check()=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [build_app_header_context] EXCEPTION uri=..., class=..., message=..., file=..., line=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [build_app_header_context] SUCCESS uri=..., session_id=..., session_status=..., user_id=..., Auth::check()=...
```

---

### 6. View::renderWithLayout()

**Konum**: Fonksiyon başında, exception catch bloğunda ve success durumunda

**Log Edilen Bilgiler**:
- URI
- View name
- Layout name
- Session ID
- Session status
- User ID
- Auth::check() sonucu
- Exception bilgileri (class, message, file, line)

**Log Formatı**:
```
[YYYY-MM-DD HH:MM:SS] [STAGE1] [View::renderWithLayout] START uri=..., view=..., layout=..., session_id=..., session_status=..., user_id=..., Auth::check()=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [View::renderWithLayout] EXCEPTION uri=..., view=..., layout=..., class=..., message=..., file=..., line=...
[YYYY-MM-DD HH:MM:SS] [STAGE1] [View::renderWithLayout] SUCCESS uri=..., view=..., layout=..., session_id=..., session_status=..., user_id=..., Auth::check()=...
```

---

## Log Dosyası

**Dosya Yolu**: `logs/login_500_trace.log`

**Format**: Her satır bir log entry'si, format:
```
[YYYY-MM-DD HH:MM:SS] [STAGE] [FUNCTION] key=value, key2=value2
```

---

## Değiştirilen Dosyalar

1. `src/Controllers/AuthController.php` - `processLogin()` metoduna log eklendi
2. `src/Lib/Auth.php` - `completeLogin()` ve `regenerateSession()` metodlarına log eklendi
3. `src/Controllers/DashboardController.php` - `today()` metoduna log eklendi
4. `src/Views/layout/partials/header-context.php` - `build_app_header_context()` fonksiyonuna log eklendi
5. `src/Lib/View.php` - `renderWithLayout()` metoduna log eklendi

---

## Sonraki Adım

**STAGE 2**: Lokal test yap ve logları topla. İlk login'de 500 hatası aldığında ve F5 sonrası düzeldiğinde oluşan logları analiz et.

---

**Not**: Bu loglar geçicidir ve STAGE 5'te temizlenecektir.

