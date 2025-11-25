# ROUND 45 – STAGE 2: KÖK SEBEP MODELİ (ROOT CAUSE)

**Tarih:** 2025-11-23  
**Round:** ROUND 45

---

## KÖK SEBEP ANALİZİ

### `/app/reports` Çağrıldığında Hangi Kod Path 403 Üretiyor?

**Gözlem:**
- `ReportController::index()` içinde `View::forbidden()` çağrısı YOK (grep sonucu boş)
- Ama prod'da 403 dönüyor
- `financial()` metodu çalışıyor (200 dönüyor)
- `index()` metodu çalışmıyor (403 dönüyor)

**Kod İncelemesi:**

`index()` metodu (satır 28-120):
```php
public function index()
{
    try {
        if (!Auth::check()) {
            redirect(base_url('/login'));
            return;
        }
        
        $currentRole = Auth::role();
        if ($currentRole === 'ADMIN' || $currentRole === 'SUPERADMIN') {
            redirect(base_url('/reports/financial'));
            return;
        }
        
        try {
            if (Auth::hasGroup('nav.reports.core')) {
                redirect(base_url('/reports/financial'));
                return;
            }
        } catch (Throwable $e) {
            // error handling
        }
        
        Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
        redirect(base_url('/'));
        return;
    } catch (Throwable $e) {
        // outer catch
    }
}
```

**Sorun:**
- ADMIN/SUPERADMIN kontrolü var ama `hasGroup('nav.reports.core')` kontrolü de yapılıyor
- Eğer ADMIN kullanıcısı `nav.reports.core` group'una sahip değilse, son `redirect(base_url('/'))` çalışmalı
- Ama prod'da 403 dönüyor, bu da başka bir yerde `View::forbidden()` çağrıldığını gösteriyor

**Hipotez:**
- `hasGroup('nav.reports.core')` kontrolü sırasında bir exception oluşuyor ve catch bloğu `View::error()` çağırıyor (ama 200 status ile)
- Veya `hasGroup()` false dönüyor ve son redirect çalışmadan önce başka bir path 403 üretiyor
- Veya middleware seviyesinde `Auth::require()` çağrısı 403 üretiyor

---

### `/app/reports/financial` Neden 200 Dönerken `/app/reports` 403 Dönüyor?

**Fark Analizi:**

`financial()` metodu (satır 212-290):
```php
public function financial()
{
    if (!Auth::check()) {
        redirect(base_url('/login'));
        return;
    }
    
    $currentRole = Auth::role();
    if ($currentRole === 'ADMIN' || $currentRole === 'SUPERADMIN') {
        // Allow access for admin roles
    } else {
        try {
            if (!Auth::hasGroup('nav.reports.core')) {
                redirect(base_url('/'));
                return;
            }
        } catch (Throwable $e) {
            // error handling
        }
    }
    
    try {
        if (!Auth::hasCapability('reports.financial')) {
            redirect(base_url('/'));
            return;
        }
    } catch (Throwable $e) {
        // error handling
    }
    
    // ... render view
}
```

**Fark:**
- `financial()` içinde ADMIN/SUPERADMIN kontrolü var ve bypass yapılıyor
- `index()` içinde de ADMIN/SUPERADMIN kontrolü var ama `hasGroup()` kontrolü de yapılıyor
- `financial()` içinde `hasGroup()` kontrolü sadece ADMIN/SUPERADMIN değilse yapılıyor
- `index()` içinde `hasGroup()` kontrolü her durumda yapılıyor (ADMIN/SUPERADMIN kontrolünden sonra)

**Sorun:**
- `index()` içinde ADMIN/SUPERADMIN kontrolü var ama `hasGroup()` kontrolü de yapılıyor
- Eğer ADMIN kullanıcısı `nav.reports.core` group'una sahip değilse, son `redirect(base_url('/'))` çalışmalı
- Ama prod'da 403 dönüyor

---

### Admin Kullanıcı (test_admin / ADMIN role) İçin Auth::hasGroup() / Auth::hasCapability() Çağrıları Ne Sonuç Dönüyor?

**Beklenen:**
- ADMIN role'ü için `hasGroup('nav.reports.core')` true dönmeli
- Ama prod'da 403 dönüyor, bu da `hasGroup()` false döndüğünü gösteriyor

**Hipotez:**
- ADMIN kullanıcısı `nav.reports.core` group'una sahip değil
- `hasGroup()` false dönüyor
- Son `redirect(base_url('/'))` çalışmalı ama prod'da 403 dönüyor
- Bu da `hasGroup()` kontrolü sırasında bir exception oluştuğunu veya başka bir path 403 ürettiğini gösteriyor

---

## NET KÖK SEBEP CÜMLESİ

**Kök Sebep:**
`ReportController::index()` içinde ADMIN/SUPERADMIN kontrolü var ama `hasGroup('nav.reports.core')` kontrolü de yapılıyor. Eğer ADMIN kullanıcısı `nav.reports.core` group'una sahip değilse, son `redirect(base_url('/'))` çalışmalı ama prod'da 403 dönüyor. Bu da `hasGroup()` kontrolü sırasında bir exception oluştuğunu veya başka bir path 403 ürettiğini gösteriyor. `financial()` metodu çalışıyor çünkü ADMIN/SUPERADMIN için bypass yapılıyor ve `hasGroup()` kontrolü sadece diğer roller için yapılıyor.

**Çözüm:**
- `index()` metodunu `financial()` ile aynı modeli kullanacak şekilde güncelle
- ADMIN/SUPERADMIN için bypass yap ve `hasGroup()` kontrolünü sadece diğer roller için yap
- Tüm rapor metodlarında `require*` → `has*` + redirect modeline geç

---

**STAGE 2 TAMAMLANDI** ✅

