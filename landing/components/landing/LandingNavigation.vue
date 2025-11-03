<template>
    <nav class="relative z-50 bg-accent px-6 py-4">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <div class="flex items-center">
                <NuxtLink to="/" class="flex items-center">
                    <div class="w-48 overflow-hidden">
                        <img src="/tavira_logo_blanco.png" alt="Tavira" class="h-full w-full object-contain" />
                    </div>
                    <span class="sr-only">Tavira</span>
                </NuxtLink>
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
                    <NuxtLink to="/features"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-secondary">
                        {{ $t('nav.features') }}
                    </NuxtLink>
                    <NuxtLink to="/security"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-secondary">
                        {{ $t('nav.security') }}
                    </NuxtLink>
                    <NuxtLink to="/provider-register"
                        class="font-medium text-white/80 transition-colors duration-200 hover:text-secondary">
                        {{ $t('nav.providers') }}
                    </NuxtLink>
                </div>

                <a href="https://tavira.com.co/login"
                    class="rounded-lg px-4 py-2 font-medium text-white/80 transition-colors duration-200 hover:bg-white/10 hover:text-secondary">
                    {{ $t('nav.login') }}
                </a>
                <a href="https://tavira.com.co/register"
                    class="inline-flex flex-shrink-0 items-center rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-primary-700 hover:shadow-xl sm:px-6 sm:py-3 sm:text-base">
                    {{ $t('nav.register') }}
                </a>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div v-if="mobileMenuOpen" class="border-t border-white/10 lg:hidden">
            <div class="space-y-2 py-4">
                <NuxtLink to="/features"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                    {{ $t('nav.features') }}
                </NuxtLink>
                <NuxtLink to="/security"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                    {{ $t('nav.security') }}
                </NuxtLink>
                <NuxtLink to="/provider-register"
                    class="block rounded-lg px-4 py-2 text-white/80 transition-colors hover:bg-white/10"
                    @click="mobileMenuOpen = false">
                    {{ $t('nav.providers') }}
                </NuxtLink>

                <div class="space-y-2 border-t border-white/10 pt-4">
                    <a href="https://tavira.com.co/login"
                        class="block rounded-lg px-4 py-2 text-center text-white/80 transition-colors hover:bg-white/10">
                        {{ $t('nav.login') }}
                    </a>
                    <a href="https://tavira.com.co/register"
                        class="block rounded-lg bg-primary px-4 py-2 text-center font-medium text-white">
                        {{ $t('nav.register') }}
                    </a>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
const mobileMenuOpen = ref(false)
</script>
