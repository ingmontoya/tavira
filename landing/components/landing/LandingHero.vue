<template>
  <section
    id="hero"
    class="relative overflow-hidden bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-20"
  >
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div
        class="absolute -top-1/2 -right-1/2 h-full w-full rounded-full bg-gradient-to-l from-secondary/15 to-transparent blur-3xl"
      ></div>
      <div
        class="absolute -bottom-1/2 -left-1/2 h-full w-full rounded-full bg-gradient-to-r from-accent/20 to-transparent blur-3xl"
      ></div>
    </div>

    <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
      <div
        :class="[
          'space-y-8 transition-all duration-1000',
          isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0',
        ]"
      >
        <div class="space-y-6">
          <div
            class="inline-flex items-center space-x-2 rounded-full border-2 border-white/40 bg-white/25 px-4 py-2.5 text-white shadow-lg backdrop-blur-md"
          >
            <span class="h-2 w-2 animate-pulse rounded-full bg-white shadow-sm"></span>
            <span class="text-sm font-semibold">{{ $t('hero.badge') }}</span>
          </div>

          <h1 class="font-brand text-4xl font-bold leading-tight text-white md:text-5xl lg:text-7xl">
            {{ $t('hero.title') }}
            <span class="bg-gradient-to-r from-primary via-orange-400 to-primary bg-clip-text text-transparent">
              {{ $t('hero.titleHighlight') }}
            </span>
            <br />{{ $t('hero.titleEnd') }}
          </h1>

          <p class="max-w-lg text-lg leading-relaxed text-white/85 md:text-xl">
            {{ $t('hero.description') }}
          </p>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
          <a
            href="https://tavira.com.co/register"
            class="group flex-shrink-0 transform rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-2xl transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:bg-primary-700 hover:shadow-primary/40 md:px-8 md:py-4 md:text-lg"
          >
            <span class="flex items-center justify-center space-x-2">
              <span>{{ $t('hero.cta') }}</span>
              <svg
                class="h-5 w-5 transition-transform group-hover:translate-x-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </span>
          </a>

          <button
            class="flex-shrink-0 rounded-xl border-2 border-secondary/50 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:border-secondary/70 hover:bg-secondary/20 md:px-8 md:py-4 md:text-lg"
          >
            {{ $t('hero.demo') }}
          </button>
        </div>

        <!-- Stats -->
        <LandingStats :parent-visible="isVisible" />
      </div>

      <!-- Hero Visual -->
      <LandingHeroVisual :is-visible="isVisible" />
    </div>
  </section>
</template>

<script setup lang="ts">
const isVisible = ref(false)

onMounted(() => {
  setTimeout(() => {
    isVisible.value = true
  }, 100)
})
</script>
