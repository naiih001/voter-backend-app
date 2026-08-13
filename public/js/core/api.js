// ============================================================
//  UniVote EVS — API client (token auth, single source of truth)
// ============================================================

const API_BASE = '/api';

export const api = {
  token: localStorage.getItem('uv_token') || null,

  setToken(token) {
    this.token = token;
    if (token) {
      localStorage.setItem('uv_token', token);
    } else {
      localStorage.removeItem('uv_token');
    }
  },

  get headers() {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    };
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    return headers;
  },

  async request(method, endpoint, body = null, requireAuth = true) {
    if (requireAuth && !this.token) {
      this.setToken(null);
      window.location.href = '/login';
      return { ok: false, status: 401, data: {} };
    }

    const options = { method, headers: this.headers };
    if (body !== null) {
      options.body = JSON.stringify(body);
    }

    let response;
    try {
      response = await fetch(`${API_BASE}${endpoint}`, options);
    } catch (e) {
      return { ok: false, status: 0, data: { message: 'Network error. Please check your connection.' } };
    }

    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
      this.setToken(null);
      window.location.href = '/login';
      return { ok: false, status: 401, data };
    }

    return { ok: response.ok, status: response.status, data };
  },

  get(endpoint, requireAuth = true) {
    return this.request('GET', endpoint, null, requireAuth);
  },

  post(endpoint, body, requireAuth = true) {
    return this.request('POST', endpoint, body, requireAuth);
  },

  put(endpoint, body, requireAuth = true) {
    return this.request('PUT', endpoint, body, requireAuth);
  },

  del(endpoint, requireAuth = true) {
    return this.request('DELETE', endpoint, null, requireAuth);
  },
};
