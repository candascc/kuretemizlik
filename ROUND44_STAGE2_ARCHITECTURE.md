# ROUND 44 – STAGE 2: MİMARİ KARAR: TEK MODEL TANIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 44

---

## HEDEF MODEL

### 1. WEB CONTROLLER'LAR (HTML View Dönenler)

**Kural:**
- `Auth::require*()` KULLANILMAYACAK
- **Sadece:**
  ```php
  if (!Auth::check()) {
      Utils::flash('error', 'Bu sayfaya erişmek için giriş yapmanız gerekiyor.');
      redirect(base_url('/login'));
      return;
  }
  
  if (!Auth::hasGroup('nav.reports.core')) {
      Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
      redirect(base_url('/'));
      return;
  }
  
  if (!Auth::hasCapability('jobs.create')) {
      Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
      redirect(base_url('/jobs'));
      return;
  }
  ```

**Exception Handling:**
- Controller içinde try/catch ile yakalanacak
- Kullanıcı asla ham 500 görmeyecek
- Gerekirse "boş state" veya sade bir hata view'i render edilecek
- Veya redirect yapılacak (500 yerine)

---

### 2. JSON / API CONTROLLER'LAR

**Kural:**
- Sadece JSON dönecek (no HTML view, no template)
- Tüm method:
  ```php
  // Clear all output buffers
  while (ob_get_level() > 0) {
      ob_end_clean();
  }
  ob_start();
  
  // Set JSON headers FIRST
  if (!headers_sent()) {
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');
  }
  
  try {
      if (!Auth::check()) {
          http_response_code(401);
          echo json_encode([
              'success' => false,
              'error' => 'Authentication required',
              'code' => 'AUTH_REQUIRED',
              'data' => []
          ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          ob_end_flush();
          exit;
      }
      
      // normal logic
      http_response_code(200);
      echo json_encode([
          'success' => true,
          'data' => $data
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      ob_end_flush();
      exit;
  } catch (Throwable $e) {
      // Clear any partial output
      while (ob_get_level() > 0) {
          ob_end_clean();
      }
      ob_start();
      
      // Log error
      if (class_exists('AppErrorHandler')) {
          AppErrorHandler::logException($e, ['context' => 'ApiController::method()']);
      } else {
          error_log("ApiController::method() - Error: " . $e->getMessage());
      }
      
      // Always return JSON
      if (!headers_sent()) {
          header('Content-Type: application/json; charset=utf-8');
      }
      http_response_code(500);
      echo json_encode([
          'success' => false,
          'error' => 'Internal server error',
          'code' => 'INTERNAL_ERROR',
          'data' => []
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      ob_end_flush();
      exit;
  }
  ```

**Auth Hatası:**
- 401/403 JSON, DAİMA JSON
- HTML template render YOK

---

### 3. Global Error Handler

**Kural:**
- Bu üç alan (jobs/new, reports, api/services) için:
  - Global HTML 500/403 template'in devreye girmesini istemiyoruz
  - Tüm hata yönetimi controller seviyesinde bitecek

**Uygulama:**
- Controller içinde kapsayıcı try/catch kullanılacak
- Exception controller içinde yakalanacak, global error handler'a ulaşmayacak

---

## HANGİ SINIFLARDA `Auth::require*()` TAMAMEN TEMİZLENECEK?

### ReportController
- `ReportController::index()` → Zaten temiz (hasGroup kullanıyor)
- `ReportController::financial()` → Zaten temiz (ROUND 42'de düzeltildi)
- Diğer metodlar → Kontrol edilecek, gerekirse temizlenecek

### JobController
- `JobController::create()` → Zaten temiz (hasCapability kullanıyor)
- `JobController::store()` → `Auth::requireCapability()` kaldırılacak
- Diğer metodlar → Kontrol edilecek, gerekirse temizlenecek

### ApiController
- `ApiController::services()` → Zaten temiz (Auth::check() kullanıyor)
- Diğer metodlar → Kontrol edilecek, gerekirse temizlenecek

---

## HANGİ JSON ENDPOINT'LERDE ASLA HTML ÇAĞRILMAYACAK?

### `/app/api/services`
- Her durumda JSON-only
- Exception durumunda bile JSON error
- HTML template render YOK

### Diğer `/app/api/*` endpoint'leri
- Aynı kural geçerli
- Her durumda JSON-only

---

**STAGE 2 TAMAMLANDI** ✅

