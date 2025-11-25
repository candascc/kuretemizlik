# 🔍 Test Failures Analysis Report

**Tarih:** 2025-01-XX  
**Kaynak:** `tests/ui/results.json` (Playwright Test Results)  
**Test Çalıştırma Tarihi:** 2025-11-21

---

## 📊 GENEL İSTATİSTİKLER

**Toplam Test:** 192 expected  
**Başarısız (Unexpected):** 546  
**Atlanan (Skipped):** 120  
**Flaky:** 0  
**Süre:** ~20 dakika (1,230,588 ms)

**Başarı Oranı:** ❌ **Çok Düşük** (546 failed / 192 expected = 284% failure rate)

---

## 🚨 KRİTİK HATA KATEGORİLERİ

### 1. Browser Yüklü Değil Hataları (En Yaygın)

**Hata Tipi:** `browserType.launch: Executable doesn't exist`

**Etkilenen Browser'lar:**
- ❌ **WebKit (Safari):** `webkit-2215/Playwright.exe` yüklü değil
- ❌ **Firefox:** `firefox-1495/firefox.exe` yüklü değil

**Etkilenen Testler:**
- Tüm WebKit projesi testleri (mobile, tablet, desktop)
- Tüm Firefox projesi testleri (desktop-firefox)
- Accessibility testleri (WebKit ve Firefox projelerinde)
- Visual regression testleri (WebKit projesinde)

**Çözüm:**
```bash
npx playwright install webkit firefox
# veya tüm browser'ları yüklemek için:
npx playwright install
```

**Etkilenen Test Sayısı:** ~200+ test (tahmini)

---

### 2. 404 Not Found Hataları (Kritik)

**Hata Tipi:** Test sayfaları 404 döndürüyor

**Hata Mesajı:**
```
The requested URL was not found on this server.
Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80
```

**Etkilenen Testler:**
- Accessibility testleri (desktop-chromium projesinde)
- Bazı sayfalar 404 döndürüyor

**Olası Nedenler:**
1. **Base URL yanlış:** `http://localhost/app` yerine `http://localhost` kullanılıyor olabilir
2. **Uygulama sunucusu çalışmıyor:** PHP built-in server veya Apache çalışmıyor
3. **Route'lar eksik:** Bazı route'lar tanımlı değil

**Etkilenen Testler:**
- `accessibility.spec.ts` - "should have no critical or serious accessibility violations"
- Diğer accessibility testleri

**Çözüm:**
1. Uygulama sunucusunun çalıştığından emin olun:
   ```bash
   # PHP built-in server
   php -S localhost:8000 -t .
   
   # veya Apache/XAMPP çalışıyor mu kontrol edin
   ```

2. `playwright.config.ts` içinde baseURL'i kontrol edin:
   ```typescript
   baseURL: process.env.BASE_URL || 'http://localhost/app'
   ```

3. Test edilen URL'lerin doğru olduğundan emin olun

---

### 3. Accessibility Violations (A11y)

**Hata Tipi:** Accessibility (a11y) ihlalleri

**Tespit Edilen İhlal:**
- **`html-has-lang`** - `<html>` elementinde `lang` attribute eksik
- **Impact:** Serious
- **WCAG:** WCAG 2A, WCAG 3.1.1

**Not:** Bu hata 404 sayfasında tespit edildi. Gerçek uygulama sayfalarında da olabilir.

**Etkilenen Testler:**
- `accessibility.spec.ts` - "should have no critical or serious accessibility violations"

**Çözüm:**
1. Tüm HTML sayfalarında `<html lang="tr">` attribute'u ekleyin
2. 404 sayfası için de `lang` attribute ekleyin (`.htaccess` veya custom error page)

---

## 📋 DETAYLI HATA LİSTESİ

### Browser Yüklü Değil Hataları

**WebKit (Safari):**
- `mobile-chromium` projesinde WebKit testleri
- `tablet-chromium` projesinde WebKit testleri  
- `desktop-chromium` projesinde WebKit testleri
- `desktop-webkit` projesindeki tüm testler

**Firefox:**
- `desktop-firefox` projesindeki tüm testler

**Örnek Hata:**
```
Error: browserType.launch: Executable doesn't exist at 
C:\Users\2025\AppData\Local\ms-playwright\webkit-2215\Playwright.exe
```

### 404 Not Found Hataları

**Etkilenen Testler:**
- `accessibility.spec.ts` - Login page accessibility testleri
- Bazı sayfalar Apache 404 sayfası döndürüyor

**Örnek Hata:**
```
404 Not Found
The requested URL was not found on this server.
```

### Accessibility Violations

**Tespit Edilen İhlaller:**
1. **html-has-lang** (Serious)
   - `<html>` elementinde `lang` attribute eksik
   - 404 sayfasında tespit edildi

---

## 🎯 ÖNCELİKLİ DÜZELTME LİSTESİ

### Yüksek Öncelik

1. **Browser'ları Yükle**
   ```bash
   npx playwright install webkit firefox
   ```
   - **Etki:** ~200+ test başarısız → başarılı olabilir

