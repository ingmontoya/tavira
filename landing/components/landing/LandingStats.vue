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
const { t } = useI18n()

const props = defineProps<{
  parentVisible?: boolean
}>()

const isVisible = ref(false)

const stats = computed(() => [
  { number: t('stats.complexesValue'), label: t('stats.complexes') },
  { number: t('stats.residentsValue'), label: t('stats.residents') },
  { number: t('stats.uptimeValue'), label: t('stats.uptime') },
  { number: t('stats.supportValue'), label: t('stats.support') },
])

// Watch for parent's isVisible changes
watch(
  () => props.parentVisible,
  (visible) => {
    if (visible) {
      setTimeout(() => {
        isVisible.value = true
      }, 200)
    }
  },
  { immediate: true },
)
</script>
