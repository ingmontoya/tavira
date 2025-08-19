<template>
    <section id="testimonials" class="bg-gradient-to-br from-[#1D3557] via-[#1D3557] to-[#06D6A0] px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-3xl md:text-4xl lg:text-5xl font-bold text-white">
                    {{ $t('testimonials.sectionTitle') }}
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent">
                        {{ $t('testimonials.sectionTitleHighlight') }}
                    </span>
                </h2>
                <p class="mx-auto max-w-3xl text-lg md:text-xl text-white/85">
                    {{ $t('testimonials.sectionDescription') }}
                </p>
            </div>

            <div class="grid gap-6 md:gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    class="rounded-2xl border border-white/20 bg-white/10 p-6 md:p-8 backdrop-blur-lg transition-all duration-300 hover:-translate-y-1 hover:bg-white/15 hover:border-white/30">
                >
                    <div class="mb-4 flex space-x-1">
                        <svg v-for="i in 5" :key="i" class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                        </svg>
                    </div>
                    <p class="mb-6 text-white/90 italic">"{{ testimonial.content }}"</p>
                    <div>
                        <div class="font-semibold text-white">{{ testimonial.name }}</div>
                        <div class="text-sm text-white/70">{{ testimonial.role }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const testimonials = computed(() => [
    {
        name: t('testimonials.maria.name'),
        role: t('testimonials.maria.role'),
        content: t('testimonials.maria.content'),
    },
    {
        name: t('testimonials.carlos.name'),
        role: t('testimonials.carlos.role'),
        content: t('testimonials.carlos.content'),
    },
    {
        name: t('testimonials.ana.name'),
        role: t('testimonials.ana.role'),
        content: t('testimonials.ana.content'),
    },
]);
</script>
