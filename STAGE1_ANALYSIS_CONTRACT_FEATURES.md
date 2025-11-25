# AŞAMA 1 – KEŞİF VE ANALİZ ÖZETİ

## Tarih
2025-01-XX

---

## 1. MEVCUT SÖZLEŞME LİSTELEME YAPISI

### ContractController::index() Mevcut Durumu

✅ **Mevcut Controller**: `src/Controllers/ContractController.php`
- Route: `/contracts` (zaten tanımlı)
- Yetki: `Auth::require()` + `$requireOperatorReadOnly` middleware

**Mevcut İşlevsellik**:
- Eski `contracts` tablosundan veriler çekiliyor
- `job_contracts` tablosundan veriler çekiliyor ve normalize ediliyor (satır 105-131)
- İki veri birleştiriliyor ve birlikte gösteriliyor
- View: `src/Views/contracts/list.php` mevcut
- Filtreler: status, contract_type, customer, date_from, date_to, expiring_soon

**Mevcut Sütunlar (list.php)**:
- Contract Number / Title
- Customer
- Type
- Start/End Date
- Status
- Total Amount
- Actions (View, Edit, Delete)

**Önemli Not**: Mevcut liste ekranı **zaten job_contracts'ı gösteriyor** ama eski contract formatına normalize edilmiş. Bizim ihtiyacımız olan **job-specific filtreleme ve görünüm** için mevcut yapıyı genişletebiliriz veya yeni bir ekran ekleyebiliriz.

**Karar**: Mevcut `ContractController::index()` kullanılabilir ama `job_contracts` için özel filtreler ve görünümler eklenmeli.

---

## 2. jobs/manage.php İÇİNDEKİ MEVCUT SÖZLEŞME BÖLÜMÜ

### Mevcut Yapı

✅ **Dosya**: `src/Views/jobs/manage.php` (satır 164-273)

**Mevcut İçerik**:
- "Temizlik İşi Sözleşmesi" başlığı (`contracts.panel.section_title`)
- Durum badge'i
- SMS gönderim butonu
- Public link (kopyalanabilir)
- SMS gönderim bilgisi (son gönderim zamanı, toplam sayı)

**Timeline İçin Kullanılabilir Alanlar**:
- `$job['created_at']` → İş oluşturuldu
- `$contract['created_at']` → Sözleşme oluşturuldu
- `$contract['sms_sent_at']` → SMS gönderildi
- `$contract['approved_at']` → Sözleşme onaylandı
- `$contract['updated_at']` → Sözleşme güncellendi

**Timeline Yeri**: Sözleşme bölümünün altına veya yanına eklenebilir.

---

## 3. PUBLIC SÖZLEŞME GÖRÜNÜMÜ YAPISI

### Mevcut Yapı

✅ **Controller**: `src/Controllers/PublicContractController.php`
- Route: `GET /contract/{id}` (public, OTP akışı için)

