import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import es from './locales/es.json';

const messages = {
    en,
    es,
};

// Get language from localStorage or default to Spanish
const savedLanguage = localStorage.getItem('language') || 'es';

const i18n = createI18n({
    legacy: false,
    locale: savedLanguage,
    fallbackLocale: 'es',
    messages,
});

export default i18n;