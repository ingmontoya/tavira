import es from '../locales/es.json'
import en from '../locales/en.json'

export default defineNuxtPlugin((nuxtApp) => {
  // Get the i18n instance
  const i18n = nuxtApp.$i18n as any

  // Manually set locale messages
  i18n.setLocaleMessage('es', es)
  i18n.setLocaleMessage('en', en)

  // Ensure Spanish is the active locale
  i18n.setLocale('es')
})