✅ **View**: `src/Views/contracts/public_show.php`
- Layout: `max-w-4xl mx-auto` container
- CSS: Tailwind CSS kullanılıyor
- Print CSS: `assets/css/print.css` mevcut (header.php'de referans var)

**Yazdırılabilir Görünüm İçin**:
- Print CSS dosyası zaten var (`print.css`)
- Layout yapısı mevcut (`View::renderWithLayout()`)
- Public sayfa minimal, temiz görünüm (admin için de benzer yapı kullanılabilir)

---

## 4. LAYOUT YAPISI

### View Sistemi

✅ **View Helper**: `src/Lib/View.php`
- `View::renderWithLayout($view, $data = [], $layout = 'base')`
- Layout: `src/Views/layout/base.php` (muhtemelen)

✅ **Print Desteği**:
- `assets/css/print.css` dosyası header.php'de referans ediliyor
- `media="print"` attribute ile yüklü

**Print View İçin**:
- Yeni bir layout oluşturulabilir: `print` layout
- Veya mevcut `base` layout kullanılır, print CSS ile stil verilir

---

## 5. TIMELINE İÇİN MEVCUT VERİ ALANLARI

### JobContract Model Alanları

✅ **Kullanılabilir Tarih Alanları**:
- `created_at` → Sözleşme oluşturuldu
- `updated_at` → Sözleşme güncellendi
- `sms_sent_at` → SMS gönderildi (JobContract::incrementSmsCount() tarafından set ediliyor)
- `approved_at` → Sözleşme onaylandı
- `expires_at` → Sözleşme süresi doldu

✅ **Job Model Alanları**:
- `created_at` → İş oluşturuldu
- `start_at` → İş tarihi
- `updated_at` → İş güncellendi

**Timeline Event'leri** (Mevcut alanlarla):
1. İş oluşturuldu → `$job['created_at']`
2. Sözleşme oluşturuldu → `$contract['created_at']` (varsa)
3. SMS gönderildi → `$contract['sms_sent_at']` (varsa)
4. Sözleşme onaylandı → `$contract['approved_at']` (varsa)

**Ek Tablo Gerekli mi?**: ❌ **HAYIR** - Mevcut alanlar yeterli.

---

## 6. LANG DOSYASI MEVCUT YAPISI

### contracts.* Key'leri

✅ **Dosya**: `lang/tr.php`

**Mevcut Keys**:
- `contracts.panel.*` → Panel tarafı metinleri
- `contracts.public.*` → Public tarafı metinleri

**Eklenecek Yeni Keys**:
- `contracts.admin.index.*` → Liste ekranı metinleri
- `contracts.admin.print.*` → Print view metinleri
- `contracts.panel.timeline.*` → Timeline metinleri

---

## 7. ROUTE YAPISI

### Mevcut Routes

✅ **Dosya**: `app/index.php`

**Mevcut Contract Routes**:
- `GET /contracts` → `ContractController::index()` (zaten var)
- `GET /contract/{id}` → `PublicContractController::show()` (public)

**Eklenecek Yeni Routes**:
- `GET /contracts/{id}/print` → Print view (yeni)
- Mevcut `/contracts` route'unu genişletebiliriz veya ayrı bir endpoint kullanabiliriz

---

## 8. ÖNEMLİ KARARLAR

### 8.1. Liste Ekranı

**Karar**: Mevcut `ContractController::index()` kullanılacak, ama job_contracts için özel filtreler ve görünüm iyileştirmeleri yapılacak.

**Gerekçe**:
- Zaten job_contracts verilerini gösteriyor
- Filtreleme yapısı mevcut
- Sadece job_contracts için özel sütunlar ve filtreler eklenebilir

### 8.2. Timeline

**Karar**: Timeline için **ek tablo gerekmez**, mevcut `JobContract` ve `Job` alanları yeterli.

**Gerekçe**:
- `job.created_at`, `job_contracts.created_at`, `job_contracts.sms_sent_at`, `job_contracts.approved_at` mevcut
- Ek tablo gereksiz karmaşıklık yaratır

### 8.3. Print View

**Karar**: Yeni bir controller method ve view oluşturulacak (`ContractController::showPrintable()` ve `contracts/print.php`).

**Gerekçe**:
- Public view ile karışmaması için ayrı endpoint
- Admin için özel layout ve stil

---

## 9. TASARIM NOTLARI

### 9.1. Liste Ekranı İyileştirmeleri

- `job_contracts` için özel sütunlar:
  - İş ID (job_id)
  - Hizmet adı (service_name)
  - İş tarihi (job_start_at)
  - Durum (job_contract_status)
  - Son SMS zamanı
  - Onay zamanı

- Filtreler:
  - Sözleşme durumu (job_contract_status)
  - Hizmet tipi (service_key veya service_name)
  - Müşteri telefonu veya adı
  - İş tarihi aralığı

### 9.2. Timeline Tasarımı

- Basit, dikey timeline
- Her event için: Tarih - Saat - Başlık - Açıklama
- Icon'lar: farklı event tipleri için farklı icon'lar
- Sıralama: Tarihe göre ASC (en eski en üstte)

### 9.3. Print View Tasarımı

- Minimal layout
- Şirket logosu/başlığı (üstte)
- Sözleşme başlığı
- Contract text (HTML formatında)
- Alt bilgi: Referans numarası, tarih, onay bilgisi
- Print butonu (`window.print()`)

---

## 10. EK TABLO GEREKSİNİMİ

❌ **YENİ TABLO GEREKMEZ**

Tüm ihtiyaçlar mevcut tablolarla karşılanabilir:
- `jobs` → İş bilgileri, `created_at`
- `job_contracts` → Sözleşme bilgileri, `created_at`, `sms_sent_at`, `approved_at`
- `contract_otp_tokens` → OTP logları (timeline için detay istenirse)

---

## 11. GÜVENLİK VE YETKİLENDİRME

### Mevcut Yapı

✅ **Auth Kontrolü**: `Auth::require()`, `Auth::requireAdmin()`
✅ **Company Scope**: `CompanyScope` trait kullanılıyor
✅ **CSRF**: `CSRF::verifyRequest()` mevcut

**Yeni Özellikler İçin**:
- Liste ekranı: `Auth::require()` yeterli (zaten var)
- Print view: `Auth::require()` + company scope kontrolü
- Timeline: Zaten `JobController::manage()` içinde, auth kontrolü var

---

## 12. SONUÇ VE ÖNERİLER

### Önerilen Yaklaşım

1. **Liste Ekranı**: Mevcut `ContractController::index()` genişletilecek, job_contracts için özel filtreler ve sütunlar eklenecek
2. **Timeline**: `JobController::manage()` içinde timeline array'i hazırlanacak, view'a geçirilecek
3. **Print View**: Yeni `ContractController::showPrintable()` metodu ve `contracts/print.php` view'ı oluşturulacak

### Riskler

✅ **Düşük Risk**: Mevcut yapılar üzerine inşa ediliyor, bozucu değişiklik yok

### Gereken Dosyalar

**Yeni**:
- `src/Views/contracts/print.php` (print view)
- `src/Views/jobs/_timeline.php` (opsiyonel, partial)

**Değiştirilecek**:
- `src/Controllers/ContractController.php` (showPrintable metodu eklenecek)
- `src/Controllers/JobController.php` (timeline array eklenecek)
- `src/Views/jobs/manage.php` (timeline bloğu eklenecek)
- `src/Views/contracts/list.php` (job_contracts için özel sütunlar/filtreler)
- `lang/tr.php` (yeni metinler)

**Route Eklenecek**:
- `GET /contracts/{id}/print` → `ContractController::showPrintable()`

---

**Rapor Hazırlayan**: AI Assistant
**Durum**: ✅ Analiz Tamamlandı

