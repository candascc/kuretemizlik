# ROUND 46 – STAGE 1: DASHBOARD TASARIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 46

---

## TASARIM İLKELERİ

### Tasarım Dili
- `src/Views/reports/financial.php` ile aynı Tailwind / dark-mode stili
- Card'lar, grid yapısı, responsive layout
- `space-y-8`, `bg-white dark:bg-gray-800`, `rounded-xl`, `shadow-soft` gibi class'lar

---

## İÇERİK YAPISI

### 1. Üst Başlık
- **Title:** "Raporlar"
- **Subtitle:** "Operasyon ve finans performansını tek ekrandan takip edin"
- **Period Info:** "Son dönem: [tarih] – [tarih]" (sağ üstte)

### 2. KPI Kartları (4 adet)
- **Grid:** `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6`
- **Kartlar:**
  1. **Son 30 Günde Toplam Gelir** (yeşil)
  2. **Son 30 Günde Tamamlanan İş** (mavi)
  3. **Aktif Müşteri Sayısı** (mor)
  4. **Bu Ay Net Kâr** (turuncu/sarı)

### 3. Orta Bölüm (2 kolon)
- **Grid:** `grid-cols-1 lg:grid-cols-2 gap-6`
- **Sol:** "Son İşler" tablosu (son 5-10 job)
- **Sağ:** "En Aktif Müşteriler" tablosu (top 5 customer)

### 4. Alt Bölüm (4 kart)
- **Grid:** `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4`
- **Kartlar:**
  1. **Finans Raporları** → link `/reports/financial`
  2. **İş Raporları** → link `/reports/jobs`
  3. **Müşteri Raporları** → link `/reports/customers`
  4. **Hizmet Raporları** → link `/reports/services`

---

## VERİ MODELİ

```php
$dashboard = [
    'period' => [
        'from' => $dateFrom,   // DateTimeImmutable: bugün - 29 gün
        'to'   => $dateTo,     // DateTimeImmutable: bugün
    ],
    'kpis' => [
        'total_income_30d'      => float,  // Son 30 günde toplam gelir
        'total_jobs_completed'  => int,    // Son 30 günde tamamlanan iş sayısı
        'active_customers'      => int,    // Son 30 günde aktif müşteri sayısı
        'net_profit_month'      => float|null, // Bu ay net kâr (opsiyonel)
    ],
    'recent_jobs' => [
        // Son 5-10 iş kaydı: id, date, customer_name, service_name, status, amount
    ],
    'top_customers' => [
        // En çok gelir getiren 5 müşteri: name, total_revenue, job_count
    ],
];
```

---

## TASARIM REFERANSI

`financial.php`'den alınacak stil öğeleri:
- Header: `text-3xl font-bold text-gray-900 dark:text-white`
- Subtitle: `text-gray-600 dark:text-gray-400`
- Card: `bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700`
- KPI Card: `text-center p-4 bg-[color]-50 dark:bg-[color]-900 rounded-lg`
- Table: `min-w-full divide-y divide-gray-200 dark:divide-gray-700`

---

**STAGE 1 TAMAMLANDI** ✅

