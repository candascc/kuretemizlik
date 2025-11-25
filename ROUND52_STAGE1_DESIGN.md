# ROUND 52 - STAGE 1: Log Formatı & Helper Fonksiyon Tasarımı

## Log Formatı

### Yeni Log Dosyası: `logs/r52_view_fatal.log`

**Format:**
```
[timestamp] [request_id] R52_VIEW_FATAL uri={uri} method={method} user_id={user_id} view={view} layout={layout} class={class} message={message} file={file} line={line} trace={trace}
```

**Alanlar:**
- `timestamp`: Y-m-d H:i:s formatında
- `request_id`: uniqid('req_', true) ile oluşturulan benzersiz ID
- `uri`: REQUEST_URI
- `method`: REQUEST_METHOD
- `user_id`: Auth::id() veya 'none' (Auth kullanılabiliyorsa)
- `view`: Render edilmeye çalışılan view adı
- `layout`: Kullanılan layout adı (null olabilir)
- `class`: Exception/Throwable sınıf adı
- `message`: Exception mesajı
- `file`: Exception dosyası
- `line`: Exception satırı
- `trace`: İlk 5-10 frame (kısa trace, çok uzun string dolandırma)

**Marker:** `R52_VIEW_FATAL` (unique grep için)

## Double-logging / Recursive 500 Loop Önlemi

**Static Flag:**
```php
private static $r52LoggingActive = false;
```

**Kullanım:**
- Flag sadece fatal log fonksiyonunun içinde kullanılacak
- Eğer flag true ise, tekrar log yazmaya çalışma (recursive 500'ü engelle)
- Log yazma işlemi başarılı olduktan sonra flag'i false'a çevir (veya log yazma işlemi bittikten sonra)

## Enstrümantasyon Noktaları

### a) View::renderWithLayout()

**Lokasyon:** `src/Lib/View.php` - Line 255 (en dış catch bloğu)

**Aksiyon:**
- En dış catch bloğunda R52 log helper'ını çağır
- Mevcut fallback davranışına devam et (500 template render veya error view)
- Davranışı değiştirme, sadece log ekle

### b) build_app_header_context()

**Lokasyon:** `src/Views/layout/partials/header-context.php` - En dış catch bloğu

**Aksiyon:**
- En dış catch içinde R52 fatal log helper'ını çağır
- `view='header-context'`, `layout='base'` veya null
- Safe defaults döndürmeye devam et (davranışı bozma)

### c) "Hata 500" HTML Template

**Lokasyon:** `src/Views/errors/error.php`

**Aksiyon:**
- HTML comment ekle: `<!-- R52_500_TEMPLATE -->`
- Mümkünse request_id'yi de buraya göm (global değişkenden veya header context'ten)

**Fayda:**
- 500 sayfası gerçekten bu template ise, source'da `R52_500_TEMPLATE` görülecek
- Aynı anda `r52_view_fatal.log`'da da ilgili entry oluşmuş olacak

## Helper Fonksiyon Tasarımı

### logViewFatal()

**Lokasyon:** `src/Lib/View.php` (private static method)

**Signature:**
```php
private static function logViewFatal(Throwable $e, ?string $viewName, ?string $layoutName): void
```

**İçerik:**
1. Static flag kontrolü (recursive loop önleme)
2. Request bilgilerini topla (uri, method, user_id)
3. Trace'i kısalt (ilk 5-10 frame)
4. Log dosyasına yaz
5. Flag'i güncelle

**Not:** Bu fonksiyon sadece log yazacak, exception'ı yakalamayacak veya re-throw etmeyecek.

