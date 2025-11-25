# System Admin Crawl Test - Kullanım Kılavuzu

**PATH_CRAWL_SYSADMIN_V1**: Sistem admin (candas) kullanıcısı için tam menü crawl testi  
**PATH_CRAWL_SYSADMIN_WEB_V1**: Web üzerinden sysadmin crawl çalıştırma

---

## Web Üzerinden Sysadmin Crawl Çalıştırma

### Hızlı Başlangıç (Web UI)

**Adım 1**: `candas` ile `https://www.kuretemizlik.com/app` adresine login ol.

**Adım 2**: Sağ üst köşedeki sistem menüsüne (server ikonu) tıkla.

**Adım 3**: Açılan menüden **"Sistem Tarama (Sysadmin Crawl)"** linkine tıkla.

**Adım 4**: Açılan sayfada crawl sonuçları tablo halinde görüntülenir.

### Özellikler

- ✅ **Terminal/SSH gerekmez** - Tüm işlem web arayüzünden yapılır
- ✅ **Tek tıkla çalıştırma** - Menüden linke tıklamak yeterli
- ✅ **Anlık sonuçlar** - Crawl sonuçları sayfada tablo olarak gösterilir
- ✅ **Detaylı raporlama** - Her URL için status, marker, hata bilgisi
- ✅ **Recursive link-based crawl** - Dashboard'tan başlayarak tüm tıklanabilir linkleri otomatik keşfeder

### Crawl Mantığı (PATH_CRAWL_SYSADMIN_DEEPCLICK_V1)

**Recursive Link-Based Crawl**:

- Crawl `/app/` dashboard sayfasından başlar
- Dashboard'ta bulunan tüm `<a href="...">` linklerini otomatik olarak keşfeder
- Her sayfada bulunan linkler de recursive olarak taranır
- Bu işlem sistem admin kullanıcısının UI üzerinde tıklayarak erişebileceği tüm GET sayfalarını kapsar

**Güvenlik Filtreleri**:

- Sadece `/app/...` ile başlayan linkler taranır (aynı origin)
- Destructive linkler otomatik olarak atlanır:
  - `logout`, `log-out`, `signout`, `sign-out`
  - `delete`, `destroy`, `remove`, `drop`, `truncate`
  - `?action=delete`, `?do=delete` gibi pattern'ler
- Sadece GET istekleri yapılır (POST/form submit atlanır)

**Limitler**:

- **Max URL sayısı**: 500 (sonsuz loop önleme)
- **Max depth**: 10 (çok derin link ağacı önleme)

**Özel Seed URL'ler**:

- Dashboard'a ek olarak, menüden tıklanması zor olan önemli endpoint'ler de seed olarak eklenir:
  - `/app/performance/metrics`
  - `/app/health`

### Sonuçların Yorumlanması

Web arayüzünde gösterilen sonuçlar CLI script ile aynı formatı kullanır:

- **Status 200**: Sayfa başarıyla yüklendi ✅
- **Status 302**: Yönlendirme (normal) 🔄
- **Status 403**: Yetki sorunu ⚠️
- **Status 404**: Sayfa bulunamadı ⚠️
- **Status 500**: Kritik hata ❌

**Marker**: `GLOBAL_R50_MARKER_1` marker'ı varsa sayfa başarıyla render edilmiş demektir.

**Depth**: Her URL'nin keşif derinliğini gösterir:
- `0` = Seed URL (dashboard veya özel endpoint)
- `1+` = Dashboard'tan keşfedilen linkler

### Notlar

- Bu web arayüzü, CLI script ile aynı recursive crawl mantığını kullanır
- 500/403/404 gibi hatalar tablo içinde net şekilde görülebilir
- Sadece sistem admin (candas) kullanıcısı bu aracı görebilir ve kullanabilir
- Normal admin, operator, müşteri vs bu linki göremez
- Destructive linkler (logout, delete vb.) otomatik olarak atlanır

---

## CLI Üzerinden Sysadmin Crawl Çalıştırma (Alternatif)

### Recursive Link-Based Crawl

**PATH_CRAWL_SYSADMIN_DEEPCLICK_V1**: CLI script artık recursive link-based crawl kullanır:

- `/app/` dashboard'tan başlar
- Tüm tıklanabilir linkleri otomatik olarak keşfeder
- Her sayfada bulunan linkler recursive olarak taranır
- Destructive linkler (logout, delete vb.) otomatik olarak atlanır
- Max 500 URL, max depth 10 ile sınırlıdır

### Özellikler

