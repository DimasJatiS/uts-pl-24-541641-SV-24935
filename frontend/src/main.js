import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { onUnauthorized } from './api/http'
import { clearSession } from './stores/auth'
import './style.css'

// Token ditolak server (kedaluwarsa/dicabut) → bersihkan sesi lalu ke halaman login.
onUnauthorized(() => {
  clearSession()
  const current = router.currentRoute.value
  if (current.meta.requiresAuth) {
    router.replace({ name: 'login', query: { redirect: current.fullPath, expired: '1' } })
  }
})

createApp(App).use(router).mount('#app')
