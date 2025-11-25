# Standalone Scripts - Hata Özeti

Tarih: 2025-11-24

## Hızlı Özet

11 standalone script çalıştırıldı:
- ✅ 8 başarılı (73%)
- ❌ 3 başarısız (27%)

## Kritik Hatalar

### 🔴 KRİTİK: RbacAccessTest.php

**Hata**: `Call to undefined method Roles::getAll()`

**Sorun**: 
- `Permission.php:162` → `Roles::getAll()` çağrılıyor
- `Roles.php:57` → `Roles::all()` method'u var
- Method adı uyumsuzluğu

**Çözüm**: 
- `Permission.php:162` → `Roles::getAll()` yerine `Roles::all()` kullan
- VEYA `Roles.php` → `getAll()` method'u ekle (wrapper olarak)

**Etki**: RBAC sistemi tamamen çalışmıyor, tüm permission kontrolleri başarısız

---

### 🟡 YÜKSEK: JobContractFlowTest.php

**Hata**: `Exception: Sözleşme oluşturulurken hata oluştu.`

**Lokasyon**: `ContractTemplateService.php:359`

**Sorun**: 
- `JobContract::create()` false/null dönüyor
- Database constraint violation olabilir

**İnceleme Gereken**:
- `JobContract::create()` method'u
- Database schema (job_contracts tablosu)
- Required fields

---

### 🟢 ORTA: PerformanceTest.php

**Hata**: `Class "PHPUnit\Framework\TestCase" not found`

**Sorun**: PHPUnit dependency eksik

**Çözüm**: PHPUnit ile çalıştır veya standalone'a dönüştür

---

## Detaylı Raporlar

- `STANDALONE_SCRIPTS_REPORT.md` - Genel rapor
- `STANDALONE_SCRIPTS_DETAILED_ERRORS.md` - Detaylı hata analizi  
- `STANDALONE_SCRIPTS_FINAL_REPORT.md` - Final rapor
- `STANDALONE_SCRIPTS_ERROR_SUMMARY.md` - Bu dosya (hızlı özet)

