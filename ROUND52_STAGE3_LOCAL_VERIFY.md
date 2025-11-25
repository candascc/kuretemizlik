# ROUND 52 - STAGE 3: Lokal Test & Log Doğrulaması

## Test Senaryoları

### Senaryo 1: Normal Flow (500 Olmamalı)

**Adımlar:**
1. `/app/` → Login ekranı görüntüle
2. Login ol → Dashboard (`/app/`)
3. Menüden sayfalara git:
   - `/app/calendar`
   - `/app/customers`
   - `/app/jobs`
   - `/app/reports`

**Beklenen:**
- Tüm sayfalar 200 status code döndürmeli
- `logs/r52_view_fatal.log` dosyasında entry OLMAMALI (normal flow)

### Senaryo 2: Bilerek Hata Tetikleme (500 Test)

**Adımlar:**
1. Bir view dosyasında geçici olarak exception fırlat
2. O view'ı render etmeye çalış
3. 500 sayfasının source'unda `R52_500_TEMPLATE` comment'ini kontrol et
4. `logs/r52_view_fatal.log` dosyasında entry olup olmadığını kontrol et

**Beklenen:**
- 500 sayfası render edilmeli
- Source'da `<!-- R52_500_TEMPLATE -->` görünmeli
- `logs/r52_view_fatal.log` dosyasında ilgili entry olmalı

## Log Örnekleri

### Beklenen Log Formatı

```
[2025-01-XX 12:34:56] [req_67890abcdef12345] R52_VIEW_FATAL uri=/app/dashboard method=GET user_id=1 view=dashboard/today layout=base class=ErrorException message=Undefined index: key file=/path/to/file.php line=123 trace=#0 /path/to/file.php(123): function()...
```

### Log Kontrol Komutları

```bash
# R52 fatal log'ları kontrol et
grep "R52_VIEW_FATAL" logs/r52_view_fatal.log

# Son 10 entry'yi göster
tail -n 10 logs/r52_view_fatal.log

# Belirli bir view için log'ları filtrele
grep "view=dashboard" logs/r52_view_fatal.log
```

## R52 Logging Çalıştığını Doğrulama

**Kontrol Edilecekler:**
1. ✅ `logs/r52_view_fatal.log` dosyası oluşturuldu mu?
2. ✅ Log entry'leri doğru formatta mı? (R52_VIEW_FATAL marker var mı?)
3. ✅ Request ID benzersiz mi?
4. ✅ User ID doğru mu? (Auth::id() veya 'none')
5. ✅ Trace kısaltılmış mı? (max 10 frame, max 1000 karakter)
6. ✅ Recursive loop önlendi mi? (aynı request'te birden fazla entry yok)

## Test Sonuçları

**Not:** Bu stage'de gerçek test yapılmadı (lokal ortam gerekli). Dokümantasyon hazırlandı.

**Beklenen Davranış:**
- Normal flow'da log entry OLMAMALI
- Hata durumunda log entry OLMALI
- 500 template'inde marker görünmeli