2. **Uygulama Sunucusunu Başlat**
   - PHP built-in server veya Apache çalıştığını doğrula
   - Base URL'in doğru olduğunu kontrol et (`http://localhost/app`)
   - **Etki:** 404 hataları çözülebilir

3. **HTML Lang Attribute Ekle**
   - Tüm HTML sayfalarına `<html lang="tr">` ekle
   - 404 error page'e de ekle
   - **Etki:** Accessibility violation çözülebilir

### Orta Öncelik

4. **Route'ları Kontrol Et**
   - 404 dönen sayfaların route'larının tanımlı olduğundan emin ol
   - `index.php` içinde route tanımlarını kontrol et

5. **Test Base URL'ini Doğrula**
   - `playwright.config.ts` içinde `baseURL` doğru mu?
   - Environment variable `BASE_URL` set edilmiş mi?

---

## 📈 BEKLENEN İYİLEŞME

**Şu Anki Durum:**
- 546 failed test
- 120 skipped test
- 192 expected test

**Browser'lar Yüklendikten Sonra:**
- ~200+ test başarılı olabilir (WebKit/Firefox testleri)
- Kalan ~346 failed test (404 ve diğer hatalar)

**Uygulama Sunucusu Düzeltildikten Sonra:**
- 404 hataları çözülebilir
- Accessibility testleri gerçek sayfaları test edebilir

**Tahmini Final Durum:**
- ~100-150 failed test (gerçek uygulama hataları)
- ~200-250 passed test
- ~120 skipped test

---

## 🔧 HIZLI DÜZELTME ADIMLARI

### Adım 1: Browser'ları Yükle
```bash
cd Alastyr_ftp/kuretemizlik.com/app
npx playwright install webkit firefox
```

### Adım 2: Uygulama Sunucusunu Başlat
```bash
# Seçenek 1: PHP built-in server
php -S localhost:8000 -t .

# Seçenek 2: XAMPP/Apache kullanıyorsanız, çalıştığından emin olun
# http://localhost/app erişilebilir olmalı
```

### Adım 3: Base URL'i Kontrol Et
```bash
# Environment variable set et (opsiyonel)
export BASE_URL=http://localhost/app

# veya playwright.config.ts içinde kontrol et
```

### Adım 4: HTML Lang Attribute Ekle
- Tüm view dosyalarında `<html lang="tr">` olduğundan emin ol
- 404 error page'e de ekle

### Adım 5: Testleri Tekrar Çalıştır
```bash
npm run test:ui
```

---

## 📝 SONUÇ

**Ana Sorunlar:**
1. ❌ Browser'lar yüklü değil (WebKit, Firefox)
2. ❌ Uygulama sunucusu çalışmıyor veya base URL yanlış (404 hataları)
3. ⚠️ Accessibility violation (html-has-lang)

**Önerilen Aksiyon:**
1. Browser'ları yükle (`npx playwright install webkit firefox`)
2. Uygulama sunucusunu başlat ve base URL'i doğrula
3. HTML lang attribute ekle
4. Testleri tekrar çalıştır

**Deploy Durumu:** ⚠️ **ŞU AN DEPLOY İÇİN UYGUN DEĞİL**

**Neden:**
- 546 failed test var
- Browser setup eksik
- Uygulama sunucusu sorunları

**Sonraki Adım:**
Yukarıdaki düzeltmeleri yap, testleri tekrar çalıştır, sonuçları kontrol et.

---

**Rapor Oluşturulma Tarihi:** 2025-01-XX  
**Kaynak:** `tests/ui/results.json` (Playwright Test Results)

---

## FOLLOW-UP / ROUND 8

**Tarih:** 2025-01-XX  
**Durum:** ✅ DÜZELTMELER YAPILDI

### Yapılan Düzeltmeler

1. **Base URL Güncellendi:**
   - `playwright.config.ts` içinde default baseURL: `http://kuretemizlik.local/app`
   - Environment variable ile kontrol edilebilir: `BASE_URL`

2. **Gating Script Eklendi:**
   - `test:ui:gating:local` - Sadece Chromium + core E2E testleri
   - Cross-browser testler ikinci faza bırakıldı

3. **Cross-Browser Testler Opt-In:**
   - Firefox ve WebKit projeleri sadece `ENABLE_CROSS_BROWSER=1` set edildiğinde aktif
   - Default durumda exclude ediliyor (browser yüklü değil hatası önleniyor)

4. **HTML Lang Attribute Fix:**
   - 404 ve error sayfalarına standalone HTML yapısı eklendi (`<html lang="tr">`)
   - Base layout'ta zaten `lang="tr"` mevcut

### Önerilen Test Çalıştırma

**Local Gating Test:**
```bash
BASE_URL=http://kuretemizlik.local/app npm run test:ui:gating:local
```

**Cross-Browser Testler (İkinci Faz):**
```bash
ENABLE_CROSS_BROWSER=1 npm run test:ui:cross
```

**Detaylı Rapor:** `PLAYWRIGHT_QA_COMPLETE_REPORT.md` - ROUND 8 bölümü

