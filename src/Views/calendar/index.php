<div class="space-y-8" x-data="calendarApp()" aria-labelledby="calendar-title" role="region">
            <!-- Sayfa Başlığı -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Takvim</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">İşlerinizi takvim görünümünde yönetin ve planlayın</p>
                </div>
        <div class="flex flex-col gap-4">
            <!-- Filtreler -->
            <div class="flex flex-col sm:flex-row gap-3">
                <select x-model="filters.customer" @change="applyFilters()" 
                        class="px-3 sm:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    <option value="">Tüm Müşteriler</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['id'] ?>"><?= e($customer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select x-model="filters.service" @change="applyFilters()" 
                        class="px-3 sm:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    <option value="">Tüm Hizmetler</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service['id'] ?>"><?= e($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select x-model="filters.status" @change="applyFilters()" 
                        class="px-3 sm:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    <option value="">Tüm Durumlar</option>
                    <option value="SCHEDULED">Planlandı</option>
                    <option value="DONE">Tamamlandı</option>
                    <option value="CANCELLED">İptal</option>
                </select>
            </div>
            
            <!-- Kontrol Satırı -->
            <div class="flex flex-col sm:flex-row gap-3">
            
                <!-- Görünüm Değiştirici -->
                <div class="flex rounded-lg shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <a href="?view=day&date=<?= $date ?>" 
                       class="relative inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium transition-all duration-200 <?= $view === 'day' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-calendar-day mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline">Gün</span>
                    </a>
                    <a href="?view=week&date=<?= $date ?>" 
                       class="relative inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium transition-all duration-200 border-l border-r border-gray-200 dark:border-gray-700 <?= $view === 'week' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-calendar-week mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline">Hafta</span>
                    </a>
                    <a href="?view=month&date=<?= $date ?>" 
                       class="relative inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium transition-all duration-200 <?= $view === 'month' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-calendar mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline">Ay</span>
                    </a>
                </div>
                
                <!-- Tarih Gezinme -->
                <div class="flex rounded-lg shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden" role="group" aria-label="Tarih gezinme">
                    <a href="?view=<?= $view ?>&date=<?= date('Y-m-d', strtotime($date . ' -1 ' . ($view === 'day' ? 'day' : ($view === 'week' ? 'week' : 'month')))) ?>" 
                       class="relative inline-flex items-center px-2 sm:px-3 py-2 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="relative inline-flex items-center px-2 sm:px-4 py-2 bg-white dark:bg-gray-800 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 border-l border-r border-gray-200 dark:border-gray-700">
                        <?= Utils::formatDate($date, $view === 'day' ? 'd F Y' : ($view === 'week' ? 'd M' : 'F Y')) ?>
                    </span>
                    <a href="?view=<?= $view ?>&date=<?= date('Y-m-d', strtotime($date . ' +1 ' . ($view === 'day' ? 'day' : ($view === 'week' ? 'week' : 'month')))) ?>" 
                       class="relative inline-flex items-center px-2 sm:px-3 py-2 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <!-- Ekstralar: Bugün, Yoğunluk, Yazdır -->
                <div class="flex items-center gap-2">
                    <a href="?view=<?= $view ?>&date=<?= date('Y-m-d') ?>" class="inline-flex items-center px-3 py-2 text-sm rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <i class="fas fa-dot-circle mr-2 text-primary-600"></i>Bugün
                    </a>
                    <button type="button" @click="dense = !dense" class="inline-flex items-center px-3 py-2 text-sm rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" :aria-pressed="dense.toString()">
                        <i class="fas" :class="dense ? 'fa-compress-arrows-alt' : 'fa-expand-arrows-alt'"></i>
                        <span class="ml-2 hidden sm:inline" x-text="dense ? 'Sıkışık' : 'Geniş'"></span>
                    </button>
                    <button type="button" onclick="window.print()" class="inline-flex items-center px-3 py-2 text-sm rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <i class="fas fa-print mr-2"></i>Yazdır
                    </button>
                </div>
                
                <!-- Hızlı Ekle Butonu -->
                <button @click="showQuickAddModal = true" 
                        class="inline-flex items-center px-4 sm:px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <i class="fas fa-plus mr-1 sm:mr-2"></i>
                    <span class="hidden sm:inline">Hızlı Ekle</span>
                </button>
                <!-- Hızlı saat seçenekleri -->
                <div class="flex items-center gap-1">
                    <?php $quickTimes = ['09:00','13:00','17:00']; foreach ($quickTimes as $qt): ?>
                    <button type="button" @click="createAt('<?= $qt ?>')" class="px-2 py-1 text-xs rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <?= $qt ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Durum gösterge kutusu -->
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mt-2">
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"><i class="fas fa-clock"></i> Planlandı</span>
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400"><i class="fas fa-check-circle"></i> Tamamlandı</span>
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400"><i class="fas fa-times-circle"></i> İptal</span>
            </div>
        </div>
    </div>
    
    <!-- Düzen: Ana Takvim + Mini Navigator -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Ana Takvim Izgarası -->
        <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8" :class="dense ? 'p-3 sm:p-4' : 'p-4 sm:p-6 lg:p-8'">
            <?php if ($view === 'day'): ?>
                <!-- Gün Görünümü -->
                <div class="space-y-4 sm:space-y-8 relative" x-ref="dayContainer">
                    <!-- Gün görünümü için mevcut zaman göstergesi -->
                    <div x-show="currentLine.topPct !== null" :style="`top:${currentLine.topPct}%;`" class="absolute left-0 right-0 h-px bg-red-500/60" aria-hidden="true">
                        <div class="-mt-1 ml-0.5 w-2 h-2 rounded-full bg-red-500"></div>
                    </div>
                    <!-- Çalışma saatleri gölgelendirmesi -->
                    <template x-if="workingHoursBand.heightPct > 0">
                        <div class="absolute inset-x-0 bg-primary-50/50 dark:bg-primary-900/10 pointer-events-none" :style="`top:${workingHoursBand.topPct}%; height:${workingHoursBand.heightPct}%;`"></div>
                    </template>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0">
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-calendar-day mr-2 sm:mr-3 text-primary-600 dark:text-primary-400 text-lg sm:text-xl"></i>
                            <span class="text-base sm:text-xl"><?= Utils::formatDate($date, 'd F Y, l') ?></span>
                        </h3>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold bg-primary-100 dark:bg-primary-900/20 text-primary-800 dark:text-primary-300">
                                <i class="fas fa-tasks mr-1.5 sm:mr-2 text-xs"></i>
                                <?= count($jobs) ?> iş
                            </span>
                        </div>
                    </div>
                    
                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-16">
                            <div class="mx-auto w-24 h-24 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-calendar-day text-4xl text-primary-600 dark:text-primary-400"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Bu tarihte iş yok</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">Bu tarihe yeni bir iş eklemek için aşağıdaki butona tıklayın veya hızlı ekleme özelliğini kullanın.</p>
                            <button @click="showQuickAddModal = true" 
                                    class="inline-flex items-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                <i class="fas fa-plus mr-3"></i>
                                İş Ekle
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 sm:space-y-4" :class="dense ? 'space-y-1.5 sm:space-y-2' : 'space-y-3 sm:space-y-4'"
                             @mousedown="startDragCreate($event)" @mousemove="dragCreate($event)" @mouseup="endDragCreate($event)" @mouseleave="cancelDragCreate()" style="user-select:none;">
                            <!-- Sürükle-oluştur seçim katmanı -->
                            <div x-show="selection.active" class="absolute inset-x-0 bg-blue-500/20 border border-blue-400 rounded" :style="`top:${selection.topPct}%; height:${selection.heightPct}%;`"></div>
                            <?php foreach ($jobs as $job): ?>
                                <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl p-4 sm:p-6 hover:shadow-medium transition-all duration-200 hover:border-primary-300 dark:hover:border-primary-600 cursor-pointer group" 
                                     draggable="true" 
                                     @dragstart="dragStart($event, <?= $job['id'] ?>)"
                                     @click="viewJob(<?= $job['id'] ?>)"
                                     @mouseenter="showPreview($event)" @mousemove="movePreview($event)" @mouseleave="hidePreview()"
                                     data-customer="<?= e($job['customer_name']) ?>" 
                                     data-service="<?= htmlspecialchars($job['service_name'] ?? '') ?>" 
                                     data-time="<?= Utils::formatDateTime($job['start_at'], 'H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?>"
                                     data-note="<?= htmlspecialchars($job['note'] ?? '') ?>"
                                     data-job-id="<?= $job['id'] ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="flex items-start sm:items-center space-x-4 sm:space-x-6 flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                <div class="h-12 w-12 sm:h-16 sm:w-16 bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/30 dark:to-primary-800/30 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                                    <i class="fas fa-clock text-primary-600 dark:text-primary-400 text-base sm:text-xl"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-2">
                                                    <i class="fas fa-clock text-primary-500 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                                                    <span class="text-sm sm:text-base"><?= Utils::formatDateTime($job['start_at'], 'H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?></span>
                                                </div>
                                                <div class="space-y-1.5 sm:space-y-2">
                                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate"><?= e($job['customer_name']) ?></div>
                                                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                                        <i class="fas fa-tag text-primary-500 mr-2 text-xs"></i>
                                                        <span class="truncate"><?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?></span>
                                                    </div>
                                                    <?php if (!empty($job['note'])): ?>
                                                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 flex items-start">
                                                            <i class="fas fa-sticky-note text-gray-400 mr-2 mt-0.5 text-xs"></i>
                                                            <span class="line-clamp-2"><?= e($job['note']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between sm:justify-end gap-2 sm:gap-4 sm:flex-shrink-0">
                                            <span class="inline-flex px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold rounded-full whitespace-nowrap <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                                <i class="fas <?= $job['status'] === 'DONE' ? 'fa-check-circle' : ($job['status'] === 'CANCELLED' ? 'fa-times-circle' : 'fa-clock') ?> mr-1.5 sm:mr-2 text-xs"></i>
                                                <span class="hidden sm:inline"><?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?></span>
                                                <span class="sm:hidden"><?= $job['status'] === 'DONE' ? 'Tamam' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Plan') ?></span>
                                            </span>
                                            <div class="flex space-x-1 sm:space-x-2">
                                                <a href="<?= base_url("/jobs/edit/{$job['id']}") ?>" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200" 
                                                   title="Düzenle" @click.stop>
                                                    <i class="fas fa-edit text-sm sm:text-base"></i>
                                                </a>
                                                <?php if ($job['status'] === 'DONE'): ?>
                                                    <a href="<?= base_url("/finance/from-job/{$job['id']}") ?>" 
                                                       class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-all duration-200" 
                                                       title="Gelir Oluştur" @click.stop>
                                                        <i class="fas fa-money-bill text-sm sm:text-base"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($view === 'week'): ?>
                <!-- Hafta Görünümü -->
                <div class="space-y-4 sm:space-y-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0">
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-calendar-week mr-2 sm:mr-3 text-primary-600 dark:text-primary-400 text-lg sm:text-xl"></i>
                            <span class="text-sm sm:text-base"><?= Utils::formatDate($startDate, 'd M') ?> - <?= Utils::formatDate($endDate, 'd M Y') ?></span>
                        </h3>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold bg-primary-100 dark:bg-primary-900/20 text-primary-800 dark:text-primary-300">
                                <i class="fas fa-tasks mr-1.5 sm:mr-2 text-xs"></i>
                                <?= count($jobs) ?> iş
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3 lg:gap-4">
                        <?php
                        $weekDays = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                        $weekStartDate = clone new DateTime($startDate);
                        ?>
                        
                        <?php for ($i = 0; $i < 7; $i++): 
                            $currentDate = clone $weekStartDate;
                            $currentDate->modify("+{$i} days");
                        ?>
                            <div class="space-y-2 sm:space-y-3 <?= $i >= 5 ? 'bg-gray-50 dark:bg-gray-800/60 rounded-lg p-1' : '' ?>">
                                <div class="text-center p-3 sm:p-4 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl border border-primary-200 dark:border-primary-700">
                                    <div class="text-xs sm:text-sm font-semibold text-primary-700 dark:text-primary-300"><?= $weekDays[$i] ?></div>
                                    <div class="text-xl sm:text-2xl font-bold text-primary-900 dark:text-primary-100"><?= $currentDate->format('d') ?></div>
                                </div>
                                
                                <div class="space-y-1 min-h-[150px] sm:min-h-[200px]">
                                    <?php
                                    $dayJobs = array_filter($jobs, function($job) use ($currentDate) {
                                        return date('Y-m-d', strtotime($job['start_at'])) === $currentDate->format('Y-m-d');
                                    });
                                    ?>
                                    
                                    <?php foreach ($dayJobs as $job): ?>
                                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-2 sm:p-3 hover:shadow-medium transition-all duration-200 hover:border-primary-300 dark:hover:border-primary-600 cursor-pointer group" 
                                             draggable="true" 
                                             @dragstart="dragStart($event, <?= $job['id'] ?>)" 
                                             @click="viewJob(<?= $job['id'] ?>)" 
                                             data-job-id="<?= $job['id'] ?>">
                                            <div class="space-y-1.5 sm:space-y-2">
                                                <div class="font-semibold text-gray-900 dark:text-white flex items-center text-xs sm:text-sm">
                                                    <i class="fas fa-clock text-primary-500 mr-1.5 sm:mr-2 text-xs"></i>
                                                    <?= Utils::formatDateTime($job['start_at'], 'H:i') ?>
                                                </div>
                                                <div class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 truncate"><?= e($job['customer_name']) ?></div>
                                                <div class="text-[10px] sm:text-xs text-gray-600 dark:text-gray-400 truncate"><?= htmlspecialchars($job['service_name'] ?? '') ?></div>
                                                <div class="flex items-center justify-between">
                                                    <span class="inline-flex px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs font-semibold rounded-full <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                                        <i class="fas <?= $job['status'] === 'DONE' ? 'fa-check-circle' : ($job['status'] === 'CANCELLED' ? 'fa-times-circle' : 'fa-clock') ?> mr-0.5 sm:mr-1 text-[9px] sm:text-xs"></i>
                                                        <span class="hidden sm:inline"><?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?></span>
                                                        <span class="sm:hidden"><?= $job['status'] === 'DONE' ? 'Tam' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Plan') ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($dayJobs)): ?>
                                        <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                                            <i class="fas fa-plus-circle text-2xl mb-2"></i>
                                            <div class="text-xs">Boş</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Ay Görünümü -->
                <div class="space-y-4 sm:space-y-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0">
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-calendar mr-2 sm:mr-3 text-primary-600 dark:text-primary-400 text-lg sm:text-xl"></i>
                            <span class="text-base sm:text-xl"><?= Utils::formatDate($date, 'F Y') ?></span>
                        </h3>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold bg-primary-100 dark:bg-primary-900/20 text-primary-800 dark:text-primary-300">
                                <i class="fas fa-tasks mr-1.5 sm:mr-2 text-xs"></i>
                                <?= count($jobs) ?> iş
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-1 gap-y-2 sm:gap-2" x-data="calendarQuickAdd()">
                        <?php
                        $weekDays = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                        foreach ($weekDays as $day): ?>
                            <div class="text-center text-xs sm:text-sm font-medium text-gray-500 py-2 sm:py-3 bg-gray-50 dark:bg-gray-700 rounded-t">
                                <i class="fas fa-calendar-day mr-1 hidden sm:inline"></i>
                                <?= $day ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php
                        $firstDay = new DateTime($startDate);
                        $lastDay = new DateTime($endDate);
                        
                        // İlk haftanın boş günleri
                        $firstDayOfWeek = (int)$firstDay->format('N') - 1;
                        for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                            <div class="min-h-[120px] sm:min-h-[140px] border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"></div>
                        <?php endfor; ?>
                        
                        <?php 
                        $iterDay = clone $firstDay;
                        while ($iterDay <= $lastDay): 
                            $currentDay = clone $iterDay;
                        ?>
                            <div class="min-h-[120px] sm:min-h-[140px] border border-gray-200 dark:border-gray-600 p-1 relative group hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors <?= in_array((int)$currentDay->format('N'), [6,7]) ? 'bg-gray-50 dark:bg-gray-800/60' : '' ?>" 
                                 @drop="dropJob($event, '<?= $currentDay->format('Y-m-d') ?>')" 
                                 @dragover.prevent 
                                 @dragenter.prevent>
                                <div class="flex items-center justify-between mb-1">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white"><?= $currentDay->format('d') ?></div>
                                    <button class="hidden group-hover:block bg-blue-600 text-white rounded-full w-4 h-4 sm:w-5 sm:h-5 text-xs hover:bg-blue-700 transition-colors" 
                                            @click.prevent="open('<?= $currentDay->format('Y-m-d') ?>')" 
                                            title="Hızlı Ekle">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                
                                <?php
                                $dayJobs = array_filter($jobs, function($job) use ($currentDay) {
                                    return date('Y-m-d', strtotime($job['start_at'])) === $currentDay->format('Y-m-d');
                                });
                                ?>
                                
                                <div class="space-y-0.5 sm:space-y-1">
                                    <?php foreach (array_slice($dayJobs, 0, 3) as $job): ?>
                                        <div class="bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded px-0.5 py-0.5 sm:px-1 sm:py-1 text-[10px] sm:text-xs hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer" 
                                             draggable="true" 
                                             @dragstart="dragStart($event, <?= $job['id'] ?>)" 
                                             @click="viewJob(<?= $job['id'] ?>)" 
                                             @mouseenter="showPreview($event)" @mousemove="movePreview($event)" @mouseleave="hidePreview()" 
                                             data-customer="<?= e($job['customer_name']) ?>" 
                                             data-service="<?= htmlspecialchars($job['service_name'] ?? '') ?>" 
                                             data-time="<?= Utils::formatDateTime($job['start_at'], 'H:i') ?>" 
                                             data-note="<?= htmlspecialchars($job['note'] ?? '') ?>" 
                                             data-job-id="<?= $job['id'] ?>">
                                            <div class="text-blue-900 dark:text-blue-100 truncate font-medium text-[10px] sm:text-xs"><?= e($job['customer_name']) ?></div>
                                            <div class="text-blue-700 dark:text-blue-300 text-[9px] sm:text-xs"><?= Utils::formatDateTime($job['start_at'], 'H:i') ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($dayJobs) > 3): ?>
                                        <div class="text-[9px] sm:text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-600 rounded px-1 py-0.5">
                                            <i class="fas fa-ellipsis-h mr-1"></i>
                                            +<?= count($dayJobs) - 3 ?> daha
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $iterDay->modify('+1 day'); ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
        
        <!-- Mini Ay Navigator (Kenar Çubuğu) -->
        <div class="lg:col-span-1">
            <?php
                $currentTs = strtotime($date);
                $monthFirst = new DateTime(date('Y-m-01', $currentTs));
                $monthLast = new DateTime(date('Y-m-t', $currentTs));
                $firstWeekday = (int)$monthFirst->format('N'); // 1=Mon ... 7=Sun
                $daysInMonth = (int)$monthLast->format('j');
                $prevMonth = date('Y-m-d', strtotime('-1 month', $currentTs));
                $nextMonth = date('Y-m-d', strtotime('+1 month', $currentTs));
                $selectedDay = date('Y-m-d', $currentTs);
                $todayStr = date('Y-m-d');
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sticky top-4">
                <div class="flex items-center justify-between mb-3">
                    <a href="?view=<?= $view ?>&date=<?= $prevMonth ?>" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-chevron-left text-gray-600 dark:text-gray-300"></i></a>
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        <?= Utils::formatDate($monthFirst->format('Y-m-d'), 'F Y') ?>
                    </div>
                    <a href="?view=<?= $view ?>&date=<?= $nextMonth ?>" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-chevron-right text-gray-600 dark:text-gray-300"></i></a>
                </div>
                <div class="grid grid-cols-7 gap-1 text-[10px] text-center text-gray-500 dark:text-gray-400 mb-2">
                    <div>Pzt</div><div>Sal</div><div>Çar</div><div>Per</div><div>Cum</div><div>Cmt</div><div>Paz</div>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    <?php for ($i=1; $i<$firstWeekday; $i++): ?>
                        <div class="h-6"></div>
                    <?php endfor; ?>
                    <?php for ($d=1; $d <= $daysInMonth; $d++): 
                        $dayDate = $monthFirst->format('Y-m-') . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                        $isToday = $dayDate === $todayStr;
                        $isSelected = $dayDate === $selectedDay;
                    ?>
                        <a href="?view=day&date=<?= $dayDate ?>" class="h-7 flex items-center justify-center rounded-md text-xs <?php
                            echo $isSelected ? 'bg-primary-600 text-white' : ($isToday ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300');
                        ?>">
                            <?= $d ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hızlı Ekle Modal -->
    <div x-show="showQuickAddModal" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         @keydown.escape="showQuickAddModal = false"
         x-init="$watch('showQuickAddModal', value => value ? $nextTick(() => $refs.firstInput?.focus()) : null)">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="showQuickAddModal = false"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-strong transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="submitQuickAdd()">
                    <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4 sm:p-8 sm:pb-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-soft sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                                    Hızlı İş Ekleme
                                </h3>
                                
                                <div class="space-y-8">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri <span class="text-red-500">*</span>
                                        </label>
                                        <select x-model="quickAdd.customer_id" 
                                                x-ref="firstInput"
                                                required 
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                            <option value="">Müşteri seçiniz</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer['id'] ?>"><?= e($customer['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-cogs mr-2 text-primary-600"></i>Hizmet
                                        </label>
                                        <select x-model="quickAdd.service_id" 
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                            <option value="">Hizmet seçiniz</option>
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?= $service['id'] ?>"><?= e($service['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-play mr-2 text-primary-600"></i>Başlangıç <span class="text-red-500">*</span>
                                            </label>
                                            <input type="datetime-local" 
                                                   x-model="quickAdd.start_at" 
                                                   required 
                                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-stop mr-2 text-primary-600"></i>Bitiş <span class="text-red-500">*</span>
                                            </label>
                                            <input type="datetime-local" 
                                                   x-model="quickAdd.end_at" 
                                                   required 
                                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Not
                                        </label>
                                        <textarea x-model="quickAdd.note" 
                                                  rows="3" 
                                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none" 
                                                  placeholder="İş hakkında notlar..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                        <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:ml-3 sm:w-auto">
                            <i class="fas fa-save mr-2"></i>
                            Kaydet
                        </button>
                        <button type="button" 
                                @click="showQuickAddModal = false" 
                                class="mt-3 w-full inline-flex justify-center items-center px-6 py-3 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium rounded-lg border border-gray-300 dark:border-gray-500 shadow-medium hover:shadow-strong transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:mt-0 sm:w-auto">
                            <i class="fas fa-times mr-2"></i>
                            İptal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calendarApp() {
    return {
        showQuickAddModal: false,
        dense: false,
        currentLine: { topPct: null },
        preview: { show: false, x: 0, y: 0, customer: '', service: '', time: '', note: '' },
        prevUrl: '<?= base_url('/calendar?view=') . $view . '&date=' . date('Y-m-d', strtotime($date . ' -1 ' . ($view === 'day' ? 'day' : ($view === 'week' ? 'week' : 'month')))) ?>',
        nextUrl: '<?= base_url('/calendar?view=') . $view . '&date=' . date('Y-m-d', strtotime($date . ' +1 ' . ($view === 'day' ? 'day' : ($view === 'week' ? 'week' : 'month')))) ?>',
        // working hours band (defaults 09:00-18:00)
        workingHoursBand: { topPct: 0, heightPct: 0 },
        selection: { active: false, startPct: 0, endPct: 0, topPct: 0, heightPct: 0 },
        filters: {
            customer: '',
            service: '',
            status: ''
        },
        quickAdd: {
            customer_id: '',
            service_id: '',
            start_at: '',
            end_at: '',
            note: ''
        },
        draggedJob: null,
        
        init() {
            // Hızlı ekleme için varsayılan zamanları ayarla
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(9, 0, 0, 0);
            
            const endTime = new Date(tomorrow);
            endTime.setHours(10, 0, 0, 0);
            
            this.quickAdd.start_at = tomorrow.toISOString().slice(0, 16);
            this.quickAdd.end_at = endTime.toISOString().slice(0, 16);

            // Gün görünümü için mevcut zaman göstergesi
            const updateLine = () => {
                const start = 6; // 06:00
                const end = 22; // 22:00
                const d = new Date();
                const hour = d.getHours() + d.getMinutes()/60;
                const pct = ((hour - start) / (end - start)) * 100;
                this.currentLine.topPct = Math.max(0, Math.min(100, pct));
            };
            updateLine();
            setInterval(updateLine, 60000);

            // Klavye ile gezinme
            document.addEventListener('keydown', (e) => {
                if (['INPUT','TEXTAREA','SELECT'].includes((e.target.tagName||'').toUpperCase())) return;
                if (e.key === 'ArrowLeft') { window.location.href = this.prevUrl; }
                if (e.key === 'ArrowRight') { window.location.href = this.nextUrl; }
            });

            // Çalışma saatleri bandını başlat (09:00-18:00)
            this.updateWorkingHoursBand('09:00', '18:00');
        },
        
        applyFilters() {
            // Takvim görünümüne filtreleri uygula
            const params = new URLSearchParams(window.location.search);
            
            if (this.filters.customer) params.set('customer', this.filters.customer);
            else params.delete('customer');
            
            if (this.filters.service) params.set('service', this.filters.service);
            else params.delete('service');
            
            if (this.filters.status) params.set('status', this.filters.status);
            else params.delete('status');
            
            window.location.href = window.location.pathname + '?' + params.toString();
        },
        
        dragStart(event, jobId) {
            this.draggedJob = jobId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target.outerHTML);
            // Sürükleme işlemi başladığında tıklama olayını engelle
            event.target.style.pointerEvents = 'none';
        },
        
        dropJob(event, targetDate) {
            event.preventDefault();
            if (!this.draggedJob) return;
            
            // API üzerinden iş tarihini güncelle
            this.updateJobDate(this.draggedJob, targetDate);
            
            // Sürükleme işlemi bittiğinde pointer events'i geri aç
            setTimeout(() => {
                const draggedElement = document.querySelector(`[data-job-id="${this.draggedJob}"]`);
                if (draggedElement) {
                    draggedElement.style.pointerEvents = 'auto';
                }
                this.draggedJob = null;
            }, 100);
        },
        
        async updateJobDate(jobId, newDate) {
            try {
                const response = await fetch(`<?= base_url('/api/jobs/') ?>${jobId}/update-date`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ date: newDate })
                });
                
                if (response.ok) {
                    // Güncellenmiş veriyi göstermek için sayfayı yenile
                    window.location.reload();
                } else {
                    alert('İş tarihi güncellenirken bir hata oluştu.');
                }
            } catch (error) {
                alert('Bir hata oluştu.');
            }
        },
        
        async submitQuickAdd() {
            if (!this.quickAdd.customer_id || !this.quickAdd.start_at || !this.quickAdd.end_at) {
                alert('Lütfen tüm zorunlu alanları doldurun.');
                return;
            }
            
            try {
                const response = await fetch('<?= base_url('/calendar/create') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.quickAdd)
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('İş oluşturulurken bir hata oluştu.');
                }
            } catch (error) {
                alert('Bir hata oluştu.');
            }
        },
        
        viewJob(jobId) {
            // İş detay sayfasına git
            window.location.href = '<?= base_url('/jobs/show/') ?>' + jobId;
        },
        showPreview(e) {
            const t = e.currentTarget;
            this.preview.customer = t.dataset.customer || '';
            this.preview.service = t.dataset.service || '';
            this.preview.time = t.dataset.time || '';
            this.preview.note = t.dataset.note || '';
            this.preview.show = true;
            this.movePreview(e);
        },
        movePreview(e) {
            this.preview.x = e.clientX + 12;
            this.preview.y = e.clientY + 12;
        },
        hidePreview() { 
            this.preview.show = false; 
        },
        createAt(timeStr) {
            // Seçilen tarih ve önerilen saat ile hızlı oluşturma URL'i oluştur
            const d = '<?= $date ?>';
            const start = `${d}T${timeStr}`;
            // Varsayılan olarak +60 dakika
            const [h,m] = timeStr.split(':').map(Number);
            const endDate = new Date(`${d}T${timeStr}:00`);
            endDate.setMinutes(endDate.getMinutes() + 60);
            const endH = String(endDate.getHours()).padStart(2,'0');
            const endM = String(endDate.getMinutes()).padStart(2,'0');
            const end = `${d}T${endH}:${endM}`;
            const url = '<?= base_url('/jobs/new') ?>' + `?date=${encodeURIComponent(d)}&start_at=${encodeURIComponent(start)}&end_at=${encodeURIComponent(end)}`;
            window.location.href = url;
        },
        // Yardımcı fonksiyonlar
        timeToPct(time) {
            const [hh, mm] = time.split(':').map(Number);
            const start = 6, end = 22; // Görünür pencere
            const val = hh + (mm||0)/60;
            return Math.max(0, Math.min(100, ((val - start) / (end - start)) * 100));
        },
        updateWorkingHoursBand(start='09:00', end='18:00') {
            const top = this.timeToPct(start);
            const bottom = this.timeToPct(end);
            const height = Math.max(0, bottom - top);
            this.workingHoursBand = { topPct: top, heightPct: height };
        },
        // Sürükle-oluştur etkileşimleri
        startDragCreate(e) {
            if (e.button !== 0) return;
            const rect = this.$refs.dayContainer.getBoundingClientRect();
            const pct = ((e.clientY - rect.top) / rect.height) * 100;
            this.selection.active = true;
            this.selection.startPct = this.selection.endPct = Math.max(0, Math.min(100, pct));
            this.selection.topPct = this.selection.startPct;
            this.selection.heightPct = 0;
        },
        dragCreate(e) {
            if (!this.selection.active) return;
            const rect = this.$refs.dayContainer.getBoundingClientRect();
            const pct = ((e.clientY - rect.top) / rect.height) * 100;
            this.selection.endPct = Math.max(0, Math.min(100, pct));
            const top = Math.min(this.selection.startPct, this.selection.endPct);
            const height = Math.abs(this.selection.endPct - this.selection.startPct);
            this.selection.topPct = top;
            this.selection.heightPct = height;
        },
        endDragCreate(e) {
            if (!this.selection.active) return;
            const startPct = Math.min(this.selection.startPct, this.selection.endPct);
            const endPct = Math.max(this.selection.startPct, this.selection.endPct);
            this.selection.active = false; this.selection.heightPct = 0;
            // Yüzdeyi 06:00-22:00 arası zamana dönüştür
            const pctToTime = (p) => {
                const start = 6, end = 22; const hours = start + (p/100) * (end-start);
                const hh = Math.floor(hours); const mm = Math.round((hours - hh)*60/15)*15; // 15 dk'ya hizala
                return `${String(hh).padStart(2,'0')}:${String(mm).padStart(2,'0')}`;
            };
            let startT = pctToTime(startPct); let endT = pctToTime(endPct);
            if (startT === endT) { // Minimum 30 dk
                const [h,m] = startT.split(':').map(Number);
                const d = new Date(0,0,0,h,m,0,0); d.setMinutes(d.getMinutes()+30);
                endT = `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
            }
            // Seçilen endT ile navigasyon (daha hassas)
            const dstr = '<?= $date ?>';
            const url = '<?= base_url('/jobs/new') ?>' + `?date=${encodeURIComponent(dstr)}&start_at=${encodeURIComponent(dstr+'T'+startT)}&end_at=${encodeURIComponent(dstr+'T'+endT)}`;
            window.location.href = url;
        },
        cancelDragCreate() { 
            if (this.selection.active) { 
                this.selection.active = false; 
                this.selection.heightPct = 0; 
            } 
        }
    }
}

function calendarQuickAdd() {
    return {
        date: null,
        open(date) {
            this.date = date;
            const start = date + 'T09:00';
            const end = date + 'T10:00';
            const url = '<?= base_url('/jobs/new') ?>' +
                '?date=' + encodeURIComponent(date) +
                '&start_at=' + encodeURIComponent(start) +
                '&end_at=' + encodeURIComponent(end);
            window.location.href = url;
        }
    }
}
</script>
</div>

<!-- Yüzen etkinlik önizleme tooltip'i -->
<div x-cloak x-show="preview.show" :style="`left:${preview.x}px; top:${preview.y}px;`" class="fixed z-50 max-w-xs pointer-events-none">
    <div class="rounded-lg shadow-strong border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-xs text-gray-700 dark:text-gray-200">
        <div class="font-semibold text-gray-900 dark:text-white" x-text="preview.customer"></div>
        <div class="text-gray-500 dark:text-gray-400" x-text="preview.service"></div>
        <div class="mt-1 inline-flex items-center text-primary-600 dark:text-primary-400"><i class="fas fa-clock mr-1"></i><span x-text="preview.time"></span></div>
        <template x-if="preview.note">
            <div class="mt-2 line-clamp-3" x-text="preview.note"></div>
        </template>
    </div>
</div>