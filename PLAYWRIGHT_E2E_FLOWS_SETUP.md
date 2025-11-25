# 🔄 E2E User Flows Test Setup

## 📋 Özet

Bu doküman, E2E (End-to-End) user flow testlerinin setup, data stratejisi ve kullanımını açıklar.

## 🎯 Test Kapsamı

E2E testleri, gerçek iş akışlarını uçtan uca test eder:

1. **Manager Flow** (`e2e-flows.spec.ts`)
   - Building ve unit oluşturma
   - Job oluşturma ve atama
   - Dashboard entegrasyonu

2. **Staff Flow** (`e2e-flows.spec.ts`)
   - Atanan görevleri görüntüleme
   - Görev tamamlama

3. **Finance Flow** (`e2e-finance.spec.ts`)
   - Management fee oluşturma
   - Ödeme işleme
   - Bakiye güncellemeleri

4. **Multi-Tenant Isolation** (`e2e-multitenant.spec.ts`)
   - Veri izolasyonu
   - Session izolasyonu
   - URL parameter koruması

## 📁 Test Dosyaları

```
tests/ui/
├── e2e-flows.spec.ts           # Manager ve Staff flow testleri
├── e2e-finance.spec.ts         # Finance flow testleri
├── e2e-multitenant.spec.ts     # Multi-tenant isolation testleri
└── helpers/
    └── data.ts                 # Test data helper fonksiyonları
```

## 🔧 Test Data Stratejisi

### 1. Test User'lar

**Mevcut Environment Variables:**
- `TEST_ADMIN_EMAIL` - Admin kullanıcı email'i
- `TEST_ADMIN_PASSWORD` - Admin kullanıcı şifresi
- `TEST_RESIDENT_PHONE` - Resident kullanıcı telefon numarası

**Multi-Tenant Testleri İçin (Opsiyonel):**
- `TEST_COMPANY_A_EMAIL` - Company A kullanıcı email'i
- `TEST_COMPANY_A_PASSWORD` - Company A kullanıcı şifresi
- `TEST_COMPANY_B_EMAIL` - Company B kullanıcı email'i
- `TEST_COMPANY_B_PASSWORD` - Company B kullanıcı şifresi

### 2. Data Setup

**Strateji: UI Üzerinden Oluşturma**

Testler, veriyi UI üzerinden oluşturur:
- `createBuildingViaUI()` - Building oluşturma
- `createUnitViaUI()` - Unit oluşturma
- `createJobViaUI()` - Job oluşturma
- `createManagementFeeViaUI()` - Management fee oluşturma

**Avantajlar:**
- Gerçek kullanıcı akışını test eder
- Form validation'ları da test edilir
- Backend API'ye bağımlı değil

**Dezavantajlar:**
- Daha yavaş (her test için UI interaction)
- Test ortamında UI değişikliklerinden etkilenir

### 3. Data Cleanup

**Mevcut Strateji: Minimal Cleanup**

Testler şu anda cleanup yapmaz. Bunun yerine:
- Test ortamı periyodik olarak reset edilir
- Test data'ları unique identifier'lar kullanır (`generateTestId()`)
- Test ortamı production'dan ayrıdır

**Gelecek İyileştirme:**
- UI üzerinden delete fonksiyonları eklenebilir
- Test sonunda cleanup helper'ları çağrılabilir
- API endpoint'leri varsa cleanup için kullanılabilir

## 🚀 Test Çalıştırma

### Tüm E2E Testleri
```bash
npm run test:ui:e2e
```

### Kategori Bazlı
```bash
# Sadece user flow testleri
npm run test:ui:e2e:flows

# Sadece finance testleri
npm run test:ui:e2e:finance

# Sadece multi-tenant testleri
npm run test:ui:e2e:multitenant
```

### Belirli Test Dosyası
```bash
npx playwright test e2e-flows.spec.ts
```

### Debug Mode
```bash
npx playwright test e2e-flows.spec.ts --debug
```

## 📊 Test Senaryoları Detayları

### Manager Flow Testleri

1. **Create Building and Unit**
   - Building oluşturma
   - Unit oluşturma
   - Liste sayfalarında görünürlük kontrolü

2. **Create and Assign Job**
   - Job oluşturma
   - Job'a staff atama
   - Liste sayfasında görünürlük

3. **Dashboard Integration**
   - Oluşturulan item'ların dashboard'da görünmesi
   - KPI güncellemeleri

### Staff Flow Testleri

1. **View Assigned Jobs**
   - Jobs list sayfası erişimi
   - Atanan görevlerin görünürlüğü

2. **Complete Job**
   - Görev detay sayfası
   - Görevi tamamlandı olarak işaretleme
   - Status güncellemesi

### Finance Flow Testleri

