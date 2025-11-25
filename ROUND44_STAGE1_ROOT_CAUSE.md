# ROUND 44 – STAGE 1: KÖK SEBEP MODELİ: AUTH + ERROR HANDLING İKİLİĞİ

**Tarih:** 2025-11-23  
**Round:** ROUND 44

---

## KÖK SEBEP ANALİZİ

### Eski Model (Exception → HTML Template)

**Kullanım Yerleri:**
- `Auth::requireGroup()` → `View::forbidden()` çağırıyor → 403 HTML template
- `Auth::requireCapability()` → `View::forbidden()` çağırıyor → 403 HTML template
- `Auth::require()` → `View::forbidden()` çağırıyor → 403 HTML template
- `View::forbidden()` → `View::error($message, 403)` → HTML error template render

**Kod Akışı:**
```php
Auth::requireGroup('nav.reports.core')
  → Auth::require()
  → if (!hasGroup) → View::forbidden()
  → View::error($message, 403)
  → HTML 403 template render
```

**Sorun:**
- Exception fırlatmıyor ama `View::forbidden()` çağrısı ile HTML template render ediyor
- Bu, JSON endpoint'lerde HTML leak'e neden oluyor
- Controller seviyesinde kontrol edilemiyor

---

### Yeni Model (has* + Redirect / JSON)

**Kullanım Yerleri:**
- `Auth::check()` + `Auth::hasGroup()` + redirect
- `Auth::check()` + `Auth::hasCapability()` + redirect
- Controller içinde try/catch ile exception handling
- JSON endpoint'lerde JSON-only guarantee

**Kod Akışı:**
```php
if (!Auth::check()) {
    redirect(base_url('/login'));
    return;
}
if (!Auth::hasGroup('nav.reports.core')) {
    redirect(base_url('/'));
    return;
}
```

**Avantaj:**
- Controller seviyesinde kontrol edilebilir
- JSON endpoint'lerde HTML leak yok
- Kullanıcıya daha iyi UX (redirect vs 403)

---

## HANGİ ENDPOINT HANGİ MODELİ KULLANIYOR?

### `/app/jobs/new` (JobController::create)
- **Eski Model:** `Auth::requireCapability('jobs.create')` (store metodunda hala var)
- **Yeni Model:** `Auth::check()` + `Auth::hasCapability()` + redirect (create metodunda)
- **Sorun:** Karışık kullanım, view rendering sırasında exception oluşursa global error handler devreye giriyor

### `/app/reports` (ReportController::index)
- **Eski Model:** Yok (zaten `hasGroup` kullanıyor)
- **Yeni Model:** `Auth::hasGroup()` + redirect
- **Sorun:** Middleware seviyesinde `Auth::requireGroup()` çağrılıyor olabilir

### `/app/api/services` (ApiController::services)
- **Eski Model:** Yok (zaten JSON-only)
- **Yeni Model:** `Auth::check()` + JSON error
- **Sorun:** Exception durumunda global error handler devreye giriyor, HTML döndürüyor

---

## BU KARIŞIM NEDEN 500/403/HTML LEAK ÜRETİYOR?

1. **Middleware Seviyesinde Eski Model:**
   - Route seviyesinde `$requireAuth` middleware `Auth::require()` çağırıyor
   - Bu, `View::forbidden()` çağırabilir → HTML 403 template

2. **Controller İçinde Eski Model Kalıntıları:**
   - Bazı metodlarda hala `Auth::requireCapability()` kullanılıyor
   - Bu, `View::forbidden()` çağırabilir → HTML 403 template

3. **Global Error Handler:**
   - Exception oluştuğunda global error handler devreye giriyor
   - Bu, HTML 500 template render ediyor
   - JSON endpoint'lerde bile HTML döndürüyor

4. **Output Buffering Sorunları:**
   - Exception catch edilse bile, önceki output buffer'da HTML kalmış olabilir
   - JSON endpoint'lerde HTML leak oluşuyor

---

## GERÇEK KÖK SEBEP MODELİ

**JOB-01, REP-01, SERVICES-01 sorunlarının ortak kök sebebi:**

1. **Auth.php dosyasında `Auth::require*()` metodlarının `View::forbidden()` çağırması** → HTML 403 template render ediyor
2. **Middleware seviyesinde `Auth::require()` kullanımı** → Controller'a ulaşmadan önce HTML 403 template render ediyor
3. **Global error handler'ın exception durumunda HTML 500 template render etmesi** → JSON endpoint'lerde bile HTML döndürüyor
4. **Output buffering sorunları** → Exception catch edilse bile önceki output buffer'da HTML kalmış olabilir

---

**STAGE 1 TAMAMLANDI** ✅

