# Jobs & Recurring Flows – Manuel Test Sonuçları (2025-11-15)

## Ortam
- Build: @app (multi-tenant branch)
- Tarayıcı: Chrome 119
- Roller: `ADMIN` (tüm izinler), `STAFF` (salt okunur)

## Testler

1. **İş listesi varsayılanı / show_past toggle**
   - ADMIN olarak `/jobs` açıldığında yalnızca bugün ve sonrası listelendi, sıralama artan (en yakın iş üstte).
   - “Geçmiş İşleri Göster” düğmesi tıklandığında URL `show_past=1` ekledi, liste ters (DESC) oldu.
   - Toggle tekrarlandığında filtreler (status + company_filter) korunarak geleceğe döndü.

2. **RBAC görünürlüğü**
   - ADMIN kullanıcı bulk işlemleri, seçim kutuları ve yeni iş butonlarını gördü.
   - STAFF rolü aynı sayfada yalnızca görüntüleme rozetini gördü; bulk bar ve aksiyon ikonları gizlendi.

3. **Periyodik iş – PER_JOB modeli**
   - Formda “İş Başına Tutar” alanına 1500 girildi; fiyat modeli değiştirilip geri gelindiğinde değer korunmaya devam etti.
   - Kaydetme sırasında backend değerini `default_total_amount=1500` olarak sakladı.

4. **Periyodik iş – PER_MONTH modeli**
   - Aylık tutar alanına 5000 TL girildi, kaydetme doğrulandı; yanlışlıkla 0 girildiğinde hem frontend hem backend engelledi.

5. **Periyodik iş – TOTAL_CONTRACT modeli**
   - Sözleşme tutarı >0 girildiğinde kayıt başarılı; bitiş tarihi kaldırıldığında beklenen hata mesajı görüldü.

## Sonuç
- Jobs ekranı artık varsayılan olarak gelecekteki işleri gösteriyor; toggle ve RBAC kontrolleri beklendiği gibi çalışıyor.
- Recurring formunda üç fiyat modeli de >0 tutar kabul ediyor; değerler model değiştirirken korunuyor ve backend doğru kaydediyor.

