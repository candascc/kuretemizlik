# Recurring Ücret Alanı – Ön Analiz (2025-11-15)

## 1. Ön Uç Taraması
- `app/assets/js/payment-validation.js` tüm `input[name*="payment"]` ve `input[name*="amount"]` alanlarını otomatik olarak izliyor. Her input için doğrudan en yakın `form` seçiliyor ve formun `dataset.paymentSkip !== 'true'` olması bekleniyor.
- `recurring/form.php` içinde isimlerinde `amount` geçen üç alan var: `default_total_amount`, `monthly_amount`, `contract_total_amount`. Sayfa tek bir `<form>` içinde ve `data-payment-skip="true"` attribüsüne sahip.
- Validator, her izlenen input için aynı formdaki ilk `input[name*="total"]` veya `[data-total-amount]` elemanını “toplam tutar” olarak kabul ediyor. Recurring formunda ilk `input[name*="total"]`, `default_total_amount` (per-job ücreti) olduğu için `monthly_amount` ve `contract_total_amount` alanları da yanlışlıkla bu değeri “toplam” olarak görüyor.
- Kullanıcı `monthly_amount > 0` girdiğinde, `totalInput` olarak seçilen `default_total_amount` varsayılan 0 olduğundan validator “Ödeme tutarı (X) toplam tutardan (0)” mesajını gösteriyor ve değeri 0’a geri çekiyor.
- Tarama sırasında PaymentValidation’ın forma bağlandığını doğrulayan tek davranış bu; başka frontend scripti aylık/kontrat alanlarını sıfırlamıyor. `onPricingModelChange()` fonksiyonunun sıfırlama yapan eski sürümü tekrar eklenmişti ve kaldırıldı (artık sadece `autoSave()` çağrılıyor).

## 2. Beklenen vs. Gerçek Davranış
- **Beklenen:** `data-payment-skip="true"` sayesinde recurring formundaki `monthly_amount` ve `contract_total_amount` alanlarının PaymentValidation’dan muaf olması, her modelin kendi tutarını serbestçe kabul etmesi.
- **Gerçek:** Validator hâlâ tetiklenip “Ödeme tutarı toplam tutardan fazla” uyarısı ve otomatik düzeltme mesajını gösteriyor. Formdaki `data-payment-skip="true"` attribüsü olmasına rağmen, validator bu alanlara bağlanmaya devam ediyor. Bunun iki olası nedeni:
  - Sayfada PaymentValidation’ın bağlandığı sırada `form.dataset.paymentSkip` boş geliyor (ör. form wrapper’ı farklı ya da attribute render edilmemiş durumda).
  - Veya ileride eklenecek başka bileşenler alanları yeniden DOM’a taşıdığı için `closest('form')` farklı bir node döndürerek `data-payment-skip`’i görmüyor.
- `totalInput` seçimi form içinde ilk `name*="total"` elemanını döndüğü için, PER-MONTH / TOTAL_CONTRACT modlarında daima per-job alanına referans veriliyor. Bu davranış validator skip edilse bile hatalı.

## 3. İlk Sonuç
- Sıfırlama sorunu iki katmanlı:
  1. **Bağlantı Kapsamı:** PaymentValidation, `data-payment-skip` atribüsüne rağmen recurring formun alanlarını hâlâ izliyor.
  2. **Toplam Alanı Tespiti:** `monthly_amount` ve `contract_total_amount` için gerçek karşılaştırma referansı olmadığı için validator `default_total_amount` değerini “toplam” sanıyor.
- Bir sonraki adımda backend akışı (controller/model) loglanacak, ardından form + validator davranışı adım adım yeniden üretilecektir.

## 4. Backend İncelemesi (Özet)
- `RecurringJobController::store/update` çağrıları her pricing modeline göre `Utils::normalizeMoney()` ile gelen değerleri parse ediyor ve yalnızca aktif modele karşılık gelen alanları saklıyor. PER_JOB dışındaki modellerde `default_total_amount` PHP tarafında `null` olarak gönderiliyor.
- `RecurringJob` modeli `create()` / `update()` sırasında `default_total_amount`, `monthly_amount`, `contract_total_amount` alanlarını `float`’a cast ediyor; `null` gelen değerler `isset()` kontrolü olmadığı için 0’a set ediliyor ancak bu yalnızca formda ilgili alan kullanılmadığı durumlar için beklenen davranış.
- Veritabanı şemasında `default_total_amount` için default 0 tanımlı; PER_MONTH / TOTAL_CONTRACT kayıtlarının 0 görünmesi normal. Bu yüzden pozitif bir değer girilip POST edildiğinde backend tarafında tekrar sıfırlayan bir mantık bulunmuyor.

## 5. Reprodüksiyon ve Loglama
- `assets/js/payment-validation.js` artık `window.PAYMENT_VALIDATION_DEBUG === true` olduğunda hangi inputların izlendiğini ve hangi “toplam” alanına bağlandığını `console.debug` ile raporluyor. Recurring formu açıldığında (sayfa kaynağına eklenen küçük script ile) bu bayrak otomatik olarak `true`.
- Üç fiyat modeli için izleme adımları:
  1. **PER_JOB:** `default_total_amount` alanına 100 yazıldığında konsolda `[payment-validation] validate` satırları görünür; toplam alanı `default_total_amount` olur. Girilen değer backend logunda `[RecurringPricing][store]` ile doğrulanır.
  2. **PER_MONTH:** `monthly_amount` alanına >0 değer girildiğinde konsol çıktısı aynı validatorın bu alanı `default_total_amount` ile karşılaştırdığını gösterir (totalField: `default_total_amount`). Bu, kullanıcı tarafındaki sıfırlamanın tetiklendiği noktayı doğrular.
  3. **TOTAL_CONTRACT:** `contract_total_amount` alanı için hem JS tarafı (hangi total alanını kullandığı) hem de backend `error_log` kaydı ile POST edilen değerin normalize edildiği görülür.
- PHP tarafında `RecurringJobController::store/update` metotları `APP_DEBUG` açıkken `error_log`’a `[RecurringPricing][store|update]` prefiksiyle raw ve normalize edilmiş değerleri yazıyor. Böylece formda girilen rakamların backend’e hangi değerlerle ulaştığı takip edilebiliyor.

## 6. Kök Sebep
- PaymentValidation formu skip etmesi gerekirken, mevcut üretim çıktısında `form.dataset.paymentSkip` değeri gözlemlenebilir şekilde `undefined` geliyor. Bu sebeple validator recurring formuna bağlanıyor.
- Bağlandıktan sonra `form.querySelector('input[name*=\"total\"], ...')` ifadesi DOM’daki ilk eşleşmeyi, yani `default_total_amount` alanını döndürüyor. PER_MONTH ve TOTAL_CONTRACT alanlarının kendi “toplam” referansı olmadığı için validator her seferinde per-job alanını ölçüt alıyor ve >0 değerlerin tamamını 0’a çekiyor.
- Backend tarafında pozitif değerler herhangi bir yerde sıfırlanmadığı için, sorunun tek kaynağı PaymentValidation’ın yanlış alanlara bağlanması ve yanlış “toplam” seçmesi olarak izole edildi.

