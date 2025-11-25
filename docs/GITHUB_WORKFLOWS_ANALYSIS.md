# 🔍 GitHub Workflows Detaylı Analiz Raporu

Bu doküman, `.github/workflows/` klasöründeki tüm workflow dosyalarını analiz eder ve olası hataları açıklar.

## 📋 Workflow Dosyaları Özeti

### 1. `deploy.yml` ✅ (Hata Vermez)
- **Amaç:** Deployment bildirimi
- **Tetiklenme:** `master` veya `main` branch'ine push
- **Durum:** Sadece bildirim gönderir, hata vermez
- **Sorun:** Yok

### 2. `ci.yml` ⚠️ (Potansiyel Hatalar)
- **Amaç:** PHP kod kalitesi kontrolü
- **Tetiklenme:** `main`, `master`, `develop` branch'lerine push/PR
- **Jobs:**
  - `phpstan` - PHPStan analizi
  - `php-cs-fixer` - Kod formatı kontrolü
  - `tests` - PHPUnit testleri (Fast/Slow suite)
  - `all-tests` - Tüm testler

### 3. `tests.yml` ⚠️ (Potansiyel Hatalar)
- **Amaç:** PHP testleri çalıştırma
- **Tetiklenme:** `main`, `develop` branch'lerine push/PR + günlük schedule
- **Jobs:**
  - `test` - PHP 8.1, 8.2 testleri
  - `test-stress` - Stress testleri

### 4. `ui-tests.yml` ⚠️ (Potansiyel Hatalar)
- **Amaç:** Playwright UI testleri
- **Tetiklenme:** `main`, `develop`, `master` branch'lerine push/PR
- **Jobs:**
  - `ui-tests` - Playwright testleri
  - `ui-tests-cross` - Cross-browser testleri
  - `performance-tests` - Lighthouse performans testleri

---

## 🚨 Olası Hatalar ve Çözümleri

### Hata 1: `ci.yml` - PHPStan Hataları

**Sorun:** PHPStan analizi başarısız olabilir.

**Neden:**
- `phpstan.neon` dosyası eksik veya yanlış yapılandırılmış olabilir
- `composer stan` komutu çalışmıyor olabilir
- PHPStan seviyesi çok yüksek olabilir

**Çözüm:**
```yaml
# ci.yml içinde zaten continue-on-error: true var
# Bu yüzden workflow durmaz ama hata maili gelir
```

**Kontrol:**
- [ ] `phpstan.neon` dosyası var mı?
- [ ] `composer.json`'da `stan` script'i tanımlı mı?
- [ ] PHPStan seviyesi uygun mu?

### Hata 2: `ci.yml` - PHP-CS-Fixer Hataları

**Sorun:** Kod formatı kontrolü başarısız olabilir.

**Neden:**
- `.php-cs-fixer.php` dosyası eksik veya yanlış yapılandırılmış olabilir
- `composer cs-check` komutu çalışmıyor olabilir

**Çözüm:**
```yaml
# ci.yml içinde zaten continue-on-error: true var
# Bu yüzden workflow durmaz ama hata maili gelir
```

**Kontrol:**
- [ ] `.php-cs-fixer.php` dosyası var mı?
- [ ] `composer.json`'da `cs-check` script'i tanımlı mı?

### Hata 3: `ci.yml` - PHPUnit Test Hataları

**Sorun:** PHPUnit testleri başarısız olabilir.

**Neden:**
- `phpunit.xml` dosyası eksik veya yanlış yapılandırılmış olabilir
- Test suite'ler (`Fast`, `Slow`, `All`) tanımlı değil
- Test dosyaları eksik veya hatalı

**Çözüm:**
- `phpunit.xml` dosyasını kontrol edin
- Test suite'lerin tanımlı olduğundan emin olun

**Kontrol:**
- [ ] `phpunit.xml` dosyası var mı?
- [ ] Test suite'ler (`Fast`, `Slow`, `All`) tanımlı mı?
- [ ] Test dosyaları mevcut mu?

### Hata 4: `tests.yml` - Test Script Hataları

**Sorun:** `tests/run_all_tests_one_by_one.php` script'i çalışmıyor olabilir.

**Neden:**
- Script dosyası eksik
- Script içinde hata var
- PHP versiyonu uyumsuz

**Çözüm:**
- Script dosyasının varlığını kontrol edin
- Script'i test edin

**Kontrol:**
- [ ] `tests/run_all_tests_one_by_one.php` dosyası var mı?
- [ ] `tests/run_coverage.php` dosyası var mı?
- [ ] `tests/generate_dashboard.php` dosyası var mı?

### Hata 5: `ui-tests.yml` - Playwright Hataları

**Sorun:** Playwright testleri başarısız olabilir.

**Neden:**
- `package.json`'da `test:ui` script'i tanımlı değil
- Playwright yapılandırması eksik
- Test dosyaları eksik
- Environment variables eksik

