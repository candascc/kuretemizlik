    <!-- Modern Confirmation Dialog -->
    <?php include __DIR__ . '/../../partials/confirmation-dialog.php'; ?>
    
    <!-- Modern Alert Dialog -->
    <?php include __DIR__ . '/../../partials/alert-dialog.php'; ?>

    <!-- Command Palette Modal -->
    <div id="cmdk" class="fixed inset-0 z-[10001] hidden">
        <div class="absolute inset-0 bg-black/30" data-cmdk-close></div>
        <div class="mx-auto mt-20 max-w-2xl rounded-xl shadow-strong overflow-hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center">
                <i class="fas fa-magnifying-glass text-gray-400 mr-2"></i>
                <input id="cmdk-input" type="text" class="w-full bg-transparent outline-none text-sm text-gray-800 dark:text-gray-100" placeholder="Ara veya komut yazın... (Örn: yeni sözleşme, müşteri bul)" autocomplete="off">
                <span class="ml-3 text-[10px] text-gray-500">Esc</span>
            </div>
            <div id="cmdk-results" class="max-h-80 overflow-y-auto">
                <div class="p-4 text-sm text-gray-500 dark:text-gray-400">İpuçları: “yeni iş”, “müşteri: ali”, “sözleşme oluştur”</div>
            </div>
        </div>
    </div>

    <script>
    // Status bar metrics (lightweight)
    (function(){
        const $cache = document.getElementById('sb-cache');
        const $db = document.getElementById('sb-db');
        const $disk = document.getElementById('sb-disk');
        const $queue = document.getElementById('sb-queue');
        const $cacheFoot = document.getElementById('sb-cache-foot');
        const $dbFoot = document.getElementById('sb-db-foot');
        const $diskFoot = document.getElementById('sb-disk-foot');
        const $queueFoot = document.getElementById('sb-queue-foot');
        async function loadMetrics(){
            try{
                const res = await fetch('<?= base_url('/performance/metrics') ?>', { headers: {'X-CSRF-Token': '<?= CSRF::get() ?>'} });
                if(!res.ok) return;
                const data = await res.json();
                const hit = Math.round((data.cache?.hit_ratio ?? data.cache?.cache_hit_ratio ?? 0.85) * 100);
                const avg = data.queries?.slow_queries?.length ? Math.round((data.queries.slow_queries[0]?.duration_ms ?? 50)) : Math.round((<?= json_encode((int) (1000*0.05)) ?>));
                const diskPct = data.system?.disk_usage?.percentage ?? 0;
                const cacheLabel = `Cache: ${hit}%`;
                const dbLabel = `DB: ~${avg}ms`;
                const diskLabel = `Disk: ${diskPct}%`;
                const queueLabel = 'Queue: ok';
                $cache && ($cache.textContent = cacheLabel);
                $db && ($db.textContent = dbLabel);
                $disk && ($disk.textContent = diskLabel);
                $queue && ($queue.textContent = queueLabel);
                $cacheFoot && ($cacheFoot.textContent = cacheLabel);
                $dbFoot && ($dbFoot.textContent = dbLabel);
                $diskFoot && ($diskFoot.textContent = diskLabel);
                $queueFoot && ($queueFoot.textContent = queueLabel);
            }catch(e){ /* silent */ }
        }
        // Performance: Delay metrics loading to avoid blocking initial render
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(loadMetrics, 1000);
            });
        } else {
            setTimeout(loadMetrics, 1000);
        }
        setInterval(loadMetrics, 30000);

        // Notification center (desktop + mobile unified)
        const notifEndpoints = {
            list: '<?= base_url('/api/notifications/list') ?>',
            markAll: '<?= base_url('/api/notifications/mark-all-read') ?>',
            mark: '<?= base_url('/api/notifications/mark-read') ?>'
        };
        const csrfToken = '<?= CSRF::get() ?>';

        const escapeHtml = (value = '') => {
            const lookup = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            };
            return value.toString().replace(/[&<>"']/g, char => lookup[char] || char);
        };

        const emptyStateMarkup = '<div class="notification-panel__empty"><div class="notification-panel__empty-icon"><i class="fas fa-bell-slash"></i></div><div class="notification-panel__empty-text">Yeni bildirim yok</div><div class="notification-panel__empty-subtext">Tüm bildirimleriniz burada görünecek</div></div>';
        const errorStateMarkup = '<div class="notification-panel__empty"><div class="notification-panel__empty-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="notification-panel__empty-text">Yüklenemedi</div><div class="notification-panel__empty-subtext">Lütfen tekrar deneyin</div></div>';

        const renderNotificationItems = (items) => {

            if(!Array.isArray(items) || items.length === 0) {

                return emptyStateMarkup;

            }

            return items.map(function(n){

                const type = n.type || 'ops';

                const typeClass = type === 'critical'

                    ? 'notification-item--critical'

                    : (type === 'system' ? 'notification-item--system' : 'notification-item--ops');

                const readCls = n.read ? 'notification-item--read' : '';

                const key = n.key || '';

                const icon = n.icon || (type === 'critical'

                    ? 'fa-exclamation-triangle'

                    : (type === 'system' ? 'fa-hdd' : 'fa-info-circle'));

                const badge = n.read ? '' : '<span class="notification-item__badge"><span class="notification-item__badge-dot"></span><span>Yeni</span></span>';

                const meta = n.meta ? '<div class="notification-item__meta" title="'+escapeHtml(n.meta)+'">'+escapeHtml(n.meta)+'</div>' : '';

                const title = escapeHtml(n.text || '');

                const safeKey = escapeHtml(key);

                const hrefAttr = n.href ? ' data-href="'+escapeHtml(n.href)+'"' : '';

                const unreadToggle = key ? '<button class="notification-item__mark-toggle" data-action="mark-unread" type="button" title="Okunmadı olarak işaretle"><i class="fas fa-rotate-left"></i></button>' : '';

                return `

                    <div class="notification-item ${typeClass} ${readCls}" data-key="${safeKey}" data-type="${type}"${hrefAttr} data-read="${n.read ? 'true' : 'false'}" tabindex="0" role="button">

                        <div class="notification-item__glow"></div>

                        ${unreadToggle}

                        <div class="notification-item__content">

                            <div class="notification-item__icon-wrapper">

                                <div class="notification-item__icon-bg"></div>

                                <i class="fas ${icon} notification-item__icon"></i>

                            </div>

                            <div class="notification-item__body">

                                <div class="notification-item__header">

                                    ${badge}

                                    <div class="notification-item__title" title="${title}">${title}</div>

                                </div>

                                ${meta}

                            </div>

                        </div>

                    </div>

                `;

            }).join('');

        };

        function updateBadge(targetId, count, chipId) {
            const text = count > 0 ? (count > 99 ? '99+' : count) : '';
            [targetId, chipId].forEach(id => {
                if(!id) { return; }
                const el = document.getElementById(id);
                if(!el) { return; }
                if(count <= 0) {
                    el.classList.add('hidden');
                    el.textContent = '';
                } else {
                    el.classList.remove('hidden');
                    el.textContent = text;
                }
            });
        }

        function setBodyLock(state) {
            document.body.classList.toggle('notification-panel-open', !!state);
        }

        function createNotificationLoader(listEl) {
            let isLoading = false;
            return async function(showSpinner = true, preserveContent = true) {
                if(!listEl || isLoading) { return; }
                isLoading = true;
                const original = listEl.innerHTML;

                if(showSpinner && preserveContent && listEl.children.length > 0) {
                    listEl.setAttribute('data-loading', 'true');
                }

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000);
                    const res = await fetch(`${notifEndpoints.list}?t=${Date.now()}`, { cache: 'no-store', signal: controller.signal });
                    clearTimeout(timeoutId);

                    if(!res.ok) {
                        throw new Error('HTTP '+res.status);
                    }

                    const data = await res.json();
                    const items = Array.isArray(data?.data) ? data.data : [];
                    listEl.innerHTML = renderNotificationItems(items);
                    const unreadCount = items.filter(item => !item.read).length;
                    updateBadge('notif-badge', unreadCount, 'notif-count-chip');
                    updateBadge('notif-badge-mobile', unreadCount, 'notif-count-chip-mobile');
                } catch(err) {
                    if(!preserveContent) {
                        listEl.innerHTML = errorStateMarkup;
                    }
                } finally {
                    listEl.removeAttribute('data-loading');
                    isLoading = false;
                }
            };
        }

        const listEl = document.getElementById('notif-list');
        const listElMobile = document.getElementById('notif-list-mobile');
        const loadNotifs = createNotificationLoader(listEl);
        const loadNotifsMobile = createNotificationLoader(listElMobile);

        async function refreshNotifications(preserveContent = true) {
            await Promise.all([
                loadNotifs(false, preserveContent),
                loadNotifsMobile(false, preserveContent)
            ]);
        }

        async function markNotification(key, state = 'read', { refresh = true } = {}) {
            if(!key) { return false; }
            try{
                const body = new URLSearchParams({ key, state });
                const res = await fetch(notifEndpoints.mark, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded','X-CSRF-Token': csrfToken},
                    body: body.toString()
                });
                if(!res.ok) {
                    throw new Error('HTTP '+res.status);
                }
                if(refresh) {
                    await refreshNotifications(false);
                }
                return true;
            }catch(err){
                console.warn('Notification mark failed', err);
                return false;
            }
        }

        function handleNotificationActivation(item, event) {
            if(!item) { return; }
            const notifKey = item.getAttribute('data-key');
            const href = item.getAttribute('data-href');
            const isRead = item.dataset.read === 'true';
            if(notifKey && !isRead){
                item.dataset.read = 'true';
                item.classList.add('notification-item--read');
                const shouldRefresh = !href;
                markNotification(notifKey, 'read', { refresh: shouldRefresh }).then(success => {
                    if(!success){
                        item.dataset.read = 'false';
                        item.classList.remove('notification-item--read');
                    }
                });
            }
            if(href){
                const openNew = event && (event.metaKey || event.ctrlKey || event.button === 1);
                if(openNew){
                    window.open(href, '_blank', 'noopener');
                } else {
                    window.location.href = href;
                }
            }
        }

        function attachNotificationInteractions(targetList) {
            if(!targetList) { return; }
            targetList.addEventListener('click', function(e){
                const unreadBtn = e.target.closest('[data-action="mark-unread"]');
                if(unreadBtn){
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = unreadBtn.closest('.notification-item');
                    if(!parent) { unreadBtn.disabled = false; return; }
                    const notifKey = parent.getAttribute('data-key');
                    if(!notifKey) { return; }
                    unreadBtn.disabled = true;
                    markNotification(notifKey, 'unread', { refresh: true }).then(success => {
                        if(success){
                            parent.dataset.read = 'false';
                            parent.classList.remove('notification-item--read');
                        }
                    }).finally(() => {
                        unreadBtn.disabled = false;
                    });
                    return;
                }
                const item = e.target.closest('.notification-item');
                if(!item) { return; }
                handleNotificationActivation(item, e);
            });

            targetList.addEventListener('keydown', function(e){
                if(e.key !== 'Enter' && e.key !== ' ') { return; }
                const item = e.target.closest('.notification-item');
                if(!item) { return; }
                e.preventDefault();
                handleNotificationActivation(item, e);
            });

            targetList.addEventListener('auxclick', function(e){
                if(e.button !== 1) { return; }
                const item = e.target.closest('.notification-item');
                if(!item) { return; }
                e.preventDefault();
                handleNotificationActivation(item, e);
            });
        }

        const configs = [
            { button: document.getElementById('notif-button'), panel: document.getElementById('notif-menu'), variant: 'desktop' },
            { button: document.getElementById('notif-button-mobile'), panel: document.getElementById('notif-menu-mobile'), variant: 'mobile' },
        ].filter(cfg => cfg.button && cfg.panel);

        function closeAllPanels() {
            configs.forEach(cfg => {
                cfg.panel.classList.add('hidden');
                cfg.panel.dataset.state = 'closed';
                cfg.button.setAttribute('aria-expanded', 'false');
            });
            setBodyLock(false);
        }

        function openPanel(cfg) {
            configs.forEach(other => {
                if(other === cfg) { return; }
                other.panel.classList.add('hidden');
                other.panel.dataset.state = 'closed';
                other.button.setAttribute('aria-expanded', 'false');
            });
            cfg.panel.classList.remove('hidden');
            cfg.panel.dataset.state = 'open';
            cfg.button.setAttribute('aria-expanded', 'true');
            setBodyLock(cfg.variant === 'mobile');
        }

        configs.forEach(cfg => {
            cfg.panel.dataset.state = 'closed';
            cfg.button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const isOpen = !cfg.panel.classList.contains('hidden');
                if(isOpen) {
                    closeAllPanels();
                } else {
                    openPanel(cfg);
                    if(cfg.variant === 'mobile') {
                        loadNotifsMobile();
                    } else {
                        loadNotifs();
                    }
                }
            });
            cfg.panel.addEventListener('click', function(e){ e.stopPropagation(); });
            const scrim = cfg.panel.querySelector('.notification-panel__scrim');
            scrim && scrim.addEventListener('click', function(e){
                e.preventDefault();
                closeAllPanels();
            });
        });

        if(configs.length){
            document.addEventListener('click', function(e){
                const clickInside = configs.some(cfg => cfg.panel.contains(e.target) || cfg.button.contains(e.target));
                if(!clickInside) {
                    closeAllPanels();
                }
            });
            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape'){
                    closeAllPanels();
                }
            });
        }

        function attachMarkAllHandler(button){
            if(!button) { return; }
            button.addEventListener('click', async function(e){
                e.preventDefault();
                e.stopPropagation();
                try{
                    const res = await fetch(notifEndpoints.markAll, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json','X-CSRF-Token': csrfToken},
                    });
                    if(res.ok){
                        await refreshNotifications(false);
                    }
                }catch(err){ /* silent */ }
            });
        }
        attachMarkAllHandler(document.getElementById('notif-mark-all'));
        attachMarkAllHandler(document.getElementById('notif-mark-all-mobile'));

        attachNotificationInteractions(listEl);
        attachNotificationInteractions(listElMobile);

    })();
    </script>

    <!-- PWA Service Worker Registration (guarded - SSL errors won't affect UI) -->
    <script>
    (function(){
        try {
            // Auth helper: optional refresh endpoint and VAPID public key
            window.AUTH_REFRESH_URL = '<?= base_url('/api/auth/refresh') ?>';
            window.VAPID_PUBLIC_KEY = '<?= htmlspecialchars(getenv('VAPID_PUBLIC') ?: '') ?>';
            // Expose fetchAuth if auth.js is present
            (function injectAuth(){
                var s = document.createElement('script');
                s.src = '<?= Utils::asset('js/auth.js') ?>' + '?v=' + (window.assetVersion || '1');
                s.defer = true;
                document.head && document.head.appendChild(s);
            })();
            // Inject manifest link if not present
            (function ensureManifest(){
                var has = document.querySelector('link[rel="manifest"]');
                if (!has) {
                    var link = document.createElement('link');
                    link.rel = 'manifest';
                    link.href = '<?= Utils::asset('manifest.webmanifest') ?>';
                    document.head && document.head.appendChild(link);
                }
            })();

            // Service Worker disabled (ROUND 15) - application does not use PWA/offline features
            // Unregister any existing Service Workers silently
            (function unregisterServiceWorkers(){
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                        for(var i = 0; i < registrations.length; i++) {
                            registrations[i].unregister().catch(function() {
                                // Silent failure - SW cleanup failed, but don't break page
                            });
                        }
                    }).catch(function() {
                        // Silent failure - getRegistrations failed, but don't break page
                    });
                }
            })();
        } catch (e) {
            // Silently ignore errors
        }
    })();
    </script>






