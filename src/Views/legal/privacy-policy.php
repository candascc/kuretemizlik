<?php
/**
 * Privacy Policy Page
 * ROUND 31: Created for legal pages hardening
 */
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Gizlilik Politikası</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-shield-alt mr-3 text-primary-600"></i>
            Gizlilik Politikası
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Kişisel verilerinizin korunması hakkında bilgiler</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Content -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 space-y-6">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Veri Toplama</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Küre Temizlik olarak, hizmetlerimizi sunabilmek için gerekli olan kişisel verilerinizi topluyoruz. 
                    Bu veriler arasında ad, soyad, telefon numarası, e-posta adresi ve adres bilgileriniz bulunmaktadır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. Veri Kullanımı</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Toplanan kişisel verileriniz, hizmetlerimizin sunulması, randevu yönetimi, faturalandırma ve 
                    müşteri ilişkileri yönetimi amacıyla kullanılmaktadır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. Veri Güvenliği</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Kişisel verilerinizin güvenliği için teknik ve idari önlemler almaktayız. Verileriniz, 
                    yetkisiz erişim, kayıp veya tahribata karşı korunmaktadır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Veri Paylaşımı</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Kişisel verileriniz, yasal yükümlülüklerimiz dışında üçüncü taraflarla paylaşılmamaktadır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">5. Haklarınız</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    KVKK kapsamında, kişisel verileriniz hakkında bilgi alma, düzeltme, silme ve itiraz etme 
                    haklarınız bulunmaktadır. Bu haklarınızı kullanmak için bizimle iletişime geçebilirsiniz.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">6. İletişim</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Gizlilik politikamız hakkında sorularınız için bizimle iletişime geçebilirsiniz.
                </p>
            </section>

            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Son güncelleme: <?= date('d.m.Y') ?>
                </p>
            </div>
        </div>
    </div>
</div>

