<template>
    <section id="pricing" class="bg-gradient-to-br from-gray-50 to-white px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-4xl font-bold text-gray-900 lg:text-5xl">
                    Precios
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#1D3557] bg-clip-text text-transparent">
                        Transparentes
                    </span>
                    para cada Conjunto
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    Planes flexibles que crecen contigo. Paga solo por lo que necesitas, cuando lo necesitas.
                </p>
            </div>

            <!-- Plans -->
            <div class="grid gap-8 lg:grid-cols-3 mb-16">
                <!-- Starter Plan -->
                <div class="relative rounded-3xl border-2 border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Starter</h3>
                        <p class="mt-2 text-gray-600">Perfecto para comenzar</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$70.000</span>
                        <span class="text-gray-600">/mes</span>
                    </div>
                    <div class="mb-8 space-y-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Gestión Básica Completa</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Sistema de Comunicación</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Hasta 50 apartamentos</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Base de datos exclusiva</span>
                        </div>
                    </div>
                    <button class="w-full rounded-xl bg-gray-900 py-3 font-semibold text-white transition-colors hover:bg-gray-800">
                        Comenzar Prueba
                    </button>
                </div>

                <!-- Professional Plan -->
                <div class="relative rounded-3xl border-2 border-[#06D6A0] bg-white p-8 shadow-2xl transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-gradient-to-r from-[#06D6A0] to-[#1D3557] text-white px-4 py-2 rounded-full text-sm font-medium">
                            Más Popular
                        </span>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Professional</h3>
                        <p class="mt-2 text-gray-600">Lo más completo para la mayoría</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$120.000</span>
                        <span class="text-gray-600">/mes</span>
                    </div>
                    <div class="mb-8 space-y-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Todo el plan Starter</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Sistema Financiero Completo</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Módulo de Mantenimiento</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Hasta 200 apartamentos</span>
                        </div>
                    </div>
                    <button class="w-full rounded-xl bg-[#06D6A0] py-3 font-semibold text-white transition-colors hover:bg-[#05c48a]">
                        Comenzar Prueba
                    </button>
                </div>

                <!-- Enterprise Plan -->
                <div class="relative rounded-3xl border-2 border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Enterprise</h3>
                        <p class="mt-2 text-gray-600">Para grandes conjuntos</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$180.000</span>
                        <span class="text-gray-600">/mes</span>
                    </div>
                    <div class="mb-8 space-y-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Todos los módulos incluidos</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Apartamentos ilimitados</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Soporte prioritario 24/7</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-700">Personalización avanzada</span>
                        </div>
                    </div>
                    <button class="w-full rounded-xl bg-gray-900 py-3 font-semibold text-white transition-colors hover:bg-gray-800">
                        Contactar Ventas
                    </button>
                </div>
            </div>

            <!-- Individual Modules -->
            <div class="rounded-3xl bg-gradient-to-r from-[#1D3557] to-[#06D6A0] p-1">
                <div class="rounded-3xl bg-white p-8">
                    <div class="mb-8 text-center">
                        <h3 class="mb-4 text-3xl font-bold text-gray-900">
                            ¿Prefieres módulos individuales?
                        </h3>
                        <p class="text-xl text-gray-600">
                            Construye tu plan personalizado seleccionando solo los módulos que necesitas
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-gray-50 p-6 text-center border-2 border-transparent hover:border-[#06D6A0] transition-colors">
                            <div class="mb-4 text-4xl">💰</div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Financiero</h4>
                            <p class="mb-4 text-sm text-gray-600">Contabilidad + Facturación + Pagos</p>
                            <div class="text-2xl font-bold text-[#06D6A0]">$35.000/mes</div>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-6 text-center border-2 border-transparent hover:border-[#06D6A0] transition-colors">
                            <div class="mb-4 text-4xl">🔧</div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Mantenimiento</h4>
                            <p class="mb-4 text-sm text-gray-600">Solicitudes + Órdenes + Personal</p>
                            <div class="text-2xl font-bold text-[#06D6A0]">$30.000/mes</div>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-6 text-center border-2 border-transparent hover:border-[#06D6A0] transition-colors">
                            <div class="mb-4 text-4xl">📢</div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Comunicación</h4>
                            <p class="mb-4 text-sm text-gray-600">Comunicados + Emails + Templates</p>
                            <div class="text-2xl font-bold text-[#06D6A0]">$25.000/mes</div>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-6 text-center border-2 border-transparent hover:border-[#06D6A0] transition-colors">
                            <div class="mb-4 text-4xl">🔐</div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Seguridad</h4>
                            <p class="mb-4 text-sm text-gray-600">Control de Acceso + Visitas</p>
                            <div class="text-2xl font-bold text-[#06D6A0]">$20.000/mes</div>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <button class="rounded-xl bg-gradient-to-r from-[#06D6A0] to-[#1D3557] px-8 py-4 text-lg font-semibold text-white transition-all hover:scale-105">
                            Personalizar Mi Plan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Security Guarantee -->
            <div class="mt-16 rounded-3xl bg-gradient-to-r from-[#1D3557] to-[#06D6A0] p-8 text-center">
                <div class="mx-auto max-w-4xl">
                    <div class="mb-6">
                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-4xl">
                            🔒
                        </div>
                    </div>
                    <h3 class="mb-4 text-3xl font-bold text-white">
                        Garantía de Seguridad Bancaria
                    </h3>
                    <p class="mb-6 text-xl text-white/90">
                        <strong>Base de datos exclusiva por conjunto.</strong> Tus datos nunca se mezclan con otros. 
                        Encriptación de nivel bancario, backups automatizados y cumplimiento normativo colombiano.
                    </p>
                    <div class="flex flex-wrap justify-center gap-6 text-white/80">
                        <div class="flex items-center">
                            <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            BD Exclusiva
                        </div>
                        <div class="flex items-center">
                            <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            SSL 256-bit
                        </div>
                        <div class="flex items-center">
                            <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Backup Diario
                        </div>
                        <div class="flex items-center">
                            <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            GDPR Compliant
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
// Component logic here if needed
</script>