# STAGE 1 - PATH C HARİTALAMA: `/app` CALL GRAPH

**Tarih**: 2024-12-XX  
**Görev**: STAGE 1 - `/app` Call Graph Çıkarma (READ-ONLY + MINIMAL DOC)  
**Durum**: TAMAMLANDI

---

## 1. `/app` CALL GRAPH (HIGH LEVEL)

```php
// PATH C CALL GRAPH (high level)
// /app (GET)

//   -> index.php bootstrap
//       -> Session initialization
//       -> Router initialization
//       -> Route matching: "/" or "/app"

//   -> Router::run() → route "/" handler
//       -> Auth::check() → Session kontrolü
//       -> HeaderManager::bootstrap() → Mode kontrolü
//       -> DashboardController::today()

//       -> DashboardController::today()
//           -> Auth::require() → Auth kontrolü
//           -> PathCLogger::log('APP_HTML_START')
//           -> PathCLogger::log('APP_HTML_TRY_ENTER')
//           -> buildDashboardData($today)
//               -> getCurrentCompanyContext()
//               -> getTodayJobs()
//               -> getWeekIncome($weekStart, $weekEnd)  // ⚠️ jp.company_id hatası burada
//                   -> scopeToCompany("WHERE DATE(jp.created_at) BETWEEN ? AND ?", 'jp')
//                   -> SQL: SELECT ... FROM job_payments jp WHERE ... AND jp.company_id = 1
//               -> getRecentActivities()  // ⚠️ jp.company_id hatası burada
//                   -> scopeToCompany("WHERE jp.created_at >= datetime('now', '-24 hours')", 'jp')
//                   -> SQL: SELECT ... FROM job_payments jp ... WHERE ... AND jp.company_id = 1
//               -> getWeeklyIncomeTrend()  // ⚠️ jp.company_id hatası burada
//                   -> scopeToCompany("WHERE DATE(jp.created_at) = ?", 'jp')
//                   -> SQL: SELECT ... FROM job_payments jp WHERE ... AND jp.company_id = 1
//               -> getRecurringJobsStats()
//               -> Cache::remember("dashboard:today:{$today}", ...)
//           -> PathCLogger::log('APP_HTML_BEFORE_RENDER')
//           -> View::renderWithLayout('dashboard', $data)
//               -> PathCLogger::log('VIEW_RENDER_START')
//               -> View::render('dashboard/today.php', $data)
//               -> PathCLogger::log('VIEW_RENDER_AFTER_LAYOUT')
//               -> View::render('layout/base.php', $data)
//                   -> build_app_header_context()
//                       -> PathCLogger::log('HEADER_CONTEXT_START')
//                       -> HeaderManager::bootstrap()
//                       -> PathCLogger::log('HEADER_CONTEXT_AFTER_HEADERMANAGER')
//                       -> HeaderManager::getCurrentRole()
//                       -> HeaderManager::getNavigationItems()
//                       -> PathCLogger::log('HEADER_CONTEXT_DONE')
//                   -> include layout/header.php
//                   -> include dashboard/today.php
//                   -> include layout/footer.php
//                       -> include layout/partials/global-footer.php
//                           -> JavaScript: fetch('/performance/metrics')  // XHR
//                           -> JavaScript: fetch('/api/notifications/list')  // XHR
//               -> PathCLogger::log('VIEW_RENDER_DONE')
//           -> PathCLogger::log('APP_HTML_AFTER_RENDER')
//           -> PathCLogger::log('APP_HTML_TRY_EXIT')
```

---

## 2. POTANSİYEL 500 ÜRETME NOKTALARI

### 2.1. DB Sorguları (Özellikle job_payments, metrics, analytics)

**Kritik Noktalar**:

1. **getWeekIncome()** (satır ~489-534)
   - `scopeToCompany("WHERE DATE(jp.created_at) BETWEEN ? AND ?", 'jp')` → `jp.company_id = 1` ekliyor
   - `job_payments` tablosunda `company_id` kolonu YOK
   - **Exception**: `SQLSTATE[HY000]: General error: 1 no such column: jp.company_id`
   - **Etki**: Try/catch var ama exception loglanıyor, 0.0 döndürülüyor

