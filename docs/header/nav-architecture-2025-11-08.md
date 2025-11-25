<!-- Header Navigation Architecture Draft - 2025-11-08 -->

# Yönetim Hizmetleri Bilgi Mimarisi

## 1. Üst Düzey Modlar

| Mod | Etiket | Amaç | Tema |
| --- | --- | --- | --- |
| operations | Temizlik Operasyonları | Saha ekipleri, temizlik işleri, operasyonel süreçler | `from-primary-600 to-primary-700`, accent `text-primary-100` |
| management | Yönetim Hizmetleri | Apartman/site yönetimi, aidat-finans, sakin iletişimi | `from-indigo-500 to-emerald-500`, accent `text-emerald-100` |

- `HeaderManager::getModes()` üzerinden `management` adıyla yeni mod.
- `description`: “Site & apartman yönetimi, sakin deneyimi, finansal takip.”

## 2. Yönetim Hizmetleri Navigation

### 2.1 Üst Menü

1. **Yönetim Hizmetleri** (`/management/dashboard`)
   - Yönetim metrikleri (aidat tahsilatı, portal aktivitesi, toplantı ajandası).
   - Ana ikon: `fa-city` (brand consistency).
2. **Varlıklar**
   - `Binalar` (`/buildings`)
   - `Daireler` (`/units`)
   - `Tesis & Rezervasyon` (`/reservations`)
3. **Finans**
   - `Aidat Yönetimi` (`/management-fees`)
   - `Gider Yönetimi` (`/expenses`)
   - `Finansal Raporlar` (`/building-reports/financial`)
4. **İletişim**
   - `Doküman Merkezi` (`/documents`)
   - `Toplantılar` (`/meetings`)
   - `Duyurular` (`/announcements`)
   - `Anketler` (`/surveys`)
5. **Sakin Yönetimi**
   - `Sakinler` (`/management/residents`)
   - `Portal Durumu` (`/management/residents#portal-overview`)
   - `Sakin Talepleri` (`/management/residents#resident-requests`)
6. **Ayarlar**
   - Roller: `ADMIN`, `SUPERADMIN` görür; mevcut sistem menüsü ile hizalı.

### 2.2 Mobil Davranış

- Mobil menüde çocukları bulunan üst menüler (ör. Varlıklar, Finans) artık katlanabilir `<details>` yapılarıyla sunuluyor.
- Aktif çocuk linki bulunan bölüm varsayılan olarak açık geliyor; diğerleri dar halde.
- Uzun listelerde kaydırma azaltıldı, tuş alanları 44px üstü tutuluyor.

### 2.3 Alt Şerit (context chipleri)

- 2025-11-08 itibarıyla gri zeminli context chip şeridi kaldırıldı.
- `context_links` verisi backend’de tutulmaya devam ediyor; gelecekte spesifik sayfalar için yeniden kullanılabilir.

## 3. Hızlı Aksiyon Setleri

| Mod | Aksiyon | URL | Icon |
| --- | --- | --- | --- |
| operations | Yeni İş | `/jobs/new` | `fa-plus-circle` |
| operations | Periyodik İş | `/recurring/new` | `fa-redo` |
| operations/management | Yeni Tahsilat | `/finance/new` | `fa-credit-card` |
| management | Aidat Oluştur | `/management-fees/generate` | `fa-money-check-alt` |
| management | Toplantı Planla | `/meetings/create` | `fa-calendar-plus` |
| management | Yeni Duyuru | `/announcements/create` | `fa-bullhorn` |
| management | Sakin Portalı | `/management/residents#portal-actions` | `fa-user-group` |

## 4. Mikro Metin & Badge’ler

- Menü başlığı: “Yönetim Hizmetleri”
- Tooltip/metin: “Site & apartman modüllerinin tamamı – aidat, sakin, toplantı”
- Rozetler:
  - `Aidat % Ödenme` (progress)
  - `Bekleyen Duyuru`
  - `Bugünkü Rezervasyon`
- Backend `HeaderManager` `getModeMeta('management')` → `stats` placeholder.

## 5. Teknik Yaklaşım

