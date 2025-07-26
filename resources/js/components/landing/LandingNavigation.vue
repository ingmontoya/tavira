<template>
    <nav class="relative z-50 bg-slate-900 px-6 py-4">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500">
                    <span class="text-xl font-bold text-white">H</span>
                </div>
                <span class="text-xl font-bold text-white">Habitta</span>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <LanguageSwitcher />
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="transform rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-2.5 font-medium text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-cyan-600 hover:to-blue-700 hover:shadow-xl"
                >
                    {{ $t('nav.dashboard') }}
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-lg px-4 py-2 text-white/80 transition-colors duration-200 hover:text-white"
                    >
                        {{ $t('nav.login') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="transform rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-2 py-1.5 sm:px-6 sm:py-2.5 text-xs sm:text-base font-medium text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-cyan-600 hover:to-blue-700 hover:shadow-xl flex-shrink-0"
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
