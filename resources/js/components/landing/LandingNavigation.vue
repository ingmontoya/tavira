<template>
    <nav class="relative z-50 bg-[#1D3557] px-6">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <div class="flex items-center">
                <Link :href="route('home')" class="flex items-center">
                <div class="w-48 overflow-hidden">
                    <AppLogoIcon :black="true" class="h-full w-full object-contain" />
                </div>
                <span class="sr-only">Tavira</span>
                </Link>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-6">
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="rounded-lg p-2 text-white transition-colors hover:bg-white/10 lg:hidden">
                    <svg v-if="!mobileMenuOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <LanguageSwitcher />

                <div class="mx-6 hidden items-center space-x-6 lg:flex">
                    <Link href="/features"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-[#06D6A0]">
                    Características
                    </Link>
                    <Link href="security.main"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-[#06D6A0]">
                    Seguridad
                    </Link>
                    <Link href="provider-register"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-[#06D6A0]">
                    ¿Eres Proveedor?
                    </Link>
                </div>

                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-6 py-3 font-medium text-white shadow-lg transition-all duration-300 hover:scale-105 hover:from-[#2a4870] hover:to-[#07e8b5] hover:shadow-xl">
                Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')"
                        class="rounded-lg px-4 py-2 font-medium text-white/80 transition-colors duration-200 hover:bg-white/10 hover:text-[#06D6A0]">
                    Iniciar Sesión
                    </Link>
                    <Link :href="route('register')"
                        class="inline-flex flex-shrink-0 items-center rounded-lg bg-[#FF6B6B] px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-[#ff5555] hover:shadow-xl sm:px-6 sm:py-3 sm:text-base">
                    Regístrate
                    </Link>
                </template>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div v-if="mobileMenuOpen" class="border-t border-white/10 lg:hidden">
            <div class="space-y-2 py-4">
                <Link href="/features"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                Características
                </Link>
                <Link :href="route('security.main')"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                Seguridad
                </Link>
                <Link :href="route('provider-register')"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                Eres Proveedor
                </Link>

                <div class="space-y-2 border-t border-white/10 pt-4">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                        class="block rounded-lg bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-4 py-2 text-center font-medium text-white"
                        @click="mobileMenuOpen = false">
                    Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')"
                            class="block rounded-lg px-4 py-2 text-center text-white/80 transition-colors hover:bg-white/10"
                            @click="mobileMenuOpen = false">
                        Iniciar Sesión
                        </Link>
                        <Link :href="route('register')"
                            class="block rounded-lg bg-[#FF6B6B] px-4 py-2 text-center font-medium text-white"
                            @click="mobileMenuOpen = false">
                        Regístrate
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LanguageSwitcher from '../LanguageSwitcher.vue';

const mobileMenuOpen = ref(false);
</script>