1. **Create Management Fee**
   - Fee oluşturma
   - Liste sayfasında görünürlük
   - Tutar ve unit bilgisi doğruluğu

2. **Payment Processing**
   - Fee'yi ödendi olarak işaretleme
   - Status güncellemesi
   - Bakiye güncellemesi

3. **Financial Summary**
   - Dashboard KPI'ları
   - Status filtreleme
   - Overdue fees

### Multi-Tenant Testleri

1. **Data Isolation**
   - Company A verilerinin Company B'de görünmemesi
   - Buildings, units, jobs, fees izolasyonu

2. **Session Isolation**
   - Ayrı session'ların korunması
   - Logout sonrası veri erişiminin engellenmesi

3. **URL Parameter Protection**
   - Direkt URL erişiminin engellenmesi
   - 404 veya access denied kontrolü

## ⚠️ Önemli Notlar

### 1. Test Bağımlılıkları

E2E testler, test ortamında şunların olmasını gerektirir:
- Çalışan bir uygulama instance'ı
- Test kullanıcıları (admin, staff, vb.)
- Temiz veya reset edilebilir test veritabanı

### 2. Test Stability

- Testler, UI değişikliklerinden etkilenebilir
- Selector'lar generic tutulmuştur (text-based, class-based)
- Test başarısız olursa, UI değişikliği veya test ortamı sorunu olabilir

### 3. Skip Mekanizması

Testler, gerekli UI element'leri bulunamazsa `test.skip()` ile atlanır:
- Building/unit/job creation UI yoksa
- Payment UI yoksa
- Multi-tenant yapı yoksa

Bu, testlerin farklı ortamlarda çalışabilmesini sağlar.

### 4. Test Data Unique Identifier'ları

Her test, unique identifier kullanır:
```typescript
const testId = generateTestId(); // "test-1234567890-abc123"
const buildingName = `E2E Building ${testId}`;
```

Bu sayede:
- Test data'ları birbirine karışmaz
- Paralel test çalıştırma mümkündür
- Cleanup daha kolaydır

## 🔮 Gelecek İyileştirmeler

1. **API-Based Data Setup** ✅ (Temel Altyapı Kuruldu)
   - ✅ Test seeding endpoint'leri oluşturuldu (`/tests/seed`, `/tests/cleanup`)
   - ✅ Helper fonksiyonlar eklendi (`seedBasicTestDataViaAPI`, `cleanupTestDataViaAPI`)
   - 🔄 Testlerde API-based seeding kullanımı (opsiyonel, fallback UI-based)
   - **Not:** API endpoint'leri sadece test ortamında aktif (APP_ENV=test)

2. **Test Data Seeding**
   - Test başlangıcında seed script çalıştırma
   - Önceden hazırlanmış test data setleri

3. **Parallel Test Execution**
   - Test data izolasyonu ile paralel çalıştırma
   - Test süresini azaltma

4. **Test Data Cleanup** ✅ (Temel Altyapı Kuruldu)
   - ✅ Cleanup endpoint'i oluşturuldu (`/tests/cleanup`)
   - 🔄 Testlerde otomatik cleanup kullanımı

5. **Multi-User Scenarios**
   - Gerçek multi-user senaryoları
   - Concurrent access testleri

## 📝 API-Based Seeding Kullanımı

### Test Endpoint'leri
- **GET/POST `/tests/seed`** - Test data oluşturma
- **GET/POST `/tests/cleanup`** - Test data temizleme

### Güvenlik
- Sadece `APP_ENV=test` ortamında aktif
- Production'da otomatik olarak devre dışı
- `APP_DEBUG` kontrolü ile ekstra güvenlik

### Kullanım Örneği
```typescript
// Helper fonksiyon kullanımı
import { seedBasicTestDataViaAPI } from './helpers/data';

const buildingId = await seedBasicTestDataViaAPI(page, 'building', {
  name: 'Test Building',
  address: 'Test Address'
});

// Eğer API mevcut değilse, null döner ve UI-based creation kullanılır
if (!buildingId) {
  // Fallback to UI-based creation
  await createBuildingViaUI(page, 'Test Building');
}
```

## 📚 İlgili Dokümanlar

- [Playwright Test Setup](./PLAYWRIGHT_TEST_SETUP.md)
- [Playwright QA Complete Report](./PLAYWRIGHT_QA_COMPLETE_REPORT.md)
- [UI Tests README](./tests/ui/README.md)
- [CI/CD Guide](./CI_UI_TESTS.md)

---

**Oluşturulma Tarihi:** 2025-01-XX  
**Test Dosyaları:** `e2e-flows.spec.ts`, `e2e-finance.spec.ts`, `e2e-multitenant.spec.ts`  
**Helper Dosyası:** `helpers/data.ts`

