<?php
    $remainingAmount = max(0, $fee['total_amount'] - $fee['paid_amount']);
    $isOverdue = $remainingAmount > 0 && strtotime($fee['due_date']) < time();
    $dueLabel = $isOverdue ? 'gecikme var' : 'gecikme yok';
    $dueBadgeClass = $isOverdue
        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'
        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200';

    $methodOptions = [
        'cash' => [
            'label' => 'Nakit',
            'icon' => 'fa-money-bill-wave',
            'description' => 'Site ofisine elden ödeme. Muhasebe kaydı anında güncellenir.',
            'eta' => 'Anında',
        ],
        'transfer' => [
            'label' => 'Havale / EFT',
            'icon' => 'fa-building-columns',
            'description' => 'Banka transferi ile ödeme. Dekontu not kısmına ekleyebilirsiniz.',
            'eta' => '1 iş günü',
        ],
        'card' => [
            'label' => 'Kredi Kartı',
            'icon' => 'fa-credit-card',
            'description' => 'POS veya sanal POS üzerinden ödeme. Onay sonrası makbuz e-postanıza gelir.',
            'eta' => 'Anında',
        ],
        'check' => [
            'label' => 'Çek',
            'icon' => 'fa-file-signature',
            'description' => 'Çek ile ödeme. Onay için muhasebe birimi ile iletişime geçilir.',
            'eta' => '3 iş günü',
        ],
    ];
?>

