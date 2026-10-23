// Angka dari API berupa string desimal. Konversi ke Number HANYA untuk tampilan.

const usd = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const usdPrecise = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
  minimumFractionDigits: 2,
  maximumFractionDigits: 8,
})

const amount = new Intl.NumberFormat('en-US', { maximumFractionDigits: 10 })

export function formatUsd(value) {
  if (value === null || value === undefined) return '—'
  return usd.format(Number(value))
}

/** Untuk harga per koin (bisa sangat kecil, mis. SHIB). */
export function formatPrice(value) {
  if (value === null || value === undefined) return '—'
  return usdPrecise.format(Number(value))
}

export function formatAmount(value) {
  if (value === null || value === undefined) return '—'
  return amount.format(Number(value))
}

export function formatPercent(value) {
  if (value === null || value === undefined) return '—'
  const n = Number(value)
  return `${n > 0 ? '+' : ''}${n.toFixed(2)}%`
}

export function formatSignedUsd(value) {
  if (value === null || value === undefined) return '—'
  const n = Number(value)
  return `${n > 0 ? '+' : ''}${usd.format(n)}`
}

/** Kelas CSS untuk untung (hijau) / rugi (merah). */
export function trendClass(value) {
  if (value === null || value === undefined) return ''
  const n = Number(value)
  if (n > 0) return 'is-up'
  if (n < 0) return 'is-down'
  return ''
}
