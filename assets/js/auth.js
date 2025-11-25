/* Lightweight Auth helper for header-only JWT on web/mobile */
(function(){
  const STORAGE_KEY = 'auth.tokens';
  function getTokens() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(_) { return {}; }
  }
  function setTokens(tokens) {
    if (!tokens) { localStorage.removeItem(STORAGE_KEY); return; }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(tokens));
  }
  function clearTokens() { localStorage.removeItem(STORAGE_KEY); }
  function getAccessToken() { return getTokens().accessToken || null; }
  function getRefreshToken() { return getTokens().refreshToken || null; }
  async function tryRefresh(refreshUrl) {
    const rt = getRefreshToken();
    if (!rt || !refreshUrl) return false;
    try {
      const res = await fetch(refreshUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: rt })
      });
      if (!res.ok) return false;
      const data = await res.json();
      if (data && data.access_token) {
        const prev = getTokens();
        setTokens({ accessToken: data.access_token, refreshToken: data.refresh_token || prev.refreshToken });
        return true;
      }
    } catch(_) {}
    return false;
  }
  async function fetchAuth(input, init) {
    init = init || {};
    init.headers = new Headers(init.headers || {});
    const at = getAccessToken();
    if (at && !init.headers.has('Authorization')) {
      init.headers.set('Authorization', 'Bearer ' + at);
    }
    const res = await fetch(input, init);
    if (res.status !== 401) return res;
    // Attempt refresh once if configured
    const refreshUrl = window.AUTH_REFRESH_URL || null;
    const ok = await tryRefresh(refreshUrl);
    if (!ok) {
      clearTokens();
      window.dispatchEvent(new CustomEvent('auth:expired'));
      return res;
    }
    const retryInit = Object.assign({}, init);
    retryInit.headers = new Headers(init.headers || {});
    retryInit.headers.set('Authorization', 'Bearer ' + getAccessToken());
    return fetch(input, retryInit);
  }
  window.Auth = { getTokens, setTokens, clearTokens, getAccessToken, getRefreshToken, fetchAuth };
})();


