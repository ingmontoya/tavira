<template>
  <section id="fast-response" class="bg-white px-6 py-16 md:py-24">
    <div class="mx-auto max-w-7xl">
      <!-- Title -->
      <h2 class="mb-6 text-center font-brand text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
        {{ $t('fastResponse.title') }}
      </h2>

      <!-- Subtitle -->
      <p class="mx-auto mb-12 max-w-3xl text-center text-lg text-gray-600 md:text-xl">
        {{ $t('fastResponse.subtitle') }}
      </p>

      <!-- Comparison -->
      <div class="grid gap-8 lg:grid-cols-2">
        <!-- Before -->
        <div class="rounded-2xl border-4 border-red-200 bg-red-50 p-8">
          <div class="mb-6 flex items-center justify-between">
            <h3 class="text-2xl font-bold text-gray-900">{{ $t('fastResponse.before.title') }}</h3>
            <span class="text-4xl">{{ $t('fastResponse.before.status') }}</span>
          </div>

          <div class="space-y-4">
            <div
              v-for="(step, index) in beforeSteps"
              :key="index"
              class="flex items-center space-x-3 rounded-lg bg-white p-4"
            >
              <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-600"
              >
                {{ index + 1 }}
              </div>
              <span class="text-gray-700">{{ step }}</span>
            </div>
          </div>

          <div class="mt-6 rounded-lg bg-red-600 p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $t('fastResponse.before.total') }}</div>
          </div>
        </div>

        <!-- After -->
        <div class="rounded-2xl border-4 border-green-200 bg-green-50 p-8">
          <div class="mb-6 flex items-center justify-between">
            <h3 class="text-2xl font-bold text-gray-900">{{ $t('fastResponse.after.title') }}</h3>
            <span class="text-4xl">{{ $t('fastResponse.after.status') }}</span>
          </div>

          <div class="space-y-4">
            <div
              v-for="(step, index) in afterSteps"
              :key="index"
              class="flex items-center space-x-3 rounded-lg bg-white p-4"
            >
              <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600"
              >
                {{ index + 1 }}
              </div>
              <span class="text-gray-700">{{ step }}</span>
            </div>
          </div>

          <div class="mt-6 rounded-lg bg-green-600 p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $t('fastResponse.after.total') }}</div>
          </div>
        </div>
      </div>

      <!-- Included -->
      <div class="mt-12 text-center">
        <p class="text-lg text-gray-600">
          <span class="font-bold text-accent">{{ $t('fastResponse.included') }}</span>
        </p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const beforeSteps = computed(() => [
  t('fastResponse.before.step1'),
  t('fastResponse.before.step2'),
  t('fastResponse.before.step3'),
  t('fastResponse.before.step4'),
])

const afterSteps = computed(() => [
  t('fastResponse.after.step1'),
  t('fastResponse.after.step2'),
  t('fastResponse.after.step3'),
  t('fastResponse.after.step4'),
])
</script>
