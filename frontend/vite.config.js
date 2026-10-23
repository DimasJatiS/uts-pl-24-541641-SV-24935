import vue from '@vitejs/plugin-vue'
import { defineConfig, loadEnv } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Prefix '' = baca semua variabel .env, tapi hanya yang berawalan VITE_
  // yang ikut ter-bundle ke browser. DEV_API_PROXY_TARGET hanya dipakai di sini.
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [vue()],
    server: {
      port: 5173,
      // Saat development, /api diteruskan ke Laravel (php artisan serve)
      // supaya browser melihat satu origin, sama seperti di Docker.
      proxy: {
        '/api': {
          target: env.DEV_API_PROXY_TARGET || 'http://localhost:8000',
          changeOrigin: true,
        },
      },
    },
    build: {
      sourcemap: false,
    },
  }
})
