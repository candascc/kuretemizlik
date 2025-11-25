# ROUND 52 - STAGE 2: Uygulama (Instrumentation)

## Değişen Dosyalar

### 1. `src/Lib/View.php`

**Eklenenler:**
- `private static $r52LoggingActive = false;` - Recursive loop önleme flag'i
- `private static function logViewFatal(Throwable $e, ?string $viewName, ?string $layoutName): void` - R52 fatal log helper fonksiyonu

**Değişiklikler:**
- `renderWithLayout()` metodunun en dış catch bloğunda (line 255) `self::logViewFatal($e, $view, $layout);` çağrısı eklendi

**Fonksiyon Detayları:**
- `logViewFatal()` fonksiyonu:
  - Recursive loop önleme (static flag kontrolü)
  - Request bilgilerini toplama (uri, method, user_id)
  - Trace'i kısaltma (ilk 10 frame, max 1000 karakter)
  - `logs/r52_view_fatal.log` dosyasına yazma
  - Marker: `R52_VIEW_FATAL`

### 2. `src/Views/layout/partials/header-context.php`

**Değişiklikler:**
- `build_app_header_context()` fonksiyonunun en dış catch bloğunda (line 356) R52 log çağrısı eklendi
- Reflection kullanarak private `View::logViewFatal()` metoduna erişim sağlandı
- Reflection başarısız olursa fallback olarak direkt log yazma yapılıyor

**Not:** Safe defaults döndürmeye devam ediyor (davranış değişmedi)

### 3. `src/Views/errors/error.php`

**Değişiklikler:**
- HTML comment eklendi: `<!-- R52_500_TEMPLATE -->` (line 2)
- Bu sayede 500 sayfasının source'unda marker görülebilir

## Log Formatı

**Örnek Log Entry:**
```
[2025-01-XX 12:34:56] [req_67890abcdef12345] R52_VIEW_FATAL uri=/app/dashboard method=GET user_id=1 view=dashboard/today layout=base class=ErrorException message=Undefined index: key file=/path/to/file.php line=123 trace=#0 /path/to/file.php(123): function()...
```

**Alanlar:**
- `timestamp`: Y-m-d H:i:s
- `request_id`: uniqid('req_', true)
- `uri`: REQUEST_URI
- `method`: REQUEST_METHOD
- `user_id`: Auth::id() veya 'none'
- `view`: View adı veya 'null'
- `layout`: Layout adı veya 'null'
- `class`: Exception/Throwable sınıf adı
- `message`: Exception mesajı (newline'lar space'e çevrildi)
- `file`: Exception dosyası
- `line`: Exception satırı
- `trace`: İlk 10 frame (max 1000 karakter)

## Recursive Loop Önleme

**Mekanizma:**
- `$r52LoggingActive` static flag kullanılıyor
- Log yazma işlemi başlamadan önce flag kontrol ediliyor
- Flag true ise, log yazma işlemi atlanıyor
- Log yazma işlemi bittikten sonra (finally bloğunda) flag false'a çevriliyor

**Fayda:**
- Recursive 500 loop'ları önleniyor
- Log dosyası spam'lenmiyor
- Error handling bozulmuyor

## Davranış Değişiklikleri

**YOK:** Mevcut fallback davranışları korunuyor:
- `View::renderWithLayout()` hala safe fallback HTML döndürüyor
- `build_app_header_context()` hala minimum viable header context döndürüyor
- Error template hala aynı şekilde render ediliyor

**Sadece eklenen:** R52 fatal log yazma

