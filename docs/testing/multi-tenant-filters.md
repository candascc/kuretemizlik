# Multi-Tenant Filtre Test Senaryoları

Bu doküman, yeni şirket seçicilerinin doğru çalıştığını manuel olarak doğrulamak için kullanılacak adımları içerir.

## Ön Koşullar
- En az iki şirket ve bu şirketlere bağlı test verileri (işler, müşteriler, finans kayıtları vb.).
- `tenant.switch` yetkisine sahip bir kullanıcı (varsayılan olarak SUPERADMIN veya ADMIN).
- yetkisi olmayan bir kullanıcı (ör. OPERATOR) referans için.

## Test Adımları

### 1. Yetkili Kullanıcı ile Şirket Değiştirme
1. **Giriş:** SUPERADMIN/ADMIN hesabı ile panelde oturum açın.
2. `/jobs`, `/customers`, `/finance`, `/appointments`, `/recurring` sayfalarına gidin.
3. Filtre panelinde “Şirket” açılır menüsünün göründüğünü doğrulayın.
4. Farklı şirketler seçerek:
   - Liste sonuçlarının yalnızca ilgili şirkete ait kayıtlarla güncellendiğini,
   - URL’de `company_filter` parametresinin güncellendiğini,
   - Export linklerinin (varsa) bu parametreyi taşıdığını doğrulayın.

### 2. Yetkisiz Kullanıcı Davranışı
1. OPERATOR gibi yetkisi olmayan bir hesapla giriş yapın.
2. Aynı sayfalarda şirket seçicisinin görünmediğini ve başka şirketlere ait kayıtların listelenmediğini doğrulayın.
3. URL’ye manuel olarak `company_filter` parametresi eklemeye çalışın; sonuçların yine mevcut şirketle sınırlı kaldığını kontrol edin.

### 3. Periyodik İşler Listesi
1. SUPERADMIN olarak `/recurring` sayfasında şirket seçicisini kullanın.
2. Tablo ve istatistik kartlarının seçilen şirket verileriyle senkronize olduğunu doğrulayın.
3. Filtre clear edildiğinde tüm şirketler için sonuçların döndüğünü kontrol edin.

### 4. Export Senaryoları
1. Jobs ve Customers export linklerini kullanarak CSV/Excel çıkarın.
2. Dosyalarda sadece seçili şirkete ait satırların yer aldığını doğrulayın.
3. Farklı şirketler için export alarak karşılaştırın.

### 5. Finans Toplu İşlemler
1. `/finance` sayfasında şirket filtrelemek için dropdown kullanın.
2. Günlük/haftalık/aylık özet kartlarının seçilen şirket bazlı güncellendiğini kontrol edin.

### 6. Geri Dönüş / Hata
1. Filtre alanını boşaltıp formu gönderin; tüm şirket verilerinin listelendiğini görün.
2. Yanlış bir `company_filter` değeri gönderildiğinde uygulamanın güvenli şekilde default şirkete döndüğünü kontrol edin (hata veya veri sızıntısı olmamalı).

## Beklenen Sonuç
Tüm sayfalarda şirket seçici yalnızca yetkili kullanıcılara görünmeli, liste/export çıktıları seçilen şirkete göre sınırlanmalı ve yetkisiz kullanıcılar parametre eklese bile başka şirketlere erişememelidir.

