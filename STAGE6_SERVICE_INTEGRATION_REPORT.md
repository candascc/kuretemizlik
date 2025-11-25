# AŞAMA 6 RAPORU - HİZMETLER SAYFASI ENTEGRASYONU

## 1. ANALİZ

### Mevcut Hizmet Yönetimi

**Controller**: `src/Controllers/ServiceController.php`
- `index()`: Hizmet listesi
- `create()`: Yeni hizmet formu
- `store()`: Hizmet kaydet
- `edit($id)`: Hizmet düzenleme formu
- `update($id)`: Hizmet güncelle
- `delete($id)`: Hizmet sil
- `toggleActive($id)`: Hizmet aktif/pasif yap

**View Dosyaları**:
- `src/Views/services/list.php`: Hizmet listesi
- `src/Views/services/form.php`: Hizmet formu (create/edit)

**Route'lar**:
- `GET /services`: Hizmet listesi
- `GET /services/new`: Yeni hizmet formu
- `POST /services/create`: Hizmet kaydet
- `GET /services/edit/{id}`: Hizmet düzenleme formu
- `POST /services/update/{id}`: Hizmet güncelle
- `POST /services/delete/{id}`: Hizmet sil
- `POST /services/toggle/{id}`: Hizmet aktif/pasif yap

**Hizmet Detay Ekranı**: `GET /services/edit/{id}` → `ServiceController::edit()` → `services/form.php`

## 2. EKLENEN ÖZELLİKLER

### 2.1. Controller Metodları

**Dosya**: `src/Controllers/ServiceController.php`

#### Yeni Metodlar

1. **`editContractTemplate($serviceId)`**
   - Hizmet sözleşme şablonu düzenleme formunu gösterir
   - Service kaydını bulur
   - Service name'den `service_key` türetir
   - İlgili template'i bulur (varsa)
   - Kullanılabilir placeholder'ları hazırlar
   - `services/contract_template_form.php` view'ını render eder

2. **`updateContractTemplate($serviceId)`**
   - Hizmet sözleşme şablonu güncelleme/kaydetme işlemini yapar
   - CSRF kontrolü yapar
   - Form validation yapar (name, template_text zorunlu)
   - Service_key'i türetir
   - Mevcut template varsa update, yoksa create yapar
   - ActivityLogger ile log kaydı oluşturur
   - Flash mesaj gösterir ve hizmet edit sayfasına redirect eder

#### Güncellenen Constructor

```php
public function __construct()
{
    $this->serviceModel = new Service();
    $this->templateModel = new ContractTemplate(); // YENİ
    $this->templateService = new ContractTemplateService(); // YENİ
}
```

### 2.2. Route'lar

**Dosya**: `index.php`

**Eklenen Route'lar**:
```php
// Service Contract Template
$router->get('/services/{id}/contract-template/edit', [ServiceController::class, 'editContractTemplate'], ['middlewares' => [$requireAuth]]);
$router->post('/services/{id}/contract-template/update', [ServiceController::class, 'updateContractTemplate'], ['middlewares' => [$requireAuth]]);
```

### 2.3. View Güncellemeleri

#### services/form.php

**Eklenen Bölüm**: "Hizmet Sözleşme Şablonu" bloğu

**Konum**: Form'un sonunda, "Form Actions" öncesinde

**Özellikler**:
- Sadece edit modunda gösterilir (`$isEdit` kontrolü)
- Service için sözleşme şablonu bilgisi dinamik olarak çekilir
- İki durum:
  1. **Özel şablon varsa**:
     - Şablon adı, versiyon, durum (aktif/pasif) gösterilir
     - Template text'ten kısa önizleme (ilk 200 karakter)
     - "Şablonu Düzenle" butonu
  2. **Özel şablon yoksa**:
     - Bilgilendirme mesajı
     - "Yeni Şablon Oluştur" butonu

**Görsel Tasarım**:
- Purple/indigo gradient header
- Yeşil bilgi kutusu (şablon varsa)
- Sarı bilgi kutusu (şablon yoksa)
- Responsive ve dark mode desteği

#### services/contract_template_form.php (YENİ)

**Amaç**: Sözleşme şablonu düzenleme/oluşturma formu

