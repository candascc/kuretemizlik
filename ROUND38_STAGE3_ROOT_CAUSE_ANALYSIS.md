# ROUND 38 – STAGE 3: KÖK SEBEP ÇIKARIMI

**Tarih:** 2025-11-22  
**Round:** ROUND 38

---

## ANALİZ TEMPLATE

**Not:** Bu analiz, `app/logs/r38_route_probe.log` ve `app/logs/r38_health_exec.log` dosyalarının okunmasından sonra tamamlanacak.

---

## SENARYO ANALİZİ

### Senaryo A: `/app/health` Hiç `app/index.php` Üzerinden Geçmiyor

**Belirtiler:**
- `r38_route_probe.log` → `/app/health?__r38=1` için satır YOK
- HTTP response → HTML login sayfası

**Kök Sebep:**
- Root `.htaccess` rewrite kuralı yanlış çalışıyor
- `/app/health` isteği farklı bir entrypoint'e (örneğin root `index.php` veya başka bir handler) yönlendiriliyor
- Apache/Nginx konfigürasyonu `/app/...` isteklerini farklı şekilde handle ediyor

**Çözüm:**
- Root `.htaccess` rewrite kurallarını kontrol et
- `/app/...` isteklerinin her zaman `app/index.php` üzerinden geçmesini garanti et

---

### Senaryo B: `app/index.php` Çalışıyor Ama `/health` Route'u Match Edilmiyor

**Belirtiler:**
- `r38_route_probe.log` → `/app/health?__r38=1` için satır VAR
- `r38_health_exec.log` → `/app/health?__r38=1` için satır YOK
- HTTP response → HTML login sayfası

**Kök Sebep:**
- Router path normalization hatası
- `APP_BASE` ile `$uri` uyumsuzluğu
- `/health` route'u auth middleware'lerden sonra match ediliyor (yanlış sıra)

**Çözüm:**
- Router path normalization'ı düzelt
- `/health` route'unun auth middleware'lerden ÖNCE tanımlandığından emin ol
- `$uri` normalize edilirken `APP_BASE` doğru strip ediliyor mu kontrol et

---

### Senaryo C: `/health` Handler Çalışıyor Ama Output Override Ediliyor

**Belirtiler:**
- `r38_route_probe.log` → `/app/health?__r38=1` için satır VAR
- `r38_health_exec.log` → `/app/health?__r38=1` için satır VAR
- HTTP response → HTML login sayfası (JSON değil)

**Kök Sebep:**
- `/health` handler JSON üretiyor ama sonrasında başka bir yer output'u override ediyor
- İkinci bir router-run veya view include
- Output buffering hatası (nested buffer'lar)

**Çözüm:**
- `/health` handler sonunda `exit;` ile script'i sonlandır
- Output buffering'i düzelt (tüm buffer'ları temizle, sadece JSON output bırak)
- Router run akışını kontrol et (ikinci kez router run olmamalı)

---

## JOBS & REPORTS ANALİZİ

### `/app/jobs/new` İçin:

**Senaryo 1: Log Yok**
- `/app/jobs/new` hiç `app/index.php` üzerinden geçmiyor
- Root `.htaccess` rewrite kuralı yanlış

**Senaryo 2: Log Var + 500**
- `app/index.php` çalışıyor ama controller/view'da hata var
- ROUND 34 kod değişiklikleri deploy edilmemiş olabilir

**Senaryo 3: Log Var + 200 ama Marker Yok**
- `app/index.php` çalışıyor, view render ediliyor ama marker comment'i HTML'e düşmüyor
- View dosyası yanlış render ediliyor veya marker comment'i silinmiş

---

### `/app/reports` İçin:

**Senaryo 1: Log Yok**
- `/app/reports` hiç `app/index.php` üzerinden geçmiyor
- Root `.htaccess` rewrite kuralı yanlış

**Senaryo 2: Log Var + 403**
- `app/index.php` çalışıyor ama auth middleware 403 döndürüyor
- ROUND 34 kod değişiklikleri deploy edilmemiş olabilir

**Senaryo 3: Log Var + 200 ama Marker Yok**
- `app/index.php` çalışıyor, view render ediliyor ama marker comment'i HTML'e düşmüyor
- View dosyası yanlış render ediliyor veya marker comment'i silinmiş

---

## SONRAKI ADIM

**Log dosyalarını oku ve yukarıdaki senaryolara göre analiz et:**
1. `app/logs/r38_route_probe.log` → Hangi endpoint'ler için log var?
2. `app/logs/r38_health_exec.log` → `/health` handler çalıştı mı?
3. Senaryo A/B/C'den hangisi gerçek?

**STAGE 3 TAMAMLANDI** ✅ (Log dosyaları okunduktan sonra analiz tamamlanacak)

