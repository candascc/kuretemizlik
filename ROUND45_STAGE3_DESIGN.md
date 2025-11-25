# ROUND 45 – STAGE 3: KALICI ÇÖZÜM TASARIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 45

---

## HEDEF AUTH MODELİ

### Web Üzerinden Rapor Okuyabilen Roller

**PRIMARY:**
- FINANCE

**Ayrıca:**
- ADMIN
- SUPERADMIN
- (Sistemde varsa global yönetici rolleri)

**Diğer Roller:**
- OPERATOR, SUPPORT vs. → raporları görmemeli, ama "403 hard error" yerine redirect (örn. `/app/` veya `/login`)

---

## TASARIM KARARI

### `/app/reports` (Root Endpoint)

**Davranış:**
1. Eğer `Auth::check() === false` → `/login`'e redirect
2. Eğer `Auth::hasGroup(...)` ile rapor görebilen bir roldeyse → `/reports/financial`'a redirect (veya rapor index view'i 200)
3. Eğer yetkisizse → `/app/` veya `/`'a redirect (403 yok; en fazla 200 + basit error view)

**Önerilen:**
- `/reports` → redirect → `/reports/financial` (admin/finance için)
- Böylece root endpoint sadece "default rapora yönlendiren" bir entrypoint olur

---

### `/app/reports/financial`, `/app/reports/jobs`, `/app/reports/customers`, `/app/reports/services`

**Davranış:**
- Aynı auth modelini kullanmalı, kopya logic değil, mümkünse ortak private helper

**Ortak Helper:**
```php
private function ensureReportsAccess(): ?Response
{
    try {
        if (!Auth::check()) {
            Utils::flash('error', 'Bu sayfaya erişmek için giriş yapmanız gerekiyor.');
            redirect(base_url('/login'));
            return; // veya Response objesi döndür
        }
        
        // ADMIN and SUPERADMIN always have access - bypass group check
        $currentRole = Auth::role();
        if ($currentRole === 'ADMIN' || $currentRole === 'SUPERADMIN') {
            return null; // yetkili, devam edebilir
        }
        
        // For other roles, check group (use hasGroup instead of requireGroup to avoid 403)
        try {
            if (!Auth::hasGroup('nav.reports.core')) {
                Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
                redirect(base_url('/'));
                return; // veya Response objesi döndür
            }
        } catch (Throwable $e) {
            // If hasGroup throws exception, log and redirect (safe default)
            error_log("ReportController::ensureReportsAccess() - Auth::hasGroup() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Utils::flash('error', 'Yetki kontrolü sırasında bir hata oluştu.');
            redirect(base_url('/'));
            return; // veya Response objesi döndür
        }
        
        return null; // yetkili, devam edebilir
    } catch (Throwable $e) {
        // Log error with full context
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logLine = date('c') . ' ReportController::ensureReportsAccess() - UNEXPECTED ERROR' . PHP_EOL
            . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
            . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
            . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
            . '  Exception: ' . $e->getMessage() . PHP_EOL
            . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
            . '---' . PHP_EOL;
        @file_put_contents($logDir . '/report_access_r45.log', $logLine, FILE_APPEND);
        
        if (class_exists('AppErrorHandler')) {
            AppErrorHandler::logException($e, [
                'context' => 'ReportController::ensureReportsAccess() - outer catch',
                'user_id' => Auth::check() ? Auth::id() : null,
                'role' => Auth::check() ? Auth::role() : null,
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        } else {
            error_log("ReportController::ensureReportsAccess() - UNEXPECTED ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
        
        // Kullanıcıya 200 status ile redirect göster (403/500 DEĞİL)
        Utils::flash('error', 'Rapor sayfası yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.');
        redirect(base_url('/'));
        return; // veya Response objesi döndür
    }
}
```

**Kullanım:**
```php
public function index()
{
    if ($response = $this->ensureReportsAccess()) {
        return $response;
    }
    
    // Yetkili, default rapora yönlendir
    redirect(base_url('/reports/financial'));
    return;
}

public function financial()
{
    if ($response = $this->ensureReportsAccess()) {
        return $response;
    }
    
    // Yetkili, raporu göster
    // ... render view
}
```

---

## UYGULAMA PLANI

1. `ensureReportsAccess()` helper metodunu oluştur
2. `index()` metodunu `ensureReportsAccess()` kullanacak şekilde güncelle
3. `financial()` metodunu `ensureReportsAccess()` kullanacak şekilde güncelle (zaten doğru model ama helper kullan)
4. `jobs()`, `customers()`, `services()` metodlarını `ensureReportsAccess()` kullanacak şekilde güncelle (require* → has* + redirect)
5. Tüm rapor metodlarında `require*` → `has*` + redirect modeline geç

---

**STAGE 3 TAMAMLANDI** ✅