<div class="max-w-3xl mx-auto space-y-8">
    <!-- Header -->
    <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1.5">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-300">Aidat Ödeme Akışı</p>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Aidatınızı güvenle ödeyin</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Bu ay ödenecek tutar: <span class="font-semibold text-gray-900 dark:text-gray-100">₺<?= number_format($remainingAmount, 2, ',', '.') ?></span>
                <span class="ml-2 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $dueBadgeClass ?>">
                    <i class="fas <?= $isOverdue ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
                    <?= $dueLabel ?>
                </span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= base_url('/resident/fees') ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-arrow-left"></i>
                Geri Dön
            </a>
        </div>
    </header>

    <!-- Fee Summary -->
    <section class="grid grid-cols-1 gap-4 md:grid-cols-2" aria-label="Aidat özeti">
        <article class="rounded-2xl border border-primary-100 bg-primary-50 p-6 shadow-sm dark:border-primary-900/40 dark:bg-primary-950/30">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-300">Aidat Bilgileri</p>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($fee['fee_name']) ?></h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Dönem: <span class="font-medium text-gray-900 dark:text-gray-100"><?= e($fee['period']) ?></span>
                    </p>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Toplam</dt>
                            <dd class="text-base font-semibold text-gray-900 dark:text-gray-100">₺<?= number_format($fee['total_amount'], 2, ',', '.') ?></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Ödenen</dt>
                            <dd class="text-base font-semibold text-gray-900 dark:text-gray-100">₺<?= number_format($fee['paid_amount'], 2, ',', '.') ?></dd>
                        </div>
                    </dl>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-200">
                    <i class="fas fa-receipt text-lg"></i>
                </span>
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ödeme Durumu</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">₺<?= number_format($remainingAmount, 2, ',', '.') ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Kalan tutarınızı tercih ettiğiniz yöntemle birkaç adımda tamamlayın.
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        <i class="fas fa-calendar-day"></i>
                        Vade: <?= date('d.m.Y', strtotime($fee['due_date'])) ?>
                    </div>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    <i class="fas fa-hourglass-half text-lg"></i>
                </span>
            </div>
        </article>
    </section>

    <!-- Payment Form -->
    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg shadow-primary-900/5 dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Ödeme Bilgileri</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Tutarınızı kontrol edin, yöntem seçin ve makbuzunuzu alın. Form, klavye ve ekran okuyucu dostudur.
        </p>

        <form method="POST" class="mt-6 space-y-8" novalidate>
            <?= CSRF::field() ?>

            <div>
                <label for="amount" class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
                    Ödeme Tutarı
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Maks. ₺<?= number_format($remainingAmount, 2, ',', '.') ?></span>
                </label>
                <div class="relative mt-2 rounded-xl border border-gray-300 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/40 dark:border-gray-600 dark:focus-within:border-primary-400">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <span class="text-base font-semibold text-gray-500 dark:text-gray-400">₺</span>
                    </div>
                    <input
                        type="text"
                        name="amount"
                        id="amount"
                        value="<?= number_format($remainingAmount, 2, ',', '.') ?>"
                        inputmode="decimal"
                        pattern="^[0-9]+([.,][0-9]{1,2})?$"
                        data-max="<?= number_format($remainingAmount, 2, '.', '') ?>"
                        aria-describedby="amount-feedback"
                        aria-invalid="false"
                        class="block w-full rounded-xl border-0 bg-transparent py-4 pl-10 pr-4 text-lg font-semibold text-gray-900 outline-none transition placeholder:text-gray-400 focus:ring-0 dark:text-white"
                        placeholder="1.250,50"
                        required
                    >
                </div>
                <p id="amount-feedback" class="mt-2 text-sm text-gray-500 dark:text-gray-400" aria-live="polite">
                    Nokta veya virgül kullanabilirsiniz. Maksimum: ₺<?= number_format($remainingAmount, 2, ',', '.') ?>
                </p>
                <noscript>
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">JavaScript devre dışı ise tutarı 1.234,56 formatında girin.</p>
                </noscript>
            </div>

            <fieldset class="space-y-4" aria-labelledby="payment-method-label">
                <legend id="payment-method-label" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Ödeme Yöntemi
                </legend>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <?php foreach ($methodOptions as $methodValue => $meta): ?>
                        <label class="group relative flex cursor-pointer flex-col gap-3 rounded-2xl border border-gray-200 p-4 transition hover:border-primary-400 hover:shadow-sm focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/40 dark:border-gray-700 dark:hover:border-primary-500/60">
                            <input
                                type="radio"
                                name="payment_method"
                                value="<?= e($methodValue) ?>"
                                class="peer absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                required
                            >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-700 transition group-hover:bg-primary-50 group-hover:text-primary-600 peer-checked:bg-primary-100 peer-checked:text-primary-600 dark:bg-gray-700 dark:text-gray-200 dark:group-hover:bg-primary-900/30 dark:group-hover:text-primary-300 dark:peer-checked:bg-primary-900/40 dark:peer-checked:text-primary-200">
                                        <i class="fas <?= e($meta['icon']) ?> text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white"><?= e($meta['label']) ?></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($meta['description']) ?></p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400 transition group-hover:text-primary-500 peer-checked:text-primary-500 dark:text-gray-500 dark:group-hover:text-primary-300 dark:peer-checked:text-primary-300">
                                    <?= e($meta['eta']) ?>
                                </span>
                            </div>
                            <span class="pointer-events-none absolute inset-0 rounded-2xl border-2 border-transparent transition peer-checked:border-primary-500"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Yönteme göre onay süresi değişebilir. Makbuz e-posta ve SMS ile paylaşılır.
                </p>
            </fieldset>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Notlar (Opsiyonel)
                </label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="3"
                    class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    placeholder="Ödeme açıklaması, dekont numarası veya özel notlarınızı ekleyin..."
                ></textarea>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="<?= base_url('/resident/fees') ?>"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 sm:w-auto"
                >
                    İptal et
                </a>
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto"
                >
                    <i class="fas fa-lock"></i>
                    Güvenli Ödeme Yap
                </button>
            </div>
        </form>
    </section>

    <!-- Payment Info -->
    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-900/40 dark:bg-blue-950/30">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">
                    <i class="fas fa-shield-check text-lg"></i>
                </span>
                <div>
                    <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100">Ödeme güvenliği ve makbuz</h3>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                        Ödeme kaydınız otomatik olarak yönetim muhasebesine işlenir ve makbuzunuz dijital olarak saklanır.
                    </p>
                </div>
            </div>
            <ul class="grid grid-cols-1 gap-2 text-sm text-blue-800 dark:text-blue-200 md:w-1/2">
                <li class="inline-flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Ödeme sonrası durumunuz ve bakiye hesapları anında güncellenir.
                </li>
                <li class="inline-flex items-center gap-2">
                    <i class="fas fa-envelope-open-text"></i>
                    Makbuz e-posta ve SMS ile paylaşılır; ayrıca panelden indirebilirsiniz.
                </li>
                <li class="inline-flex items-center gap-2">
                    <i class="fas fa-headset"></i>
                    Herhangi bir sorun için site yönetimi ile dilediğiniz zaman iletişime geçebilirsiniz.
                </li>
            </ul>
        </div>
    </section>
