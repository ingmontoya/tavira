<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-20">
      <div class="mx-auto max-w-4xl text-center">
        <h1 class="mb-6 text-4xl font-bold text-white md:text-5xl lg:text-6xl">
          {{ locale === 'es' ? 'Características' : 'Complete' }}
          <span class="text-primary">{{ locale === 'es' ? 'Completas' : 'Features' }}</span>
        </h1>
        <p class="text-xl text-white/90 md:text-2xl">
          {{ locale === 'es' ? 'Todo lo que necesitas para una administración moderna y eficiente de conjuntos residenciales' : 'Everything you need for modern and efficient residential complex management' }}
        </p>
      </div>
    </section>

    <!-- Detailed Features -->
    <section class="px-6 py-16 md:py-20">
      <div class="mx-auto max-w-7xl">
        <!-- Financial Management -->
        <div class="mb-20">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
              <div
                class="mb-4 inline-flex items-center rounded-full bg-secondary/10 px-4 py-2 font-medium text-secondary"
              >
                💰 Sistema Financiero
              </div>
              <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">Finanzas Profesionales</h2>
              <div class="mb-8 space-y-4 text-lg text-gray-600">
                <p>
                  <strong>Contabilidad de Doble Partida:</strong> Sistema contable completo que cumple con
                  la normatividad colombiana (Decreto 2650).
                </p>
                <p>
                  <strong>Facturación Automatizada:</strong> Genera facturas automáticas por administración,
                  cuotas extras y conceptos personalizados. <em>Facturación electrónica DIAN opcional.</em>
                </p>
                <p>
                  <strong>Gestión de Pagos:</strong> Seguimiento completo de pagos, conciliación bancaria
                  automática y múltiples formas de pago.
                </p>
                <p>
                  <strong>Reportes Ejecutivos:</strong> Balance General, Estado de Resultados, Flujo de Caja y
                  más de 15 reportes diferentes.
                </p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center">
                  <svg class="mr-2 h-5 w-5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="text-gray-700">Facturación DIAN</span>
                </div>
                <div class="flex items-center">
                  <svg class="mr-2 h-5 w-5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="text-gray-700">Presupuestos Anuales</span>
                </div>
                <div class="flex items-center">
                  <svg class="mr-2 h-5 w-5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="text-gray-700">Control de Morosidad</span>
                </div>
                <div class="flex items-center">
                  <svg class="mr-2 h-5 w-5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span class="text-gray-700">Conciliación Bancaria</span>
                </div>
              </div>
            </div>
            <div class="lg:pl-8">
              <div class="rounded-3xl bg-gradient-to-br from-gray-100 to-white p-8 shadow-2xl">
                <div class="space-y-4">
                  <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                    <span class="font-medium text-gray-900">Balance General</span>
                    <span class="text-secondary">📊</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                    <span class="font-medium text-gray-900">Estado de Resultados</span>
                    <span class="text-secondary">💹</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                    <span class="font-medium text-gray-900">Flujo de Caja</span>
                    <span class="text-secondary">💰</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                    <span class="font-medium text-gray-900">Cartera por Edades</span>
                    <span class="text-secondary">📈</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Communication System -->
        <div class="mb-20">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="lg:order-2">
              <div
                class="mb-4 inline-flex items-center rounded-full bg-primary/10 px-4 py-2 font-medium text-primary"
              >
                📧 Comunicación Institucional
              </div>
              <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">
                Memoria Institucional Permanente
              </h2>
              <div class="mb-8 space-y-4 text-lg text-gray-600">
                <p>
                  <strong>Sistema de Correos Institucional:</strong> Los emails pertenecen al conjunto, no a
                  la administración o concejo de turno. Garantiza continuidad institucional permanente.
                </p>
                <p>
                  <strong>Comunicados Masivos:</strong> Envía comunicados a todos los residentes con
                  confirmación de lectura y estadísticas de alcance.
                </p>
                <p>
                  <strong>Correspondencia Oficial:</strong> Gestiona documentos oficiales, actas de consejo y
                  correspondencia with entes externos con historial completo.
                </p>
                <p>
                  <strong>Templates Profesionales:</strong> Crea templates institucionales para comunicados,
                  facturas y documentos oficiales que permanecen en el conjunto.
                </p>
              </div>
            </div>
            <div class="lg:order-1">
              <div class="rounded-3xl bg-gradient-to-br from-primary/5 to-primary/10 p-8">
                <div class="space-y-6">
                  <div class="rounded-lg border-l-4 border-primary bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                      <span class="font-semibold text-gray-900">Comunicado de Asamblea</span>
                      <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800"
                        >Enviado</span
                      >
                    </div>
                    <p class="text-sm text-gray-600">94% de residentes han leído el comunicado</p>
                    <div class="mt-2 h-2 w-full rounded-full bg-gray-200">
                      <div class="h-2 rounded-full bg-secondary" style="width: 94%"></div>
                    </div>
                  </div>
                  <div class="rounded-lg border-l-4 border-secondary bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                      <span class="font-semibold text-gray-900">Mantenimiento Ascensores</span>
                      <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
                        >Programado</span
                      >
                    </div>
                    <p class="text-sm text-gray-600">Envío programado para mañana 8:00 AM</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security & Access Control -->
        <div class="mb-20">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
              <div
                class="mb-4 inline-flex items-center rounded-full bg-accent/10 px-4 py-2 font-medium text-accent"
              >
                🔐 Seguridad
              </div>
              <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">Máxima Seguridad</h2>
              <div class="mb-8 space-y-4 text-lg text-gray-600">
                <p>
                  <strong>Base de Datos Exclusiva:</strong> Cada conjunto tiene su propia base de datos
                  completamente aislada.
                </p>
                <p>
                  <strong>Encriptación Bancaria:</strong> SSL 256-bit y encriptación de datos sensibles al
                  nivel de instituciones financieras.
                </p>
                <p>
                  <strong>Control de Acceso Granular:</strong> Roles y permisos específicos para
                  administradores, consejales y residentes.
                </p>
                <p>
                  <strong>Auditoría Completa:</strong> Registro completo de todas las acciones realizadas en
                  el sistema con timestamps.
                </p>
              </div>
            </div>
            <div class="lg:pl-8">
              <div class="rounded-3xl bg-gradient-to-br from-accent to-secondary p-8 text-white">
                <div class="mb-6 text-center">
                  <div
                    class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-4xl"
                  >
                    🔒
                  </div>
                  <h3 class="text-xl font-bold">Seguridad Bancaria</h3>
                </div>
                <div class="space-y-4">
                  <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span>Base de datos dedicada</span>
                  </div>
                  <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span>Encriptación SSL 256-bit</span>
                  </div>
                  <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span>Backup automático diario</span>
                  </div>
                  <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span>Cumplimiento GDPR</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA Section -->
        <div class="rounded-3xl bg-gradient-to-r from-accent to-secondary p-8 text-center text-white md:p-12">
          <h2 class="mb-6 text-3xl font-bold md:text-4xl">¿Listo para Transformar tu Conjunto?</h2>
          <p class="mb-8 text-xl text-white/90">
            Únete a más de 500 conjuntos que ya confían en Tavira
          </p>
          <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
            <a
              href="https://tavira.com.co/register"
              class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-lg font-semibold text-white transition-all hover:scale-105 hover:bg-primary/90"
            >
              Regístrate
              <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 7l5 5m0 0l-5 5m5-5H6"
                />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const { locale } = useI18n()

// SEO
useHead({
  title: 'Características Completas - Software Administración Conjuntos Residenciales Colombia | Tavira',
  link: [{ rel: 'canonical', href: 'https://tavira.com.co/features' }],
})

useSeoMeta({
  description:
    'Descubre todas las características de Tavira: apps móviles iOS/Android, citófonos digitales, pases QR, gestión paquetes, facturación DIAN, contabilidad, marketplace comunitario, control acceso. La solución más completa para conjuntos residenciales en Colombia.',
  ogTitle:
    'Características Completas - Software Administración Conjuntos Residenciales Colombia | Tavira',
  ogDescription:
    'Apps móviles nativas, citófonos digitales, pases QR, gestión paquetes, facturación DIAN, contabilidad completa, marketplace comunitario. Todo para administrar tu conjunto residencial.',
  ogType: 'website',
})
</script>
