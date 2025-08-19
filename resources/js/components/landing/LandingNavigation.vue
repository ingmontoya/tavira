<template>
    <nav class="relative z-50 bg-[#1D3557] px-6 py-4">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#06D6A0] shadow-lg">
                    <span class="text-xl font-bold text-white font-brand">T</span>
                </div>
                <span class="text-2xl font-bold text-white font-brand tracking-tight">Tavira</span>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-6">
                <LanguageSwitcher />
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-6 py-3 font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 hover:from-[#2a4870] hover:to-[#07e8b5]"
                >
                    {{ $t('nav.dashboard') }}
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="rounded-lg px-4 py-2 text-white/80 font-medium transition-colors duration-200 hover:text-[#06D6A0] hover:bg-white/10">
                        {{ $t('nav.login') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="flex-shrink-0 inline-flex items-center rounded-lg bg-[#06D6A0] px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 hover:bg-[#07e8b5] sm:px-6 sm:py-3 sm:text-base"
                    >
                        {{ $t('nav.register') }}
                    </Link>
                </template>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LanguageSwitcher from '../LanguageSwitcher.vue';
</script>
