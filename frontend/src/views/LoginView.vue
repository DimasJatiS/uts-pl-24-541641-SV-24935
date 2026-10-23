<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '../api'
import { setSession } from '../stores/auth'
import { firstError, parseApiError } from '../utils/errors'

const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const submitting = ref(false)
const error = ref({ message: '', fields: {} })
const expired = computed(() => route.query.expired === '1')

// Hanya izinkan redirect ke path internal (cegah open redirect ke situs lain).
function safeRedirect(value) {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//') ? value : '/'
}

async function submit() {
  submitting.value = true
  error.value = { message: '', fields: {} }
  try {
    const session = await authApi.login({ email: form.email, password: form.password })
    setSession(session)
    router.replace(safeRedirect(route.query.redirect))
  } catch (e) {
    error.value = parseApiError(e)
    form.password = ''
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="auth">
    <div class="auth-card">
      <h1>Masuk ke ChainFolio</h1>
      <p class="muted">Pantau nilai aset kripto kamu di satu tempat.</p>

      <p v-if="expired" class="alert alert-info">Sesi kamu sudah berakhir. Silakan masuk lagi.</p>
      <p v-if="error.message" class="alert alert-error" role="alert">{{ error.message }}</p>

      <form novalidate @submit.prevent="submit">
        <label class="field">
          <span>Email</span>
          <input v-model.trim="form.email" type="email" autocomplete="email" required />
          <small v-if="firstError(error.fields, 'email')" class="field-error">{{ firstError(error.fields, 'email') }}</small>
        </label>
        <label class="field">
          <span>Password</span>
          <input v-model="form.password" type="password" autocomplete="current-password" required />
          <small v-if="firstError(error.fields, 'password')" class="field-error">{{ firstError(error.fields, 'password') }}</small>
        </label>
        <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
          {{ submitting ? 'Memproses…' : 'Masuk' }}
        </button>
      </form>

      <p class="auth-switch">
        Belum punya akun? <RouterLink :to="{ name: 'register' }">Daftar</RouterLink>
      </p>
    </div>
  </section>
</template>
