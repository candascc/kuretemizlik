# ROUND 34 – STAGE 1: KOD ENVANTERİ

**Tarih:** 2025-11-22  
**Round:** ROUND 34

---

## 1. `/app/jobs/new` – KOD ZİNCİRİ

### Route Tanımı:
- **Dosya:** `index.php`
- **Route:** `/jobs/new` → `JobController::create()`
- **Middleware:** `$requireAuth` (auth kontrolü yapıyor)

### Controller:
- **Dosya:** `src/Controllers/JobController.php`
- **Method:** `create()` (satır 197-360)
- **Auth Zinciri:**
  1. `Auth::check()` (satır 204) - boolean döner, exception atmaz
  2. `Auth::hasCapability('jobs.create')` (satır 213) - **TRY/CATCH İÇİNDE** (satır 212-226)
  3. Exception durumunda redirect (satır 219-225)

### Data Loading:
- `$this->customerModel->all()` (satır 241) - **TRY/CATCH İÇİNDE** (satır 240-250)
- `$this->serviceModel->getActive()` (satır 253) - **TRY/CATCH İÇİNDE** (satır 252-262)
- `Job::getStatuses()` (satır 266) - **TRY/CATCH İÇİNDE** (satır 265-286)
- `$this->customerModel->find()` (satır 295) - **TRY/CATCH İÇİNDE** (satır 294-304)

### View Rendering:
- `View::renderWithLayout('jobs/form', ...)` (satır 336) - **TRY/CATCH İÇİNDE** (satır 335-360)
- Exception durumunda error page veya redirect (satır 346-360)

### View Dosyası:
- **Dosya:** `src/Views/jobs/form.php`
- Defensive variable initialization var (ROUND 29'da eklendi)

### Potansiyel 500 Sebepleri:
1. **Auth::hasCapability() exception atıyor** (try/catch var ama yeterli mi?)
2. **View rendering exception** (try/catch var ama yeterli mi?)
3. **Output buffer sorunu** (headers already sent)
4. **Middleware'den gelen exception** (`$requireAuth` middleware'i)

---

## 2. `/app/reports` – KOD ZİNCİRİ

### Route Tanımı:
- **Dosya:** `index.php`
- **Route:** `/reports` → `ReportController::index()`
- **Middleware:** `$requireAuth` (auth kontrolü yapıyor)

### Controller:
- **Dosya:** `src/Controllers/ReportController.php`
- **Method:** `index()` (satır 27-74)
- **Auth Zinciri:**
  1. `Auth::role()` (satır 33) - string döner, exception atmaz
  2. Admin/SUPERADMIN kontrolü (satır 34) - bypass, redirect (satır 36)
  3. `Auth::hasGroup('nav.reports.core')` (satır 43) - **TRY/CATCH İÇİNDE** (satır 42-54)
  4. Exception durumunda error page (satır 55-74)

### Redirect Logic:
- Admin/SUPERADMIN → `/reports/financial` redirect (satır 36)
- Has group → `/reports/financial` redirect (satır 47)
- No group → `View::forbidden()` (satır 60) - **403 döner**

### Potansiyel 403 Sebepleri:
1. **Auth::hasGroup() exception atıyor** (try/catch var ama yeterli mi?)
2. **Redirect'ten önce output buffer sorunu** (headers already sent)
3. **Middleware'den gelen exception** (`$requireAuth` middleware'i)
4. **View::forbidden() çağrılıyor** (satır 60) - bu 403 döner

---

## 3. `/app/recurring/new` + `/api/services` – KOD ZİNCİRİ

### Route Tanımı:
- **Dosya:** `index.php`
- **Route:** `/recurring/new` → `RecurringController::create()` (muhtemelen)
- **Middleware:** `$requireAuth` (auth kontrolü yapıyor)

### View Dosyası:
- **Dosya:** `src/Views/recurring/form.php`
- **JS Fonksiyon:** `loadServices()` (muhtemelen satır 63 civarında)
- **Endpoint:** `/api/services` (GET request)

### API Endpoint:
- **Dosya:** `src/Controllers/ApiController.php`
- **Method:** `services()` (satır 740-832)
- **Auth Zinciri:**
  1. `Auth::check()` (satır 760) - boolean döner, exception atmaz
  2. Unauthenticated → 401 JSON error (satır 761-769)