2. **getRecentActivities()** (satır ~782-1143)
   - `scopeToCompany("WHERE jp.created_at >= datetime('now', '-24 hours')", 'jp')` → `jp.company_id = 1` ekliyor
   - `job_payments` tablosunda `company_id` kolonu YOK
   - **Exception**: `SQLSTATE[HY000]: General error: 1 no such column: jp.company_id`
   - **Etki**: Try/catch var ama exception loglanıyor, boş array döndürülüyor

3. **getWeeklyIncomeTrend()** (satır ~716-777)
   - `scopeToCompany("WHERE DATE(jp.created_at) = ?", 'jp')` → `jp.company_id = 1` ekliyor
   - `job_payments` tablosunda `company_id` kolonu YOK
   - **Exception**: `SQLSTATE[HY000]: General error: 1 no such column: jp.company_id`
   - **Etki**: Try/catch var ama exception loglanıyor, boş array döndürülüyor

**Not**: Bu 3 fonksiyon `buildDashboardData()` içinde çağrılıyor. Exception'lar yakalanıyor ama muhtemelen yeterince graceful değil veya exception'ın kendisi 500'e sebep oluyor.

---

### 2.2. Cache Okuma/Yazma (Cache::get, CacheManager::get, vs.)

**Kritik Noktalar**:

1. **Cache::remember()** (satır ~129)
   - `Cache::remember("dashboard:today:{$today}", function() {...}, 300)`
   - Cache okuma sırasında `unserialize()` hatası oluşabilir
   - **Exception**: `unserialize(): Error at offset 0 of 106 bytes`
   - **Etki**: Log'larda görülüyor ama 500 üretip üretmediği net değil

2. **Cache.php:472** (veya 501)
   - Cache dosyası okuma sırasında unserialize hatası
   - **Exception**: `unserialize(): Error at offset 0 of 106 bytes`
   - **Etki**: Log'larda görülüyor ama 500 üretip üretmediği net değil

---

### 2.3. Header Context Build (HeaderManager, partials)

**Kritik Noktalar**:

1. **build_app_header_context()** (header-context.php)
   - `HeaderManager::bootstrap()` → Exception fırlatabilir
   - `HeaderManager::getCurrentRole()` → Exception fırlatabilir
   - **Etki**: Try/catch var, minimum header context döndürülüyor

---

### 2.4. View / Layout Include Zinciri

**Kritik Noktalar**:

1. **View::renderWithLayout()** (View.php)
   - `View::render('dashboard/today.php', $data)` → PHP syntax error olabilir
   - `View::render('layout/base.php', $data)` → PHP syntax error olabilir
   - **Etki**: Try/catch var, safe fallback HTML döndürülüyor

---

## 3. `scopeToCompany()` METODU ANALİZİ

**Konum**: `src/Lib/CompanyScope.php`

**İşlev**:
- Multi-tenant isolation için SQL WHERE clause'una `company_id` filtresi ekler
- `scopeToCompany("WHERE ...", 'jp')` → `"WHERE ... AND jp.company_id = 1"` döndürür

**Sorun**:
- `job_payments` tablosunda `company_id` kolonu YOK
- Ama `scopeToCompany()` bu kolonu ekliyor
- Bu, SQL hatasına sebep oluyor

**Çözüm Yönü**:
- `scopeToCompany()` metodunu `job_payments` için özel handle edecek şekilde düzeltmek
- Veya `job_payments` sorgularını JOIN ile `jobs.company_id` kullanacak şekilde değiştirmek

---

## 4. SONUÇ

### 4.1. En Güçlü 500 Sebebi

**jp.company_id hatası** `/app` ilk request 500'ünün en muhtemel sebebi.

**Neden**:
1. 3 kritik dashboard fonksiyonu etkileniyor
2. Bu fonksiyonlar `buildDashboardData()` içinde çağrılıyor
3. Exception yakalanıyor ama muhtemelen yeterince graceful değil
4. Log'larda aynı anda 3 sorgu hatası görülüyor

### 4.2. Sonraki STAGE'ler İçin Öneriler

1. **STAGE 2**: Dashboard fonksiyonlarına detaylı log ekle (PATHC_DB_QUERY_*)
2. **STAGE 3**: `jp.company_id` hatasını düzelt (en öncelikli)
3. **STAGE 4**: Error handling'i güçlendir

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE 2 - PATH C ENSTRÜMANTASYON

