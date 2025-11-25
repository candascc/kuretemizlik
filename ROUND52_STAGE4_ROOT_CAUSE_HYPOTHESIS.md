# ROUND 52 - STAGE 4: PROD BENZETİMLİ ROOT CAUSE ANALİZİ

## En Yüksek İhtimalli Root Cause Adayları

### 1. Dashboard View - Null Array Access (YÜKSEK İHTİMAL)

**Dosya:** `src/Views/dashboard/today.php`

**Problem:**
- Line 31: `$stats['today']['jobs']` - `$stats['today']` null olabilir
- Line 44: `$stats['today']['income']` - `$stats['today']` null olabilir
- Line 57: `$stats['today']['expense']` - `$stats['today']` null olabilir
- Line 70-71: `$stats['today']['profit']` - `$stats['today']` null olabilir
- Line 90: `$stats['week']['income']` - `$stats['week']` null olabilir
- Line 94: `$stats['week']['expense']` - `$stats['week']` null olabilir
- Line 98-99: `$stats['week']['profit']` - `$stats['week']` null olabilir
- Line 116: `$stats['month']['income']` - `$stats['month']` null olabilir
- Line 120: `$stats['month']['expense']` - `$stats['month']` null olabilir
- Line 124-125: `$stats['month']['profit']` - `$stats['month']` null olabilir

**Neden 500 Oluyor:**
- `DashboardController::buildDashboardData()` metodu `$stats` array'ini oluştururken `today`, `week`, `month` key'lerini set etmeyebilir
- Cache'den gelen data bozuk olabilir (unserialize hatası, eski format)
- `buildDashboardData()` içinde exception fırlatılırsa, safe defaults kullanılsa bile view'da nested array access yapılıyor

**Neden Bazen Olup Bazen Olmuyor:**
- Cache dolu/boş durumuna göre değişiyor
- Cache'den gelen data formatı eski olabilir (migration sonrası)
- `buildDashboardData()` içindeki DB query'leri bazen boş dönüyor, bazen exception fırlatıyor

**Çözüm:**
- View'da null-safe access kullan: `$stats['today']['jobs'] ?? 0`
- Veya controller'da `buildDashboardData()` metodunun her zaman doğru format döndüğünden emin ol

### 2. HeaderManager Bootstrap Exception (ORTA İHTİMAL)

**Dosya:** `src/Lib/HeaderManager.php`

**Problem:**
- `HeaderManager::bootstrap()` içinde session start sırasında exception fırlatılabilir
- `build_app_header_context()` içinde `HeaderManager::bootstrap()` çağrılıyor
- Exception yakalanıyor ama view render sırasında tekrar patlayabilir

**Neden 500 Oluyor:**
- Session zaten başlamışsa `session_start()` exception fırlatabilir
- Cookie path yanlış ayarlanmışsa session başlatılamaz
- `HeaderManager::getCurrentMode()` çağrıldığında session yoksa exception fırlatılabilir

**Neden Bazen Olup Bazen Olmuyor:**
- Session durumu request'ten request'e değişiyor
- İlk request'te session yok, sonraki request'lerde var
- Cookie path değişikliği sonrası eski session cookie'si hala geçerli olabilir

**Çözüm:**
- `HeaderManager::bootstrap()` içindeki exception handling'i güçlendir
- Session start öncesi `session_status()` kontrolü yap

### 3. Cache Unserialize Failure (DÜŞÜK İHTİMAL, AMA MÜMKÜN)

**Dosya:** `src/Controllers/DashboardController.php`

**Problem:**
- `Cache::remember()` içinde serialize/unserialize işlemi yapılıyor
- Bozuk cache data'sı unserialize edilemezse exception fırlatılabilir
- Exception yakalanıyor ama view render sırasında data formatı yanlış olabilir

**Neden 500 Oluyor:**
- Cache dosyası bozuk (disk hatası, partial write)
- PHP version değişikliği sonrası serialize format uyumsuzluğu
- Cache key collision (aynı key farklı format'ta data içeriyor)

**Neden Bazen Olup Bazen Olmuyor:**
- Cache TTL dolduğunda yeni data yazılıyor, eski bozuk data temizleniyor
- Cache clear sonrası problem geçici olarak çözülüyor

**Çözüm:**
- `Cache::remember()` içinde unserialize hatası yakalanıp cache invalidate edilmeli
- Try/catch ile bozuk cache data'sı handle edilmeli

## Öncelik Sırası

1. **Dashboard View - Null Array Access** (En yüksek ihtimal, en kolay fix)
2. **HeaderManager Bootstrap Exception** (Orta ihtimal, mevcut try/catch var ama yeterli değil)
3. **Cache Unserialize Failure** (Düşük ihtimal, ama production'da görülebilir)

## Sonraki Adım

STAGE 5'te bu root cause'ları tek tek ele alacağız:
- Dashboard view'da null-safe access ekle
- HeaderManager exception handling'i güçlendir
- Cache unserialize hatası için fallback ekle

