<template>
    <section id="hero"
        class="relative overflow-hidden bg-gradient-to-br from-[#1D3557] via-[#1D3557] to-[#06D6A0] px-6 py-16 md:py-20">
        <!-- Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-1/2 -right-1/2 h-full w-full rounded-full bg-gradient-to-l from-[#06D6A0]/15 to-transparent blur-3xl">
            </div>
            <div
                class="absolute -bottom-1/2 -left-1/2 h-full w-full rounded-full bg-gradient-to-r from-[#1D3557]/20 to-transparent blur-3xl">
            </div>
        </div>

        <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
            <div
                :class="['space-y-8 transition-all duration-1000', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0']">
                <div class="space-y-6">
                    <div
                        class="inline-flex items-center space-x-2 rounded-full border border-[#06D6A0]/30 bg-white/15 px-4 py-2 text-[#06D6A0] backdrop-blur-sm">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-[#06D6A0]"></span>
                        <span class="text-sm font-medium">🔒 Seguridad Bancaria - Base de Datos Exclusiva</span>
                    </div>

                    <h1 class="font-brand text-4xl leading-tight font-bold text-white md:text-5xl lg:text-7xl">
                        Transforma la
                        <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent">
                            Administración </span>
                        <br />de tu Conjunto
                    </h1>

                    <p class="max-w-lg text-lg leading-relaxed text-white/85 md:text-xl">
                        Plataforma integral para conjuntos residenciales en Colombia. Gestión financiera, comunicación,
                        mantenimiento y más. Con
                        seguridad bancaria y base de datos exclusiva para tu conjunto.
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <Link v-if="!$page.props.auth.user" :href="route('register')"
                        class="group flex-shrink-0 transform rounded-xl bg-[#FF6B6B] px-4 py-3 text-sm font-semibold text-white shadow-2xl transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:bg-[#ff5555] hover:shadow-[#FF6B6B]/40 md:px-8 md:py-4 md:text-lg">
                    <span class="flex items-center justify-center space-x-2">
                        <span class="sm:hidden">Regístrate</span>
                        <span class="hidden sm:inline">Regístrate</span>
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </span>
                    </Link>

                    <button
                        class="flex-shrink-0 rounded-xl border-2 border-[#06D6A0]/50 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:border-[#06D6A0]/70 hover:bg-[#06D6A0]/20 md:px-8 md:py-4 md:text-lg">
                        <span class="sm:hidden">Ver Demo</span>
                        <span class="hidden sm:inline">Ver Demostración</span>
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
