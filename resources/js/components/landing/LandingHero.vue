<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 px-6 py-20">
        <!-- Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-1/2 -right-1/2 h-full w-full rounded-full bg-gradient-to-l from-cyan-500/10 to-transparent blur-3xl"></div>
            <div class="absolute -bottom-1/2 -left-1/2 h-full w-full rounded-full bg-gradient-to-r from-blue-500/10 to-transparent blur-3xl"></div>
        </div>

        <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
            <div :class="['space-y-8 transition-all duration-1000', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0']">
                <div class="space-y-6">
                    <div
                        class="inline-flex items-center space-x-2 rounded-full border border-cyan-500/20 bg-white/10 px-4 py-2 text-cyan-300 backdrop-blur-sm"
                    >
                        <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-400"></span>
                        <span class="text-sm font-medium">{{ $t('hero.badge') }}</span>
                    </div>

                    <h1 class="text-5xl leading-tight font-bold text-white lg:text-7xl">
                        {{ $t('hero.title') }}
                        <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            {{ $t('hero.titleHighlight') }}
                        </span>
                        <br />{{ $t('hero.titleEnd') }}
                    </h1>

                    <p class="max-w-lg text-xl leading-relaxed text-white/80">
                        {{ $t('hero.description') }}
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="group flex-shrink-0 transform rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-2xl transition-all duration-200 hover:-translate-y-1 hover:from-cyan-600 hover:to-blue-700 hover:shadow-cyan-500/25 sm:px-8 sm:py-4 sm:text-lg"
                    >
                        <span class="flex items-center justify-center space-x-2">
                            <span class="sm:hidden">{{ $t('hero.ctaPrimary') }}</span>
                            <span class="hidden sm:inline">{{ $t('hero.ctaPrimaryFull') }}</span>
                            <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </span>
                    </Link>

                    <button
                        class="flex-shrink-0 rounded-xl border-2 border-white/30 px-3 py-2 text-xs font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:bg-white/10 sm:px-8 sm:py-4 sm:text-lg"
                    >
                        <span class="sm:hidden">{{ $t('hero.ctaSecondary') }}</span>
                        <span class="hidden sm:inline">{{ $t('hero.ctaSecondaryFull') }}</span>
                    </button>
                </div>

                <!-- Stats -->
                <LandingStats :parent-visible="isVisible" />
            </div>

            <!-- Hero Visual -->
            <LandingHeroVisual :is-visible="isVisible" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import LandingHeroVisual from './LandingHeroVisual.vue';
import LandingStats from './LandingStats.vue';

const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>
