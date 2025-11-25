<?php
/**
 * Skeleton Loading Component
 * Usage: include __DIR__ . '/partials/skeleton.php' with $skeletonType
 */

$skeletonType = $skeletonType ?? 'table';
?>

<?php if ($skeletonType === 'table'): ?>
    <!-- Table Skeleton -->
    <div class="bg-white rounded-md shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="skeleton h-4 w-32 rounded"></div>
        </div>
        <div class="divide-y divide-gray-200">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <div class="px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="skeleton h-4 w-24 rounded"></div>
                        <div class="skeleton h-4 w-32 rounded"></div>
                        <div class="skeleton h-4 w-20 rounded"></div>
                        <div class="skeleton h-4 w-16 rounded"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

<?php elseif ($skeletonType === 'card'): ?>
    <!-- Card Skeleton -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="skeleton h-6 w-48 rounded mb-4"></div>
        <div class="space-y-3">
            <div class="skeleton h-4 w-full rounded"></div>
            <div class="skeleton h-4 w-3/4 rounded"></div>
            <div class="skeleton h-4 w-1/2 rounded"></div>
        </div>
    </div>

<?php elseif ($skeletonType === 'form'): ?>
    <!-- Form Skeleton -->
    <div class="bg-white rounded-md shadow p-6 space-y-4">
        <div class="skeleton h-8 w-48 rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="skeleton h-4 w-16 rounded"></div>
                <div class="skeleton h-10 w-full rounded"></div>
            </div>
            <div class="space-y-2">
                <div class="skeleton h-4 w-20 rounded"></div>
                <div class="skeleton h-10 w-full rounded"></div>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <div class="skeleton h-10 w-20 rounded"></div>
            <div class="skeleton h-10 w-24 rounded"></div>
        </div>
    </div>

<?php elseif ($skeletonType === 'stats'): ?>
    <!-- Stats Cards Skeleton -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                <div class="flex items-center">
                    <div class="skeleton h-8 w-8 rounded"></div>
                    <div class="ml-5 w-0 flex-1">
                        <div class="skeleton h-4 w-20 rounded mb-2"></div>
                        <div class="skeleton h-6 w-16 rounded"></div>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

<?php endif; ?>