**Çözüm:**
- `package.json`'da script'lerin tanımlı olduğundan emin olun
- `playwright.config.ts` dosyasını kontrol edin

**Kontrol:**
- [ ] `package.json`'da `test:ui` script'i var mı? ✅ (var)
- [ ] `playwright.config.ts` dosyası var mı?
- [ ] Test dosyaları (`tests/ui/*.spec.ts`) var mı?

### Hata 6: `ui-tests.yml` - Lighthouse Hataları

**Sorun:** Lighthouse performans testleri başarısız olabilir.

**Neden:**
- `npm run test:perf:lighthouse:ci` script'i çalışmıyor
- `lighthouserc.json` dosyası eksik
- Chrome kurulumu başarısız

**Çözüm:**
- `package.json`'da script'in tanımlı olduğundan emin olun
- `lighthouserc.json` dosyasını kontrol edin

**Kontrol:**
- [ ] `package.json`'da `test:perf:lighthouse:ci` script'i var mı? ✅ (var)
- [ ] `lighthouserc.json` dosyası var mı?

---

## 📊 Workflow Tetiklenme Durumu

### Aktif Workflow'lar

| Workflow | Branch | Durum |
|----------|--------|-------|
| `deploy.yml` | `master`, `main` | ✅ Aktif |
| `ci.yml` | `main`, `master`, `develop` | ⚠️ Aktif (hata verebilir) |
| `tests.yml` | `main`, `develop` | ⚠️ Aktif (hata verebilir) |
| `ui-tests.yml` | `main`, `develop`, `master` | ⚠️ Aktif (hata verebilir) |

**Not:** `master` branch'ine push yapıldığında:
- ✅ `deploy.yml` çalışır (hata vermez)
- ⚠️ `ci.yml` çalışır (hata verebilir)
- ❌ `tests.yml` çalışmaz (`main`, `develop` için)
- ⚠️ `ui-tests.yml` çalışır (hata verebilir)

---

## 🔧 Önerilen Çözümler

### Seçenek 1: Workflow'ları Devre Dışı Bırak (Hızlı)

Eğer workflow'ları şimdilik kullanmayacaksanız:

1. Workflow dosyalarını silin veya
2. Workflow'ları devre Dışı bırakın:

```yaml
# Her workflow dosyasının başına ekleyin
on:
  workflow_dispatch:  # Sadece manuel tetikleme
  # push:  # Otomatik tetiklemeyi kapat
  #   branches: [ master, main, develop ]
```

### Seçenek 2: Workflow'ları Düzelt (Önerilen)

Eksik dosyaları oluşturun ve yapılandırmaları düzeltin:

1. **PHPStan:** `phpstan.neon` dosyasını kontrol edin
2. **PHP-CS-Fixer:** `.php-cs-fixer.php` dosyasını kontrol edin
3. **PHPUnit:** `phpunit.xml` dosyasını kontrol edin
4. **Playwright:** `playwright.config.ts` dosyasını kontrol edin
5. **Test Scripts:** Eksik script dosyalarını oluşturun

### Seçenek 3: Workflow'ları Sadece Bildirim Yapacak Şekilde Ayarla

Workflow'ları hata vermeyecek şekilde yapılandırın:

```yaml
# Tüm job'lara ekleyin
continue-on-error: true
```

---

## 📝 Checklist

Workflow'ları düzeltmek için:

- [ ] `phpstan.neon` dosyası var ve doğru yapılandırılmış mı?
- [ ] `.php-cs-fixer.php` dosyası var ve doğru yapılandırılmış mı?
- [ ] `phpunit.xml` dosyası var ve test suite'ler tanımlı mı?
- [ ] `playwright.config.ts` dosyası var mı?
- [ ] `tests/run_all_tests_one_by_one.php` dosyası var mı?
- [ ] `tests/run_coverage.php` dosyası var mı?
- [ ] `tests/generate_dashboard.php` dosyası var mı?
- [ ] `lighthouserc.json` dosyası var mı?
- [ ] `composer.json`'da tüm script'ler tanımlı mı?
- [ ] `package.json`'da tüm script'ler tanımlı mı?

---

## 🚀 Hızlı Çözüm: Workflow'ları Geçici Olarak Devre Dışı Bırak

Eğer şimdilik workflow'ları kullanmayacaksanız, sadece `deploy.yml`'i aktif tutun:

1. `ci.yml` → Sadece manuel tetikleme
2. `tests.yml` → Sadece manuel tetikleme
3. `ui-tests.yml` → Sadece manuel tetikleme
4. `deploy.yml` → Aktif (zaten hata vermiyor)

---

## 📞 Sonraki Adımlar

1. GitHub'dan gelen hata mailini kontrol edin
2. Hangi workflow'un hata verdiğini belirleyin
3. Yukarıdaki çözümlerden birini uygulayın
4. Workflow'ları test edin

---

**Son Güncelleme:** 2025-11-25  
**Versiyon:** 1.0

