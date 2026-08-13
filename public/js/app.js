/* ============================================================
   UniVote EVS — API Integration Layer
   ============================================================ */

const API_BASE = '/api';

const api = {
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
      window.location.href = '/login';
      return;
    }

    const options = {
      method,
      headers: this.headers,
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(`${API_BASE}${endpoint}`, options);
    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
      this.setToken(null);
      window.location.href = '/login';
      return;
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
};

function getInitials(name) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
  });
}

function showError(element, message) {
  if (!element) return;
  element.textContent = message;
  element.style.display = 'block';
  element.className = 'alert alert-error';
}

function showSuccess(element, message) {
  if (!element) return;
  element.textContent = message;
  element.style.display = 'block';
  element.className = 'alert alert-success';
}

function redirectIfAuthed() {
  if (api.token) {
    window.location.href = '/dashboard';
  }
}

function requireAuth() {
  if (!api.token) {
    window.location.href = '/login';
  }
}

function logout() {
  api.post('/logout', {}).finally(() => {
    api.setToken(null);
    window.location.href = '/login';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const user = JSON.parse(localStorage.getItem('uv_user') || 'null');

  document.querySelectorAll('[data-logout]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      logout();
    });
  });

  document.querySelectorAll('[data-user-name]').forEach(el => {
    if (user?.name) el.textContent = user.name;
  });

  document.querySelectorAll('[data-user-initials]').forEach(el => {
    if (user?.name) el.textContent = getInitials(user.name);
  });
});