1. `config/header.php`
   - Yönetim modu artık alt kategorileri ayrı top-level navigation item’ları olarak tanımlıyor (`management-services`, `management-assets`, `management-finance`, `management-communications`, `management-residents`).
   - Her kategori altında ilgili çocuk linkler tutuluyor; link erişimi rol filtresi (`roles`) ile sınırlandırılıyor.
2. `HeaderManager`
   - `getNavigationItems` top-level filtrelemeyi rollere ve mode değerine göre yapıyor; mobil için çocuklar `details` yapısıyla gösteriliyor.
   - `getContextLinks` hâlâ backend’de mevcut fakat UI’de varsayılan olarak render edilmiyor.
3. `app-header.php`
   - Masaüstü menüsü yeni top-level item’ları tek satırda gösteriyor, `Yönetim Hizmetleri` başlığı dashboard linkine yönlendiriyor.
   - Mobil menüde çocukları bulunan item’lar collapsible `<details>` olarak render edilerek uzun listeler sadeleştiriliyor.
4. CSS
   - `.mobile-nav__section` ve `.mobile-nav__section-trigger` sınıfları collapsible yapıyı stilliyor.
   - `.module-subnav` style’ları artık kullanılmıyor; temizlik adımı sonrası kaldırılacak.

## 6. Açık Sorular

- `/management/dashboard` için bileşen kapsamı genişledikçe ek widget’lar gerekiyorsa yeni veri kaynakları determine edilmeli.
- `management/` altındaki yeni linklerin (ör. `documents`, `expenses`) tamamının prod veritabanında mevcut olduğundan emin olmak için smoke-test kapsamı genişletilebilir.
- Context chipleri yeniden ihtiyaç olursa, hangi sayfalar için gerekli olduğu ve hangi veri kaynağından besleneceği netleştirilmeli.

## 7. Tema & Mikro Metin Stratejisi

### 7.1 Renk Paleti

- `.mode-management`:
  - `background: linear-gradient(135deg, #6366f1 0%, #10b981 100%)`
  - `--mode-accent: #d1fae5`
  - `--mode-border: rgba(16, 185, 129, 0.35)`
  - Status chip: `bg-white/18 text-emerald-50 border-white/30`
- Quick Action Variant:
  - `.quick-action-btn.variant-management`
  - `@apply bg-white/15 hover:bg-white/25 text-emerald-50 border border-white/20 backdrop-blur`
  - Inner icon `text-emerald-200`

### 7.2 Mikro Metin

- Mode toggle tooltip: “Site yönetimi moduna geç – aidat ve sakin süreçlerini yönet.”
- Dashboard hero copy: “Yönetim Hizmetleri - Bugün yapılacak işleriniz burada.”
- Empty states (ör. meetings): “Henüz toplantı planlanmamış. ‘Toplantı Planla’ ile başlayın.”

### 7.3 İkonografi

- Mode chip ikonu: `fa-city`
- Quick action ikonları:
  - Aidat Planı → `fa-money-check-alt`
  - Toplantı Planla → `fa-people-group`
  - Yeni Duyuru → `fa-bullhorn`
  - Rezervasyon Aç → `fa-door-open`

### 7.4 UI Davranışı

- Mode değişince:
  - Header degrade & statü rozet renkleri güncellenecek.
  - Quick actions array `HeaderManager::getQuickActions()` ile filtrelenecek.
  - Alt navigasyon chipleri `context_bg` parametresine göre blur/renk alacak.
- Lokal depolama anahtarı: `app_header_mode = management`.

## 8. Uygulama Durumu (2025-11-08 Güncellemesi)

- Yönetim dashboard mesajları portal kullanımını vurgulayacak şekilde güncellendi.
- `/management/residents` ekranı bina filtresi, arama, sayfalama ve portal aksiyon kısayollarıyla yayınlandı.
- `ResidentPortalMetrics` yardımıyla dashboard ve portal sayfası tek sorgu katmanını paylaşıyor.
- `ManagementResidentsTest` fonksiyonel testi filtre/arama/uyarı akışlarını güvence altına aldı (run_all suite #4).
- Görsel & QA referansları `docs/header/resident-portal-qa.md` dosyasında kayıt altında.
- Portal hata mesajları korelasyon ID’leriyle destekleniyor; `Logger` çıktıları referans aynı ID’yi içeriyor.

