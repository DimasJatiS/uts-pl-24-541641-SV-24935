/**
 * Ubah error Axios menjadi { message, fields } yang siap ditampilkan.
 * fields berisi error validasi 422 dari Laravel: { amount: ['...'], ... }.
 */
export function parseApiError(error) {
  const status = error?.response?.status
  const data = error?.response?.data ?? {}

  if (!error?.response) {
    return { message: 'Tidak bisa terhubung ke server. Coba lagi sebentar.', fields: {} }
  }
  if (status === 422) {
    return { message: data.message ?? 'Periksa kembali isian kamu.', fields: data.errors ?? {} }
  }
  if (status === 429) {
    return { message: 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.', fields: {} }
  }
  if (status >= 500) {
    return { message: 'Server sedang bermasalah. Coba lagi nanti.', fields: {} }
  }
  return { message: data.message ?? 'Terjadi kesalahan.', fields: {} }
}

export function firstError(fields, name) {
  return fields?.[name]?.[0] ?? ''
}
