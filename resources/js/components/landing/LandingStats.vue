<template>
    <div class="grid grid-cols-2 gap-6 pt-8 lg:grid-cols-4">
        <div
            v-for="(stat, index) in stats"
            :key="index"
            :class="[
                'text-center transition-all duration-1000',
                `delay-[${index * 100}ms]`,
                isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0',
            ]"
        >
            <div class="text-3xl font-bold text-white">{{ stat.number }}</div>
            <div class="text-sm text-white/60">{{ stat.label }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    parentVisible?: boolean;
}>();

const isVisible = ref(false);

const stats = computed(() => [
    { number: 'Creciendo', label: t('stats.complexes') },
    { number: 'Miles', label: t('stats.apartments') },
    { number: '99.9%', label: t('stats.uptime') },
    { number: '24/7', label: t('stats.support') },
]);

// Watch for parent's isVisible changes
watch(() => props.parentVisible, (visible) => {
    if (visible) {
        setTimeout(() => {
            isVisible.value = true;
        }, 200);
    }
}, { immediate: true });
</script>