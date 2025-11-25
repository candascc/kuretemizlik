# PATH D - STAGE 0: READ-ONLY ANALİZ RAPORU

**Tarih**: 2024-12-XX  
**Görev**: STAGE 0 - Log ve Schema Analizi (READ-ONLY)  
**Durum**: TAMAMLANDI

---

## 1. LOG ANALİZİ (16:14:45 BLOKLARI)

### 1.1. `p.company_id` Hataları

**Zaman**: 2025-11-23 16:14:45  
**Hata Sayısı**: 8 adet (haftalık trend için 7 gün + 1 haftalık toplam)

**Etkilenen SQL Sorguları**:

1. **Haftalık toplam gelir** (getWeekIncome içinde):
   ```sql
   SELECT COALESCE(SUM(p.amount), 0) as total 
   FROM payments p
   WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ? AND p.company_id = 1
   ```
   - Parametreler: `["2025-11-17","2025-11-23"]`

2. **Günlük trend sorguları** (getWeeklyIncomeTrend içinde, 7 gün):
   ```sql
   SELECT COALESCE(SUM(p.amount), 0) as total 
   FROM payments p
   WHERE p.status = 'completed' AND DATE(p.created_at) = ? AND p.company_id = 1
   ```
   - Parametreler: `["2025-11-17"]`, `["2025-11-18"]`, `["2025-11-19"]`, `["2025-11-20"]`, `["2025-11-21"]`, `["2025-11-22"]`, `["2025-11-23"]`

**Fonksiyonlar**:
- `DashboardController::getWeekIncome()` (satır ~660-720)
- `DashboardController::getWeeklyIncomeTrend()` (satır ~960-1020)

---

### 1.2. `ct.company_id` Hataları

**Zaman**: 2025-11-23 16:14:45  
**Hata Sayısı**: 1 adet

**Etkilenen SQL Sorgusu**:

```sql
SELECT ct.*, c.name as customer_name, 'contract_created' as type
FROM contracts ct
LEFT JOIN customers c ON ct.customer_id = c.id
WHERE ct.created_at >= datetime('now', '-24 hours') AND ct.company_id = 1
ORDER BY ct.created_at DESC
LIMIT 10
```

**Fonksiyon**:
- `DashboardController::getRecentActivities()` (satır ~1240-1300)

---

### 1.3. `$isAppRequest` Undefined Hatası

**Zaman**: 2025-11-23 16:14:45  
**Konum**: `index.php:819`

**Durum**:
- `$isAppRequest` satır 434'te tanımlanıyor
- Satır 819'da kullanılıyor ama bazı branch'lerde tanımsız kalabiliyor

---

## 2. SCHEMA ANALİZİ

### 2.1. `payments` Tablosu

