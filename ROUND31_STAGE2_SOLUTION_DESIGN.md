# ROUND 31 – STAGE 2: ÇÖZÜM TASARIMI (KOD YAZMADAN, SADECE PLAN)

**Tarih:** 2025-11-22  
**Round:** ROUND 31

---

## ÇÖZÜM PLANLARI

### HOME-01: `/app` first-load 500 after login

**Login Flow Şeması:**
1. `/app/login` → Login form gösterilir
2. Form submit → `AuthController::processLogin()` veya `LoginController::processLogin()`
3. Auth check → `Auth::login($username, $password)`
4. Session set → `session_regenerate_id()`, cookie set
5. Redirect → `redirect(base_url('/'))` → `/app`
6. Root route handler → `DashboardController::today()`

**İlk İstekte 500 Doğurabilecek Noktalar:**
1. **Session State:**
   - Login sonrası session write/close timing sorunu
   - Redirect sonrası session read sorunu
   - Cookie path mismatch

2. **DashboardController::today():**
   - İlk DB sorgusu (user-specific data)
   - Eksik config / null dönen değerler
   - Lazily initialized servisler
   - HeaderManager::bootstrap() exception

3. **First-Time Setup:**
   - Kullanıcıya bağlı ilk DB sorgusu
   - Onboarding flow exception

**Çözüm Planı (Kalıcı Fix):**

1. **Root Route Handler Hardening:**
   - `index.php` içindeki root route handler'ı try/catch ile sar
   - `DashboardController::today()` çağrısını try/catch ile sar
   - Exception durumunda:
     - Log detaylı hata (stack trace, context)
     - Kullanıcıya anlaşılır mesaj (200 + error view)
     - 500 döndürme

2. **DashboardController::today() Hardening:**
   - Tüm DB sorgularını try/catch ile sar
   - Null guard'lar ekle
   - Default değerler kullan
   - HeaderManager::bootstrap() exception handling

3. **Session State Guarantee:**
   - Login sonrası `session_write_close()` garantisi
   - Redirect öncesi session commit
   - Cookie path consistency check

**Etkilenecek Dosyalar:**
- `index.php` (root route handler)
- `src/Controllers/DashboardController.php` (`today()` metodu)

---

### JOB-01: `/app/jobs/new` 500

**Mevcut Durum:**
- ROUND 29'da `JobController::create()` metoduna comprehensive error handling eklendi
- Ancak PROD'da hala 500 görünüyor

**Olası Sebepler:**
1. Deploy edilmemiş olabilir
2. PROD'da farklı bir exception atıyor olabilir
3. View rendering sırasında exception atıyor olabilir

**Çözüm Planı (Kalıcı Fix):**

1. **JobController::create() Re-Review:**
   - Mevcut error handling'i kontrol et
   - Tüm exception noktalarını kapsadığından emin ol
   - View rendering exception handling ekle

2. **View Hardening:**
   - `src/Views/jobs/form.php` içinde defensive variable initialization
   - Null guard'lar
   - Default değerler

3. **Deploy Verification:**
   - PROD'daki kod versiyonunu kontrol et
   - Gerekirse fix'i tekrar uygula

**Etkilenecek Dosyalar:**
- `src/Controllers/JobController.php` (`create()` metodu)
- `src/Views/jobs/form.php` (defensive initialization)

---

### REC-01: `/app/recurring/new` JSON-only API

**Mevcut Durum:**
- `/api/services` endpoint'i ROUND 29'da fix edildi
- Ancak PROD'da hala HTML döndürüyor olabilir

**Olası Sebepler:**
1. Deploy edilmemiş olabilir
2. Exception durumunda HTML error page gösteriliyor
3. Auth kontrolü redirect yapıyor (login sayfası HTML)

**Çözüm Planı (Kalıcı Fix):**

1. **ApiController::services() JSON-only Guarantee:**
   - ROUND 30'daki `/health` endpoint yaklaşımını uygula:
     - Output buffering (`ob_start()`, `ob_clean()`, `ob_end_flush()`)
     - Header'ları en başta set et
     - Exception durumunda bile JSON döndür
     - `Throwable` catch (sadece `Exception` değil)

2. **Auth Check:**
   - `Auth::check()` kullan (redirect yok)
   - Auth yoksa JSON error döndür (401)

3. **Deploy Verification:**
   - PROD'daki kod versiyonunu kontrol et
   - Gerekirse fix'i tekrar uygula

**Etkilenecek Dosyalar:**
- `src/Controllers/ApiController.php` (`services()` metodu)

---

### REP-01: `/app/reports` davranışı

**Mevcut Durum:**
- `/app/reports` → HTTP 403
- `/app/reports/*` → HTTP 200

