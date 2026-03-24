/* ── FormCraft API Client ── */

// Derive absolute API base URL once, at load time.
// Always uses window.location.origin so the Authorization header
// is never blocked by same-origin checks.
(function () {
  // Walk up the pathname to find the project root segment.
  // e.g. /php-form-builder/admin/dashboard.html  →  /php-form-builder
  const parts   = location.pathname.split('/').filter(Boolean);
  const rootIdx = parts.findIndex(p => p === 'php-form-builder');
  const root    = rootIdx >= 0
    ? '/' + parts.slice(0, rootIdx + 1).join('/')
    : '';                                    // served from web root

  // Full absolute URL — no relative path ambiguity
  window.API_BASE = location.origin + root + '/api/index.php';
  window.APP_ROOT = location.origin + root;
})();

/* ── Auth helpers ── */
const Auth = {
  init() {
    if (!localStorage.getItem('access_token')) {
      location.href = window.APP_ROOT + '/admin/login.html';
    }
  },

  getUser() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch { return null; }
  },

  getToken() {
    return localStorage.getItem('access_token');
  },

  async logout() {
    try {
      await fetch(window.API_BASE + '/auth/logout', {
        method:  'POST',
        headers: { 'Authorization': 'Bearer ' + this.getToken() },
      });
    } catch (_) {}
    localStorage.clear();
    location.href = window.APP_ROOT + '/admin/login.html';
  },

  async refreshToken() {
    const rt = localStorage.getItem('refresh_token');
    if (!rt) { await this.logout(); return null; }
    try {
      const res  = await fetch(window.API_BASE + '/auth/refresh', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ refresh_token: rt }),
      });
      const data = await res.json();
      if (data.success) {
        localStorage.setItem('access_token',  data.data.access_token);
        localStorage.setItem('refresh_token', data.data.refresh_token);
        return data.data.access_token;
      }
    } catch (_) {}
    await this.logout();
    return null;
  },
};

/* ── API client ── */
const API = {
  async request(method, path, body = null, retry = true) {
    const token = Auth.getToken();
    const headers = { 'Authorization': 'Bearer ' + token };
    if (body !== null) headers['Content-Type'] = 'application/json';

    const opts = { method, headers };
    if (body !== null) opts.body = JSON.stringify(body);

    let res = await fetch(window.API_BASE + path, opts);

    // Token expired — refresh once and retry
    if (res.status === 401 && retry) {
      const newToken = await Auth.refreshToken();
      if (newToken) {
        headers['Authorization'] = 'Bearer ' + newToken;
        res = await fetch(window.API_BASE + path, { method, headers, body: opts.body });
      }
    }

    // Guard: if response is not JSON (e.g. Apache error page), show useful error
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const text = await res.text();
      console.error('Non-JSON response from', window.API_BASE + path, ':', text.slice(0, 300));
      return { success: false, message: 'Server error (HTTP ' + res.status + '). Check console.' };
    }

    return res.json();
  },

  get(path)         { return this.request('GET',    path); },
  post(path, body)  { return this.request('POST',   path, body); },
  put(path, body)   { return this.request('PUT',    path, body); },
  patch(path, body) { return this.request('PATCH',  path, body); },
  delete(path)      { return this.request('DELETE', path); },
};

/* ── UI helpers ── */
function toast(msg, type = 'success', duration = 3500) {
  const el       = document.createElement('div');
  el.className   = 'toast ' + type;
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), duration);
}

function esc(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
