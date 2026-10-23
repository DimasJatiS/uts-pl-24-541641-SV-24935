<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '../api'
import { setSession } from '../stores/auth'
import { firstError, parseApiError } from '../utils/errors'

const router = useRouter()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const submitting = ref(false)
const error = ref({ message: '', fields: {} })

async function submit() {
  submitting.value = true
  error.value = { message: '', fields: {} }
  try {
    const session = await authApi.register({ ...form })
    setSession(session)
    router.replace({ name: 'dashboard' })
  } catch (e) {
    error.value = parseApiError(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="auth">
    <div class="auth-card">
      <h1>Buat akun</h1>
      <p class="muted">Password minimal 8 karakter, berisi huruf besar, huruf kecil, dan angka.</p>

      <p v-if="error.message" class="alert alert-error" role="alert">{{ error.message }}</p>

      <form novalidate @submit.prevent="submit">
        <label class="field">
          <span>Nama</span>
          <input v-model.trim="form.name" type="text" autocomplete="name" maxlength="100" required />
          <small v-if="firstError(error.fields, 'name')" class="field-error">{{ firstError(error.fields, 'name') }}</small>
        </label>
        <label class="field">
          <span>Email</span>
          <input v-model.trim="form.email" type="email" autocomplete="email" required />
          <small v-if="firstError(error.fields, 'email')" class="field-error">{{ firstError(error.fields, 'email') }}</small>
        </label>
        <label class="field">
          <span>Password</span>
          <input v-model="form.password" type="password" autocomplete="new-password" required />
          <small v-if="firstError(error.fields, 'password')" class="field-error">{{ firstError(error.fields, 'password') }}</small>
        </label>
        <label class="field">
          <span>Ulangi password</span>
          <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
        </label>
        <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
          {{ submitting ? 'Memproses…' : 'Daftar' }}
        </button>
      </form>

      <p class="auth-switch">
        Sudah punya akun? <RouterLink :to="{ name: 'login' }">Masuk</RouterLink>
      </p>
    </div>
  </section>
</template>
