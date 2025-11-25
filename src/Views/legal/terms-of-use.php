<?php
/**
 * Terms of Use Page
 * ROUND 31: Created for legal pages hardening
 */
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Kullanım Şartları</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-file-contract mr-3 text-primary-600"></i>
            Kullanım Şartları
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Platform kullanım koşulları ve kuralları</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Content -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 space-y-6">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Genel Hükümler</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Bu kullanım şartları, Küre Temizlik iş takip sistemi platformunun kullanımına ilişkin 
                    kuralları belirlemektedir. Platformu kullanarak bu şartları kabul etmiş sayılırsınız.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. Kullanıcı Sorumlulukları</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Platformu kullanırken, doğru ve güncel bilgiler sağlamak, güvenlik önlemlerine uymak 
                    ve platformu yasalara aykırı amaçlarla kullanmamakla yükümlüsünüz.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. Hizmet Kapsamı</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Platform, temizlik hizmetleri yönetimi, randevu takibi, müşteri yönetimi ve raporlama 
                    işlevlerini sunmaktadır. Hizmetler, teknik imkanlar dahilinde kesintisiz olarak 
                    sunulmaya çalışılmaktadır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Fikri Mülkiyet</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Platform içeriği, tasarımı ve yazılımı Küre Temizlik'e aittir. İzinsiz kopyalama, 
                    dağıtma veya kullanım yasaktır.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">5. Sorumluluk Sınırlaması</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Platform, teknik hatalar, kesintiler veya veri kayıplarından kaynaklanan zararlardan 
                    sorumlu tutulamaz. Kullanıcılar, verilerini düzenli olarak yedeklemekle yükümlüdür.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">6. Değişiklikler</h2>
                <p class="text-gray-700 dark:text-gray-300">
                    Bu kullanım şartları, önceden haber verilmeksizin değiştirilebilir. Değişiklikler, 
                    platform üzerinde yayınlandığı tarihten itibaren geçerlidir.
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