**Schema** (`db/install.sql`):
```sql
CREATE TABLE IF NOT EXISTS payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER,
  appointment_id INTEGER,
  customer_id INTEGER NOT NULL,
  amount REAL NOT NULL,
  payment_method TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  transaction_id TEXT,
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE SET NULL,
  FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

**Önemli Noktalar**:
- `company_id` kolonu YOK
- `job_id` kolonu VAR (jobs tablosuna FK)
- `customer_id` kolonu VAR (customers tablosuna FK)
- `jobs.company_id` üzerinden company scope yapılabilir
- `customers.company_id` üzerinden de company scope yapılabilir

**JOIN Stratejisi**:
- `payments.job_id → jobs.id → jobs.company_id` (tercih edilen)
- `payments.customer_id → customers.id → customers.company_id` (alternatif)

---

### 2.2. `contracts` Tablosu

**Schema** (`db/schema-current.sql`):
```sql
CREATE TABLE contracts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  contract_number TEXT UNIQUE NOT NULL,
  title TEXT NOT NULL,
  description TEXT,
  contract_type TEXT NOT NULL,
  start_date TEXT NOT NULL,
  end_date TEXT,
  total_amount REAL,
  payment_terms TEXT,
  status TEXT NOT NULL,
  auto_renewal INTEGER DEFAULT 0,
  renewal_period_days INTEGER,
  file_path TEXT,
  notes TEXT,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id)
);
```

**Önemli Noktalar**:
- `company_id` kolonu YOK
- `customer_id` kolonu VAR (customers tablosuna FK)
- `customers.company_id` üzerinden company scope yapılabilir

**JOIN Stratejisi**:
- `contracts.customer_id → customers.id → customers.company_id` (tek seçenek)

---

## 3. KOD ANALİZİ

### 3.1. `payments` Sorgularının Konumları

**DashboardController::getWeekIncome()** (satır ~660-720):
- `scopeToCompany("WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ?", 'p')` kullanılıyor
- Bu `p.company_id = 1` ekliyor ama tabloda bu kolon yok

**DashboardController::getWeeklyIncomeTrend()** (satır ~960-1020):
- Loop içinde her gün için `scopeToCompany("WHERE p.status = 'completed' AND DATE(p.created_at) = ?", 'p')` kullanılıyor
- Bu da `p.company_id = 1` ekliyor ama tabloda bu kolon yok

---

### 3.2. `contracts` Sorgularının Konumları

**DashboardController::getRecentActivities()** (satır ~1240-1300):
- `scopeToCompany("WHERE ct.created_at >= datetime('now', '-24 hours')", 'ct')` kullanılıyor
- Bu `ct.company_id = 1` ekliyor ama tabloda bu kolon yok

---

### 3.3. `$isAppRequest` Kullanımı

**index.php**:
- Satır 434: `$isAppRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (strpos($requestUri, '/app') === 0 || $requestUri === '/app' || $requestUri === '/');`
- Satır 819: `if ($isAppRequest && class_exists('PathCLogger')) { ... }`

**Sorun**:
- `$isAppRequest` sadece `/app` path'lerinde tanımlanıyor
- Bazı branch'lerde (örneğin API endpoint'leri, health check'ler) tanımsız kalabiliyor
- Satır 819'da kullanılmadan önce her zaman tanımlanmış olması garanti edilmiyor

---

## 4. ÇÖZÜM STRATEJİSİ

### 4.1. `payments` Sorguları İçin

**JOIN Stratejisi**:
- `payments.job_id → jobs.id → jobs.company_id` kullanılacak
- Eğer `job_id` NULL ise, `payments.customer_id → customers.id → customers.company_id` kullanılacak

**Uygulama**:
```sql
-- ÖNCE: scopeToCompany("WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ?", 'p')
-- SONRA: JOIN ile jobs.company_id veya customers.company_id kullan
SELECT COALESCE(SUM(p.amount), 0) as total 
FROM payments p
LEFT JOIN jobs j ON p.job_id = j.id
LEFT JOIN customers c ON COALESCE(p.customer_id, j.customer_id) = c.id
WHERE p.status = 'completed' 
  AND DATE(p.created_at) BETWEEN ? AND ? 
  AND (j.company_id = ? OR (j.company_id IS NULL AND c.company_id = ?))
```

**Daha Basit Alternatif** (eğer job_id her zaman varsa):
```sql
SELECT COALESCE(SUM(p.amount), 0) as total 
FROM payments p
LEFT JOIN jobs j ON p.job_id = j.id
WHERE p.status = 'completed' 
  AND DATE(p.created_at) BETWEEN ? AND ? 
  AND j.company_id = ?
```

---

### 4.2. `contracts` Sorguları İçin

**JOIN Stratejisi**:
- `contracts.customer_id → customers.id → customers.company_id` kullanılacak

**Uygulama**:
```sql
-- ÖNCE: scopeToCompany("WHERE ct.created_at >= datetime('now', '-24 hours')", 'ct')
-- SONRA: JOIN ile customers.company_id kullan
SELECT ct.*, c.name as customer_name, 'contract_created' as type
FROM contracts ct
LEFT JOIN customers c ON ct.customer_id = c.id
WHERE ct.created_at >= datetime('now', '-24 hours') 
  AND c.company_id = ?
ORDER BY ct.created_at DESC
LIMIT 10
```

---

### 4.3. `$isAppRequest` İçin

**Çözüm**:
- `$isAppRequest`'i index.php'nin en başında, tüm request'ler için tanımla
- Default: `false`
- Sadece `/app` HTML request'lerinde `true`

**Uygulama**:
```php
// index.php başında (satır ~430 civarı)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isAppRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' 
    && (str_starts_with($requestUri, '/app') || $requestUri === '/app' || $requestUri === '/');
```

---

## 5. SONUÇ

### 5.1. Tespit Edilen Sorunlar

1. ✅ `p.company_id` hatası: 8 sorgu etkileniyor (getWeekIncome, getWeeklyIncomeTrend)
2. ✅ `ct.company_id` hatası: 1 sorgu etkileniyor (getRecentActivities)
3. ✅ `$isAppRequest` undefined hatası: index.php:819'da kullanılıyor ama bazı branch'lerde tanımsız

### 5.2. Çözüm Yönleri

1. **payments**: JOIN ile `jobs.company_id` veya `customers.company_id` kullan
2. **contracts**: JOIN ile `customers.company_id` kullan
3. **$isAppRequest**: Index.php başında merkezi olarak tanımla

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE 1 - payments/contracts sorgularını düzeltme