- ✅ **Recursive discovery** - Dashboard'tan başlayarak tüm linkleri otomatik keşfeder
- ✅ **Güvenlik filtreleri** - Destructive linkler otomatik atlanır
- ✅ **Depth tracking** - Her URL'nin keşif derinliği gösterilir
- ✅ **Aynı mantık** - Web arayüzü ile aynı crawl algoritmasını kullanır

## Hızlı Başlangıç

### Komut

```bash
php tests/ui/crawl_sysadmin.php
```

### Varsayılan Ayarlar

- **Base URL**: `https://www.kuretemizlik.com/app`
- **Username**: `candas`
- **Password**: `12dream21`

---

## Ortam Değişkenleri (Opsiyonel)

Aşağıdaki environment variable'ları kullanarak ayarları override edebilirsiniz:

```bash
export KUREAPP_BASE_URL="https://www.kuretemizlik.com/app"
export KUREAPP_SYSADMIN_USER="candas"
export KUREAPP_SYSADMIN_PASS="12dream21"

php tests/ui/crawl_sysadmin.php
```

---

## Komut Satırı Parametreleri

```bash
php tests/ui/crawl_sysadmin.php [base_url] [username] [password]
```

**Örnekler**:

```bash
# Varsayılan ayarlarla çalıştır
php tests/ui/crawl_sysadmin.php

# Base URL belirt
php tests/ui/crawl_sysadmin.php "https://www.kuretemizlik.com/app"

# Tüm parametreleri belirt
php tests/ui/crawl_sysadmin.php "https://www.kuretemizlik.com/app" "candas" "12dream21"
```

---

## Prod Sunucuda Çalıştırma

### 1. SSH ile Sunucuya Bağlan

```bash
ssh user@kuretemizlik.com
```

### 2. Uygulama Root'una Git

```bash
cd /home/cagdasya/kuretemizlik.com/app
```

### 3. Crawl Script'ini Çalıştır

```bash
php tests/ui/crawl_sysadmin.php
```

### 4. Log Dosyasını Kontrol Et

Log dosyası `logs/crawl_sysadmin_YYYY-MM-DD_HH-MM-SS.log` formatında oluşturulur:

```bash
ls -lh logs/crawl_sysadmin_*.log
tail -f logs/crawl_sysadmin_*.log
```

---

## Çıktı Formatı

### Konsol Çıktısı

```
=== PATH_CRAWL_SYSADMIN_V1: System Admin Crawl Test ===
Base URL: https://www.kuretemizlik.com/app
Username: candas
Log File: logs/crawl_sysadmin_2025-11-23_19-30-00.log

Logging in...
Login successful.

Crawling 25 URLs...

GET /app/... OK (status=200, marker=YES)
GET /app/calendar... OK (status=200, marker=YES)
GET /app/jobs... OK (status=200, marker=YES)
...

=== CRAWL SUMMARY ===
Total URLs: 25
Success: 24
Errors: 1
Log File: logs/crawl_sysadmin_2025-11-23_19-30-00.log

=== ERROR DETAILS ===
  - /app/reports: status=403
```

### Log Dosyası Formatı

Her log satırı şu formatta:

```
[2025-11-23 19:30:00] [req_abc123def456] CRAWL_SYSADMIN_SUCCESS | Context: {"url":"/app/","status":200,"marker":"YES","body_length":45678}
[2025-11-23 19:30:01] [req_abc123def456] CRAWL_SYSADMIN_ERROR | Context: {"url":"/app/reports","status":403,"marker":"NO"}
```

---

## HTTP Status Kodları ve Anlamları

### ✅ Başarılı (200)

- Sayfa başarıyla yüklendi
- Dashboard, form, liste sayfaları normal çalışıyor

### 🔄 Yönlendirme (302)

- Login sonrası redirect normal
- Sayfa başka bir yere yönlendiriyor (genelde OK)

### ⚠️ İnceleme Gereken (403, 404)

- **403 Forbidden**: Yetki sorunu, rol kontrolü gerekebilir
- **404 Not Found**: Sayfa bulunamadı, route tanımlı değil olabilir

### ❌ Kritik (500)

- **500 Internal Server Error**: Kritik hata
- Log dosyalarını kontrol et:
  - `logs/app_YYYY-MM-DD.log`
  - `logs/error.log`
  - `logs/app_firstload_pathc.log`
- `GLOBAL_R50_MARKER_1` marker'ı kontrol et
- PATHC/PATHD/PATHE/PATHF log'larını incele

---

## Test Edilen URL'ler

