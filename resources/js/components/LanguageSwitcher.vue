<template>
    <div class="flex items-center space-x-2">
        <button
            v-for="lang in languages"
            :key="lang.code"
            @click="changeLanguage(lang.code)"
            :class="[
                'flex items-center space-x-1 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-200',
                currentLocale === lang.code ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white',
            ]"
        >
            <span class="text-lg">{{ lang.flag }}</span>
            <span>{{ lang.name }}</span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const languages = [
    { code: 'es', name: 'ES', flag: '🇨🇴' },
    { code: 'en', name: 'EN', flag: '🇺🇸' },
];

const currentLocale = computed(() => locale.value);

const changeLanguage = (langCode: string) => {
    locale.value = langCode;
    localStorage.setItem('language', langCode);
};
</script>
