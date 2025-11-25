# Çoklu Şirket Arayüz Rehberi

Bu doküman, yönetici panelinde yeni eklenen çoklu şirket filtrelerini ve kullanım şeklini özetler.

## Kimler Şirket Seçebilir?

- `tenant.switch` yetkisine sahip kullanıcılar (varsayılan olarak **SUPERADMIN** ve **ADMIN**) şirketler arasında geçiş yapabilir.
- Yetki, rol tabanlı olarak atanır; gerekirse `permissions` tablosu üzerinden diğer rollere de eklenebilir.
- Yetkisi olmayan kullanıcılar yalnızca kendi şirketlerinin verilerini görür ve filtre alanı gösterilmez.

## Güncellenen Sayfalar

- İş listesi (`/jobs`)
- Müşteri listesi (`/customers`)
- Finans kayıtları (`/finance`)
- Randevular (`/appointments`)
- Periyodik işler (`/recurring`)

Her sayfanın filtre panelinde “Şirket” açılır menüsü yer alır. Seçim değiştirildiğinde sorgular otomatik olarak ilgili şirket verisiyle yenilenir; export işlemleri de aynı filtreyi kullanır.

## Kullanım Notları

1. **Filtreleme:** Şirket seçildiğinde URL’ye `company_filter` parametresi eklenir. Kombine filtrelerde (tarih, müşteri vb.) ek bir işlem gerekmez.
2. **Varsayılan Davranış:** Yetkili kullanıcı filtreyi temizlediğinde tüm şirketler görüntülenir. Yetkisi olmayanlar `company_filter` parametresini gönderse bile kapsamları daraltılmaz.
3. **Export / Raporlama:** CSV/Excel export linkleri aktif filtre parametrelerini aynen taşır. Finans ve müşteri exportları yalnızca seçilen şirkete ait kayıtları içerir.
4. **Güvenlik:** Arka planda `CompanyScope` yalnızca yetkisi olan kullanıcıların `company_filter` parametresini kullanmasına izin verir. Her insert/update işleminde `company_id` doğrulaması yapıldığından yanlışlıkla başka şirkete veri yazılması engellenir.
5. **İşler Ekranı:** Varsayılan olarak yalnızca bugün ve sonrası listelenir; “Geçmiş İşleri Göster” düğmesi `show_past=1` parametresiyle geçmişe erişim sağlar ve diğer filtreler korunur.

## Sık Sorulanlar

- **Soru:** Neden admin rolü şirket değiştirebiliyor?
  **Yanıt:** Operasyon yöneticilerinin birden fazla şirketi yönetmesi gerekiyor. Eğer bu davranış istenmiyorsa `permissions` tablosundan `tenant.switch` yetkisi kaldırılabilir.

- **Soru:** Yeni rol ekledim, şirket seçicisi çıkmıyor.
  **Yanıt:** Yeni rol için `tenant.switch` yetkisini atadığınızdan ve kullanıcı oturumunu kapatıp açtığından emin olun (permission cache’i yenilenir).

- **Soru:** Export dosyası hâlâ tüm verileri gösteriyor.
  **Yanıt:** Export URL’sinde `company_filter` parametresinin taşındığını doğrulayın. Aksi durumda UI’daki filtre formuna bakın veya sayfayı F5 ile yenileyin.

- **Soru:** Son kullanıcıya bu filtreyi nasıl anlatmalıyım?
  **Yanıt:** Eğitim sırasında aşağıdaki kısa akışı kullanabilirsiniz:
    1. Dashboard veya rapor ekranında üstteki “Şirket Bağlamı” rozetini gösterin.
    2. Gerekirse URL’ye `company_filter=ŞirketID` parametresi ekleyerek nasıl geçiş yapılacağını canlı gösterin.
    3. Filtre uygulandıktan sonra tüm listelerin ve export dosyalarının aynı şirketle sınırlı olacağını vurgulayın.

## İlgili Testler

- `docs/testing/multi-tenant-filters.md` dokümanındaki manuel test senaryolarını uygulayarak filtrelerin doğru çalıştığını doğrulayın.


