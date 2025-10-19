<template>
    <section id="testimonials" class="bg-gradient-to-br from-[#1D3557] via-[#1D3557] to-[#06D6A0] px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl lg:text-5xl">
                    Lo que dicen nuestros
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent"> Clientes </span>
                </h2>
                <p class="mx-auto max-w-3xl text-lg text-white/85 md:text-xl">
                    Administradores, concejales y residentes de toda Colombia confían en Tavira para transformar sus conjuntos.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 md:gap-8 lg:grid-cols-3">
                <div
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    class="rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-lg transition-all duration-300 hover:-translate-y-1 hover:border-white/30 hover:bg-white/15 md:p-8"
                >
                    >
                    <div class="mb-4 flex space-x-1">
                        <svg v-for="i in 5" :key="i" class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                        </svg>
                    </div>
                    <p class="mb-6 text-white/90 italic">"{{ testimonial.content }}"</p>
                    <div>
                        <div class="font-semibold text-white">{{ testimonial.name }}</div>
                        <div class="text-sm text-white/70">{{ testimonial.role }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const testimonials = computed(() => [
    {
        name: 'María González',
        role: 'Administradora - Conjunto Torres del Norte',
        content:
            'Tavira transformó completamente nuestra administración. La facturación que antes me tomaba 3 días ahora la hago en 2 horas. Los residentes están felices con la transparencia.',
    },
    {
        name: 'Carlos Rodríguez',
        role: 'Presidente del Consejo - Ciudadela Los Cedros',
        content:
            'La seguridad de datos es impresionante. Nuestra propia base de datos y todos los reportes financieros al día. El sistema contable cumple perfectamente con la normatividad colombiana.',
    },
    {
        name: 'Ana Patricia Muñoz',
        role: 'Tesorera - Conjunto Villa Verde',
        content:
            'Los reportes financieros son increíbles. Balance general, estado de resultados, todo automatizado. La conciliación bancaria que antes era un dolor de cabeza ahora es pan comido.',
    },
    {
        name: 'Jorge Luis Vargas',
        role: 'Administrador - Torres de Andalucía',
        content:
            'La comunicación con los residentes mejoró un 100%. Los comunicados llegan instantáneamente y puedo hacer seguimiento de qué tan efectiva es la comunicación.',
    },
    {
        name: 'Sandra Liliana Castro',
        role: 'Residente - Conjunto Santa Helena',
        content:
            'Como residente, ahora tengo total claridad sobre las finanzas del conjunto. Puedo ver mi estado de cuenta, hacer pagos en línea y estar al día con todo.',
    },
    {
        name: 'Ricardo Peña',
        role: 'Gerente Inmobiliario - Grupo Construir',
        content:
            'Implementamos Tavira en 12 conjuntos que administramos. El flujo de aprobación Proveedor-Concejo-Administración transformó nuestro control de gastos. Eficiencia operativa del 100%.',
    },
    {
        name: 'Alejandra Vega',
        role: 'Ex-Administradora / Actual Residente - Conjunto Los Pinos',
        content:
            'Lo mejor es que cuando terminé como administradora, toda la información siguió en el conjunto. Los correos institucionales y el historial permanecen para siempre. Continuidad total.',
    },
    {
        name: 'Fernando Morales',
        role: 'Residente - Ciudadela del Norte',
        content:
            'El marketplace interno cambió nuestra comunidad. Encontré un plomero excelente (5 estrellas), clases de inglés para mi hija, y hasta vendo mis tortas. Confianza entre vecinos 100% real.',
    },
    {
        name: 'Luis Hernández',
        role: 'Portero - Conjunto Villa Campestre',
        content:
            'La app de portería es increíble. Los citófonos están integrados, escaneo los QR de las visitas automáticamente, y registro los paquetes con fotos. Todo desde el celular, súper fácil.',
    },
    {
        name: 'Patricia Ruiz',
        role: 'Residente - Torres de Andalucía',
        content:
            'Descargué la app y ahora todo es más fácil. Pago desde el celular, envío códigos QR a mis visitas, compro en el marketplace del conjunto. Mi mamá de 70 años también la usa sin problemas.',
    },
]);
</script>
