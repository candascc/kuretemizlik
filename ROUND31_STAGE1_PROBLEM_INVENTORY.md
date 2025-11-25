# ROUND 31 – STAGE 1: PROBLEM ENVANTERİ

**Tarih:** 2025-11-22  
**Round:** ROUND 31

---

## PROBLEM ENVANTERİ TABLOSU

| ID | Kategori | Başlık | Kısa Açıklama | Etkilenen Kullanıcı | Severity | Kaynak |
|----|----------|--------|---------------|---------------------|----------|--------|
| **HOME-01** | Backend | `/app` first-load 500 after login | Login sonrası ilk `/app` açılışında 500, F5 ile 200 | Admin | **HIGH** | Yeni gözlem |
| **JOB-01** | Backend | `/app/jobs/new` PROD'da HTTP 500 | PROD crawl'de hala 500 görünüyor (ROUND 29 fix deploy edilmemiş olabilir) | Admin | **HIGH** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **REC-01** | Backend/Frontend | `/app/recurring/new` "Server returned HTML instead of JSON" | Services API HTML döndürüyor, JSON bekleniyor | Admin | **MEDIUM** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **REP-01** | Backend | `/app/reports` 403 Forbidden | Root reports path 403, alt path'ler 200 | Admin | **MEDIUM** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **LEGAL-01** | Backend | `/app/privacy-policy` 404 | Privacy policy sayfası yok | All | **LOW** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **LEGAL-02** | Backend | `/app/terms-of-use` 404 | Terms of use sayfası yok | All | **LOW** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **LEGAL-03** | Backend | `/app/status` 404 | Status sayfası yok | All | **LOW** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **APPT-01** | Backend | `/appointments` 404 | Base domain altında appointments route yok | Anonymous/Resident | **LOW** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |
| **APPT-02** | Backend | `/appointments/new` 404 | Base domain altında appointments/new route yok | Anonymous/Resident | **LOW** | PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json |

---

## DETAYLI PROBLEM AÇIKLAMALARI

### HOME-01: `/app` first-load 500 after login

**Kullanıcı Davranışı:**
1. `https://www.kuretemizlik.com/app/login` → Login form
2. Geçerli admin kullanıcı ile login
3. Redirect → `https://www.kuretemizlik.com/app`
4. **İLK AÇILIŞTA: HTTP 500**
5. F5 ile yenilenince → HTTP 200, dashboard normal

**Yorum:**
- İlk login sonrası çalışan flow ile "refresh sonrası" flow arasında fark var
- Session / cache / initial query / onboarding / redirect chain içinde hatalı bir branch olabilir
- `DashboardController::today()` veya root route handler'da ilk istekte exception atıyor olabilir

**Görev:**
- Login flow'unu şema halinde çıkar
- İlk istekte 500 doğurabilecek tüm noktaları listele
- Kök sebebi kod üzerinden netleştir

---

### JOB-01: `/app/jobs/new` PROD'da HTTP 500

**Durum:**
- PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json'da `/app/jobs/new` için HTTP 500 görünüyor
- ROUND 29'da bu endpoint için fix yapılmıştı
- Ancak PROD CRAWL hala 500 gösteriyor

**Olası Sebepler:**
1. ROUND 29 fix'i PROD'a deploy edilmemiş olabilir
2. PROD'da farklı bir kod versiyonu çalışıyor olabilir
3. PROD'da farklı bir config/DB durumu var olabilir
4. `JobController::create()` metodunda hala exception atıyor olabilir

**Görev:**
- PROD'daki kod versiyonunu kontrol et (deploy durumu)
- `JobController::create()` metodunu tekrar incele
- Tüm normal senaryolarda 200 döndüğünden emin ol

---

### REC-01: `/app/recurring/new` "Server returned HTML instead of JSON"

**Durum:**
- `/app/recurring/new` açıldığında HTTP 200
- Console'da: "Hizmetler yüklenemedi: Server returned HTML instead of JSON"
- JS tarafında hizmetleri getiren API çağrısı JSON yerine HTML alıyor

**Olası Sebepler:**
1. `/api/services` endpoint'i hata durumunda HTML error page döndürüyor
2. Auth kontrolü redirect yapıyor (login sayfası HTML)
3. Exception durumunda HTML error page gösteriliyor

**Görev:**
- `/api/services` endpoint'ini incele
- Her durumda JSON döndürmesi garantisini sağla
- ROUND 30'daki `/health` endpoint yaklaşımını uygula (output buffering, JSON-only)

---

### REP-01: `/app/reports` 403 Forbidden

**Durum:**
- `/app/reports` → HTTP 403
- `/app/reports/financial`, `/app/reports/jobs`, `/app/reports/customers`, `/app/reports/services` → HTTP 200

**Olası Sebepler:**
1. `/app/reports` root path'i için route tanımlı değil
2. Permission/role kontrolü 403 veriyor
3. Controller'da `index()` metodu yok veya permission kontrolü var

**Görev:**
- `/app/reports` root path'i için tasarlanan davranışı belirle:
  - A) Basit bir "Raporlar ana sayfası" mı?
  - B) Otomatik bir redirect (örn. `/app/reports/financial`) mi?
- Mevcut 403 davranışının sebebini analiz et

---

### LEGAL-01/02/03: Legal & Status sayfaları 404

**Durum:**
- `/app/privacy-policy` → HTTP 404
- `/app/terms-of-use` → HTTP 404
- `/app/status` → HTTP 404

**Ürün İhtiyacı:**
- Bu sayfaların GERÇEKTEN var olması isteniyor (placeholder değil, çalışan route + sayfa)

**Görev:**
- Her path için uygun controller + view yapısını belirle
- Temel içerik taslağını (başlıklar + paragraflar) planla
- `/app/status` için statik "Sistem Durumu: Çalışıyor" sayfası (ileride monitoring entegrasyonu için hook)

---

### APPT-01/02: Appointments rotaları 404

**Durum:**
- `/appointments` → HTTP 404 (base domain altında, `/app` değil)
- `/appointments/new` → HTTP 404 (base domain altında, `/app` değil)

**Olası Seçenekler:**
- A) Bu rotaları `/app` içindeki ilgili sayfalara 301/302 redirect etmek
- B) Basit bir bilgilendirme sayfası ile "Online randevu artık şu panel üzerinden yönetiliyor" demek

**Görev:**
- Geçmiş kodu ve routing yapısını inceleyerek en mantıklı yaklaşımı öner
- Seçilen yaklaşımı uygula (redirect veya bilgi sayfası)

---

## ÖNCELİK SIRASI

1. **HIGH Priority:**
   - HOME-01: `/app` first-load 500 after login
   - JOB-01: `/app/jobs/new` PROD'da HTTP 500

2. **MEDIUM Priority:**
   - REC-01: `/app/recurring/new` JSON-only API
   - REP-01: `/app/reports` 403 Forbidden

3. **LOW Priority:**
   - LEGAL-01/02/03: Legal & Status sayfaları
   - APPT-01/02: Appointments rotaları

---

**STAGE 1 TAMAMLANDI** ✅