Script aşağıdaki URL'leri test eder:

- `/app/` - Dashboard
- `/app/calendar` - Takvim
- `/app/jobs` - İşler listesi
- `/app/jobs/new` - Yeni iş formu
- `/app/recurring` - Periyodik işler
- `/app/recurring/new` - Yeni periyodik iş
- `/app/customers` - Müşteriler
- `/app/customers/new` - Yeni müşteri
- `/app/services` - Hizmetler
- `/app/services/new` - Yeni hizmet
- `/app/finance` - Finans
- `/app/finance/new` - Yeni finans kaydı
- `/app/reports` - Raporlar ana sayfa
- `/app/reports/financial` - Finansal raporlar
- `/app/reports/jobs` - İş raporları
- `/app/reports/customers` - Müşteri raporları
- `/app/reports/services` - Hizmet raporları
- `/app/performance` - Performans
- `/app/performance/metrics` - Performans metrikleri
- `/app/analytics` - Analitik
- `/app/users` - Kullanıcılar (sistem admin)
- `/app/settings` - Ayarlar (sistem admin)
- `/app/system` - Sistem yönetimi (sistem admin)
- `/app/health` - Health check

---

## Sonuçların Yorumlanması

### Başarılı Senaryo

```
Total URLs: 25
Success: 25
Errors: 0
```

**Anlam**: Tüm sayfalar başarıyla yüklendi, 500 hatası yok.

---

### Hata Senaryosu

```
Total URLs: 25
Success: 23
Errors: 2

=== ERROR DETAILS ===
  - /app/reports: status=403
  - /app/users: status=500
```

**Anlam**:
- `/app/reports`: 403 → Yetki sorunu, rol kontrolü gerekebilir
- `/app/users`: 500 → Kritik hata, log dosyalarını kontrol et

**Yapılacaklar**:
1. Log dosyalarını kontrol et:
   ```bash
   tail -100 logs/app_$(date +%Y-%m-%d).log | grep -i "error\|exception"
   tail -100 logs/error.log
   ```
2. `GLOBAL_R50_MARKER_1` marker'ını kontrol et
3. PATHC/PATHD/PATHE/PATHF log'larını incele
4. İlgili controller'ları kontrol et

---

## Regresyon Notu

Bu script:

- ✅ Router davranışını değiştirmez
- ✅ Controller davranışını değiştirmez
- ✅ Global error handler'ı değiştirmez
- ✅ Sadece HTTP client olarak uygulamayı dışarıdan tarar
- ✅ Yük testi değildir (agresif istek atmaz, her istek arasında 0.1 saniye bekler)

**Etkilenmeyen Endpoint'ler**:
- `/app/health` - Etkilenmedi
- `/app/calendar` - Etkilenmedi
- `/app/reports` - Etkilenmedi
- `/app/jobs` - Etkilenmedi
- `/app/performance/metrics` - Etkilenmedi
- View/render - Etkilenmedi
- Header-context - Etkilenmedi
- Auth - Etkilenmedi

---

## Sorun Giderme

### Login Başarısız

**Hata**: `Login failed. Check credentials.`

**Çözüm**:
1. Kullanıcı adı ve şifrenin doğru olduğundan emin ol
2. Environment variable'ları kontrol et:
   ```bash
   echo $KUREAPP_SYSADMIN_USER
   echo $KUREAPP_SYSADMIN_PASS
   ```
3. CSRF token alınıyor mu kontrol et (login sayfası yükleniyor mu)

---

### cURL Hatası

**Hata**: `cURL exec failed` veya SSL hatası

**Çözüm**:
1. cURL extension'ının yüklü olduğundan emin ol:
   ```bash
   php -m | grep curl
   ```
2. SSL sertifikası sorunu varsa, script içinde `CURLOPT_SSL_VERIFYPEER => false` zaten ayarlı

---

### Log Dosyası Yazılamıyor

**Hata**: Log dosyası oluşturulamıyor

**Çözüm**:
1. `logs/` dizininin yazılabilir olduğundan emin ol:
   ```bash
   chmod 755 logs/
   ```
2. Disk alanı kontrol et:
   ```bash
   df -h
   ```

---

## İletişim

Sorun yaşarsanız:
1. Log dosyasını kontrol et
2. `error.log` dosyasını kontrol et
3. PATHC/PATHD/PATHE/PATHF log'larını incele
4. İlgili controller'ları kontrol et

---

**Son Güncelleme**: 2024-12-XX  
**Script Versiyonu**: PATH_CRAWL_SYSADMIN_V1

