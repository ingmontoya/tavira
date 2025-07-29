<template>
    <section class="bg-white px-6 py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-4xl font-bold text-gray-900 lg:text-5xl">
                    {{ $t('detailedFeatures.sectionTitle') }}
                    <span class="bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                        {{ $t('detailedFeatures.sectionTitleHighlight') }}
                    </span>
                    {{ $t('detailedFeatures.sectionTitleEnd') }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{ $t('detailedFeatures.sectionDescription') }}
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                >
                    <div class="mb-6 text-4xl">{{ feature.icon }}</div>
                    <h3 class="mb-3 text-2xl font-bold text-gray-900">{{ feature.title }}</h3>
                    <p class="mb-6 text-gray-600">{{ feature.description }}</p>
                    <ul class="space-y-2">
                        <li v-for="(item, idx) in feature.features" :key="idx" class="flex items-center space-x-3">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-gray-700">{{ item }}</span>
                        </li>
                    </ul>
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
        icon: '👥',
        title: t('detailedFeatures.residents.title'),
        description: t('detailedFeatures.residents.description'),
        features: Array.isArray(t('detailedFeatures.residents.features')) ? (t('detailedFeatures.residents.features') as string[]) : [],
    },
    {
        icon: '🏠',
        title: t('detailedFeatures.apartments.title'),
        description: t('detailedFeatures.apartments.description'),
        features: Array.isArray(t('detailedFeatures.apartments.features')) ? (t('detailedFeatures.apartments.features') as string[]) : [],
    },
    {
        icon: '💳',
        title: t('detailedFeatures.payments.title'),
        description: t('detailedFeatures.payments.description'),
        features: Array.isArray(t('detailedFeatures.payments.features')) ? (t('detailedFeatures.payments.features') as string[]) : [],
    },
    {
        icon: '📊',
        title: t('detailedFeatures.reports.title'),
        description: t('detailedFeatures.reports.description'),
        features: Array.isArray(t('detailedFeatures.reports.features')) ? (t('detailedFeatures.reports.features') as string[]) : [],
    },
    {
        icon: '📢',
        title: t('detailedFeatures.communication.title'),
        description: t('detailedFeatures.communication.description'),
        features: Array.isArray(t('detailedFeatures.communication.features')) ? (t('detailedFeatures.communication.features') as string[]) : [],
    },
    {
        icon: '🛡️',
        title: t('detailedFeatures.access.title'),
        description: t('detailedFeatures.access.description'),
        features: Array.isArray(t('detailedFeatures.access.features')) ? (t('detailedFeatures.access.features') as string[]) : [],
    },
]);
</script>