### Output Buffer & Headers:
- **Tüm output buffer'lar temizleniyor** (satır 744-746)
- **JSON headers set ediliyor** (satır 752-755)
- **Service model çağrısı** (satır 774) - **TRY/CATCH İÇİNDE** (satır 773-807)

### Potansiyel HTML/JSON Karışıklığı Sebepleri:
1. **Output buffer temizlenmeden önce HTML output var** (başka bir middleware/route HTML output ediyor)
2. **Auth::check() false döndüğünde redirect yapılıyor** (ama kod JSON döndürüyor, redirect yok)
3. **Exception durumunda HTML view render ediliyor** (ama kod JSON döndürüyor)
4. **Route sırası sorunu** - `/api/services` route'u başka bir route'a düşüyor olabilir

---

## 4. `/app/health` – KOD ZİNCİRİ

### Route Tanımı:
- **Dosya:** `index.php`
- **Route:** `/health` (satır 692-797)
- **Middleware:** **YOK** (public endpoint)
- **Route Sırası:** Auth middleware'lerden **ÖNCE** tanımlı olmalı

### Output Buffer & Headers:
- **Tüm output buffer'lar temizleniyor** (satır 695-697)
- **JSON headers set ediliyor** (satır 703-706)
- **SystemHealth::check() çağrısı** (satır 718) - **TRY/CATCH İÇİNDE** (satır 714-760)

### Potansiyel Login HTML Sebepleri:
1. **Route sırası sorunu** - `/health` route'u auth middleware'lerden **SONRA** tanımlı olabilir
2. **Output buffer temizlenmeden önce HTML output var** (başka bir route/middleware HTML output ediyor)
3. **Exception durumunda HTML view render ediliyor** (ama kod JSON döndürüyor)
4. **Headers already sent** - başka bir kod header gönderiyor

---

## 5. `/app/status` – KOD ZİNCİRİ

### Route Tanımı:
- **Dosya:** `index.php`
- **Route:** `/status` → `LegalController::status()`
- **Middleware:** Muhtemelen public veya `$requireAuth`

### Controller:
- **Dosya:** `src/Controllers/LegalController.php`
- **Method:** `status()`
- **View:** `legal/status`

### View Dosyası:
- **Dosya:** `src/Views/legal/status.php`
- **BUILD TAG:** `KUREAPP_BUILD_TAG` HTML comment (ROUND 33'te eklendi)

---

## 6. AUTH HELPER FONKSİYONLARI

### Auth::check()
- **Dönüş:** boolean (true/false)
- **Exception:** Atmaz
- **Kullanım:** Güvenli, exception handling gerekmez

### Auth::hasCapability()
- **Dönüş:** boolean (true/false)
- **Exception:** **ATABİLİR** (try/catch gerekli)
- **Kullanım:** `JobController::create()` içinde try/catch var

### Auth::hasGroup()
- **Dönüş:** boolean (true/false)
- **Exception:** **ATABİLİR** (try/catch gerekli)
- **Kullanım:** `ReportController::index()` içinde try/catch var

### Auth::requireCapability()
- **Dönüş:** void (exception atar veya redirect yapar)
- **Exception:** **ATABİLİR** veya `View::forbidden()` çağırır
- **Kullanım:** Kullanılmıyor (defensive programming ile değiştirildi)

### Auth::requireGroup()
- **Dönüş:** void (exception atar veya redirect yapar)
- **Exception:** **ATABİLİR** veya `View::forbidden()` çağırır
- **Kullanım:** Kullanılmıyor (defensive programming ile değiştirildi)

---

## 7. ROUTE SIRASI ANALİZİ

### `/health` Route Sırası:
- **Satır:** 692
- **Önceki Route'lar:** Auth middleware'ler, login route'ları
- **Sonraki Route'lar:** Dashboard, jobs, reports, vb.
- **Sorun:** `/health` route'u auth middleware'lerden **SONRA** tanımlı olabilir (route sırası kontrol edilmeli)

---

## 8. OUTPUT BUFFER SORUNLARI

### Potansiyel Sorunlar:
1. **Nested output buffers** - birden fazla `ob_start()` çağrısı
2. **Headers already sent** - output buffer temizlenmeden önce header gönderilmiş
3. **Middleware output** - auth middleware'leri HTML output ediyor olabilir
4. **Error handler output** - error handler HTML output ediyor olabilir

---

**STAGE 1 TAMAMLANDI** ✅

