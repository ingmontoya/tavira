<template>
  <section id="why-tavira" class="bg-gray-50 px-6 py-16 md:py-24">
    <div class="mx-auto max-w-7xl">
      <!-- Title -->
      <h2 class="mb-12 text-center font-brand text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
        {{ $t('whyTavira.title') }}
      </h2>

      <!-- Reasons Grid -->
      <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="(reason, index) in reasons"
          :key="index"
          class="rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:border-green-400 hover:shadow-xl"
        >
          <div class="mb-4 flex items-center space-x-3">
            <span class="text-3xl">{{ reason.icon }}</span>
            <h3 class="text-xl font-bold text-gray-900">{{ reason.title }}</h3>
          </div>
          <p class="text-gray-600">{{ reason.description }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const reasons = computed(() => [
  {
    icon: t('whyTavira.reason1.icon'),
    title: t('whyTavira.reason1.title'),
    description: t('whyTavira.reason1.description'),
  },
  {
    icon: t('whyTavira.reason2.icon'),
    title: t('whyTavira.reason2.title'),
    description: t('whyTavira.reason2.description'),
  },
  {
    icon: t('whyTavira.reason3.icon'),
    title: t('whyTavira.reason3.title'),
    description: t('whyTavira.reason3.description'),
  },
  {
    icon: t('whyTavira.reason4.icon'),
    title: t('whyTavira.reason4.title'),
    description: t('whyTavira.reason4.description'),
  },
  {
    icon: t('whyTavira.reason5.icon'),
    title: t('whyTavira.reason5.title'),
    description: t('whyTavira.reason5.description'),
  },
  {
    icon: t('whyTavira.reason6.icon'),
    title: t('whyTavira.reason6.title'),
    description: t('whyTavira.reason6.description'),
  },
])
</script>
