# STAGE 0 - PATH C LOG & DB HATA DOĞRULAMA RAPORU

**Tarih**: 2024-12-XX  
**Görev**: STAGE 0 - Log Analizi ve DB Hata Tespiti (READ-ONLY)  
**Durum**: TAMAMLANDI

---

## 1. LOG DOSYALARI İNCELEMESİ

### 1.1. `jp.company_id` Hatası Analizi

**Hata Mesajı**: `SQLSTATE[HY000]: General error: 1 no such column: jp.company_id`

**Etkilenen SQL Sorguları** (3 adet):

1. **getWeekIncome()** - Haftalık gelir sorgusu
   ```sql
   SELECT COALESCE(SUM(jp.amount), 0) as total 
   FROM job_payments jp
   WHERE DATE(jp.created_at) BETWEEN ? AND ? AND jp.company_id = 1
   ```

2. **getRecentActivities()** - Son ödemeler sorgusu
   ```sql
   SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type
   FROM job_payments jp
   LEFT JOIN jobs j ON jp.job_id = j.id
   LEFT JOIN customers c ON j.customer_id = c.id
   WHERE jp.created_at >= datetime('now', '-24 hours') AND jp.company_id = 1
   ORDER BY jp.created_at DESC
   LIMIT 10
   ```

3. **getWeeklyIncomeTrend()** - Haftalık gelir trend sorgusu
   ```sql
   SELECT COALESCE(SUM(jp.amount), 0) as total 
   FROM job_payments jp
   WHERE DATE(jp.created_at) = ? AND jp.company_id = 1
   ```

**Zamanlama**:
- `error.log` ve `errors_2025-11-23.log` içinde 2025-11-23 14:27:52'de görülüyor
- Aynı anda 3 sorgu da başarısız oluyor
- Bu, dashboard'un ilk yüklenmesinde `buildDashboardData()` içinde bu 3 fonksiyonun çağrıldığını gösteriyor

