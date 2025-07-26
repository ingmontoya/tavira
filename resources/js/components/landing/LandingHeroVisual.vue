<template>
    <div :class="['relative transition-all delay-300 duration-1000 overflow-hidden', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0']">
        <div class="relative px-8 py-6">
            <!-- Main Dashboard Preview -->
            <div class="rounded-2xl border border-white/20 bg-white/10 p-6 shadow-2xl backdrop-blur-lg">
                <div class="mb-4 flex items-center space-x-3">
                    <div class="flex space-x-2">
                        <div class="h-3 w-3 rounded-full bg-red-400"></div>
                        <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                        <div class="h-3 w-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="text-sm text-white/70">habitta.com/dashboard</div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-cyan-500/30 bg-gradient-to-r from-cyan-500/20 to-blue-500/20 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-medium text-white">{{ $t('dashboard.monthlyIncome') }}</span>
                            <span class="text-green-400">+12.5%</span>
                        </div>
                        <div class="text-2xl font-bold text-white">$245,680,000</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-sm text-white/70">{{ $t('dashboard.apartments') }}</div>
                            <div class="text-xl font-bold text-white">156</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-sm text-white/70">{{ $t('dashboard.delinquent') }}</div>
                            <div class="text-xl font-bold text-orange-400">8</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Elements -->
            <div class="absolute top-0 right-0 animate-bounce rounded-xl bg-gradient-to-r from-emerald-400 to-teal-500 p-4 shadow-xl">
                <div class="text-sm font-medium text-white">{{ $t('dashboard.newInvoice') }}</div>
                <div class="text-xs text-white/80">Apt 304-A</div>
            </div>

            <div class="absolute bottom-2 left-0 animate-pulse rounded-xl bg-gradient-to-r from-purple-400 to-pink-500 p-4 shadow-xl">
                <div class="text-sm font-medium text-white">{{ $t('dashboard.paymentReceived') }}</div>
                <div class="text-xs text-white/80">$850,000</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    isVisible: boolean;
}>();
</script>