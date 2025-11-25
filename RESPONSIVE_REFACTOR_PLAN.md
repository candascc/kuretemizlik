# 📋 RESPONSIVE REFACTOR UYGULAMA PLANI

**Tarih:** 2025-01-XX  
**Kaynak Rapor:** RESPONSIVE_UI_UX_AUDIT_REPORT.md

---

## UYGULAMA SIRASI (Bağımlılıklara Göre)

### STAGE 1: Breakpoint & Layout Standartizasyonu (HIGH Priority)
**Hedef Dosyalar:**
- `assets/css/custom.css` - Breakpoint standardizasyonu, media query düzeltmeleri
- `src/Views/layout/base.php` - Tailwind config breakpoint'leri
- `src/Views/dashboard.php` - Grid layout düzeltmeleri
- `src/Views/layout/footer.php` - Footer mobil optimizasyonu
- Tüm view dosyaları - Grid class'ları düzeltmeleri

**Yapılacaklar:**
1. CSS media query'lerini standartlaştır (640px, 1024px)
2. Dashboard KPI kartlarını `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` yap
3. Footer'ı mobilde accordion yapısına çevir
4. Container max-width'leri standartlaştır

---

### STAGE 2: Tasarım Sistemi & Component Tutarlılığı (MEDIUM Priority)
**Hedef Dosyalar:**
- `assets/css/custom.css` - Kart, buton, renk sistemi
- `src/Views/partials/ui/card.php` - Kart component standardizasyonu
- Tüm view dosyaları - Button class'ları, renk paleti

**Yapılacaklar:**
1. Kart border-radius ve shadow standartlaştır
2. Button variant sistemi kur
3. Renk paleti tutarlılığı (primary-600 standart)
4. Spacing scale uygula

---

### STAGE 3: Tipografi & Metin Akışı (HIGH Priority)
**Hedef Dosyalar:**
- `assets/css/custom.css` - Fluid typography, line-height
- `src/Views/layout/base.php` - Global typography ayarları
- Tüm view dosyaları - Font-size düzeltmeleri

**Yapılacaklar:**
1. Mobilde minimum font-size 14px (text-sm)
2. Fluid typography ekle (clamp())
3. Line-height iyileştirmeleri
4. Metin kırılma düzeltmeleri

---

### STAGE 4: Mikro UX & Polish (MEDIUM/LOW Priority)
**Hedef Dosyalar:**
- `assets/css/custom.css` - Hover, focus, transition
- `assets/js/mobile-table-cards.js` - Tablo responsive iyileştirmeleri
- Tüm view dosyaları - Touch target, empty state

**Yapılacaklar:**
1. Hover/focus state'leri ekle
2. Touch target'ları 44px'e çıkar
3. Smooth scroll ekle
4. Transition'lar ekle

---

## TOP 15 İYİLEŞTİRME EŞLEŞMESİ

| ID | Durum | Dosya | Açıklama |
|---|---|---|---|
| 1 | TODO | custom.css, base.php | Breakpoint standardizasyonu |
| 2 | TODO | dashboard.php | Grid mobile-first |
| 3 | TODO | mobile-table-cards.js | Tablo responsive |
| 4 | TODO | custom.css | Font-size minimum 14px |
| 5 | TODO | footer.php | Footer accordion |
| 6 | TODO | custom.css | Fluid typography |
| 7 | TODO | card.php, views | Padding standardizasyonu |
| 8 | TODO | custom.css, views | Renk paleti |
| 9 | TODO | custom.css | Focus state |
| 10 | TODO | custom.css | Hover state |
| 11 | TODO | form views | Validation feedback |
| 12 | TODO | table views | Touch target |
| 13 | TODO | card.php | Border-radius |
| 14 | TODO | card.php | Shadow |
| 15 | TODO | custom.css | Transition |

---

## BAŞLANGIÇ

STAGE 1'den başlıyoruz...

