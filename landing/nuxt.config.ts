// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@nuxt/image',
    '@nuxtjs/i18n'
  ],

  i18n: {
    locales: ['es', 'en'],
    defaultLocale: 'es',
    strategy: 'no_prefix',
    detectBrowserLanguage: false
  },

  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1, viewport-fit=cover',
      htmlAttrs: {
        lang: 'es'
      },
      link: [
        {
          rel: 'preconnect',
          href: 'https://fonts.googleapis.com'
        },
        {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: 'anonymous'
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap'
        },
        {
          rel: 'icon',
          type: 'image/png',
          href: '/favicon.png'
        },
        {
          rel: 'apple-touch-icon',
          sizes: '180x180',
          href: '/apple-touch-icon.png'
        }
      ]
    }
  },

  // Image optimization
  image: {
    quality: 80,
    format: ['webp', 'jpeg'],
    screens: {
      xs: 320,
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1280,
      xxl: 1536,
    }
  },

  // Nitro configuration for better performance
  nitro: {
    compressPublicAssets: true,
    prerender: {
      crawlLinks: true,
      routes: ['/', '/contacto', '/features', '/security', '/red-tavira', '/politica-privacidad', '/terminos-servicio']
    }
  },

  // Build optimizations
  build: {
    transpile: []
  },

  // Runtime config
  runtimeConfig: {
    // Private keys (server-side only)
    perfexBaseUrl: process.env.PERFEX_BASE_URL || 'https://perfexcrm.themesic.com',
    perfexApiUser: process.env.PERFEX_API_USER || 'precontactos',
    perfexApiToken: process.env.PERFEX_API_TOKEN || '',

    // Public keys (exposed to client)
    public: {
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'https://tavira.com.co',
      siteName: 'Tavira',
      siteDescription: 'Software líder para administración de conjuntos residenciales en Colombia',
      language: 'es-CO',
      environment: process.env.NUXT_PUBLIC_ENV || 'production'
    }
  },

  // Experimental features
  experimental: {
    payloadExtraction: true,
    renderJsonPayloads: true,
    typedPages: true
  }
})
