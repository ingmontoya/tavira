<template>
  <div class="min-h-screen bg-gradient-to-br from-accent via-secondary to-secondary">
    <!-- Hero Section -->
    <div class="container mx-auto px-4 py-12">
      <div class="mx-auto max-w-6xl">
        <div class="mb-12 text-center">
          <h1 class="mb-4 text-4xl font-bold text-white md:text-5xl">
            ¿Eres un Proveedor de Servicios?
          </h1>
          <p class="mb-8 text-xl text-white/80">
            Conecta con cientos de conjuntos residenciales y aumenta tu cartera de clientes
          </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2">
          <!-- Benefits Section -->
          <div class="space-y-6">
            <div class="rounded-2xl border border-white/20 bg-white/10 p-8 backdrop-blur-lg">
              <h2 class="mb-6 text-2xl font-bold text-white">Beneficios de Unirte</h2>
              <div class="space-y-4">
                <div class="flex items-start space-x-3">
                  <svg class="mt-1 h-6 w-6 flex-shrink-0 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <div>
                    <h3 class="font-semibold text-white">Visibilidad Aumentada</h3>
                    <p class="text-sm text-white/70">
                      Accede a una red de conjuntos residenciales buscando servicios de calidad
                    </p>
                  </div>
                </div>
                <div class="flex items-start space-x-3">
                  <svg class="mt-1 h-6 w-6 flex-shrink-0 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <div>
                    <h3 class="font-semibold text-white">Gestión Simplificada</h3>
                    <p class="text-sm text-white/70">
                      Administra tus servicios, cotizaciones y pagos desde una sola plataforma
                    </p>
                  </div>
                </div>
                <div class="flex items-start space-x-3">
                  <svg class="mt-1 h-6 w-6 flex-shrink-0 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <div>
                    <h3 class="font-semibold text-white">Pagos Seguros</h3>
                    <p class="text-sm text-white/70">Sistema de pagos confiable y transparente</p>
                  </div>
                </div>
                <div class="flex items-start space-x-3">
                  <svg class="mt-1 h-6 w-6 flex-shrink-0 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <div>
                    <h3 class="font-semibold text-white">Sin Comisiones Iniciales</h3>
                    <p class="text-sm text-white/70">
                      Regístrate gratis y empieza a recibir solicitudes de servicios
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-white/20 bg-white/10 p-8 backdrop-blur-lg">
              <h2 class="mb-4 font-bold text-white">Servicios que Buscamos</h2>
              <div class="grid grid-cols-2 gap-3 text-sm text-white/80">
                <div>• Plomería</div>
                <div>• Electricidad</div>
                <div>• Jardinería</div>
                <div>• Pintura</div>
                <div>• Construcción</div>
                <div>• Limpieza</div>
                <div>• Seguridad</div>
                <div>• Mantenimiento</div>
              </div>
            </div>
          </div>

          <!-- Registration CTA -->
          <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <h2 class="mb-4 text-2xl font-bold text-gray-900">Registro de Proveedor</h2>
            <p class="mb-8 text-gray-600">
              Completa el formulario de registro en nuestra plataforma principal y nuestro equipo se pondrá
              en contacto contigo
            </p>

            <div class="mb-8 space-y-4">
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary/10 text-secondary"
                  >
                    1
                  </div>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">Completa el Formulario</h3>
                  <p class="text-sm text-gray-600">
                    Proporciona tu información de empresa y servicios
                  </p>
                </div>
              </div>

              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary/10 text-secondary"
                  >
                    2
                  </div>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">Verificación</h3>
                  <p class="text-sm text-gray-600">
                    Nuestro equipo revisará tu solicitud
                  </p>
                </div>
              </div>

              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary/10 text-secondary"
                  >
                    3
                  </div>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">Comienza a Ofrecer Servicios</h3>
                  <p class="text-sm text-gray-600">
                    Recibe solicitudes de conjuntos residenciales
                  </p>
                </div>
              </div>
            </div>

            <a
              href="https://tavira.com.co/provider-register"
              class="block w-full rounded-lg bg-accent px-8 py-4 text-center text-lg font-semibold text-white transition-all hover:bg-accent/90"
            >
              Ir al Formulario de Registro
            </a>

            <p class="mt-4 text-center text-xs text-gray-500">
              Al registrarte, aceptas que nos comuniquemos contigo sobre tu solicitud
            </p>
          </div>
        </div>

        <!-- Footer Note -->
        <div class="mt-12 text-center">
          <p class="text-sm text-white/60">
            ¿Eres un conjunto residencial?
            <a href="https://tavira.com.co/register" class="font-medium text-primary hover:underline">
              Regístrate aquí
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// SEO
useHead({
  title: 'Registro de Proveedores - Tavira',
  link: [{ rel: 'canonical', href: 'https://tavira.com.co/provider-register' }],
})

useSeoMeta({
  description:
    'Únete a la red de proveedores de servicios para conjuntos residenciales. Conecta con cientos de clientes potenciales y gestiona tus servicios desde una sola plataforma.',
  ogTitle: 'Registro de Proveedores - Tavira',
  ogDescription:
    'Conecta con conjuntos residenciales y aumenta tu cartera de clientes. Plomería, electricidad, jardinería, mantenimiento y más.',
  ogType: 'website',
})
</script>
