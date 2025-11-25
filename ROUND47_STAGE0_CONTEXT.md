# ROUND 47 – STAGE 0: CONTEXT & ÖNCEKİ ROUND'LARIN DERSİ

**Tarih:** 2025-11-23  
**Round:** ROUND 47 – CALENDAR FIRST-LOAD 500 → KÖK SEBEP & HARDENING

---

## PROBLEM ÖZETİ

**Prod Senaryo (ADMIN user):**
1. Login ol → `/app/calendar`'a ilk giriş → HTTP 500 (Hata sayfası)
2. Aynı sayfada F5 → HTTP 200, takvim geliyor

**Benzer Problemler (Daha Önce Çözüldü):**
- `/app` first-load 500 (DashboardController::buildDashboardData) → sonra düzeldi
- `/app/jobs/new` first-load 500 (JobController::create, auth + view rendering) → sonra düzeldi

---

## ÖNCEKİ ROUND'LARIN DERSİ

### `/app` ve `/jobs/new` için First-Load 500 → Nasıl Çözdük?

**Çözüm Pattern'i:**
1. **Dışa kapsayıcı try/catch**
   - Tüm method'u `try/catch(Throwable $e)` ile sar
   - Global error handler'a ulaşmasın

2. **Safe defaults**
   - Boş array'ler: `$data ?? []`
   - Null check'ler: `$var ?? null`
   - "undefined index" patlaması önleme: `$array['key'] ?? null`

3. **Auth modeli**
   - `require*` yerine `has* + redirect`
   - Exception yerine kontrollü redirect

4. **Output buffering & JSON-only / HTML-only garantileri**
   - JSON endpoint'lerde: `Content-Type: application/json`
   - HTML endpoint'lerde: 500 template yerine kontrollü error view

---

## HEDEF

**ROUND 47'de:**
- `/app/calendar` için ilk girişte bile asla 500 görmeyeceğimiz bir yapı kurmak
- KÖK SEBEBİ net bulmak (log + stack trace bazında)
- Calendar tarafında da tek tipleşmiş auth + safe defaults + try/catch paradigmasını uygulamak

---

**STAGE 0 TAMAMLANDI** ✅

