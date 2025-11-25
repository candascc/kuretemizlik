<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/appointments') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-calendar"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Randevu Detayı</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-calendar mr-3 text-primary-600"></i>
                <?= e($appointment['title']) ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Randevu detayları ve bilgileri</p>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="flex space-x-3">
            <a href="<?= base_url("/appointments/{$appointment['id']}/edit") ?>" class="inline-flex items-center px-4 py-2 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                <i class="fas fa-edit mr-2"></i> Düzenle
            </a>
            <form method="POST" action="<?= base_url("/appointments/{$appointment['id']}/delete") ?>" class="inline" onsubmit="return confirm('Bu randevuyu silmek istediğinizden emin misiniz?')">
                <?= CSRF::field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <i class="fas fa-trash mr-2"></i> Sil
                </button>
            </form>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
    </div>

    <!-- ===== KOZMOS_OPERATOR_READONLY: hide status update form for operator (begin) -->
    <?php if (Auth::role() !== 'OPERATOR'): ?>
    <!-- Status Update Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-sync-alt mr-2 text-primary-600"></i>Durum Güncelle
        </h3>
        <form method="POST" action="<?= base_url("/appointments/{$appointment['id']}/status") ?>" class="flex items-center space-x-4">
            <?= CSRF::token() ?>
            <select name="status" class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $appointment['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Güncelle
            </button>
        </form>
    </div>
    <?php endif; ?>
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide status update form for operator (end) -->

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Randevu Bilgileri -->
        <div class="bg-white rounded-md shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Randevu Bilgileri</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Müşteri</label>
                    <p class="mt-1 text-sm text-gray-900"><?= e($appointment['customer_name']) ?></p>
                    <?php if ($appointment['customer_phone']): ?>
                        <p class="text-sm text-gray-500"><?= e($appointment['customer_phone']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Tarih ve Saat</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= Utils::formatDate($appointment['appointment_date']) ?> 
                        <?= $appointment['start_time'] ?> - <?= $appointment['end_time'] ?? 'Belirtilmemiş' ?>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Hizmet</label>
                    <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($appointment['service_name'] ?? 'Belirtilmemiş') ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Atanan Kişi</label>
                    <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($appointment['assigned_user'] ?? 'Belirtilmemiş') ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Durum</label>
                    <?php
                    $statusColors = [
                        'SCHEDULED' => 'bg-yellow-100 text-yellow-800',
                        'CONFIRMED' => 'bg-blue-100 text-blue-800',
                        'COMPLETED' => 'bg-green-100 text-green-800',
                        'CANCELLED' => 'bg-red-100 text-red-800',
                        'NO_SHOW' => 'bg-gray-100 text-gray-800'
                    ];
                    $colorClass = $statusColors[$appointment['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $colorClass ?> mt-1">
                        <?= $statuses[$appointment['status']] ?? $appointment['status'] ?>
                    </span>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Öncelik</label>
                    <?php
                    $priorityColors = [
                        'LOW' => 'bg-green-100 text-green-800',
                        'MEDIUM' => 'bg-yellow-100 text-yellow-800',
                        'HIGH' => 'bg-orange-100 text-orange-800',
                        'URGENT' => 'bg-red-100 text-red-800'
                    ];
                    $priorityColorClass = $priorityColors[$appointment['priority']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $priorityColorClass ?> mt-1">
                        <?= $priorities[$appointment['priority']] ?? $appointment['priority'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Açıklama ve Notlar -->
        <div class="bg-white rounded-md shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Detaylar</h3>
            <div class="space-y-4">
                <?php if ($appointment['description']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Açıklama</label>
                        <p class="mt-1 text-sm text-gray-900"><?= nl2br(e($appointment['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($appointment['notes']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Notlar</label>
                        <p class="mt-1 text-sm text-gray-900"><?= nl2br(e($appointment['notes'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Oluşturulma Tarihi</label>
                    <p class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($appointment['created_at']) ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Son Güncelleme</label>
                    <p class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($appointment['updated_at']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Hızlı İşlemler -->
    <div class="bg-white rounded-md shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Hızlı İşlemler</h3>
        <div class="flex flex-wrap gap-3">
            <a href="<?= base_url("/customers/{$appointment['customer_id']}") ?>" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                <i class="fas fa-user mr-2"></i> Müşteri Detayı
            </a>
            <?php if ($appointment['service_id']): ?>
                <a href="<?= base_url("/services/{$appointment['service_id']}") ?>" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-cog mr-2"></i> Hizmet Detayı
                </a>
            <?php endif; ?>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide new appointment button for operator (begin) -->
            <?php if (Auth::role() !== 'OPERATOR'): ?>
            <a href="<?= base_url('/appointments/new') ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i> Yeni Randevu
            </a>
            <?php endif; ?>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide new appointment button for operator (end) -->
        </div>
    </div>
</div>