# Yeni Müşteri → Adres → İş → Ücret/Ödeme Akışı

Bu belge, yeni müşteri oluşturup adres ekleyerek iş oluşturma ve ödeme/finans senkronunu özetler.

## Adımlar
1. İş oluştur sayfasında müşteri alanına yazın, “Yeni Müşteri Ekle” ile modalı açın.
2. Müşteri adı (zorunlu), telefon/e-posta (opsiyonel) girin ve Kaydet’e basın.
3. Müşteri seçilince adres listesinden adres seçin veya “Yeni Adres Ekle” ile ekleyin.
4. Hizmeti seçin; başlangıç/bitiş tarih-saatini belirleyin.
5. Ücret alanına toplam tutarı girin. İsteğe bağlı “Yeni Ödeme” ve notu ekleyin.
6. Kaydedin. Ödeme eklendiyse `money_entries` tablosunda gelir kaydı oluşur.

## Klavye ve Erişilebilirlik
- Arama kutusu: Yukarı/Aşağı ok ile sonuçlar; Enter ile seç; Esc ile kapat.
- Modal: `Esc` kapatır, Tab odak tuzaklıdır, `İptal` veya dışına tıklama kapatır.

## Hata İletişimi
- API hataları kullanıcıya bildirim ile gösterilir; konsola minimal bilgi loglanır.

## Finans Senkronu
- Ödeme oluşturma/silme işlemleri idempotenttir (`external_ref=job_payment:{id}`).

## Notlar
- Müşteri arama 300ms debounce ve 20 sonuçla sınırlandırılmıştır; “Daha fazla yükle” ile devam edilir.