**Özellikler**:
- Breadcrumb navigasyon
- Bilgilendirme kutusu (placeholder'lar listesi)
- İki bölüm:
  1. **Şablon Bilgileri**: Name, is_active
  2. **Sözleşme Metni**: Büyük textarea (20 satır, monospace font)
- Form validation (client-side)
- CSRF koruması
- ActivityLogger entegrasyonu

**Placeholder'lar**:
- `{customer_name}`, `{customer_phone}`, `{customer_email}`
- `{job_id}`, `{job_date}`, `{job_time}`, `{job_datetime}`
- `{job_address}`, `{job_price}`, `{job_amount}`, `{job_total_amount}`
- `{service_type}`, `{service_name}`, `{job_description}`, `{job_status}`

## 3. GÜVENLİK VE YETKİ

### Yetki Kontrolü

- Tüm yeni endpoint'ler `Auth::requireAdmin()` ile korunuyor
- Sadece admin kullanıcılar erişebilir
- Public tarafta erişim kapalı

### CSRF Koruması

- `CSRF::verifyRequest()` ile tüm POST istekleri kontrol ediliyor
- Form'larda `CSRF::field()` kullanılıyor

### Input Validation

- `Validator` sınıfı ile form validation
- `name`: Zorunlu, min 3, max 200 karakter
- `template_text`: Zorunlu, min 10 karakter
- `is_active`: Checkbox (0 veya 1)

## 4. AKIŞ ÖRNEĞİ

### Senaryo: "Ev Temizliği" Hizmeti için Sözleşme Şablonu

1. **Hizmet Detay Sayfasına Git**:
   - `/services/edit/1` (Ev Temizliği ID: 1)
   - `ServiceController::edit(1)` çağrılır
   - `services/form.php` render edilir

2. **Sözleşme Şablonu Bloğunu Gör**:
   - Form'un altında "Hizmet Sözleşme Şablonu" bölümü görünür
   - Eğer özel şablon varsa: Şablon bilgileri ve "Şablonu Düzenle" butonu
   - Eğer özel şablon yoksa: Bilgilendirme mesajı ve "Yeni Şablon Oluştur" butonu

3. **Şablonu Düzenle/Oluştur**:
   - "Şablonu Düzenle" veya "Yeni Şablon Oluştur" butonuna tıkla
   - `/services/1/contract-template/edit` açılır
   - `ServiceController::editContractTemplate(1)` çağrılır
   - `services/contract_template_form.php` render edilir

4. **Formu Doldur ve Kaydet**:
   - Şablon adı: "Ev Temizliği Hizmet Sözleşmesi"
   - Sözleşme metni: Placeholder'lar ile doldurulmuş metin
   - Durum: Aktif
   - "Sözleşme Şablonunu Kaydet" butonuna tıkla

5. **Kaydetme İşlemi**:
   - `POST /services/1/contract-template/update`
   - `ServiceController::updateContractTemplate(1)` çağrılır
   - Validation yapılır
   - Service_key türetilir: `normalizeServiceName("Ev Temizliği")` → `"house_cleaning"`
   - Template kaydedilir (create veya update)
   - ActivityLogger ile log kaydı oluşturulur
   - Flash mesaj: "Sözleşme şablonu başarıyla güncellendi."
   - Redirect: `/services/edit/1`

6. **Yeni İş Oluşturulduğunda**:
   - Job oluşturulurken `service_id = 1` (Ev Temizliği)
   - `ContractTemplateService::getTemplateForJob($job)` çağrılır
   - Service-specific template seçilir (`service_key = "house_cleaning"`)
   - Template text render edilir (placeholder'lar doldurulur)
   - JobContract oluşturulur
   - SMS ile müşteriye gönderilir

## 5. EKLENEN/GÜNCELLENEN DOSYALAR

### Controller
- ✅ `src/Controllers/ServiceController.php` (2 yeni metod eklendi)

### Views
- ✅ `src/Views/services/form.php` (Sözleşme şablonu bloğu eklendi)
- ✅ `src/Views/services/contract_template_form.php` (YENİ - Şablon düzenleme formu)

### Routes
- ✅ `index.php` (2 yeni route eklendi)

## 6. ÖZET

### Tamamlanan İşler ✅

1. ✅ Hizmet yönetimi analizi yapıldı
2. ✅ ServiceController'a 2 yeni metod eklendi
3. ✅ 2 yeni route eklendi
4. ✅ Hizmet form sayfasına sözleşme şablonu bloğu eklendi
5. ✅ Sözleşme şablonu düzenleme formu oluşturuldu
6. ✅ Güvenlik ve yetki kontrolleri eklendi
7. ✅ CSRF koruması eklendi
8. ✅ Form validation eklendi
9. ✅ ActivityLogger entegrasyonu eklendi

### Özellikler

- ✅ Service-specific template görüntüleme
- ✅ Template oluşturma/düzenleme
- ✅ Placeholder bilgilendirmesi
- ✅ Responsive tasarım
- ✅ Dark mode desteği
- ✅ Flash mesajlar
- ✅ Breadcrumb navigasyon

### Sonraki Adımlar

- İleride template preview özelliği eklenebilir
- Template versiyonlama sistemi eklenebilir
- Template geçmişi görüntüleme eklenebilir
- Template import/export özelliği eklenebilir

---

**Rapor Tarihi**: 2025-01-XX
**Hazırlayan**: AI Assistant
**Durum**: AŞAMA 6 Tamamlandı ✅