**Kök Sebep**:
- `job_payments` tablosunda `company_id` kolonu YOK (schema'da görülmüyor)
- `DashboardController` içinde `scopeToCompany()` metodu kullanılıyor
- Bu metod `jp.company_id = 1` filtresi ekliyor ama tabloda bu kolon yok

---

### 1.2. `unserialize()` Hatası Analizi

**Hata Mesajı**: `unserialize(): Error at offset 0 of 106 bytes`

**Konum**: `Cache.php:472` (veya `Cache.php:501` - log'larda farklı satır numaraları görülüyor)

**Sıklık**:
- `errors_2025-11-23.log` içinde 49+ adet görülüyor
- Her saat başı veya daha sık tetikleniyor

**Etki**:
- Log'larda görülüyor ama 500 hatası üretip üretmediği net değil
- Cache okuma sırasında oluşuyor
- Muhtemelen cache dosyası corrupt veya format değişmiş

**500 Üretme Potansiyeli**:
- Cache hatası graceful handle ediliyorsa 500 üretmez
- Ama eğer exception yakalanmıyorsa 500 üretebilir

---

### 1.3. Login Akışları Analizi

**`login_500_trace.log` İncelemesi**:

- **admin** (user_id=4, SUPERADMIN):
  - 2025-11-23 15:12:25 - Login başarılı
  - 2025-11-23 15:12:25 - `DashboardController::today` AFTER_AUTH log'u var
  - İlk request'te exception log'u YOK

- **candas** (user_id=1, ADMIN):
  - 2025-11-23 15:13:20 - Login başarılı
  - 2025-11-23 15:13:20 - `DashboardController::today` AFTER_AUTH log'u var
  - İlk request'te exception log'u YOK

- **test_admin** (user_id=88, ADMIN):
  - 2025-11-23 15:43:29 - Login başarılı
  - 2025-11-23 15:43:29 - `DashboardController::today` AFTER_AUTH log'u var
  - İlk request'te exception log'u YOK

**`app_firstload_pathc.log` İncelemesi**:

- **candas** (2025-11-23 15:43:05):
  - `APP_HTML_START` → `APP_HTML_TRY_ENTER` → `APP_HTML_BEFORE_RENDER` → `VIEW_RENDER_AFTER_LAYOUT` → `HEADER_CONTEXT_START` → `HEADER_CONTEXT_AFTER_HEADERMANAGER` → `VIEW_RENDER_DONE` → `APP_HTML_AFTER_RENDER` → `APP_HTML_TRY_EXIT`
  - Tüm adımlar başarılı, exception log'u YOK

- **test_admin** (2025-11-23 15:43:29):
  - Aynı akış, tüm adımlar başarılı, exception log'u YOK

**Not**: Log'larda ilk request'te exception görünmüyor ama kullanıcı 500 görüyor. Bu, exception'ın loglanmadan önce yakalanıp 500'e dönüştüğünü veya log'un eksik olduğunu gösteriyor.

---

## 2. `/app` İLK REQUEST 500'ÜNÜN EN MUHTEMEL SEBEPLERİ

### 2.1. **jp.company_id Hatası (EN GÜÇLÜ ADAY)**

**Neden**:
- 3 kritik dashboard fonksiyonu (`getWeekIncome`, `getRecentActivities`, `getWeeklyIncomeTrend`) `jp.company_id` kullanıyor
- Bu sorgular `buildDashboardData()` içinde çağrılıyor
- Exception yakalanıyor ama muhtemelen yeterince graceful değil

**Kanıt**:
- `error.log` içinde 2025-11-23 14:27:52'de aynı anda 3 sorgu hatası var
- Bu, dashboard'un ilk yüklenmesinde bu fonksiyonların çağrıldığını gösteriyor

**Çözüm Yönü**:
- `scopeToCompany()` metodunu `job_payments` için `company_id` kontrolü yapacak şekilde düzeltmek
- Veya `job_payments` sorgularını `jp.company_id` olmadan çalıştırmak (JOIN ile `jobs.company_id` kullanmak)

---

### 2.2. **Cache Unserialize Hatası (ORTA SEVİYE)**

**Neden**:
- Cache okuma sırasında `unserialize()` hatası oluşuyor
- Eğer exception yakalanmıyorsa 500 üretebilir

**Kanıt**:
- Log'larda çok sayıda `unserialize()` hatası var
- Ama 500 üretip üretmediği net değil

**Çözüm Yönü**:
- Cache okuma sırasında exception yakalama kontrolü
- Corrupt cache dosyalarını temizleme veya skip etme

---

### 2.3. **Diğer Potansiyel Sebepler**

- Header context build sırasında exception (ama log'larda görünmüyor)
- View render sırasında exception (ama log'larda görünmüyor)
- Session/authentication exception (ama log'larda görünmüyor)

---

## 3. `jp.company_id` HATASININ HANGİ DASHBOARD FONKSİYONLARIYLA İLİŞKİLİ OLDUĞU

### 3.1. **getWeekIncome()**

**Konum**: `DashboardController::getWeekIncome()` (satır ~489-531)

**Kullanım**: Haftalık gelir istatistiği için

**SQL**: 
```sql
SELECT COALESCE(SUM(jp.amount), 0) as total 
FROM job_payments jp
WHERE DATE(jp.created_at) BETWEEN ? AND ? AND jp.company_id = 1
```

**Çağrıldığı Yer**: `buildDashboardData()` içinde (satır ~328)

---

### 3.2. **getRecentActivities()**

**Konum**: `DashboardController::getRecentActivities()` (satır ~782-1143)

**Kullanım**: Son aktiviteler listesi için (son 24 saatteki ödemeler dahil)

**SQL**: 
```sql
SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type
FROM job_payments jp
LEFT JOIN jobs j ON jp.job_id = j.id
LEFT JOIN customers c ON j.customer_id = c.id
WHERE jp.created_at >= datetime('now', '-24 hours') AND jp.company_id = 1
ORDER BY jp.created_at DESC
LIMIT 10
```

**Çağrıldığı Yer**: `buildDashboardData()` içinde (satır ~343)

---

### 3.3. **getWeeklyIncomeTrend()**

**Konum**: `DashboardController::getWeeklyIncomeTrend()` (satır ~716-777)

**Kullanım**: Son 7 günün günlük gelir trend'i için

**SQL**: 
```sql
SELECT COALESCE(SUM(jp.amount), 0) as total 
FROM job_payments jp
WHERE DATE(jp.created_at) = ? AND jp.company_id = 1
```

**Çağrıldığı Yer**: `buildDashboardData()` içinde (satır ~361)

---

## 4. CACHE UNSERIALIZE HATALARININ 500 ÜRETTİĞİNE DAİR İZ

**Kanıt**:
- Log'larda çok sayıda `unserialize()` hatası var
- Ama `app_firstload_pathc.log` içinde cache ile ilgili exception log'u YOK
- Bu, cache hatasının graceful handle edildiğini gösteriyor

**Sonuç**:
- Cache unserialize hatası muhtemelen 500 üretmiyor
- Sadece log'da görülüyor
- Ama yine de kontrol edilmeli

---

## 5. SONUÇ VE SONRAKİ ADIMLAR

### 5.1. En Güçlü Root-Cause Hipotezi

**jp.company_id hatası** `/app` ilk request 500'ünün en muhtemel sebebi.

**Neden**:
1. 3 kritik dashboard fonksiyonu etkileniyor
2. Bu fonksiyonlar `buildDashboardData()` içinde çağrılıyor
3. Exception yakalanıyor ama muhtemelen yeterince graceful değil
4. Log'larda aynı anda 3 sorgu hatası görülüyor

### 5.2. Sonraki STAGE'ler İçin Öneriler

1. **STAGE 1**: `/app` call graph'ini çıkar, `scopeToCompany()` metodunu incele
2. **STAGE 2**: Dashboard fonksiyonlarına detaylı log ekle
3. **STAGE 3**: `jp.company_id` hatasını düzelt (en öncelikli)
4. **STAGE 4**: Error handling'i güçlendir

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE 1 - PATH C HARİTALAMA

