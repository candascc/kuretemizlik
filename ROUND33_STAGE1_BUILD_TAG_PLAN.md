# ROUND 33 – STAGE 1: BUILD TAG TASARIMI

**Tarih:** 2025-11-22  
**Round:** ROUND 33

---

## BUILD TAG TASARIMI

### 1. BUILD TAG Tanımı

**Format:** `KUREAPP_R33_2025-11-22`

**Yerleşim:**
- `config/constants.php` dosyası yok, bu yüzden `index.php` içinde global bir constant olarak tanımlanacak
- Alternatif: Küçük bir `App\Build::tag()` helper sınıfı oluşturulabilir, ama basitlik için constant tercih edilecek

**Seçim:** `index.php` içinde, config yüklendikten sonra, router tanımlanmadan önce:

```php
// ROUND 33: Build tag for production fingerprinting
define('KUREAPP_BUILD_TAG', 'KUREAPP_R33_2025-11-22');
```

---

### 2. BUILD TAG Kullanım Yerleri

#### A) `/health` JSON Çıktısı

**Dosya:** `index.php` - `/health` route handler

**Kullanım:**
- `SystemHealth` varsa: JSON array'ine `build` alanını ekle
- `SystemHealth` yoksa veya exception durumunda: JSON error objesine `build` alanını ekle

**Örnek JSON:**
```json
{
  "status": "ok",
  "build": "KUREAPP_R33_2025-11-22",
  "timestamp": "2025-11-22T21:00:00Z",
  "checks": {
    "database": {
      "status": "ok"
    }
  }
}
```

#### B) `/app/status` Sayfası

**Dosya:** `src/Views/legal/status.php`

**Kullanım:**
- HTML içinde bir comment veya ufak bir text olarak ekle
- Tercih: HTML comment (görünmez ama Playwright ile okunabilir)

**Örnek HTML:**
```html
<!-- BUILD: KUREAPP_R33_2025-11-22 -->
```

VEYA

```html
<small class="text-gray-500">Build: KUREAPP_R33_2025-11-22</small>
```

**Seçim:** HTML comment tercih edilecek (görünmez, Playwright ile okunabilir)

---

### 3. `/app/status` Route Kontrolü

**Controller:** `LegalController::status()` (ROUND 31'de oluşturuldu)

**View:** `src/Views/legal/status.php`

**Route:** `/status` (ROUND 31'de `index.php`'de tanımlandı)

**Kullanım:**
- `LegalController::status()` metodunda BUILD TAG'i view'a data olarak geçirilebilir
- VEYA view içinde direkt `KUREAPP_BUILD_TAG` constant'ını kullanabilir (daha basit)

**Seçim:** View içinde direkt `KUREAPP_BUILD_TAG` constant'ını kullan (daha basit)

---

## UYGULAMA PLANI

1. **`index.php` içinde BUILD TAG tanımla:**
   - Config yüklendikten sonra, router tanımlanmadan önce
   - `define('KUREAPP_BUILD_TAG', 'KUREAPP_R33_2025-11-22');`

2. **`/health` endpoint'ini güncelle:**
   - Output buffer temizleme (nested buffer sorunlarını çözecek şekilde)
   - Header'ları HER ZAMAN içerikten önce set et
   - JSON çıktısına `build` alanını ekle

3. **`/app/status` view'ını güncelle:**
   - HTML comment olarak BUILD TAG ekle
   - `<!-- BUILD: KUREAPP_R33_2025-11-22 -->`

---

**STAGE 1 TAMAMLANDI** ✅

