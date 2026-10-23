import http from './http'

// Semua angka dikirim sebagai string supaya presisi desimal tidak hilang.

export const authApi = {
  register: (payload) => http.post('/register', payload).then((r) => r.data),
  login: (payload) => http.post('/login', payload).then((r) => r.data),
  me: () => http.get('/me').then((r) => r.data.data),
  logout: () => http.post('/logout').then((r) => r.data),
}

export const coinsApi = {
  list: () => http.get('/coins').then((r) => r.data.data),
}

export const holdingsApi = {
  list: () => http.get('/holdings').then((r) => r.data.data),
  get: (id) => http.get(`/holdings/${encodeURIComponent(id)}`).then((r) => r.data.data),
  create: (payload) => http.post('/holdings', payload).then((r) => r.data.data),
  update: (id, payload) => http.put(`/holdings/${encodeURIComponent(id)}`, payload).then((r) => r.data.data),
  remove: (id) => http.delete(`/holdings/${encodeURIComponent(id)}`).then((r) => r.data),
}

export const portfolioApi = {
  summary: () => http.get('/portfolio/summary').then((r) => r.data.data),
}