</div>

<script>
    (function () {
        const amountInput = document.querySelector('#amount');
        if (!amountInput) {
            return;
        }

        const feedback = document.querySelector('#amount-feedback');
        const form = amountInput.closest('form');
        const submitButton = form ? form.querySelector('button[type="submit"]') : null;
        const methodRadios = form ? form.querySelectorAll('input[name="payment_method"]') : [];
        const maxValue = parseFloat(amountInput.dataset.max || '0');
        const formatter = (typeof Intl !== 'undefined' && typeof Intl.NumberFormat === 'function')
            ? new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : { format: (value) => (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2) };

        const normalize = (value) => {
            if (!value) {
                return 0;
            }
            let raw = value.toString().replace(/\s/g, '');
            const hasComma = raw.includes(',');
            const hasDot = raw.includes('.');
            if (hasComma && hasDot) {
                raw = raw.replace(/\./g, '').replace(',', '.');
            } else if (hasComma) {
                raw = raw.replace(',', '.');
            }
            raw = raw.replace(/[^0-9.]/g, '');
            return parseFloat(raw) || 0;
        };

        const updateButtonState = (isAmountValid) => {
            const hasMethod = Array.from(methodRadios).some((radio) => radio.checked);
            const isEnabled = isAmountValid && hasMethod;

            if (submitButton) {
                submitButton.disabled = !isEnabled;
                submitButton.classList.toggle('opacity-60', !isEnabled);
                submitButton.classList.toggle('cursor-not-allowed', !isEnabled);
            }
        };

        const updateState = () => {
            const normalized = normalize(amountInput.value);
            const formatted = normalized > 0 ? formatter.format(normalized) : '';
            const caretEnd = amountInput === document.activeElement;
            amountInput.value = formatted;

            const isValid = normalized > 0 && normalized <= maxValue;
            if (feedback) {
                if (!isValid) {
                    const message = normalized > maxValue
                        ? `Maksimum ödenebilecek tutar ₺${formatter.format(maxValue)}.`
                        : 'Lütfen geçerli bir tutar girin.';
                    feedback.textContent = message;
                    feedback.classList.remove('text-gray-500', 'dark:text-gray-400');
                    feedback.classList.add('text-red-600', 'dark:text-red-400');
                } else {
                    feedback.textContent = `Nokta veya virgül kullanabilirsiniz. Maksimum: ₺${formatter.format(maxValue)}`;
                    feedback.classList.add('text-gray-500', 'dark:text-gray-400');
                    feedback.classList.remove('text-red-600', 'dark:text-red-400');
                }
            }

            amountInput.setAttribute('aria-invalid', (!isValid).toString());
            if (caretEnd) {
                amountInput.selectionStart = amountInput.selectionEnd = amountInput.value.length;
            }

            updateButtonState(isValid);
            return normalized;
        };

        amountInput.addEventListener('input', updateState);
        amountInput.addEventListener('blur', updateState);
        updateState();

        methodRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                const normalized = normalize(amountInput.value);
                updateButtonState(normalized > 0 && normalized <= maxValue);
            });
        });

        if (form) {
            form.addEventListener('submit', () => {
                const normalValue = normalize(amountInput.value);
                amountInput.value = normalValue.toFixed(2);
            });
        }
    })();
</script>
