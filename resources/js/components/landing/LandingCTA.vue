<template>
    <section id="cta" class="bg-gradient-to-br from-[#1D3557] via-[#1D3557] to-[#06D6A0] px-6 py-16 md:py-20">
        <div class="mx-auto max-w-4xl text-center">
            <div class="rounded-3xl border border-white/20 bg-gradient-to-r from-white/10 to-white/5 p-8 md:p-12 backdrop-blur-lg">
                <h2 class="mb-6 text-3xl md:text-4xl lg:text-5xl font-bold text-white">
                    {{ $t('cta.title') }}
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent">
                        {{ $t('cta.titleHighlight') }}
                    </span>
                </h2>
                <p class="mx-auto mb-8 max-w-2xl text-lg md:text-xl text-white/85">
                    {{ $t('cta.description') }}
                </p>

                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="flex-shrink-0 transform rounded-xl bg-gradient-to-r from-[#FF6B6B] to-[#FF6B6B] px-4 py-3 md:px-8 md:py-4 text-sm md:text-lg font-semibold text-white shadow-2xl transition-all duration-200 hover:-translate-y-1 hover:from-[#ff5555] hover:to-[#ff5555] hover:shadow-[#FF6B6B]/25"
                    >
                        <span class="sm:hidden">{{ $t('cta.primary') }}</span>
                        <span class="hidden sm:inline">{{ $t('cta.primaryFull') }}</span>
                    </Link>
                    <button
                        class="flex-shrink-0 rounded-xl border-2 border-white/30 px-4 py-3 md:px-8 md:py-4 text-sm md:text-lg font-semibold text-white transition-all duration-200 hover:bg-white/15 hover:border-white/50"
                    >
                        <span class="sm:hidden">{{ $t('cta.secondary') }}</span>
                        <span class="hidden sm:inline">{{ $t('cta.secondaryFull') }}</span>
                    </button>
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-6 text-sm text-white/60">
                    <span v-for="benefit in benefits" :key="benefit" class="flex items-center space-x-2">
                        <svg class="h-4 w-4 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span>{{ benefit }}</span>
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const benefits = computed(() => [t('cta.benefits.freeTrial'), t('cta.benefits.noCard'), t('cta.benefits.support'), t('cta.benefits.implementation')]);
</script>
