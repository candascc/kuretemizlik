<?php
$roleDefinitions = $roleDefinitions ?? [];
$assignableRoles = $assignableRoles ?? $roleDefinitions;
$assignableRoleKeys = array_keys($assignableRoles);
$defaultRoleKey = $assignableRoleKeys[0] ?? 'ADMIN';
$rolePillClasses = [
    'SUPERADMIN' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200',
    'ADMIN' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-100',
    'OPERATOR' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-100',
    'SITE_MANAGER' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-100',
    'FINANCE' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-100',
    'SUPPORT' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-100',
    'MANAGEMENT' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/50 dark:text-slate-100',
];
?>

<?php include __DIR__ . '/../partials/flash.php'; ?>
<div class="space-y-8" data-page="settings-users">
    <!-- Header Section -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-users"></i>
                Kullanıcı Yönetimi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Sistem kullanıcılarını yönetin, rollerini belirleyin ve erişimleri kontrol edin.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition"
                    data-modal-trigger="create-user">
                <i class="fas fa-user-plus mr-2"></i>
                Yeni Kullanıcı
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <?php if (isset($stats) && is_array($stats)): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kullanıcı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Kullanıcı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="fas fa-ban text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pasif Kullanıcı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-list"></i>
                Kullanıcı Listesi
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcı Adı</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kayıt Tarihi</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>Henüz kullanıcı eklenmemiş</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                                $roleKey = strtoupper($user['role'] ?? '');
                                $roleMeta = $roleDefinitions[$roleKey] ?? null;
                                $roleLabel = $roleMeta['label'] ?? $roleKey;
                                $roleDescription = $roleMeta['description'] ?? 'Rol bilgisi tanımlı değil.';
                                $pillClass = $rolePillClasses[$roleKey] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-900/50 dark:text-slate-100';
                                $userPayload = htmlspecialchars(json_encode([
                                    'id' => (int) ($user['id'] ?? 0),
                                    'username' => (string) ($user['username'] ?? ''),
                                    'role' => $roleKey,
                                    'updateUrl' => base_url('/settings/update-user/' . ($user['id'] ?? '')),
                                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium">
                                    #<?= (int) ($user['id'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-semibold">
                                    <?= htmlspecialchars($user['username'] ?? '') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $pillClass ?>"
                                          title="<?= e($roleDescription) ?>">
                                        <?= e($roleLabel) ?>
                                        <span class="ml-1 text-[10px] font-normal opacity-70">(<?= e($roleKey) ?>)</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($user['is_active'] ?? 1) == 1): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                            Pasif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                    <?= date('d.m.Y', strtotime($user['created_at'] ?? 'now')) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3 text-base">
                                        <button type="button"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                data-user-edit="<?= $userPayload ?>"
                                                title="Kullanıcıyı düzenle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if (($user['id'] ?? 0) != Auth::id()): ?>
                                        <form method="POST"
                                              action="<?= base_url('/settings/delete-user/' . ($user['id'] ?? 0)) ?>"
                                              class="inline"
                                              data-user-delete="true"
                                              data-username="<?= htmlspecialchars($user['username'] ?? '') ?>">
                                            <?= CSRF::field() ?>
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    title="Kullanıcıyı sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" data-modal="create-user" aria-hidden="true">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Yeni Kullanıcı Oluştur</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kullanıcı bilgilerini girin ve rolünü belirleyin.</p>
            </div>
            <button type="button" class="text-gray-500 hover:text-gray-800 dark:text-gray-300" data-modal-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?= base_url('/settings/create-user') ?>" class="px-6 py-6 space-y-5">
            <?= CSRF::field() ?>
            <div>
                <label for="create_username" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kullanıcı Adı</label>
                <input type="text" id="create_username" name="username" required
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500"
                       placeholder="ornek.kullanici">
            </div>
            <div>
                <label for="create_password" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Parola</label>
                <input type="password" id="create_password" name="password" required minlength="6"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500"
                       placeholder="Güçlü bir parola">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">En az 6 karakter, harf ve rakam kombinasyonu önerilir.</p>
            </div>
            <div>
                <label for="create_role" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Rol</label>
                <select id="create_role"
                        name="role"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500"
                        data-role-select
                        data-role-info-target="#create-role-info">
                    <?php foreach ($assignableRoles as $key => $meta): ?>
                        <option value="<?= e($key) ?>"
                                data-description="<?= htmlspecialchars($meta['description'] ?? '') ?>"
                                <?= $key === $defaultRoleKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($meta['label'] ?? $key) . ' (' . $key . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="create-role-info" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    <?= htmlspecialchars($assignableRoles[$defaultRoleKey]['description'] ?? '') ?>
                </p>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200"
                        data-modal-close>İptal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" data-modal="edit-user" aria-hidden="true">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Kullanıcıyı Düzenle</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Rol veya şifre güncellemeleri yapabilirsiniz.</p>
            </div>
            <button type="button" class="text-gray-500 hover:text-gray-800 dark:text-gray-300" data-modal-close>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST"
              action="<?= base_url('/settings/update-user/__ID__') ?>"
              class="px-6 py-6 space-y-5"
              data-edit-form>
            <?= CSRF::field() ?>
            <div>
                <label for="edit_username" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kullanıcı Adı</label>
                <input type="text" id="edit_username" name="username" required
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="edit_password" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Yeni Parola (Opsiyonel)</label>
                <input type="password" id="edit_password" name="password" minlength="6"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500"
                       placeholder="Boş bırakırsanız parola değişmez">
            </div>
            <div>
                <label for="edit_role" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Rol</label>
                <select id="edit_role"
                        name="role"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500"
                        data-role-select
                        data-role-info-target="#edit-role-info">
                    <?php foreach ($assignableRoles as $key => $meta): ?>
                        <option value="<?= e($key) ?>"
                                data-description="<?= htmlspecialchars($meta['description'] ?? '') ?>">
                            <?= htmlspecialchars(($meta['label'] ?? $key) . ' (' . $key . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="edit-role-info" class="mt-2 text-sm text-gray-600 dark:text-gray-300"></p>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200"
                        data-modal-close>İptal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
                    Güncelle
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$scriptNonceFn = function_exists('csp_script_nonce') ? csp_script_nonce() : '';
$userScriptNonce = $scriptNonceFn !== '' ? e($scriptNonceFn) : '';
?>
<script<?= $userScriptNonce ? ' nonce="' . $userScriptNonce . '"' : '' ?>>
(function () {
    const doc = document;

    function qs(selector, root = doc) {
        return root.querySelector(selector);
    }

    function updateRoleInfo(selectEl) {
        if (!selectEl) return;
        const targetSelector = selectEl.getAttribute('data-role-info-target');
        if (!targetSelector) return;
        const target = qs(targetSelector);
        if (!target) return;
        const option = selectEl.selectedOptions[0];
        const description = option ? option.getAttribute('data-description') : '';
        target.textContent = description || 'Rol açıklaması bulunamadı.';
    }

    function toggleModal(name, show) {
        const modal = doc.querySelector(`[data-modal="${name}"]`);
        if (!modal) return;
        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.focus();
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    doc.querySelectorAll('[data-modal-trigger]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const name = btn.getAttribute('data-modal-trigger');
            toggleModal(name, true);
        });
    });

    doc.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                const name = modal.getAttribute('data-modal');
                toggleModal(name, false);
            }
        });
    });

    doc.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('[data-modal]');
            if (modal) {
                toggleModal(modal.getAttribute('data-modal'), false);
            }
        });
    });

    doc.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            doc.querySelectorAll('[data-modal]:not(.hidden)').forEach((modal) => {
                toggleModal(modal.getAttribute('data-modal'), false);
            });
        }
    });

    doc.querySelectorAll('[data-role-select]').forEach((selectEl) => {
        updateRoleInfo(selectEl);
        selectEl.addEventListener('change', () => updateRoleInfo(selectEl));
    });

    const editModalName = 'edit-user';
    const editForm = qs('[data-edit-form]');

    doc.querySelectorAll('[data-user-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const payloadRaw = button.getAttribute('data-user-edit');
            if (!payloadRaw) {
                console.warn('[user-admin] data-user-edit boş geldi');
                return;
            }

            let payload;
            try {
                payload = JSON.parse(payloadRaw);
            } catch (error) {
                console.error('[user-admin] Kullanıcı verisi çözümlenemedi', error);
                return;
            }

            if (!editForm) return;
            const usernameInput = qs('#edit_username', editForm);
            const passwordInput = qs('#edit_password', editForm);
            const roleSelect = qs('#edit_role', editForm);

            if (usernameInput) {
                usernameInput.value = payload.username || '';
            }
            if (passwordInput) {
                passwordInput.value = '';
            }
            if (roleSelect) {
                roleSelect.value = payload.role || '';
                updateRoleInfo(roleSelect);
            }

            const actionTemplate = editForm.getAttribute('action') || '';
            if (payload.updateUrl) {
                editForm.setAttribute('action', payload.updateUrl);
            } else {
                editForm.setAttribute('action', actionTemplate.replace('__ID__', payload.id || ''));
            }

            toggleModal(editModalName, true);
        });
    });

    doc.querySelectorAll('[data-user-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const targetName = form.getAttribute('data-username') || 'bu kullanıcıyı';
            const message = `${targetName} silinecek. Devam etmek istiyor musunuz?`;

            const confirmPromise = typeof window.modernConfirm === 'function'
                ? window.modernConfirm(message)
                : Promise.resolve(window.confirm(message));

            confirmPromise.then((approved) => {
                if (approved) {
                    form.submit();
                }
            });
        }, { passive: false });
    });
})();
</script>
