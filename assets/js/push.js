(function(){
  async function ensurePermission() {
    if (!('Notification' in window)) return false;
    if (Notification.permission === 'granted') return true;
    if (Notification.permission === 'denied') return false;
    try { const p = await Notification.requestPermission(); return p === 'granted'; } catch(_) { return false; }
  }
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  }
  async function subscribe(reg) {
    try {
      const vapid = (window.VAPID_PUBLIC_KEY || '').trim();
      if (!vapid) { console.info('WebPush: no VAPID key configured'); return null; }
      const sub = await reg.pushManager.getSubscription();
      if (sub) return sub;
      return await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapid)
      });
    } catch (e) {
      console.info('WebPush subscribe failed:', e && (e.message || e));
      return null;
    }
  }
  async function registerServer(sub) {
    if (!sub) return false;
    try {
      const pushSubscribeUrl = window.PUSH_SUBSCRIBE_URL || '/app/api/push/subscribe';
      const res = await (window.Auth && window.Auth.fetchAuth ? window.Auth.fetchAuth : fetch)(
        pushSubscribeUrl,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(sub)
        }
      );
      return res.ok;
    } catch (_) { return false; }
  }
  async function init() {
    if (!('serviceWorker' in navigator)) return;
    const ok = await ensurePermission();
    if (!ok) return;
    try {
      const swScope = window.SW_SCOPE || '/app/';
      const reg = await navigator.serviceWorker.getRegistration(swScope);
      if (!reg) return;
      const sub = await subscribe(reg);
      await registerServer(sub);
    } catch(_) {}
  }
  window.initWebPush = init;
  // auto-init after load
  window.addEventListener('load', function(){ setTimeout(init, 800); });
})(); 


