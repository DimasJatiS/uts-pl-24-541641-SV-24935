<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { coinsApi, holdingsApi } from '../api'
import { firstError, parseApiError } from '../utils/errors'

const props = defineProps({
  id: { type: String, default: null },
})

const router = useRouter()
const isEdit = computed(() => Boolean(props.id))

const form = reactive({ coin_id: '', amount: '', avg_buy_price_usd: '', notes: '' })
const coins = ref([])
const editingCoin = ref(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref({ message: '', fields: {} })

// Orang Indonesia sering menulis desimal dengan koma: "0,5" → "0.5".
const normalizeNumber = (value) => String(value).trim().replace(',', '.')

onMounted(async () => {
  try {
    if (isEdit.value) {
      const holding = await holdingsApi.get(props.id)
      editingCoin.value = holding.coin
      form.amount = holding.amount
      form.avg_buy_price_usd = holding.avg_buy_price_usd
      form.notes = holding.notes ?? ''
    } else {
      // Sembunyikan koin yang sudah dimiliki (satu koin hanya boleh satu holding).
      const [allCoins, owned] = await Promise.all([coinsApi.list(), holdingsApi.list()])
      const ownedIds = new Set(owned.map((h) => h.coin?.id))
      coins.value = allCoins.filter((c) => !ownedIds.has(c.id))
    }
  } catch (e) {
    error.value = parseApiError(e)
  } finally {
    loading.value = false
  }
})

async function submit() {
  submitting.value = true
  error.value = { message: '', fields: {} }

  const payload = {
    amount: normalizeNumber(form.amount),
    avg_buy_price_usd: normalizeNumber(form.avg_buy_price_usd),
    notes: form.notes.trim() || null,
  }

  try {
    if (isEdit.value) {
      await holdingsApi.update(props.id, payload)
    } else {
      await holdingsApi.create({ coin_id: form.coin_id, ...payload })
    }
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = parseApiError(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="form-page">
    <RouterLink class="back-link" :to="{ name: 'dashboard' }">← Kembali ke portofolio</RouterLink>
    <h1>{{ isEdit ? 'Edit holding' : 'Tambah holding' }}</h1>

    <p v-if="error.message" class="alert alert-error" role="alert">{{ error.message }}</p>

    <div v-if="loading" class="skeleton" aria-busy="true">Memuat…</div>

    <form v-else class="card" novalidate @submit.prevent="submit">
      <label v-if="!isEdit" class="field">
        <span>Koin</span>
        <select v-model="form.coin_id" required>
          <option value="" disabled>Pilih koin…</option>
          <option v-for="coin in coins" :key="coin.id" :value="coin.id">
            {{ coin.name }} ({{ coin.symbol }})
          </option>
        </select>
        <small v-if="firstError(error.fields, 'coin_id')" class="field-error">{{ firstError(error.fields, 'coin_id') }}</small>
      </label>
      <div v-else class="field">
        <span>Koin</span>
        <p class="readonly">{{ editingCoin?.name }} ({{ editingCoin?.symbol }})</p>
      </div>

      <label class="field">
        <span>Jumlah koin</span>
        <input v-model="form.amount" type="text" inputmode="decimal" placeholder="contoh: 0.25" required />
        <small v-if="firstError(error.fields, 'amount')" class="field-error">{{ firstError(error.fields, 'amount') }}</small>
      </label>

      <label class="field">
        <span>Harga beli rata-rata (USD per koin)</span>
        <input v-model="form.avg_buy_price_usd" type="text" inputmode="decimal" placeholder="contoh: 64000" required />
        <small v-if="firstError(error.fields, 'avg_buy_price_usd')" class="field-error">{{ firstError(error.fields, 'avg_buy_price_usd') }}</small>
      </label>

      <label class="field">
        <span>Catatan <em class="muted">(opsional)</em></span>
        <textarea v-model="form.notes" rows="3" maxlength="500"></textarea>
        <small v-if="firstError(error.fields, 'notes')" class="field-error">{{ firstError(error.fields, 'notes') }}</small>
      </label>

      <div class="form-actions">
        <RouterLink class="btn btn-ghost" :to="{ name: 'dashboard' }">Batal</RouterLink>
        <button class="btn btn-primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Menyimpan…' : 'Simpan' }}
        </button>
      </div>
    </form>
  </section>
</template>
