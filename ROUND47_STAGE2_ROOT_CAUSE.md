# ROUND 47 – STAGE 2: KÖK SEBEP ANALİZİ

**Tarih:** 2025-11-23  
**Round:** ROUND 47

---

## KOD İNCELEMESİ

### `CalendarController::index()` Metodu

**Mevcut Kod:**
```php
public function index()
{
    Auth::require();  // ❌ ESKİ MODEL - Exception fırlatabilir
    
    $view = $_GET['view'] ?? 'month';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Geçerli tarih kontrolü
    try {
        $currentDate = new DateTime($date);
    } catch (Exception $e) {
        $currentDate = new DateTime();
    }
    
    $jobs = [];
    $startDate = '';
    $endDate = '';
    
    switch ($view) {
        case 'day':
            $startDate = $currentDate->format('Y-m-d');
            $endDate = $startDate;
            $jobs = $this->jobModel->getByDateRange($startDate, $endDate);  // ⚠️ Null dönebilir
            break;
        // ... week, month cases
    }
    
    // Müşteriler ve hizmetler (form için)
    $customers = $this->customerModel->all();  // ⚠️ Null dönebilir
    $services = $this->serviceModel->getActive();  // ⚠️ Null dönebilir
    
    echo View::renderWithLayout('calendar/index', [
        'view' => $view,
        'date' => $date,
        'currentDate' => $currentDate,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'jobs' => $jobs,  // ⚠️ Null olabilir
        'customers' => $customers,  // ⚠️ Null olabilir
        'services' => $services,  // ⚠️ Null olabilir
        'flash' => Utils::getFlash()
    ]);
}
```

**Sorunlar:**
1. ❌ `Auth::require()` kullanıyor (ESKİ MODEL) - Exception fırlatabilir
2. ❌ Dışta kapsayıcı try/catch yok
3. ⚠️ `$this->jobModel->getByDateRange()` null dönebilir ama null check yok
4. ⚠️ `$this->customerModel->all()` null dönebilir ama null check yok
5. ⚠️ `$this->serviceModel->getActive()` null dönebilir ama null check yok
6. ⚠️ View'da `$jobs`, `$customers`, `$services` kullanılıyor ama null check yok

**Route Seviyesinde Try/Catch:**
- `index.php` içinde route seviyesinde try/catch var ama yeterli değil
- Controller içinde de kapsayıcı try/catch olmalı

---

## KÖK SEBEP HİPOTEZİ

**İlk Load'ta:**
- `Auth::require()` exception fırlatabilir (session/company context eksik)
- `$this->jobModel->getByDateRange()` null dönebilir (DB'de henüz kayıt yok)
- `$this->customerModel->all()` null dönebilir (DB'de henüz kayıt yok)
- View'da null array erişimi → "undefined index" veya "null on array access" hatası

**İkinci Load'ta:**
- İlk request bir şey yaratıyor (ör: default ayar kayıtları, cache entry, session değişkeni)
- İkinci request'te bu hazır geliyor → exception fırlamıyor

---

**STAGE 2 TAMAMLANDI** ✅

