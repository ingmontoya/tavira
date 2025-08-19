<template>
    <section id="how-it-works" class="bg-white px-6 py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-4xl font-bold text-gray-900 lg:text-5xl">
                    {{ $t('howItWorks.sectionTitle') }}
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#1D3557] bg-clip-text text-transparent">
                        {{ $t('howItWorks.sectionTitleHighlight') }}
                    </span>
                    {{ $t('howItWorks.sectionTitleEnd') }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{ $t('howItWorks.sectionDescription') }}
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <div v-for="(step, index) in steps" :key="index" class="text-center">
                    <div
                        class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-[#1D3557] to-[#06D6A0] text-2xl font-bold text-white"
                    >
                        {{ index + 1 }}
                    </div>
                    <h3 class="mb-4 text-2xl font-bold text-gray-900">{{ step.title }}</h3>
                    <p class="text-gray-600">{{ step.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const steps = computed(() => [
    {
        title: t('howItWorks.step1.title'),
        description: t('howItWorks.step1.description'),
    },
    {
        title: t('howItWorks.step2.title'),
        description: t('howItWorks.step2.description'),
    },
    {
        title: t('howItWorks.step3.title'),
        description: t('howItWorks.step3.description'),
    },
]);
</script>
