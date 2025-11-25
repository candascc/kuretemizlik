<!-- Resident Portal QA checklist and visual references -->

# Resident Portal QA & Visual References

## Visual References

| Asset | Description | Capture Notes |
| --- | --- | --- |
| `docs/header/screenshots/resident-portal-dashboard.png` | Yönetim dashboard portal kartı ve hızlı aksiyonlar | Masaüstü 1440px, management modu seçili, portal kartı görünür, pagination bar dahil |
| `docs/header/screenshots/resident-portal-filters.png` | Sakin yönetimi filtreleri (arama + bina + sayfalama) | Masaüstü 1280px, arama alanı doldurulmuş, bina filtresi seçili, sayfa 2 görüntüleniyor |
| `docs/header/screenshots/resident-portal-empty.png` | Boş state ve uyarı mesajları | Arama: “NoMatch…”, boş liste uyarısı ve alert mesajı görülüyor |

> **Capture guidance:** Tarayıcı geliştirici araçlarında `window.devicePixelRatio = 1` ayarlayın, Chrome DevTools “Capture full size screenshot” seçeneğini kullanın. Annotasyonlar için Figma/Skitch veya işletme standartları kullanılabilir.

## Manual QA Checklist

1. **Filtreleme & Arama**
   - `/management/residents` sayfasında arama alanına “Portal Test” yazın → yalnızca ilgili sakinler listelenir.
   - Bina filtresini “Portal Tower …” seçin → diğer binalara ait sakinler görünmez.
2. **Sayfalama**
   - `?per_page=5` parametresi ile listede 5’ten fazla sakin olduğunda sayfa 2’ye geçin → sayfa çubuğunda “Sayfa 2 / 2 · Toplam _n_ kayıt” görünün.
   - Sayfa 2’de “Portal Extra” sakinlerinin görünür, ilk sayfadaki kayıtlar tekrar etmez.
3. **Boş Durum**
   - Arama değeri `NoMatch123` → boş state bileşeni ve “Tanımlı sakin bulunmuyor” mesajı görünür.
4. **Uyarı Mesajları**
   - Portal veri hata simülasyonu: `php tests/functional/run_all.php` çalıştırırken `ManagementResidentsTest::testAlertOnDataFailure` log ve UI uyarısını doğrular.
5. **Regresyon Scripti**
   - `php tests/functional/run_all.php` → tüm suite PASS (özellikle “Test Suite 4”).

## Automation Artifacts

- Functional: `tests/functional/ManagementResidentsTest.php`
- Regression command: `php tests/functional/run_all.php`
- Documentation updates: `docs/header/nav-architecture-2025-11-08.md`, `BUILD_PROGRESS.md`

