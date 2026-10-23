<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '../api'
import { authState, clearSession } from '../stores/auth'

const router = useRouter()
const loggingOut = ref(false)

async function logout() {
  loggingOut.value = true
  try {
    // Cabut token di server, bukan hanya menghapusnya di browser.
    await authApi.logout()
  } catch {
    // Token mungkin sudah kedaluwarsa; sesi lokal tetap dibersihkan.
  } finally {
    clearSession()
    loggingOut.value = false
    router.replace({ name: 'login' })
  }
}
</script>

<template>
  <header class="topbar">
    <RouterLink :to="{ name: 'dashboard' }" class="brand">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="22" height="22"><rect width="32" height="32" rx="8" fill="currentColor" /><path d="M9 20.5 14 15l4 3.5 5-6.5" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
      </span>
      ChainFolio
    </RouterLink>
    <div class="topbar-right">
      <span class="topbar-user">{{ authState.user?.name }}</span>
      <button class="btn btn-ghost" type="button" :disabled="loggingOut" @click="logout">
        {{ loggingOut ? 'Keluar…' : 'Keluar' }}
      </button>
    </div>
  </header>
</template>
