# AŞAMA 2 – TASARIM: EKRANLAR VE VERİ MODELİ

## Tarih
2025-01-XX

---

## 2.1. SÖZLEŞME LİSTE/RAPOR EKRANI TASARIMI

### Controller ve Route

**Önerilen Yapı**: Mevcut `ContractController::index()` genişletilecek.

**Neden?**
- Zaten `/contracts` route'u mevcut
- Zaten `job_contracts` verilerini merge ediyor
- Filtreleme yapısı hazır
- Sadece job_contracts için özel görünüm ve filtreler eklenecek

**Değişiklikler**:
- `ContractController::index()` metodunda job_contracts için özel sorgu ve filtreleme
- View'da job_contracts için özel sütunlar gösterimi
- Filtre formuna hizmet tipi ve job_contract durumu filtreleri

**Route**: 
- Mevcut: `GET /contracts` → `ContractController::index()`
- Değişiklik yok, sadece içerik genişletilecek

### Sütunlar ve Görünüm

**Mevcut Liste Sütunları** (`contracts/list.php`):
- Contract Number
- Title
- Customer
- Type
- Start Date
- End Date
- Status
- Total Amount
- Actions

**Job_Contracts İçin Özel Sütunlar** (eğer `is_job_contract === true`):
- **İş ID**: `job_id` (link: `/jobs/manage/{job_id}`)
- **Sözleşme ID**: `id` (link: `/contracts/{id}/print`)
- **Müşteri Adı**: `customer_name` (JOIN ile)
- **Hizmet Adı**: `service_name` (JOIN ile, jobs tablosundan)
- **İş Tarihi**: `job_start_at` (format: d.m.Y)
- **Durum**: `job_contract_status` (PENDING, SENT, APPROVED, EXPIRED, REJECTED)
- **Son SMS Gönderim**: `sms_sent_at` (format: d.m.Y H:i veya "Henüz gönderilmedi")
- **Onay Zamanı**: `approved_at` (format: d.m.Y H:i veya "-")
- **İşlemler**:
  - "İş Detayı" butonu → `/jobs/manage/{job_id}`
  - "Sözleşmeyi Görüntüle/Yazdır" butonu → `/contracts/{id}/print`

### Filtreler

**Mevcut Filtreler** (zaten var):
- `status` (contract status)
- `contract_type` (contract type)
- `customer` (customer name)
- `date_from`, `date_to` (date range)
- `expiring_soon` (expiring contracts)

