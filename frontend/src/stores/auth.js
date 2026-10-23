import { computed, reactive } from 'vue'

/**
 * Sesi login disimpan di localStorage (sesuai ketentuan tugas: token dikirim lewat header).
 *
 * Risiko: token di localStorage bisa dicuri kalau ada celah XSS. Mitigasinya:
 * - tidak memakai v-html (dicek ESLint),
 * - header Content-Security-Policy dari nginx,
 * - token punya masa berlaku dan dicabut saat logout.
 */
const STORAGE_KEY = 'chainfolio.session'

function readStoredSession() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const session = JSON.parse(raw)
    if (session.expiresAt && new Date(session.expiresAt) <= new Date()) {
      localStorage.removeItem(STORAGE_KEY)
      return null
    }
    return session
  } catch {
    return null
  }
}

const stored = readStoredSession()

export const authState = reactive({
  token: stored?.token ?? null,
  expiresAt: stored?.expiresAt ?? null,
  user: stored?.user ?? null,
})

export const isAuthenticated = computed(() => Boolean(authState.token))

/** Dipanggil setelah register/login berhasil. Hanya data yang perlu yang disimpan. */
export function setSession({ token, expires_at: expiresAt, user }) {
  authState.token = token
  authState.expiresAt = expiresAt
  authState.user = { id: user.id, name: user.name, email: user.email }
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ token, expiresAt, user: authState.user }))
  } catch {
    // localStorage bisa diblokir (mode privat); sesi tetap jalan di memori.
  }
}

export function clearSession() {
  authState.token = null
  authState.expiresAt = null
  authState.user = null
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {
    // abaikan
  }
}