**Ürün Açısından En Mantıklı Davranış:**
- **Seçenek B: Otomatik redirect** (önerilen)
  - `/app/reports` → `/app/reports/financial` (veya en çok kullanılan rapor)
  - Admin için UX akışı: Tek tıkla en önemli rapora git

**Alternatif:**
- **Seçenek A: Basit overview sayfası**
  - Tüm raporların listesi
  - Her rapora link

**Çözüm Planı:**

1. **ReportController::index() Ekle:**
   - Eğer yoksa, `index()` metodu ekle
   - Permission kontrolü yap (admin için)
   - Redirect: `/app/reports/financial`

2. **Route Tanımı:**
   - `index.php` içinde `/reports` route'unu kontrol et
   - Gerekirse ekle veya düzelt

**Etkilenecek Dosyalar:**
- `src/Controllers/ReportController.php` (`index()` metodu)
- `index.php` (route tanımı)

---

### LEGAL-01/02/03: Legal sayfaların oluşturulması

**Route → Controller → View Tasarımı:**

1. **`/app/privacy-policy`:**
   - Route: `$router->get('/privacy-policy', [LegalController::class, 'privacyPolicy'])`
   - Controller: `LegalController::privacyPolicy()` (yeni controller)
   - View: `src/Views/legal/privacy-policy.php`
   - İçerik: "Gizlilik Politikası" başlığı + placeholder metin

2. **`/app/terms-of-use`:**
   - Route: `$router->get('/terms-of-use', [LegalController::class, 'termsOfUse'])`
   - Controller: `LegalController::termsOfUse()`
   - View: `src/Views/legal/terms-of-use.php`
   - İçerik: "Kullanıcı Sözleşmesi / Kullanım Şartları" başlığı + placeholder metin

3. **`/app/status`:**
   - Route: `$router->get('/status', [LegalController::class, 'status'])`
   - Controller: `LegalController::status()`
   - View: `src/Views/legal/status.php`
   - İçerik: "Sistem Durumu: Çalışıyor" (statik, ileride monitoring entegrasyonu için hook)

**Çözüm Planı:**

1. **LegalController Oluştur:**
   - `src/Controllers/LegalController.php` oluştur
   - `privacyPolicy()`, `termsOfUse()`, `status()` metodları

2. **View Dosyaları Oluştur:**
   - `src/Views/legal/privacy-policy.php`
   - `src/Views/legal/terms-of-use.php`
   - `src/Views/legal/status.php`

3. **Route Tanımları:**
   - `index.php` içinde route'ları ekle

**Etkilenecek Dosyalar:**
- `src/Controllers/LegalController.php` (yeni)
- `src/Views/legal/privacy-policy.php` (yeni)
- `src/Views/legal/terms-of-use.php` (yeni)
- `src/Views/legal/status.php` (yeni)
- `index.php` (route tanımları)

---

### APPT-01/02: Appointments 404 çözümü

**Geçmiş Kod Analizi:**
- `AppointmentController.php` mevcut
- `/app/appointments` route'ları muhtemelen `/app` altında tanımlı
- Base domain altında (`/appointments`) route yok

**Önerilen Yaklaşım:**
- **Seçenek A: Redirect** (önerilen)
  - `/appointments` → `/app` (ana dashboard)
  - `/appointments/new` → `/app/login` (login sayfası, çünkü randevu oluşturmak için login gerekli)

**Alternatif:**
- **Seçenek B: Bilgi sayfası**
  - "Online randevu artık şu panel üzerinden yönetiliyor" mesajı
  - `/app` linki

**Çözüm Planı:**

1. **Route Tanımları (Base Domain):**
   - `index.php` içinde base domain route'ları ekle:
     - `$router->get('/appointments', function() { redirect('/app'); })`
     - `$router->get('/appointments/new', function() { redirect('/app/login'); })`

2. **301 Redirect:**
   - HTTP 301 (Permanent Redirect) kullan
   - SEO ve kullanıcı deneyimi için

**Etkilenecek Dosyalar:**
- `index.php` (base domain route tanımları)

---

## ÖZET

**HIGH Priority Çözümler:**
1. HOME-01: Root route + DashboardController hardening
2. JOB-01: JobController::create() re-review + deploy verification

**MEDIUM Priority Çözümler:**
3. REC-01: ApiController::services() JSON-only guarantee (ROUND 30 pattern)
4. REP-01: ReportController::index() + redirect

**LOW Priority Çözümler:**
5. LEGAL-01/02/03: LegalController + view'lar
6. APPT-01/02: Base domain redirect'ler

---

**STAGE 2 TAMAMLANDI** ✅