**Job_Contracts İçin Ek Filtreler**:
- `job_contract_status` → PENDING, SENT, APPROVED, EXPIRED, REJECTED
- `service_key` veya `service_name` → Hizmet tipi
- `job_date_from`, `job_date_to` → İş tarihi aralığı (job_start_at bazlı)
- `contract_type_filter` → "job_contracts" checkbox (sadece job_contracts'ı göster)

**Filtre Formu Güncellemesi**:
```php
// contracts/list.php içinde
// Mevcut filtre formuna eklenecek:
- Job Contract Status dropdown (PENDING, SENT, APPROVED, EXPIRED, REJECTED)
- Service filter dropdown (services tablosundan)
- "Sadece İş Sözleşmeleri" checkbox
- İş tarihi aralığı (job_date_from, job_date_to)
```

### Veri Sorgusu

**Mevcut Sorgu Yapısı** (`ContractController::index()` satır 86-103):
- `contracts` tablosundan SELECT
- LEFT JOIN customers, users
- Filtreler WHERE clause'unda

**Job_Contracts İçin Sorgu** (`JobContract::all()` zaten mevcut):
- `job_contracts` tablosundan SELECT
- INNER JOIN jobs, LEFT JOIN customers
- Company scope otomatik

**İyileştirme Önerisi**:
- Service bilgisi de JOIN edilmeli: `LEFT JOIN services s ON j.service_id = s.id`
- `JobContract::all()` metoduna `service_name` eklenebilir veya controller'da JOIN yapılabilir

### Pagination

**Mevcut**: `Utils::paginate($total, $limit, $page)`
- Değişiklik yok, mevcut yapı kullanılacak
- `job_contracts` ve `contracts` merge edildikten sonra slice yapılıyor (zaten var)

---

## 2.2. İŞ TİMELİNE TASARIMI (jobs/manage.php)

### Timeline Event'leri

**Event Tipleri** (Mevcut alanlardan):

1. **job_created**
   - `datetime`: `$job['created_at']`
   - `label`: "İş Oluşturuldu"
   - `description`: "İş kaydı sisteme eklendi"
   - `icon`: `fa-calendar-plus`

2. **contract_created**
   - `datetime`: `$contract['created_at']` (varsa)
   - `label`: "Sözleşme Oluşturuldu"
   - `description`: "İş için sözleşme kaydı oluşturuldu"
   - `icon`: `fa-file-contract`

3. **sms_sent**
   - `datetime`: `$contract['sms_sent_at']` (varsa)
   - `label`: "SMS Gönderildi"
   - `description`: "Müşteriye sözleşme onay SMS'i gönderildi" (sms_sent_count varsa: "Toplam X SMS gönderildi")
   - `icon`: `fa-sms`

4. **approved**
   - `datetime`: `$contract['approved_at']` (varsa)
   - `label`: "Sözleşme Onaylandı"
   - `description`: "Müşteri tarafından OTP ile onaylandı"
   - `icon`: `fa-check-circle`

### Timeline Görünümü

**Yerleşim**: `jobs/manage.php` içinde, "Temizlik İşi Sözleşmesi" bölümünün altında veya yanında

**HTML Yapısı**:
```html
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="fas fa-clock mr-2 text-blue-600"></i>
            İş Zaman Çizelgesi
        </h3>
    </div>
    <div class="px-6 py-4">
        <div class="space-y-4">
            <!-- Her event için bir item -->
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/20">
                        <i class="fas fa-{icon} text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {label}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {datetime} - {description}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Responsive**: Mobilde de düzgün görünsün (flex-col yerine flex-row)

### Controller Veri Hazırlama

**JobController::manage() içinde**:
```php
// Timeline events array'i hazırla
$timelineEvents = [];

// 1. İş oluşturuldu (her zaman var)
if (!empty($job['created_at'])) {
    $timelineEvents[] = [
        'type' => 'job_created',
        'datetime' => $job['created_at'],
        'label' => __('contracts.panel.timeline.job_created'),
        'description' => __('contracts.panel.timeline.job_created_desc'),
        'icon' => 'calendar-plus',
    ];
}

// 2. Sözleşme oluşturuldu
if ($contract && !empty($contract['created_at'])) {
    $timelineEvents[] = [
        'type' => 'contract_created',
        'datetime' => $contract['created_at'],
        'label' => __('contracts.panel.timeline.contract_created'),
        'description' => __('contracts.panel.timeline.contract_created_desc'),
        'icon' => 'file-contract',
    ];
}

// 3. SMS gönderildi
if ($contract && !empty($contract['sms_sent_at'])) {
    $smsCount = $contract['sms_sent_count'] ?? 0;
    $description = $smsCount > 1 
        ? __('contracts.panel.timeline.sms_sent_desc_multi', ['count' => $smsCount])
        : __('contracts.panel.timeline.sms_sent_desc');
    
    $timelineEvents[] = [
        'type' => 'sms_sent',
        'datetime' => $contract['sms_sent_at'],
        'label' => __('contracts.panel.timeline.sms_sent'),
        'description' => $description,
        'icon' => 'sms',
    ];
}

// 4. Onaylandı
if ($contract && !empty($contract['approved_at'])) {
    $timelineEvents[] = [
        'type' => 'approved',
        'datetime' => $contract['approved_at'],
        'label' => __('contracts.panel.timeline.approved'),
        'description' => __('contracts.panel.timeline.approved_desc'),
        'icon' => 'check-circle',
    ];
}

// Tarihe göre sırala (ASC - en eski en üstte)
usort($timelineEvents, function($a, $b) {
    return strtotime($a['datetime']) - strtotime($b['datetime']);
});

// View'a geçir
echo View::renderWithLayout('jobs/manage', [
    // ... mevcut data
    'timelineEvents' => $timelineEvents,
]);
```

### View'da Timeline Bloku

**Dosya**: `src/Views/jobs/manage.php`
**Yerleşim**: "Temizlik İşi Sözleşmesi" bölümünün hemen altında (satır 273'ten sonra)

**Kod**:
```php
<?php if (!empty($timelineEvents)): ?>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-clock mr-2 text-blue-600"></i>
                <?= __('contracts.panel.timeline.title') ?>
            </h3>
        </div>
        <div class="px-6 py-4">
            <div class="space-y-4">
                <?php foreach ($timelineEvents as $event): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/20">
                                <i class="fas fa-<?= htmlspecialchars($event['icon']) ?> text-blue-600 dark:text-blue-400"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($event['label']) ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?= Utils::formatDateTime($event['datetime'], 'd.m.Y H:i') ?> - <?= htmlspecialchars($event['description']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
```

---

## 2.3. YAZDIRILABİLİR SÖZLEŞME GÖRÜNÜMÜ TASARIMI

### Controller ve Route

**Controller Method**: `ContractController::showPrintable($id)`

**Route**: `GET /contracts/{id}/print` → `ContractController::showPrintable()`

**Güvenlik**:
- `Auth::require()` → Sadece authenticated kullanıcılar
- Company scope kontrolü (JobContract model zaten yapıyor)
- `JobContract::find($id)` ile contract bulunur, company scope otomatik uygulanır

**Metod İmzası**:
```php
public function showPrintable($id)
{
    Auth::require();
    
    // Find contract
    $contract = $this->jobContractModel->find($id);
    if (!$contract) {
        View::notFound('Sözleşme bulunamadı.');
    }
    
    // Get related job and customer
    $job = (new Job())->find($contract['job_id']);
    $customer = (new Customer())->find($job['customer_id'] ?? null);
    $service = (new Service())->find($job['service_id'] ?? null);
    
    echo View::renderWithLayout('contracts/print', [
        'contract' => $contract,
        'job' => $job,
        'customer' => $customer,
        'service' => $service,
        'title' => __('contracts.admin.print.title'),
    ], 'print'); // 'print' layout kullanılabilir veya 'base'
}
```

### View Dosyası

**Dosya**: `src/Views/contracts/print.php`

**Layout Seçeneği 1**: Mevcut `base` layout kullanılır, print CSS ile stil verilir
**Layout Seçeneği 2**: Özel `print` layout oluşturulur (daha minimal)

**Öneri**: Özel `print` layout oluşturulması (sadece header/footer, minimal CSS)

**Layout Dosyası**: `src/Views/layout/print.php` (yeni)

### Print View İçeriği

**Yapı**:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= __('contracts.admin.print.title') ?></title>
    <style>
        /* Print-friendly CSS */
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header: Şirket Bilgileri -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold">Küre Temizlik</h1>
        <p class="text-sm text-gray-600">Temizlik Hizmetleri</p>
    </div>
    
    <!-- Sözleşme Başlığı -->
    <h2 class="text-xl font-semibold mb-4">
        <?= __('contracts.admin.print.contract_title', ['service' => $service['name'] ?? 'Temizlik']) ?>
    </h2>
    
    <!-- Contract Text -->
    <div class="prose max-w-none">
        <?= nl2br(htmlspecialchars($contract['contract_text'] ?? '')) ?>
    </div>
    
    <!-- Footer: Referans Bilgileri -->
    <div class="mt-8 pt-4 border-t">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <strong><?= __('contracts.admin.print.job_ref') ?>:</strong> <?= $job['id'] ?? '' ?>
            </div>
            <div>
                <strong><?= __('contracts.admin.print.contract_ref') ?>:</strong> <?= $contract['id'] ?? '' ?>
            </div>
            <div>
                <strong><?= __('contracts.admin.print.job_date') ?>:</strong> 
                <?= Utils::formatDateTime($job['start_at'] ?? '', 'd.m.Y') ?>
            </div>
            <div>
                <strong><?= __('contracts.admin.print.approved_at') ?>:</strong>
                <?= $contract['approved_at'] 
                    ? Utils::formatDateTime($contract['approved_at'], 'd.m.Y H:i')
                    : __('contracts.admin.print.not_approved') ?>
            </div>
        </div>
    </div>
    
    <!-- Print Button (no-print class ile) -->
    <div class="no-print mt-6 text-center">
        <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white rounded-lg">
            <?= __('contracts.admin.print.print_button') ?>
        </button>
        <a href="<?= base_url('/contracts') ?>" class="ml-4 px-6 py-3 bg-gray-600 text-white rounded-lg">
            <?= __('contracts.admin.print.back_button') ?>
        </a>
    </div>
</body>
</html>
```

### Print Layout (opsiyonel)

**Dosya**: `src/Views/layout/print.php`

**İçerik**: Minimal header/footer, print CSS

---

## 2.4. ROUTE EKLEMESİ

### Yeni Route

**Dosya**: `app/index.php`

**Eklenecek Satır** (Contract routes bölümüne):
```php
// Print-friendly contract view
$router->get('/contracts/{id}/print', [ContractController::class, 'showPrintable'], ['middlewares' => [$requireAuth]]);
```

---

## 2.5. LANG DOSYASI EKLEMELERİ

### Yeni Keys

**Dosya**: `lang/tr.php`

**Eklenecek** (`contracts` array'ine):

```php
'admin' => [
    'index' => [
        'title' => 'Sözleşmeler',
        'subtitle' => 'Sözleşme yönetimi ve takibi',
        'job_id' => 'İş ID',
        'contract_id' => 'Sözleşme ID',
        'service_name' => 'Hizmet',
        'job_date' => 'İş Tarihi',
        'last_sms' => 'Son SMS',
        'approved_at' => 'Onay Zamanı',
        'view_job' => 'İş Detayı',
        'print_contract' => 'Sözleşmeyi Yazdır',
        'filter_job_contracts' => 'Sadece İş Sözleşmeleri',
        // ... diğer filtre metinleri
    ],
    'print' => [
        'title' => 'Sözleşme Yazdırma',
        'contract_title' => ':service Hizmet Sözleşmesi',
        'print_button' => 'Yazdır',
        'back_button' => 'Geri Dön',
        'job_ref' => 'İş Referansı',
        'contract_ref' => 'Sözleşme Referansı',
        'job_date' => 'İş Tarihi',
        'approved_at' => 'Onay Tarihi',
        'not_approved' => 'Henüz onaylanmadı',
    ],
],
'panel' => [
    // ... mevcut keys
    'timeline' => [
        'title' => 'İş Zaman Çizelgesi',
        'job_created' => 'İş Oluşturuldu',
        'job_created_desc' => 'İş kaydı sisteme eklendi',
        'contract_created' => 'Sözleşme Oluşturuldu',
        'contract_created_desc' => 'İş için sözleşme kaydı oluşturuldu',
        'sms_sent' => 'SMS Gönderildi',
        'sms_sent_desc' => 'Müşteriye sözleşme onay SMS\'i gönderildi',
        'sms_sent_desc_multi' => 'Müşteriye toplam :count adet SMS gönderildi',
        'approved' => 'Sözleşme Onaylandı',
        'approved_desc' => 'Müşteri tarafından OTP ile onaylandı',
    ],
],
```

---

## 2.6. PANELDEN ERİŞİM

### jobs/manage.php İçinde Print Butonu

**Yerleşim**: Mevcut "Sözleşmeyi Görüntüle" butonunun yanında

**Kod** (satır 264'ten sonra):
```php
<?php if ($contractStatus['has_contract'] && !empty($contract['id'])): ?>
    <div class="mt-3 flex gap-2">
        <a href="<?= htmlspecialchars($publicLink) ?>" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <i class="fas fa-external-link-alt mr-2"></i>
            <?= __('contracts.panel.view_contract') ?>
        </a>
        <a href="<?= base_url("/contracts/{$contract['id']}/print") ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <i class="fas fa-print mr-2"></i>
            <?= __('contracts.panel.print_contract') ?>
        </a>
    </div>
<?php endif; ?>
```

**Lang Key**: `contracts.panel.print_contract` → "Sözleşmeyi Yazdır"

---

## 2.7. ÖZET

### Oluşturulacak Dosyalar

1. **Yeni**:
   - `src/Views/contracts/print.php` (print view)
   - `src/Views/layout/print.php` (opsiyonel, print layout)

2. **Değiştirilecek**:
   - `src/Controllers/ContractController.php` (`showPrintable()` metodu)
   - `src/Controllers/JobController.php` (timeline array hazırlama)
   - `src/Views/jobs/manage.php` (timeline bloğu + print butonu)
   - `src/Views/contracts/list.php` (job_contracts için özel sütunlar/filtreler)
   - `app/index.php` (print route)
   - `lang/tr.php` (yeni metinler)

### Tasarım Kararları

✅ **Liste Ekranı**: Mevcut `ContractController::index()` genişletilecek
✅ **Timeline**: Mevcut alanlardan oluşturulacak, ek tablo yok
✅ **Print View**: Yeni endpoint ve view, özel print layout (opsiyonel)

---

**Tasarım Hazırlayan**: AI Assistant
**Durum**: ✅ Tasarım Tamamlandı

