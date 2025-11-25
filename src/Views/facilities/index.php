<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-hotel mr-3 text-primary-600"></i>
                Rezervasyon Alanları
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Ortak kullanım alanları yönetimi</p>
        </div>
        <a href="<?= base_url('/facilities/new' . ($buildingId ? '?building_id=' . $buildingId : '')) ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Alan Ekle
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina</label>
                <select name="building_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Binalar</option>
                    <?php foreach ($buildings as $bld): ?>
                        <option value="<?= $bld['id'] ?>" <?= ($buildingId ?? '') == $bld['id'] ? 'selected' : '' ?>>
                            <?= e($bld['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Facilities List -->
    <?php if (empty($facilities)): ?>
        <div class="bg-white dark:bg-gray-800 p-12 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 text-center">
            <i class="fas fa-hotel text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz rezervasyon alanı eklenmemiş</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Ortak kullanım alanları ekleyerek rezervasyon sistemini başlatın</p>
            <a href="<?= base_url('/facilities/new') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                <i class="fas fa-plus mr-2"></i>İlk Alan Ekle
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($facilities as $facility): ?>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                <?= e($facility['facility_name']) ?>
                            </h3>
                            <?php
                            $typeColors = [
                                'pool' => 'bg-blue-100 text-blue-800',
                                'gym' => 'bg-red-100 text-red-800',
                                'meeting' => 'bg-green-100 text-green-800',
                                'parking' => 'bg-gray-100 text-gray-800',
                                'playground' => 'bg-yellow-100 text-yellow-800',
                                'other' => 'bg-purple-100 text-purple-800'
                            ];
                            $typeTexts = [
                                'pool' => 'Havuz',
                                'gym' => 'Spor Salonu',
                                'meeting' => 'Toplantı Salonu',
                                'parking' => 'Otopark',
                                'playground' => 'Oyun Alanı',
                                'other' => 'Diğer'
                            ];
                            $type = $facility['facility_type'] ?? 'other';
                            ?>
                            <span class="inline-block px-3 py-1 text-xs font-medium rounded-full <?= $typeColors[$type] ?? $typeColors['other'] ?>">
                                <?= $typeTexts[$type] ?? 'Diğer' ?>
                            </span>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $facility['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                            <?= $facility['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>

                    <?php if (!empty($facility['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= e($facility['description']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <?php if ($facility['capacity']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-users mr-2 w-4"></i>
                                Kapasite: <?= $facility['capacity'] ?> kişi
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($facility['hourly_rate'] > 0): ?>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 w-4"></i>
                                Saatlik: <?= number_format($facility['hourly_rate'], 2) ?> ₺
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($facility['daily_rate'] > 0): ?>
                            <div class="flex items-center">
                                <i class="fas fa-calendar-day mr-2 w-4"></i>
                                Günlük: <?= number_format($facility['daily_rate'], 2) ?> ₺
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($facility['requires_approval']): ?>
                            <div class="flex items-center text-orange-600">
                                <i class="fas fa-check-circle mr-2 w-4"></i>
                                Onay Gerekli
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex space-x-2">
                        <a href="<?= base_url('/facilities/' . $facility['id'] . '/edit') ?>" 
                           class="flex-1 text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Düzenle
                        </a>
                        <a href="<?= base_url('/reservations/new?facility_id=' . $facility['id']) ?>" 
                           class="flex-1 text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-calendar-plus mr-1"></i>Rezervasyon
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

