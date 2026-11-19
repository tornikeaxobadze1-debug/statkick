import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv, type ProxyOptions } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  /*
   * Dev/preview proxy: the browser calls same-origin `/api`, and Vite forwards it to
   * Laravel server-side. That removes CORS (any dev port works) and the browser's
   * certificate check on Lerd's local mkcert HTTPS. `secure: false` only affects this
   * local proxy hop; production builds call VITE_API_URL directly.
   */
  const apiProxy: Record<string, ProxyOptions> = {
    '/api': {
      target: env.API_PROXY_TARGET || 'http://localhost:8000',
      changeOrigin: true,
      secure: false,
    },
    // Player cut-outs the API serves from Laravel's public storage (/storage/cutouts/...).
    '/storage': {
      target: env.API_PROXY_TARGET || 'http://localhost:8000',
      changeOrigin: true,
      secure: false,
    },
  }

  return {
    plugins: [react(), tailwindcss()],
    server: { proxy: apiProxy },
    preview: { proxy: apiProxy },
    build: {
      // Flags stay separate, cacheable SVG files fetched only when shown, rather than
      // ~270 data URIs inlined into the main bundle.
      assetsInlineLimit: (file: string) => (file.includes('flag-icons') ? false : undefined),
    },
  }
})
