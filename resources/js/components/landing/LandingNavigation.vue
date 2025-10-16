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
                    class="lg:hidden text-white p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <LanguageSwitcher />

                <div class="hidden lg:flex items-center space-x-6 mx-6">
                    <Link href="/features"
                        class="text-white/80 font-medium transition-colors duration-200 hover:text-[#06D6A0]">
                    Características
                    </Link>
                    <Link href="security.main"
                        class="text-white/80 font-medium transition-colors duration-200 hover:text-[#06D6A0]">
                    Seguridad
                    </Link>
                    <Link href="provider-register"
                        class="text-white/80 font-medium transition-colors duration-200 hover:text-[#06D6A0]">
                    Eres Proveedor
                    </Link>
                </div>

                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-6 py-3 font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 hover:from-[#2a4870] hover:to-[#07e8b5]">
                Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')"
                        class="rounded-lg px-4 py-2 text-white/80 font-medium transition-colors duration-200 hover:text-[#06D6A0] hover:bg-white/10">
                    Iniciar Sesión
                    </Link>
                    <Link :href="route('register')"
                        class="flex-shrink-0 inline-flex items-center rounded-lg bg-[#FF6B6B] px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 hover:bg-[#ff5555] sm:px-6 sm:py-3 sm:text-base">
                    Prueba Gratis
                    </Link>
                </template>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div v-if="mobileMenuOpen" class="lg:hidden border-t border-white/10">
            <div class="py-4 space-y-2">
                <Link href="/features"
                    class="block px-4 py-2 text-white/80 hover:bg-white/10 rounded-lg transition-colors"
                    @click="mobileMenuOpen = false">
                Características
                </Link>
                <Link :href="route('security.main')"
                    class="block px-4 py-2 text-white/80 hover:bg-white/10 rounded-lg transition-colors"
                    @click="mobileMenuOpen = false">
                Seguridad
                </Link>
                <Link :href="route('provider-register')"
                    class="block px-4 py-2 text-white/80 hover:bg-white/10 rounded-lg transition-colors"
                    @click="mobileMenuOpen = false">
                Eres Proveedor
                </Link>

                <div class="pt-4 space-y-2 border-t border-white/10">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                        class="block px-4 py-2 text-center bg-gradient-to-r from-[#1D3557] to-[#06D6A0] rounded-lg text-white font-medium"
                        @click="mobileMenuOpen = false">
                    Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')"
                            class="block px-4 py-2 text-center text-white/80 hover:bg-white/10 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false">
                        Iniciar Sesión
                        </Link>
                        <Link :href="route('register')"
                            class="block px-4 py-2 text-center bg-[#FF6B6B] rounded-lg text-white font-medium"
                            @click="mobileMenuOpen = false">
                        Prueba Gratis
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LanguageSwitcher from '../LanguageSwitcher.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const mobileMenuOpen = ref(false);
</script>
