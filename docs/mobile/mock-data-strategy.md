# Mobile Showcase Mock Data Strategy (2025-11-08)

## 1. Goals
- Provide rich, believable datasets for marketing demos without touching production data.
- Support instant toggling between live and mock sources (env flag / query param).
- Ensure all dashboards, charts, and tables render populated states plus meaningful empty-state variants.

## 2. Data Domains & Fixtures

| Domain | Key Structures | Sample Volume | Notes |
| --- | --- | --- | --- |
| **Finans (Aidat & Gider)** | `summary` metrics, `topOutstandingUnits`, monthly trend series | 12 months | Include scenarios: yüksek tahsilat (%92), kritik gider kalemleri, bütçe karşılaştırması |
| **Sakin Portalı** | `portalStats`, `recentPortalLogins`, `pendingVerifications`, `requestStats`, `recentRequests` | 20 sakin, 10 davet, 12 talepler | Lifecycle states: yeni davet, aktif kullanıcı, pasif sakin, şikayet süreci |
| **Toplantılar & Rezervasyonlar** | `upcomingMeetings`, `upcomingReservations`, `facilityUsageTrend` (new) | 5 toplantı, 6 rezervasyon | Sağlayan alanlar: bina adı, salon adı, katılım durumu, özel not |
| **Duyurular** | `recentAnnouncements` | 6 adet | Her biri kategori (`Bakım`, `Finans`, `Bilgilendirme`) ve okunma oranı ile |
| **Bakım & İş Emirleri** | `maintenanceQueue`, `scheduledVisits` | 4 aktif iş | Mobil listeler için SLA/öncelik alanları ekle |
| **Analytics** | `collectionTrend`, `expenseBreakdown`, `portalEngagement` charts | 12 nokta (ay bazlı) | Uygun JSON serileri; grafikleri rahatça doldurur |

## 3. Delivery Mechanism
1. **Mock Provider Class** (`src/Lib/Mock/ManagementMockData.php`)
   - Static getters: `dashboard()` ve `residents()` gerçek view formatına uygun veri döndürüyor
   - Örnek trendler, davet listeleri, sakin kayıtları tek yerden yönetiliyor
2. **Toggle Strategy**
   - `.env` flag: `APP_USE_MOCKS=true` → tüm yönetim ekranları mock moda geçer
   - İsteğe bağlı `?mock=1` query paramı (session’a yazılır), `?mock=0` ile kapatılır
   - `MockHelper::bootstrap()` front controller’da çalışır; backend sorguları bypass edilir
3. **Seeder Script**
   - Optional CLI (`php scripts/seed_mock_data.php --refresh`) to populate SQLite dev database for integration tests.
   - Ensures parity between mocked UI and sample exports (PDF/CSV).
4. **Factory Helpers**
   - Use Faker (if available) or lightweight helper for names, Turkish locales, date ranges.
   - Keep deterministic seeds for repeatable demos (`mt_srand(2025)`).

## 4. Data Shapes

### Summary Metrics
```php
return [
    'buildings_total'     => 18,
    'buildings_active'    => 16,
    'units_total'         => 842,
    'occupancy_rate'      => 93.6,
    'fees_outstanding'    => 185320.75,
    'fees_overdue'        => 43210.10,
    'fees_collected'      => 896540.33,
    'collection_rate'     => 95.2,
];
```

### Portal Stats & Recent Activity
```php
return [
    'portalStats' => [
        'total'       => 612,
        'active'      => 524,
        'inactive'    => 88,
        'verified'    => 571,
        'unverified'  => 41,
        'logged_in'   => 312,
        'logged_in_recent' => 148,
    ],
    'recentPortalLogins' => [/* 8 entries */],
    'pendingVerifications' => [/* 6 entries */],
    'requestStats' => [
        'total'       => 74,
        'open'        => 18,
        'in_progress' => 9,
        'resolved'    => 47,
        'sla_breaches'=> 2,
    ],
];
```

### Finance Trend Series
```php
return [
    'collectionTrend' => [
        ['month' => '2025-01', 'expected' => 82000, 'collected' => 78500],
        // ...
    ],
    'expenseBreakdown' => [
        ['category' => 'Güvenlik', 'amount' => 38200],
        ['category' => 'Temizlik', 'amount' => 26500],
        ['category' => 'Bakım', 'amount' => 19800],
        ['category' => 'Enerji', 'amount' => 15200],
    ],
];
```

## 5. Empty-State Coverage
- Provide flag (`MockFixtures::scenario('empty-reservations')`) to render dashboards without data for UX showcase.
- Each view should accept `MockFixtures::empty()` arrays to avoid repetitive `empty` checks.
- Document mapping of scenario → UI screenshot (for marketing collateral).

## 6. Testing & Validation
- PHPUnit smoke test to load dashboard/residents with `APP_USE_MOCKS=true` and assert key metrics appear.
- Visual regression snapshots capturing both populated and empty states.
- Lighthouse mobile run with mocks to ensure performance budgets unaffected.

## 7. Next Steps
1. Scaffold mock provider namespace and register autoloading.
2. Implement toggle logic in `ManagementDashboardController`, `ManagementResidentsController`, and relevant models.
3. Build CLI command `php bin/console demo:enable-mocks` to simplify demos.
4. Update documentation (`docs/mobile/demo-guide.md`) to explain switching between live/mock data.

