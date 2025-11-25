# LOGIN 500 STAGE 0: Log Analizi Raporu

**Tarih**: 2025-11-23  
**Aşama**: STAGE 0 - Log Analizi (READ-ONLY)

## Özet

Production log dosyalarında analiz yapıldı. İlk login'de 500 hatasına neden olabilecek birkaç kritik sorun tespit edildi.

---

## Tespit Edilen Hatalar

### 1. `kozmos_is_https()` Redeclare Hatası (FATAL ERROR)

**Durum**: ⚠️ **KRİTİK** - Production'da hala görünüyor

**Log Örnekleri**:
```
[2025-11-23 00:06:50] [ERROR] Cannot redeclare kozmos_is_https() (previously declared in /home/cagdasya/kuretemizlik.com/app/config/config.php:49)
[2025-11-23 00:24:47] [ERROR] Cannot redeclare kozmos_is_https() (previously declared in /home/cagdasya/kuretemizlik.com/app/config/config.php:49)
[2025-11-23 01:02:17] [ERROR] Cannot redeclare kozmos_is_https() (previously declared in /home/cagdasya/kuretemizlik.com/app/config/config.php:49)
```

**Sıklık**: 45+ kez görüldü (2025-11-23 tarihinde)

**Kök Sebep**: `config/config.php` dosyası birden fazla kez include ediliyor. ROUND 52'de `if (!function_exists('kozmos_is_https'))` kontrolü eklendi, ancak production'da hala eski kod çalışıyor olabilir.

**Etki**: Bu fatal error, ilk login'de 500 hatasına neden oluyor. İkinci request'te (F5) muhtemelen cache veya farklı bir kod path'i kullanıldığı için hata oluşmuyor.

---

### 2. Session Cookie Params Hatası (WARNING → ERROR)

**Durum**: ⚠️ **YÜKSEK** - Production'da hala görünüyor

**Log Örnekleri**:
```
[2025-11-23 00:07:11] [ERROR] session_set_cookie_params(): Session cookie parameters cannot be changed when a session is active | Context: {"type":"Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/src\/Lib\/Auth.php","line":262}
[2025-11-23 00:07:11] [ERROR] ini_set(): Session ini settings cannot be changed when a session is active | Context: {"type":"Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/src\/Lib\/Auth.php","line":272}
```

**Sıklık**: 20+ kez görüldü (2025-11-23 tarihinde)

**Kök Sebep**: `Auth.php` dosyasında line 262 ve 272'de hala `session_set_cookie_params()` ve `ini_set()` çağrıları var. ROUND 51'de bu çağrılar kaldırılmıştı, ancak production'da hala eski kod çalışıyor olabilir.

**Etki**: Bu hatalar warning seviyesinde, ancak error handler tarafından exception'a dönüştürülüyorsa 500 hatasına neden olabilir.

---

### 3. Auth Flow Log Analizi

**Log Dosyası**: `logs/auth_flow_r51.log`

**Gözlemler**:
- Login başarılı: `[LOGIN_SUCCESS] user_id=4, session_id_before=6e32cf56..., session_id_after=6b7e713c...`
- Session ID regenerate ediliyor: `6e32cf56...` → `6b7e713c...`
- Login sonrası çok sayıda `/app/` request'i yapılıyor (redirect loop gibi görünüyor)
- Tüm request'lerde `AUTH_CHECK` true dönüyor, `user_id=4` mevcut

**Örnek Log**:
```
2025-11-23 09:19:58 [LOGIN_ATTEMPT] email=admin, ip=78.181.239.246, session_status=2, session_id=6e32cf56..., uri=/app/login
2025-11-23 09:19:58 [LOGIN_SUCCESS] user_id=4, session_id_before=6e32cf56..., session_id_after=6b7e713c..., uri=/app/login
2025-11-23 09:19:58 [AUTH_CHECK] uri=/app/, result=true, user_id=4, session_status=2, session_id=6b7e713c...
```

**Analiz**: Login başarılı, session ID regenerate ediliyor, ancak sonrasında çok sayıda `/app/` request'i yapılıyor. Bu, redirect loop veya view rendering sırasında oluşan bir sorun olabilir.

---

### 4. Undefined Variable `$content` Hatası

**Durum**: ⚠️ **ORTA** - Production'da görülüyor

**Log Örnekleri**:
```
[2025-11-23 00:30:23] [ERROR] Undefined variable $content | Context: {"type":"Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/src\/Views\/layout\/header.php","line":608}
[2025-11-23 01:06:45] [ERROR] Undefined variable $content | Context: {"type":"Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/src\/Views\/layout\/header.php","line":608}
```

**Sıklık**: 3+ kez görüldü (2025-11-23 tarihinde)

**Kök Sebep**: `header.php` dosyasında line 608'de `$content` değişkeni tanımlı değil.

**Etki**: Bu hata view rendering sırasında oluşuyor ve 500 hatasına neden olabilir.

---

### 5. Cache Unserialize Hatası

**Durum**: ⚠️ **DÜŞÜK** - Production'da görülüyor, ancak graceful fallback var

**Log Örnekleri**:
```
[2025-11-23 00:07:11] [ERROR] unserialize(): Error at offset 0 of 106 bytes | Context: {"type":"Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/src\/Lib\/Cache.php","line":472}
```

**Sıklık**: 20+ kez görüldü (2025-11-23 tarihinde)

**Kök Sebep**: Corrupted cache dosyaları. ROUND 50'de graceful fallback eklendi, bu hatalar artık 500'e dönüşmüyor.

**Etki**: Bu hatalar artık 500'e dönüşmüyor, ancak log spam'i oluşturuyor.

---

## Öncelik Sıralaması

1. **`kozmos_is_https()` redeclare hatası** - FATAL ERROR, ilk login'de 500 hatasına neden oluyor
2. **Session cookie params hatası** - WARNING/ERROR, error handler tarafından exception'a dönüştürülüyorsa 500'e neden olabilir
3. **Undefined variable `$content`** - View rendering sırasında 500'e neden olabilir
4. **Auth flow redirect loop** - Login sonrası çok sayıda request yapılıyor, bu da 500'e neden olabilir
5. **Cache unserialize hatası** - Graceful fallback var, düşük öncelik

---

## Sonuç ve Öneriler

### En Olası Kök Sebep

**`kozmos_is_https()` redeclare hatası** muhtemelen ilk login'de 500 hatasına neden oluyor. Bu fatal error, `config/config.php` dosyasının birden fazla kez include edilmesi nedeniyle oluşuyor. İkinci request'te (F5) muhtemelen cache veya farklı bir kod path'i kullanıldığı için hata oluşmuyor.

### Önerilen Aksiyonlar

1. **STAGE 1**: Login akışına detaylı enstrümantasyon ekle
2. **STAGE 2**: Lokal test yap ve logları topla
3. **STAGE 3**: Kök sebep hipotezini doğrula (özellikle `kozmos_is_https()` redeclare hatası)
4. **STAGE 4**: Kalıcı çözümü uygula (production'da eski kod çalışıyor olabilir, deploy kontrolü yap)

---

## Notlar

- Production'da hala eski kod çalışıyor olabilir (ROUND 51 ve ROUND 52 değişiklikleri deploy edilmemiş)
- `r52_view_fatal.log` dosyası henüz oluşturulmamış (view rendering fatal error'ları loglanmıyor)
- Auth flow log'larında redirect loop pattern'i görülüyor

---

**Sonraki Adım**: STAGE 1 - Login Akışı Enstrümantasyonu

