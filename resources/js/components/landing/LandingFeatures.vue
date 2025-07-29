<template>
    <section class="bg-white px-6 py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-4xl font-bold text-gray-900 lg:text-5xl">
                    {{ $t('features.sectionTitle') }}
                    <span class="bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                        {{ $t('features.sectionTitleHighlight') }}
                    </span>
                    {{ $t('features.sectionTitleEnd') }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{ $t('features.sectionDescription') }}
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
                >
                    <div :class="['mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-r text-2xl', feature.gradient]">
                        {{ feature.icon }}
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-gray-900">{{ feature.title }}</h3>
                    <p class="leading-relaxed text-gray-600">{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const features = computed(() => [
    {
        icon: '🏢',
        title: t('features.management.title'),
        description: t('features.management.description'),
        gradient: 'from-blue-500 to-cyan-500',
    },
    {
        icon: '💰',
        title: t('features.finance.title'),
        description: t('features.finance.description'),
        gradient: 'from-emerald-500 to-teal-500',
    },
    {
        icon: '📱',
        title: t('features.mobile.title'),
        description: t('features.mobile.description'),
        gradient: 'from-purple-500 to-pink-500',
    },
    {
        icon: '🔐',
        title: t('features.security.title'),
        description: t('features.security.description'),
        gradient: 'from-orange-500 to-red-500',
    },
]);
</script>
