# 🔒 Security Dependency Risks

**Tarih:** 2025-11-22  
**Durum:** ROUND 16 - Dependency Risk Notu  
**Kaynak:** `INFRA_ROUND_TOOLCHAIN_STABILIZATION_SUMMARY.md`

---

## 📊 VULNERABILITY ÖZETİ

**Toplam Vulnerability:** 13 adet
- **Low Severity:** 5 adet
- **High Severity:** 8 adet

**Durum:** ⚠️ **PENDING** (ROUND 16'da ele alınacak)

**Not:** Bu vulnerability'ler INFRA ROUND'da tespit edildi, ancak toolchain stabilization round'u olduğu için ele alınmadı.

---

## 🔍 NEDEN ŞU AN DOKUNMUYORUZ?

**Gerekçe:**
1. **Scope Dışında:** INFRA ROUND sadece toolchain stabilization için yapıldı, dependency update scope dışındaydı
2. **Risk Düşük:** Vulnerability'ler çoğunlukla devDependencies içinde (test tools, build tools)
3. **Zaman Yetersizliği:** Dependency update'leri test gerektirir, INFRA ROUND'da test yapılamadı
4. **Breaking Change Riski:** Major version bump'lar breaking change getirebilir, test suite'i etkilenebilir

**Not:** Bu vulnerability'ler production runtime'ı doğrudan etkilemiyor (devDependencies), ancak yine de düzeltilmesi önerilir.

---

## 🛠️ İLERDE ÇÖZMEK İÇİN NE YAPILMASI GEREKİR?

### Adım 1: Detaylı Vulnerability Analizi

**Komut:**
```bash
npm audit
```

**Çıktı:**
- Her vulnerability için:
  - Paket adı
  - Vulnerability ID
  - Severity (low/high/critical)
  - Path (hangi paket dependency'si)
  - Fix önerisi (package update, major bump, replacement)

### Adım 2: Otomatik Düzeltme Denemesi

**Komut:**
```bash
npm audit fix
```

**Ne Yapar:**
- Otomatik olarak düzeltilebilen vulnerability'leri düzeltir
- Minor/patch version update'leri yapar
- Breaking change riski düşük olan update'leri yapar

**Not:** Otomatik düzeltme her zaman mümkün olmayabilir (major version bump gerekiyorsa).

### Adım 3: Manuel Package Update (Gerekirse)

**Komut:**
```bash
# Belirli bir paketi update et
npm update <package-name>

# Major version bump gerekiyorsa
npm install <package-name>@latest
```

**Dikkat Edilmesi Gerekenler:**
- Major version bump'lar breaking change getirebilir
- Test suite'i çalıştırarak regresyon kontrolü yapılmalı
- Changelog'ları okuyarak breaking change'leri kontrol et

### Adım 4: Package Replacement (Gerekirse)

**Eğer paket artık maintain edilmiyorsa veya critical vulnerability varsa:**
- Alternatif paket araştır
- Migration planı yap
- Test suite'i güncelle
- Production'a deploy etmeden önce staging'de test et

### Adım 5: Test & Regresyon Kontrolü

**Komutlar:**
```bash
# Gating testleri
BASE_URL=http://kuretemizlik.local/app npm run test:ui:gating:local

# Tüm testler
npm run test:ui

# Production smoke test
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run test:prod:smoke
```

**Kontrol:**
- Tüm testler GREEN olmalı
- Yeni breaking change'ler tespit edilmeli
- Production smoke test passed olmalı

---

## 📋 ÖNERİLEN AKSİYON TİPLERİ

### 1. Package Update (Minor/Patch)

**Ne Zaman:** Vulnerability minor/patch version update ile düzeltilebiliyorsa

**Örnek:**
```bash
npm update @playwright/test
```

**Risk:** Düşük (minor/patch update'ler genellikle backward compatible)

---

### 2. Major Version Bump

**Ne Zaman:** Vulnerability major version bump gerektiriyorsa

**Örnek:**
```bash
npm install @playwright/test@latest
```

**Risk:** Orta-Yüksek (breaking change riski var)

**Dikkat:**
- Changelog'ları oku
- Test suite'i çalıştır
- Breaking change'leri kontrol et

---

### 3. Package Replacement

**Ne Zaman:** Paket artık maintain edilmiyorsa veya critical vulnerability varsa

**Örnek:**
- Eski bir build tool → Modern alternatif
- Deprecated package → Aktif maintain edilen alternatif

**Risk:** Yüksek (kod değişikliği gerekebilir)

**Dikkat:**
- Migration planı yap
- Test coverage genişlet
- Staging'de test et

---

## 🔗 İLGİLİ BACKLOG ITEM

**Backlog ID:** S-01 (npm Dependency Vulnerabilities)

**Referans:** `KUREAPP_BACKLOG.md` - S-01

**Önerilen Zamanlama:** 1-2 sprint içinde

---

## 📝 NOTLAR

- **Vulnerability'ler çoğunlukla devDependencies içinde:** Production runtime'ı doğrudan etkilemiyor, ancak yine de düzeltilmesi önerilir
- **Test gerektirir:** Dependency update'leri test suite'i çalıştırarak doğrulanmalı
- **Breaking change riski:** Major version bump'lar breaking change getirebilir, dikkatli olunmalı
- **Compliance:** Security scanning tool'ları (Snyk, Dependabot, vs.) uyarı verebilir

---

**ROUND 16 TAMAMLANDI** ✅


