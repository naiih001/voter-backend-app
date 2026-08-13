// ============================================================
//  UniVote EVS — Current-user state store
// ============================================================

import { api } from './api.js';

export const store = {
  getUser() {
    try {
      return JSON.parse(localStorage.getItem('uv_user') || 'null');
    } catch {
      return null;
    }
  },

  setUser(user) {
    if (user) {
      localStorage.setItem('uv_user', JSON.stringify(user));
    } else {
      localStorage.removeItem('uv_user');
    }
  },

  clear() {
    localStorage.removeItem('uv_user');
  },

  async refreshUser() {
    const res = await api.get('/user');
    if (res.ok) {
      this.setUser(res.data);
      return res.data;
    }
    return null;
  },

  isAdmin() {
    return this.getUser()?.role === 'admin';
  },
};
