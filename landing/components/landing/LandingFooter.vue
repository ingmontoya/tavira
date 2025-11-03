<template>
  <footer class="border-t border-gray-100 bg-accent px-6 py-16">
    <div class="mx-auto max-w-7xl">
      <div class="grid gap-12 lg:grid-cols-5">
        <!-- Company Info -->
        <div class="lg:col-span-2">
          <div class="mb-6 flex items-center space-x-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary shadow-lg">
              <span class="font-brand text-xl font-bold text-white">T</span>
            </div>
            <span class="font-brand text-3xl font-bold tracking-tight text-white">Tavira</span>
          </div>
          <p class="mb-6 max-w-lg text-lg leading-relaxed text-white/90">
            {{ $t('footer.description') }}
          </p>
          <div class="mb-6">
            <div class="mb-4 inline-flex items-center rounded-xl bg-primary px-4 py-2 text-sm font-medium text-white shadow-md">
              {{ $t('footer.tagline') }}
            </div>
            <div class="space-y-1 text-sm text-white/80">
              <p>📧 {{ $t('footer.email') }}</p>
              <p>📱 WhatsApp: {{ $t('footer.phone') }}</p>
            </div>
          </div>
        </div>

        <!-- Product -->
        <div>
          <h4 class="mb-6 text-lg font-bold text-white">{{ $t('footer.product') }}</h4>
          <ul class="space-y-3 text-sm text-white/80">
            <li>
              <a href="#features" class="transition-colors duration-200 hover:font-medium hover:text-secondary">
                Características
              </a>
            </li>
            <li>
              <a href="#security" class="transition-colors duration-200 hover:font-medium hover:text-secondary">
                Seguridad
              </a>
            </li>
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Apps Móviles
              </a>
            </li>
            <li>
              <a
                href="#detailed-features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Integraciones
              </a>
            </li>
            <li>
              <a
                href="#testimonials"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Casos de Éxito
              </a>
            </li>
          </ul>
        </div>

        <!-- Solutions -->
        <div>
          <h4 class="mb-6 text-lg font-bold text-white">{{ $t('footer.solutions') }}</h4>
          <ul class="space-y-3 text-sm text-white/80">
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Gestión Financiera
              </a>
            </li>
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Comunicación
              </a>
            </li>
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Control de Acceso
              </a>
            </li>
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Mantenimiento
              </a>
            </li>
            <li>
              <a
                href="#features"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Marketplace
              </a>
            </li>
          </ul>
        </div>

        <!-- Support & Legal -->
        <div>
          <h4 class="mb-6 text-lg font-bold text-white">{{ $t('footer.support') }}</h4>
          <ul class="mb-8 space-y-3 text-sm text-white/80">
            <li>
              <a href="#cta" class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary">
                Contacto
              </a>
            </li>
            <li>
              <a href="#cta" class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary">
                Centro de Ayuda
              </a>
            </li>
            <li>
              <a
                href="#how-it-works"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Capacitación
              </a>
            </li>
            <li>
              <a
                href="#testimonials"
                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-secondary"
              >
                Testimonios
              </a>
            </li>
          </ul>

          <h5 class="mb-4 text-sm font-semibold text-white">{{ $t('footer.legal') }}</h5>
          <ul class="space-y-2 text-xs text-white/70">
            <li><a href="#" class="transition-colors duration-200 hover:text-secondary">Términos de Servicio</a></li>
            <li><a href="#" class="transition-colors duration-200 hover:text-secondary">Política de Privacidad</a></li>
            <li><a href="#" class="transition-colors duration-200 hover:text-secondary">RGPD & Ley 1581</a></li>
          </ul>
        </div>
      </div>

      <!-- Bottom Section -->
      <div class="mt-12 border-t border-white/20 pt-8">
        <div class="flex flex-col items-center justify-between space-y-4 lg:flex-row lg:space-y-0">
          <div class="text-center text-sm text-white/70 lg:text-left">
            <p>{{ $t('footer.copyright') }}</p>
            <p class="mt-1">{{ $t('footer.compliance') }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-white/70">Disponible en:</span>
            <div class="flex space-x-2">
              <div class="flex items-center rounded-lg bg-white/10 px-3 py-1 text-xs text-white">🍎 App Store</div>
              <div class="flex items-center rounded-lg bg-white/10 px-3 py-1 text-xs text-white">🤖 Google Play</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
</script>
