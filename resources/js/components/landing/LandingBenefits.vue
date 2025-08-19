<template>
    <section id="benefits" class="bg-gradient-to-br from-[#F5F5F5] to-white px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-3xl md:text-4xl lg:text-5xl font-bold text-[#1D3557]">
                    {{ $t('benefits.sectionTitle') }}
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent">
                        {{ $t('benefits.sectionTitleHighlight') }}
                    </span>
                </h2>
                <p class="mx-auto max-w-3xl text-lg md:text-xl text-gray-700">
                    {{ $t('benefits.sectionDescription') }}
                </p>
            </div>

            <div class="grid gap-6 md:gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(benefit, index) in benefits"
                    :key="index"
                    class="rounded-2xl bg-white p-6 md:p-8 text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-xl border-l-4 border-[#06D6A0]"
                >
                    <div class="mb-4 text-4xl">{{ benefit.icon }}</div>
                    <div class="mb-2 text-2xl md:text-3xl font-bold text-[#1D3557]">{{ benefit.percentage }}</div>
                    <h3 class="mb-3 text-lg md:text-xl font-bold text-[#1D3557]">{{ benefit.title }}</h3>
                    <p class="text-gray-600">{{ benefit.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const benefits = computed(() => [
    {
        title: t('benefits.timeSaving.title'),
        description: t('benefits.timeSaving.description'),
        icon: '⏰',
        percentage: t('benefits.timeSaving.percentage'),
    },
    {
        title: t('benefits.costReduction.title'),
        description: t('benefits.costReduction.description'),
        icon: '💸',
        percentage: t('benefits.costReduction.percentage'),
    },
    {
        title: t('benefits.satisfaction.title'),
        description: t('benefits.satisfaction.description'),
        icon: '😊',
        percentage: t('benefits.satisfaction.percentage'),
    },
    {
        title: t('benefits.transparency.title'),
        description: t('benefits.transparency.description'),
        icon: '🔍',
        percentage: t('benefits.transparency.percentage'),
    },
]);
</script>
