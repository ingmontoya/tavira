<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-20">
      <div class="mx-auto max-w-4xl text-center">
        <div class="mb-6">
          <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-white/20 text-6xl">
            🔒
          </div>
        </div>
        <h1 class="mb-6 text-4xl font-bold text-white md:text-5xl lg:text-6xl">
          Seguridad de <span class="text-primary">Nivel Bancario</span>
        </h1>
        <p class="mx-auto max-w-3xl text-xl text-white/90 md:text-2xl">
          Tu información está protegida con los más altos estándares de seguridad de la industria. Base de
          datos exclusiva, encriptación total y cumplimiento normativo garantizado.
        </p>
      </div>
    </section>

    <!-- Main Security Features -->
    <section class="px-6 py-16 md:py-20">
      <div class="mx-auto max-w-7xl">
        <!-- Database Isolation -->
        <div class="mb-20">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
              <div
                class="mb-4 inline-flex items-center rounded-full bg-secondary/10 px-4 py-2 font-medium text-secondary"
              >
                🏢 Aislamiento Total
              </div>
              <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">Base de Datos Exclusiva</h2>
              <div class="mb-8 space-y-4 text-lg text-gray-600">
                <p>
                  <strong>Cada conjunto tiene su propia base de datos dedicada.</strong> No compartimos
                  infraestructura de datos con otros clientes. Tu información está completamente aislada y
                  protegida.
                </p>
                <p>
                  <strong>Arquitectura Multi-Tenant Avanzada:</strong> Utilizamos tecnología de punta
                  (stancl/tenancy) que garantiza separación total de datos a nivel de base de datos.
                </p>
                <p>
                  <strong>Imposibilidad de Filtración:</strong> Es técnicamente imposible que los datos de un
                  conjunto se filtren o sean accesibles por otro conjunto.
                </p>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                  <div class="mb-2 flex items-center">
                    <svg class="mr-2 h-6 w-6 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="font-semibold text-gray-900">Base de Datos Propia</span>
                  </div>
                  <p class="text-sm text-gray-600">Cada conjunto tiene su BD exclusiva</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                  <div class="mb-2 flex items-center">
                    <svg class="mr-2 h-6 w-6 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="font-semibold text-gray-900">Aislamiento Total</span>
                  </div>
                  <p class="text-sm text-gray-600">Cero posibilidad de filtración</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                  <div class="mb-2 flex items-center">
                    <svg class="mr-2 h-6 w-6 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="font-semibold text-gray-900">Backup Independiente</span>
                  </div>
                  <p class="text-sm text-gray-600">Respaldos exclusivos por conjunto</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                  <div class="mb-2 flex items-center">
                    <svg class="mr-2 h-6 w-6 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="font-semibold text-gray-900">Escalabilidad</span>
                  </div>
                  <p class="text-sm text-gray-600">Crece sin afectar rendimiento</p>
                </div>
              </div>
            </div>
            <div class="lg:pl-8">
              <div class="relative">
                <!-- Database visualization -->
                <div class="rounded-3xl bg-gradient-to-br from-accent to-secondary p-8 text-center text-white">
                  <h3 class="mb-6 text-xl font-bold">Arquitectura de Aislamiento</h3>
                  <div class="space-y-4">
                    <div class="rounded-lg bg-white/20 p-4">
                      <div class="mb-2 text-2xl">🏢</div>
                      <div class="font-semibold">Conjunto Torres del Norte</div>
                      <div class="text-sm text-white/80">Base de Datos: tenant_001</div>
                    </div>
                    <div class="rounded-lg bg-white/20 p-4">
                      <div class="mb-2 text-2xl">🏢</div>
                      <div class="font-semibold">Conjunto Villa Verde</div>
                      <div class="text-sm text-white/80">Base de Datos: tenant_002</div>
                    </div>
                    <div class="rounded-lg bg-white/20 p-4">
                      <div class="mb-2 text-2xl">🏢</div>
                      <div class="font-semibold">Tu Conjunto</div>
                      <div class="text-sm text-white/80">Base de Datos: Exclusiva</div>
                    </div>
                  </div>
                  <div class="mt-6 text-sm text-white/90">
                    ✓ Cada conjunto = Base de datos independiente<br />
                    ✓ Cero comunicación entre bases de datos<br />
                    ✓ Imposible acceso cruzado
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Encryption -->
        <div class="mb-20">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="lg:order-2">
              <div
                class="mb-4 inline-flex items-center rounded-full bg-primary/10 px-4 py-2 font-medium text-primary"
              >
                🔐 Encriptación
              </div>
              <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">
                Encriptación de Grado Bancario
              </h2>
              <div class="mb-8 space-y-4 text-lg text-gray-600">
                <p>
                  <strong>SSL/TLS 256-bit:</strong> Toda la comunicación entre tu navegador y nuestros
                  servidores está protegida con el mismo nivel de encriptación que usan los bancos.
                </p>
                <p>
                  <strong>Encriptación en Reposo:</strong> Todos los datos sensibles (contraseñas,
                  información financiera, documentos) están encriptados en nuestras bases de datos.
                </p>
                <p>
                  <strong>Hashing Seguro:</strong> Las contraseñas nunca se almacenan en texto plano.
                  Utilizamos algoritmos de hashing bcrypt con salt aleatorio.
                </p>
                <p>
                  <strong>Tokens de Sesión:</strong> Autenticación basada en tokens seguros con expiración
                  automática y renovación transparente.
                </p>
              </div>
            </div>
            <div class="lg:order-1">
              <div class="rounded-3xl bg-gradient-to-br from-gray-900 to-gray-700 p-8 text-green-400">
                <div class="space-y-2 font-mono text-sm">
                  <div class="text-gray-400">// Ejemplo de encriptación de datos</div>
                  <div class="text-white">const userData = {</div>
                  <div class="pl-4 text-white">name: "María González",</div>
                  <div class="pl-4 text-white">apartment: "Torre A - 501",</div>
                  <div class="pl-4 text-white">payment: "$450.000"</div>
                  <div class="text-white">};</div>
                  <div class="mt-4 text-gray-400">// Datos encriptados en BD:</div>
                  <div class="break-all text-green-400">encrypt(userData) =></div>
                  <div class="break-all text-xs text-green-400">
                    "AES256:8f2a9d7c4e1b6f0a3d8c9b2e5f4a7d1c<br />
                    9e6b3f8a2d5c7b4e9f1a6d3c8b5e2f7a<br />
                    4d1c6f9b2e8a5d7c3f6b9e2a8d5c7f1b"
                  </div>
                </div>
                <div class="mt-6 text-center">
                  <div class="inline-flex items-center rounded-full bg-green-400/20 px-3 py-1 text-sm text-green-400">
                    <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Encriptación AES-256
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Compliance & Certifications -->
        <div class="mb-20">
          <div class="mb-12 text-center">
            <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">Cumplimiento Normativo</h2>
            <p class="mx-auto max-w-3xl text-xl text-gray-600">
              Cumplimos con todas las regulaciones colombianas e internacionales de protección de datos
            </p>
          </div>

          <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border-2 border-green-200 bg-white p-6 text-center shadow-lg">
              <div class="mb-4 text-4xl">🇨🇴</div>
              <h3 class="mb-2 text-lg font-bold text-gray-900">Ley 1581 de 2012</h3>
              <p class="text-sm text-gray-600">
                Protección de datos personales en Colombia. Cumplimiento total con autorización y tratamiento
                de datos.
              </p>
            </div>

            <div class="rounded-2xl border-2 border-blue-200 bg-white p-6 text-center shadow-lg">
              <div class="mb-4 text-4xl">🇪🇺</div>
              <h3 class="mb-2 text-lg font-bold text-gray-900">GDPR Compliant</h3>
              <p class="text-sm text-gray-600">
                Reglamento General de Protección de Datos europeo. Estándares internacionales de privacidad.
              </p>
            </div>

            <div class="rounded-2xl border-2 border-purple-200 bg-white p-6 text-center shadow-lg">
              <div class="mb-4 text-4xl">🏦</div>
              <h3 class="mb-2 text-lg font-bold text-gray-900">PCI DSS</h3>
              <p class="text-sm text-gray-600">
                Estándar de seguridad para procesamiento de pagos. Protección de información financiera.
              </p>
            </div>

            <div class="rounded-2xl border-2 border-orange-200 bg-white p-6 text-center shadow-lg">
              <div class="mb-4 text-4xl">🔍</div>
              <h3 class="mb-2 text-lg font-bold text-gray-900">ISO 27001</h3>
              <p class="text-sm text-gray-600">
                Gestión de seguridad de la información. Procedimientos y controles certificados
                internacionalmente.
              </p>
            </div>
          </div>
        </div>

        <!-- Trust Indicators -->
        <div class="mb-20">
          <div class="mb-12 text-center">
            <h2 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">Indicadores de Confianza</h2>
            <p class="mx-auto max-w-3xl text-xl text-gray-600">
              Métricas que demuestran nuestro compromiso con la seguridad
            </p>
          </div>

          <div class="grid gap-8 md:grid-cols-4">
            <div class="text-center">
              <div class="mb-2 text-5xl font-bold text-secondary">99.9%</div>
              <div class="mb-2 text-lg font-semibold text-gray-900">Uptime</div>
              <div class="text-sm text-gray-600">Disponibilidad garantizada con SLA</div>
            </div>
            <div class="text-center">
              <div class="mb-2 text-5xl font-bold text-secondary">0</div>
              <div class="mb-2 text-lg font-semibold text-gray-900">Brechas</div>
              <div class="text-sm text-gray-600">Cero filtraciones de datos en 5 años</div>
            </div>
            <div class="text-center">
              <div class="mb-2 text-5xl font-bold text-secondary">24/7</div>
              <div class="mb-2 text-lg font-semibold text-gray-900">Monitoreo</div>
              <div class="text-sm text-gray-600">Vigilancia continua automatizada</div>
            </div>
            <div class="text-center">
              <div class="mb-2 text-5xl font-bold text-secondary">256</div>
              <div class="mb-2 text-lg font-semibold text-gray-900">Bits SSL</div>
              <div class="text-sm text-gray-600">Encriptación de grado militar</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Security Guarantee -->
    <section class="bg-gradient-to-r from-accent to-secondary px-6 py-16">
      <div class="mx-auto max-w-4xl text-center">
        <div class="mb-6 text-6xl">🛡️</div>
        <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">Garantía de Seguridad Total</h2>
        <p class="mx-auto mb-8 max-w-3xl text-xl text-white/90">
          Estamos tan seguros de nuestro sistema de seguridad que ofrecemos garantía total. Si tu conjunto
          sufre alguna brecha de seguridad por falla de nuestro sistema, asumimos la responsabilidad
          completa.
        </p>
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
          <a
            href="https://tavira.com.co/register"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-lg font-semibold text-white transition-all hover:scale-105 hover:bg-primary/90"
          >
            Comenzar con Seguridad Total
            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </a>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
// SEO
useHead({
  title: 'Seguridad Bancaria y Base de Datos Exclusiva por Conjunto | Tavira Colombia',
  link: [{ rel: 'canonical', href: 'https://tavira.com.co/security' }],
})

useSeoMeta({
  description:
    'Seguridad de nivel bancario para tu conjunto residencial. Base de datos exclusiva por conjunto, encriptación SSL 256-bit, backups automatizados, cumplimiento GDPR y Ley 1581 colombiana. Imposible filtración de datos entre conjuntos.',
  ogTitle: 'Seguridad Bancaria y Base de Datos Exclusiva por Conjunto | Tavira Colombia',
  ogDescription:
    'Base de datos exclusiva, encriptación bancaria, backups automatizados. La máxima seguridad para la información de tu conjunto residencial.',
  ogType: 'website',
})
</script>
