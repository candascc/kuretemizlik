<?php
$currentDate = strtotime($currentMonth . '-01');
$monthName = date('F Y', $currentDate);
$prevMonth = date('Y-m', strtotime($currentMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($currentMonth . '-01 +1 month'));

// Check if today's attendance exists
$todayAttendance = null;
$today = date('Y-m-d');
foreach ($attendance as $att) {
    if ($att['date'] === $today) {
        $todayAttendance = $att;
        break;
    }
}

// Monthly stats
$totalHours = 0;
$totalOvertime = 0;
$workingDays = 0;
foreach ($attendance as $att) {
    if ($att['status'] === 'present') {
        $totalHours += $att['total_hours'] ?? 0;
        $totalOvertime += $att['overtime_hours'] ?? 0;
        $workingDays++;
    }
}
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-calendar-check mr-3"></i>
                Personel Yoklama - <?= htmlspecialchars($staff['name'] . ' ' . $staff['surname']) ?>
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                <?= date('F Y', strtotime($currentMonth . '-01')) ?> ayı yoklama kayıtları
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= base_url('/staff') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Month Navigation -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <a href="?month=<?= $prevMonth ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-chevron-left mr-2"></i>
                Önceki Ay
            </a>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <?= date('F Y', strtotime($currentMonth . '-01')) ?>
            </h2>
            <a href="?month=<?= $nextMonth ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Sonraki Ay
                <i class="fas fa-chevron-right ml-2"></i>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-calendar-day text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Çalışma Günü</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $workingDays ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-clock text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Saat</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($totalHours, 1) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <i class="fas fa-briefcase text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mesai Saati</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($totalOvertime, 1) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-calendar-times text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Saat</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= $workingDays > 0 ? number_format($totalHours / $workingDays, 1) : 0 ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Check In/Out Button -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-center">
            <?php if (!$todayAttendance || !$todayAttendance['check_in']): ?>
                <form method="POST" action="<?= base_url("/staff/checkin/{$staff['id']}") ?>">
                    <?= CSRF::field() ?>
                    <button type="submit" 
                            class="inline-flex items-center px-8 py-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-sign-in-alt mr-3 text-2xl"></i>
                        <span class="text-xl">Giriş Yap</span>
                    </button>
                </form>
            <?php elseif (!$todayAttendance['check_out']): ?>
                <form method="POST" action="<?= base_url("/staff/checkin/{$staff['id']}") ?>">
                    <?= CSRF::field() ?>
                    <button type="submit" 
                            class="inline-flex items-center px-8 py-4 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-sign-out-alt mr-3 text-2xl"></i>
                        <span class="text-xl">Çıkış Yap</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <div class="text-gray-600 dark:text-gray-400 mb-2">
                        <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 font-medium">Bugün çıkış yapıldı</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= date('H:i', strtotime($todayAttendance['check_out'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Giriş</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Çıkış</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Toplam Saat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mesai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                Bu ay için yoklama kaydı yok
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $att): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= date('d.m.Y', strtotime($att['date'])) ?>
                                    <?php if ($att['date'] === $today): ?>
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">Bugün</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $att['check_in'] ? date('H:i', strtotime($att['check_in'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $att['total_hours'] ? number_format($att['total_hours'], 1) . ' h' : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $att['overtime_hours'] ? number_format($att['overtime_hours'], 1) . ' h' : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($att['status'] === 'present'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Present
                                        </span>
                                    <?php elseif ($att['status'] === 'absent'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Absent
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            <?= $att['status'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
