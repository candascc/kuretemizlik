# ROUND 42 – STAGE 2: ROOT CAUSE ANALİZİ

**Tarih:** 2025-11-23  
**Round:** ROUND 42

---

## JOB-01: `/app/jobs/new` → 500

### Kök Sebep
- **Route:** `$router->get('/jobs/new', [JobController::class, 'create'], ['middlewares' => [$requireAuth]]);`
- **Controller:** `JobController::create()` zaten iyi exception handling'e sahip
- **Olası Sorun:** `$requireAuth` middleware exception fırlatıyor veya `View::forbidden()` çağrılıyor
- **Trigger Koşulu:** Auth check başarısız olduğunda veya capability check başarısız olduğunda
- **Etkilenen Roller:** Tüm roller (admin dahil, eğer middleware seviyesinde sorun varsa)

### Analiz
- `JobController::create()` içinde zaten `Auth::check()` ve `Auth::hasCapability()` kontrolü var
- Ama route seviyesinde `$requireAuth` middleware de çalışıyor
- Eğer middleware exception fırlatırsa, controller'a hiç ulaşmıyor

---

## REP-01: `/app/reports` → 403

### Kök Sebep
- **Route:** `$router->get('/reports', [ReportController::class, 'index'], ['middlewares' => [$requireAuth]]);`
- **Controller:** `ReportController::index()` zaten `Auth::hasGroup()` kullanıyor ve redirect yapıyor
- **Olası Sorun:** `$requireAuth` middleware exception fırlatıyor veya `View::forbidden()` çağrılıyor
- **Alternatif:** `ReportController::financial()` içinde `Auth::requireGroup('nav.reports.core')` çağrılıyor olabilir
- **Trigger Koşulu:** Auth check başarısız olduğunda veya group check başarısız olduğunda
- **Etkilenen Roller:** ADMIN rolü için bile 403 döndürüyor (middleware seviyesinde sorun olabilir)

### Analiz
- `ReportController::index()` içinde zaten `Auth::hasGroup()` kontrolü var ve redirect yapıyor
- Ama route seviyesinde `$requireAuth` middleware de çalışıyor
- Eğer middleware exception fırlatırsa, controller'a hiç ulaşmıyor
- `ReportController::financial()` içinde `Auth::requireGroup('nav.reports.core')` çağrılıyor - bu `View::forbidden()` çağırabilir

---

## REC-01 / SERVICES-01: `/app/api/services` → HTML/500

### Kök Sebep
- **Route:** `$router->get('/api/services', [ApiController::class, 'services'], ['middlewares' => []]);`
- **Controller:** `ApiController::services()` zaten JSON-only guarantee'ye sahip
- **Olası Sorun:** Route seviyesinde başka bir middleware HTML döndürüyor veya global error handler devreye giriyor
- **Trigger Koşulu:** Exception oluştuğunda global error handler HTML döndürüyor
- **Etkilenen Roller:** Tüm roller (unauthenticated dahil)

### Analiz
- `ApiController::services()` içinde zaten JSON-only guarantee var
- Ama route seviyesinde middleware yok (doğru)
- Eğer global error handler devreye girerse, HTML döndürebilir
- Output buffering sorunları olabilir

---

## ÖNERİLEN ÇÖZÜMLER

### JOB-01
1. `JobController::create()` içinde zaten iyi exception handling var
2. Route seviyesinde `$requireAuth` middleware'in exception fırlatmamasını sağla
3. Veya `JobController::create()` içinde middleware'i bypass et (zaten kendi auth check'i var)

### REP-01
1. `ReportController::index()` içinde zaten iyi exception handling var
2. Route seviyesinde `$requireAuth` middleware'in exception fırlatmamasını sağla
3. `ReportController::financial()` içinde `Auth::requireGroup()` yerine `Auth::hasGroup()` + redirect kullan

### REC-01 / SERVICES-01
1. `ApiController::services()` içinde zaten JSON-only guarantee var
2. Global error handler'ın JSON endpoint'ler için HTML döndürmemesini sağla
3. Output buffering sorunlarını çöz

---

**STAGE 2 TAMAMLANDI** ✅

