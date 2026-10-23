import axios from 'axios'
import { authState } from '../stores/auth'

/**
 * Satu instance Axios untuk seluruh aplikasi.
 * baseURL dari VITE_API_BASE_URL (nilai publik), default "/api" (origin yang sama).
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Setiap request membawa token di header Authorization (bukan di URL).
http.interceptors.request.use((config) => {
  if (authState.token) {
    config.headers.Authorization = `Bearer ${authState.token}`
  }
  return config
})

let unauthorizedHandler = null

/** Didaftarkan di main.js, dipanggil setiap server membalas 401. */
export function onUnauthorized(handler) {
  unauthorizedHandler = handler
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.endsWith('/login')
    if (error.response?.status === 401 && !isLoginRequest && unauthorizedHandler) {
      unauthorizedHandler()
    }
    return Promise.reject(error)
  },
)

export default http
