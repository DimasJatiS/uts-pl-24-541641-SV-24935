<script setup>
import { computed, onMounted, ref } from 'vue'
import { holdingsApi, portfolioApi } from '../api'
import StatCard from '../components/StatCard.vue'
import { authState } from '../stores/auth'
import { parseApiError } from '../utils/errors'
import {
  formatAmount,
  formatPercent,
  formatPrice,
  formatSignedUsd,
  formatUsd,
  trendClass,
} from '../utils/format'

const summary = ref(null)
const loading = ref(true)
const error = ref('')
const notice = ref('')
const deletingId = ref(null)

const holdings = computed(() => summary.value?.holdings ?? [])
const totals = computed(() => summary.value?.totals ?? {})

async function load() {
  loading.value = true
  error.value = ''
  try {
    summary.value = await portfolioApi.summary()
  } catch (e) {
    error.value = parseApiError(e).message
  } finally {
    loading.value = false
  }
}

async function remove(row) {
  if (!window.confirm(`Hapus ${row.symbol} dari portofolio?`)) return
  deletingId.value = row.holding_id
  notice.value = ''
  try {
    await holdingsApi.remove(row.holding_id)
    notice.value = `${row.symbol} dihapus.`
    await load()
  } catch (e) {
    error.value = parseApiError(e).message
  } finally {
    deletingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <p class="eyebrow">Halo, {{ authState.user?.name }}</p>
        <h1>Portofolio</h1>
      </div>
      <div class="page-actions">
        <button class="btn btn-ghost" type="button" :disabled="loading" @click="load">Muat ulang harga</button>
        <RouterLink class="btn btn-primary" :to="{ name: 'holding-create' }">+ Tambah holding</RouterLink>
      </div>
    </div>

    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
    <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
    <p v-if="summary?.prices_stale" class="alert alert-warning" role="status">
      Harga terbaru belum bisa diambil dari CoinGecko. Nilai pasar sementara tidak ditampilkan; modal tetap akurat.
    </p>

    <div v-if="loading && !summary" class="skeleton" aria-busy="true">Memuat portofolio…</div>

    <template v-else-if="summary">
      <div class="stats">
        <StatCard label="Nilai saat ini" :value="formatUsd(totals.value_usd)" />
        <StatCard label="Total modal" :value="formatUsd(totals.cost_usd)" />
        <StatCard
          label="Untung / rugi"
          :value="formatSignedUsd(totals.pnl_usd)"
          :sub="formatPercent(totals.pnl_percent)"
          :tone="trendClass(totals.pnl_usd)"
        />
        <StatCard label="Jumlah aset" :value="String(holdings.length)" />
      </div>

      <div v-if="holdings.length === 0" class="empty">
        <h2>Portofolio masih kosong</h2>
        <p class="muted">Tambahkan koin pertama kamu untuk mulai memantau nilainya.</p>
        <RouterLink class="btn btn-primary" :to="{ name: 'holding-create' }">Tambah holding</RouterLink>
      </div>

      <div v-else class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th scope="col">Koin</th>
              <th scope="col" class="num">Jumlah</th>
              <th scope="col" class="num">Harga beli rata-rata</th>
              <th scope="col" class="num">Harga sekarang</th>
              <th scope="col" class="num">Nilai</th>
              <th scope="col" class="num">Untung / rugi</th>
              <th scope="col">Alokasi</th>
              <th scope="col"><span class="sr-only">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in holdings" :key="row.holding_id">
              <td>
                <span class="coin-symbol">{{ row.symbol }}</span>
                <span class="coin-name">{{ row.name }}</span>
              </td>
              <td class="num">{{ formatAmount(row.amount) }}</td>
              <td class="num">{{ formatPrice(row.avg_buy_price_usd) }}</td>
              <td class="num">{{ formatPrice(row.price_usd) }}</td>
              <td class="num">{{ formatUsd(row.value_usd) }}</td>
              <td class="num" :class="trendClass(row.pnl_usd)">
                {{ formatSignedUsd(row.pnl_usd) }}
                <small>{{ formatPercent(row.pnl_percent) }}</small>
              </td>
              <td>
                <div v-if="row.allocation_percent !== null" class="alloc" :title="`${row.allocation_percent}%`">
                  <div class="alloc-bar" :style="{ width: `${Math.min(Number(row.allocation_percent), 100)}%` }"></div>
                  <span class="num">{{ Number(row.allocation_percent).toFixed(1) }}%</span>
                </div>
                <span v-else class="muted">—</span>
              </td>
              <td class="row-actions">
                <RouterLink class="btn btn-small" :to="{ name: 'holding-edit', params: { id: row.holding_id } }">Edit</RouterLink>
                <button
                  class="btn btn-small btn-danger"
                  type="button"
                  :disabled="deletingId === row.holding_id"
                  @click="remove(row)"
                >
                  Hapus
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="footnote">Harga dari CoinGecko dalam USD, diperbarui maksimal setiap 60 detik.</p>
    </template>
  </section>
</template>
