<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Decorative Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-20 right-10 h-72 w-72 rounded-full bg-secondary/5 blur-3xl"></div>
      <div class="absolute bottom-20 left-10 h-96 w-96 rounded-full bg-accent/5 blur-3xl"></div>
      <div class="absolute top-1/2 left-1/3 h-64 w-64 rounded-full bg-primary/3 blur-3xl"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative overflow-hidden px-6 py-20 lg:px-8 lg:py-32">
      <div class="container mx-auto max-w-7xl">
        <div class="text-center">
          <div class="mb-6 inline-block rounded-full border border-secondary/20 bg-secondary/10 px-6 py-2 text-sm font-semibold text-secondary">
            {{ $t('redTaviraPage.hero.badge') }}
          </div>
          <h1 class="mb-6 text-4xl font-bold text-gray-900 lg:text-6xl">
            {{ $t('redTaviraPage.hero.title') }}
            <span class="bg-gradient-to-r from-secondary to-accent bg-clip-text text-transparent">
              {{ $t('redTaviraPage.hero.titleHighlight') }}
            </span>
          </h1>
          <p class="mx-auto mb-8 max-w-3xl text-lg text-gray-600 lg:text-xl">
            {{ $t('redTaviraPage.hero.subtitle') }}
          </p>

          <!-- Live Stats -->
          <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
              <div class="mb-2 flex items-center justify-center">
                <div class="h-3 w-3 animate-pulse rounded-full bg-green-500 mr-2"></div>
                <span class="text-sm text-gray-600">{{ $t('redTaviraPage.stats.liveStatus') }}</span>
              </div>
              <div class="text-4xl font-bold text-gray-900">500+</div>
              <div class="text-sm text-gray-500">{{ $t('redTaviraPage.stats.connectedComplexes') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
              <div class="text-4xl font-bold text-gray-900">127K</div>
              <div class="text-sm text-gray-500">{{ $t('redTaviraPage.stats.protectedResidents') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
              <div class="text-4xl font-bold text-secondary">30s</div>
              <div class="text-sm text-gray-500">{{ $t('redTaviraPage.stats.avgResponse') }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Neural Network Concept -->
    <section class="relative px-6 py-20 lg:px-8 bg-white">
      <div class="container mx-auto max-w-7xl">
        <div class="mb-12 text-center">
          <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-5xl">
            {{ $t('redTaviraPage.concept.title') }}
          </h2>
          <p class="mx-auto max-w-3xl text-lg text-gray-600">
            {{ $t('redTaviraPage.concept.subtitle') }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
          <!-- Brain Analogy -->
          <div class="rounded-2xl border border-gray-200 bg-gray-50 p-8 shadow-sm">
            <div class="mb-4 text-5xl">🧠</div>
            <h3 class="mb-4 text-2xl font-bold text-gray-900">
              {{ $t('redTaviraPage.concept.brain.title') }}
            </h3>
            <ul class="space-y-3 text-gray-600">
              <li class="flex items-start">
                <span class="mr-2 text-secondary font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.brain.point1') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-secondary font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.brain.point2') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-secondary font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.brain.point3') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-secondary font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.brain.point4') }}</span>
              </li>
            </ul>
          </div>

          <!-- Red Tavira -->
          <div class="rounded-2xl border-2 border-secondary/30 bg-gradient-to-br from-secondary/5 to-accent/5 p-8 shadow-sm">
            <div class="mb-4 text-5xl">🔗</div>
            <h3 class="mb-4 text-2xl font-bold text-gray-900">
              {{ $t('redTaviraPage.concept.network.title') }}
            </h3>
            <ul class="space-y-3 text-gray-700">
              <li class="flex items-start">
                <span class="mr-2 text-accent font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.network.point1') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-accent font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.network.point2') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-accent font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.network.point3') }}</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2 text-accent font-bold">→</span>
                <span>{{ $t('redTaviraPage.concept.network.point4') }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works - Technical -->
    <section class="relative px-6 py-20 lg:px-8 bg-gray-50">
      <div class="container mx-auto max-w-7xl">
        <div class="mb-12 text-center">
          <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-5xl">
            {{ $t('redTaviraPage.technical.title') }}
          </h2>
          <p class="mx-auto max-w-3xl text-lg text-gray-600">
            {{ $t('redTaviraPage.technical.subtitle') }}
          </p>
        </div>

        <!-- Technical Flow -->
        <div class="relative">
          <!-- Timeline Line -->
          <div class="absolute left-8 top-0 bottom-0 w-1 bg-gradient-to-b from-secondary via-accent to-purple-400 lg:left-1/2"></div>

          <!-- Steps -->
          <div class="space-y-12">
            <!-- Step 1 -->
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-8">
              <div class="lg:text-right">
                <div class="mb-4 inline-block rounded-xl border border-secondary/30 bg-secondary/10 px-6 py-2 text-sm font-semibold text-secondary">
                  {{ $t('redTaviraPage.technical.step1.badge') }}
                </div>
                <h3 class="mb-3 text-2xl font-bold text-gray-900">
                  {{ $t('redTaviraPage.technical.step1.title') }}
                </h3>
                <p class="text-gray-600">
                  {{ $t('redTaviraPage.technical.step1.description') }}
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-500">
                  <li>• {{ $t('redTaviraPage.technical.step1.detail1') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step1.detail2') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step1.detail3') }}</li>
                </ul>
              </div>
              <div class="hidden lg:block"></div>
              <div class="absolute left-4 -translate-x-1/2 lg:left-1/2">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-secondary text-2xl font-bold text-white shadow-lg">
                  1
                </div>
              </div>
            </div>

            <!-- Step 2 -->
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-8">
              <div class="hidden lg:block"></div>
              <div>
                <div class="mb-4 inline-block rounded-xl border border-accent/30 bg-accent/10 px-6 py-2 text-sm font-semibold text-accent">
                  {{ $t('redTaviraPage.technical.step2.badge') }}
                </div>
                <h3 class="mb-3 text-2xl font-bold text-gray-900">
                  {{ $t('redTaviraPage.technical.step2.title') }}
                </h3>
                <p class="text-gray-600">
                  {{ $t('redTaviraPage.technical.step2.description') }}
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-500">
                  <li>• {{ $t('redTaviraPage.technical.step2.detail1') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step2.detail2') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step2.detail3') }}</li>
                </ul>
              </div>
              <div class="absolute left-4 -translate-x-1/2 lg:left-1/2">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-accent text-2xl font-bold text-white shadow-lg">
                  2
                </div>
              </div>
            </div>

            <!-- Step 3 -->
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-8">
              <div class="lg:text-right">
                <div class="mb-4 inline-block rounded-xl border border-purple-500/30 bg-purple-500/10 px-6 py-2 text-sm font-semibold text-purple-600">
                  {{ $t('redTaviraPage.technical.step3.badge') }}
                </div>
                <h3 class="mb-3 text-2xl font-bold text-gray-900">
                  {{ $t('redTaviraPage.technical.step3.title') }}
                </h3>
                <p class="text-gray-600">
                  {{ $t('redTaviraPage.technical.step3.description') }}
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-500">
                  <li>• {{ $t('redTaviraPage.technical.step3.detail1') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step3.detail2') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step3.detail3') }}</li>
                </ul>
              </div>
              <div class="hidden lg:block"></div>
              <div class="absolute left-4 -translate-x-1/2 lg:left-1/2">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-purple-500 text-2xl font-bold text-white shadow-lg">
                  3
                </div>
              </div>
            </div>

            <!-- Step 4 -->
            <div class="relative lg:grid lg:grid-cols-2 lg:gap-8">
              <div class="hidden lg:block"></div>
              <div>
                <div class="mb-4 inline-block rounded-xl border border-green-500/30 bg-green-500/10 px-6 py-2 text-sm font-semibold text-green-600">
                  {{ $t('redTaviraPage.technical.step4.badge') }}
                </div>
                <h3 class="mb-3 text-2xl font-bold text-gray-900">
                  {{ $t('redTaviraPage.technical.step4.title') }}
                </h3>
                <p class="text-gray-600">
                  {{ $t('redTaviraPage.technical.step4.description') }}
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-500">
                  <li>• {{ $t('redTaviraPage.technical.step4.detail1') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step4.detail2') }}</li>
                  <li>• {{ $t('redTaviraPage.technical.step4.detail3') }}</li>
                </ul>
              </div>
              <div class="absolute left-4 -translate-x-1/2 lg:left-1/2">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-green-500 text-2xl font-bold text-white shadow-lg">
                  4
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Real Case Studies -->
    <section class="relative px-6 py-20 lg:px-8 bg-white">
      <div class="container mx-auto max-w-7xl">
        <div class="mb-12 text-center">
          <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-5xl">
            {{ $t('redTaviraPage.cases.title') }}
          </h2>
          <p class="mx-auto max-w-3xl text-lg text-gray-600">
            {{ $t('redTaviraPage.cases.subtitle') }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Case 1 -->
          <div class="flex flex-col rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-8 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
              <div class="text-5xl">✅</div>
              <div class="rounded-lg bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                {{ $t('redTaviraPage.cases.case1.badge') }}
              </div>
            </div>
            <h3 class="mb-4 text-xl font-bold text-gray-900">
              {{ $t('redTaviraPage.cases.case1.title') }}
            </h3>
            <p class="mb-6 flex-grow text-gray-600">
              {{ $t('redTaviraPage.cases.case1.description') }}
            </p>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
              <div class="mb-2 text-sm font-semibold text-green-600">{{ $t('redTaviraPage.cases.result') }}</div>
              <div class="text-2xl font-bold text-gray-900">{{ $t('redTaviraPage.cases.case1.result') }}</div>
            </div>
          </div>

          <!-- Case 2 -->
          <div class="flex flex-col rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 p-8 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
              <div class="text-5xl">🚨</div>
              <div class="rounded-lg bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                {{ $t('redTaviraPage.cases.case2.badge') }}
              </div>
            </div>
            <h3 class="mb-4 text-xl font-bold text-gray-900">
              {{ $t('redTaviraPage.cases.case2.title') }}
            </h3>
            <p class="mb-6 flex-grow text-gray-600">
              {{ $t('redTaviraPage.cases.case2.description') }}
            </p>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
              <div class="mb-2 text-sm font-semibold text-blue-600">{{ $t('redTaviraPage.cases.result') }}</div>
              <div class="text-2xl font-bold text-gray-900">{{ $t('redTaviraPage.cases.case2.result') }}</div>
            </div>
          </div>

          <!-- Case 3 -->
          <div class="flex flex-col rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-8 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
              <div class="text-5xl">⚡</div>
              <div class="rounded-lg bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                {{ $t('redTaviraPage.cases.case3.badge') }}
              </div>
            </div>
            <h3 class="mb-4 text-xl font-bold text-gray-900">
              {{ $t('redTaviraPage.cases.case3.title') }}
            </h3>
            <p class="mb-6 flex-grow text-gray-600">
              {{ $t('redTaviraPage.cases.case3.description') }}
            </p>
            <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
              <div class="mb-2 text-sm font-semibold text-purple-600">{{ $t('redTaviraPage.cases.result') }}</div>
              <div class="text-2xl font-bold text-gray-900">{{ $t('redTaviraPage.cases.case3.result') }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Network Impact -->
    <section class="relative px-6 py-20 lg:px-8 bg-gray-50">
      <div class="container mx-auto max-w-7xl">
        <div class="mb-12 text-center">
          <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-5xl">
            {{ $t('redTaviraPage.impact.title') }}
          </h2>
          <p class="mx-auto max-w-3xl text-lg text-gray-600">
            {{ $t('redTaviraPage.impact.subtitle') }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="mb-2 text-4xl font-bold text-secondary">5X</div>
            <div class="text-sm text-gray-600">{{ $t('redTaviraPage.impact.metric1') }}</div>
          </div>
          <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="mb-2 text-4xl font-bold text-accent">40%</div>
            <div class="text-sm text-gray-600">{{ $t('redTaviraPage.impact.metric2') }}</div>
          </div>
          <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="mb-2 text-4xl font-bold text-green-500">100%</div>
            <div class="text-sm text-gray-600">{{ $t('redTaviraPage.impact.metric3') }}</div>
          </div>
          <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="mb-2 text-4xl font-bold text-purple-500">2 años</div>
            <div class="text-sm text-gray-600">{{ $t('redTaviraPage.impact.metric4') }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="relative px-6 py-20 lg:px-8 bg-white">
      <div class="container mx-auto max-w-4xl">
        <div class="rounded-3xl border-2 border-secondary/20 bg-gradient-to-br from-secondary/5 to-accent/5 p-12 text-center shadow-lg">
          <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-4xl">
            {{ $t('redTaviraPage.cta.title') }}
          </h2>
          <p class="mb-8 text-lg text-gray-600">
            {{ $t('redTaviraPage.cta.subtitle') }}
          </p>
          <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
            <a
              href="#"
              class="inline-flex items-center rounded-full bg-gradient-to-r from-secondary to-accent px-8 py-4 font-semibold text-white shadow-lg transition-all hover:scale-105 hover:shadow-xl"
            >
              {{ $t('redTaviraPage.cta.primary') }}
            </a>
            <a
              href="/"
              class="inline-flex items-center rounded-full border-2 border-gray-300 bg-white px-8 py-4 font-semibold text-gray-700 transition-all hover:border-secondary hover:bg-gray-50"
            >
              {{ $t('redTaviraPage.cta.secondary') }}
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'Red Tavira - Red de Seguridad Inteligente',
  meta: [
    {
      name: 'description',
      content: 'Descubre cómo Red Tavira conecta 500+ conjuntos residenciales en una red neuronal de seguridad inteligente. Alertas en tiempo real, respuesta en 30 segundos.'
    }
  ]
})
</script>
