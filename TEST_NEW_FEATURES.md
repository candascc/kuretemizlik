# 🧪 Yeni Özellikler Test Rehberi

## ⚠️ ÖNCELİKLE YAPIN

### 1. XAMPP Apache Restart
```
XAMPP Control Panel → Apache STOP → START
```

### 2. Browser Cache Temizle
```
Ctrl + Shift + Delete → "Önbelleğe alınan resimler ve dosyalar" → Temizle
```

### 3. Hard Refresh
```
Ctrl + F5 (veya Ctrl + Shift + R)
```

---

## 🎯 TEST ADIMLARI

### ✅ Test 1: Job Wizard (5 Adımlı Form)

**Adres**: `http://localhost/app/jobs/wizard`

**Görecekleriniz**:
1. Üstte 5 adımlı progress bar (mavi çemberler)
2. Step 1: "Kim için iş oluşturuyorsunuz?" başlığı
3. Büyük arama kutusu (customer search)
4. Modern, renkli tasarım

**Eğer 404 hatası alırsanız**:
- Apache restart ettiniz mi?
- `/jobs` sayfasına gidin, "Yeni İş (Wizard)" butonuna tıklayın

---

### ✅ Test 2: Buton Görünürlüğü

**Adres**: `http://localhost/app/jobs`

**Görecekleriniz**:
- **MAVİ BUTON**: "Yeni İş (Wizard)" - Gradient mavi/indigo, sarı ⭐ icon
- **GRİ BUTON**: "Klasik Form" - Açık gri, border

**DOĞRU görünüm**:
```
[🪄 Yeni İş (Wizard)]  [📋 Klasik Form]
   MAVİ GRADIENT           GRİ BG
```

**Eğer göremiyorsanız**:
- Sayfayı Ctrl + F5 ile yenileyin
- Dark mode aktif mi? (toggle edin)

---

### ✅ Test 3: Global Search

**Nasıl Test Edilir**:
1. Herhangi bir sayfaya gidin
2. `Ctrl + /` (forward slash) tuşlarına basın
   - ⚠️ `Ctrl + +` (artı) DEĞİL!
   - ✅ `Ctrl + /` (slash, shift olmadan)
3. Büyük arama modal'ı açılmalı

**Alternatif**:
- Browser console (F12): `window.globalSearch.open()`

**Görecekleriniz**:
- Tam ekran modal
- "Herhangi bir şey ara..." placeholder
- Esc ile kapanır

---

### ✅ Test 4: Timezone Clock

**Adres**: Herhangi bir sayfa

**Görecekleriniz**:
- **Sağ alt köşede** küçük widget
- "Türkiye: 14:30:45" (canlı saat)
- Her saniye güncellenir

**Browser Console Test**:
```javascript
window.timezoneHandler  // Object görmelisiniz
```

---

### ✅ Test 5: Keyboard Shortcuts Help

**Nasıl Test Edilir**:
1. Herhangi bir sayfada `?` (shift + 7) tuşuna basın
2. Modal açılmalı: "Klavye Kısayolları"
3. Tüm shortcuts listesi görünür

**Alternatif**:
- Console: `window.keyboardShortcutsHelp.show()`

---

### ✅ Test 6: Mobile Responsive

**Nasıl Test Edilir**:
1. F12 → Device Toolbar (ikinci icon)
2. iPhone X seçin (375px)
3. Dashboard'a gidin

**Görecekleriniz**:
- Tables → Card view'a dönüşür
- Tab navigation çıkar (Bugün/Bu Hafta/İstatistikler)
- Daha az scroll

---

## 🐛 SORUN GİDERME

### Sorun: "404 Not Found" (/jobs/wizard)

**Çözümler**:
1. ✅ Apache restart edildi mi?
2. ✅ `index.php` güncel mi? (route eklenmiş mi kontrol)
3. ✅ JobWizardController.php mevcut mu?

**Manuel Kontrol**:
```powershell
# PowerShell'de çalıştırın:
Test-Path "C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\src\Controllers\JobWizardController.php"
# True dönmeli
```

**Hızlı Fix**:
- `/jobs` sayfasındaki "Yeni İş (Wizard)" butonuna tıklayın
- Aynı route'a götürmeli

---

### Sorun: "Butonlar beyaz, görünmüyor"

**Çözüm**: Buton stilleri güncellendi ✅

**Yeni görünüm**:
- Wizard butonu: **MAVİ gradient** (from-blue-600 to-indigo-600)
- Klasik form: **GRİ** (bg-gray-100)

**Hala görünmüyorsa**:
- Tailwind CSS yüklü mü?
- Dark mode aktif mi? (icon'a tıklayıp toggle edin)

---

### Sorun: "JavaScript çalışmıyor"

**Kontrol Adımları**:

1. **Console Errors** (F12 → Console):
   - Kırmızı error var mı?
   - "Uncaught ReferenceError" var mı?

2. **Network Tab** (F12 → Network → Refresh):
   - `timezone-handler.js` → 200 OK
   - `global-search.js` → 200 OK
   - `bulk-operations.js` → 200 OK

3. **JS Objects Test**:
```javascript
// Console'da çalıştırın:
window.timezoneHandler      // undefined ise script yüklenmemiş
window.globalSearch         // undefined ise yüklenmemiş
window.bulkOperations       // undefined ise yüklenmemiş
```

**Çözüm**:
- base.php güncel mi kontrol edin
- Script tag'leri doğru mu?

---

## ✅ BAŞARILI TEST ÇIKTISI

**Şunları görüyorsanız BAŞARILI**:

✅ `/jobs/wizard` → 5 adımlı wizard  
✅ `/jobs` → Mavi wizard butonu  
✅ Ctrl + / → Global search modal  
✅ Sağ alt → Türkiye saati clock  
✅ ? tuşu → Keyboard shortcuts modal  
✅ Console → window.timezoneHandler = Object

**Tümü çalışıyorsa**: 🎉 **SİSTEM KUSURSUZ!**

---

## 📞 DESTEK

**Hala sorun varsa**:

1. Screenshots çekin:
   - Jobs sayfası
   - Wizard sayfası (veya 404 error)
   - Console errors (F12)

2. Paylaşın:
   - Hangi browser (Chrome, Edge, Firefox)?
   - XAMPP restart edildi mi?
   - Cache temizlendi mi?

3. Test sonuçları:
   - Hangi testler geçti ✅
   - Hangi testler başarısız ❌

---

**Hazırlayan**: AI Build System  
**Tarih**: 2025-11-05  
**Versiyon**: 2.0 (Cache fix)

