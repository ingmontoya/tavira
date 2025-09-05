<template>
    <footer class="border-t border-gray-100 bg-[#1D3557] px-6 py-16">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-5">
                <!-- Company Info -->
                <div class="lg:col-span-2">
                    <div class="mb-6 flex items-center space-x-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#ff6b6b] shadow-lg">
                            <span class="text-xl font-bold text-white font-brand">T</span>
                        </div>
                        <span class="text-3xl font-bold text-white font-brand tracking-tight">Tavira</span>
                    </div>
                    <p class="mb-6 max-w-lg text-white/90 text-lg leading-relaxed">
                        Plataforma líder en administración de conjuntos residenciales en Colombia.
                        Transformamos la gestión inmobiliaria con tecnología de punta y seguridad bancaria.
                    </p>
                    <div class="mb-6">
                        <div
                            class="inline-flex items-center rounded-xl bg-[#ff6b6b] px-4 py-2 text-sm font-medium text-white shadow-md mb-4">
                            Gestión simple, comunidades fuertes
                        </div>
                        <div class="text-white/80 text-sm space-y-1">
                            <p>📧 hola@tavira.com.co</p>
                            <p>📱 WhatsApp: + +44 7447 313219</p>
                            <p style="display: none;">🏢 </p>
                        </div>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="mb-6 font-bold text-white text-lg">Producto</h4>
                    <ul class="space-y-3 text-sm text-white/80">
                        <li>
                            <Link href="/features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">
                            Características</Link>
                        </li>
                        <li>
                            <Link href="/security"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium">Seguridad
                            </Link>
                        </li>
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Apps Móviles</a></li>
                        <li><a href="#detailed-features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('detailed-features')">Integraciones</a></li>
                        <li><a href="#testimonials"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('testimonials')">Casos de Éxito</a></li>
                    </ul>
                </div>

                <!-- Solutions -->
                <div>
                    <h4 class="mb-6 font-bold text-white text-lg">Soluciones</h4>
                    <ul class="space-y-3 text-sm text-white/80">
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Gestión Financiera</a></li>
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Comunicación</a></li>
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Control de Acceso</a></li>
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Mantenimiento</a></li>
                        <li><a href="#features"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('features')">Marketplace</a></li>
                    </ul>
                </div>

                <!-- Support & Legal -->
                <div>
                    <h4 class="mb-6 font-bold text-white text-lg">Soporte</h4>
                    <ul class="space-y-3 text-sm text-white/80 mb-8">
                        <li><a href="#cta"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('cta')">Contacto</a></li>
                        <li><a href="#cta"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('cta')">Centro de Ayuda</a></li>
                        <li><a href="#how-it-works"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('how-it-works')">Capacitación</a></li>
                        <li><a href="#testimonials"
                                class="transition-colors duration-200 hover:text-[#06D6A0] hover:font-medium cursor-pointer"
                                @click="scrollToSection('testimonials')">Testimonios</a></li>
                    </ul>

                    <h5 class="mb-4 font-semibold text-white text-sm">Legal</h5>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">Términos de
                                Servicio</a></li>
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">Política de
                                Privacidad</a></li>
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">RGPD & Ley 1581</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="mt-12 border-t border-white/20 pt-8">
                <div class="flex flex-col lg:flex-row justify-between items-center space-y-4 lg:space-y-0">
                    <div class="text-white/70 text-sm text-center lg:text-left">
                        <p>&copy; 2024 Tavira - Software para Administración de Conjuntos Residenciales en Colombia.</p>
                        <p class="mt-1">Cumplimiento DIAN • Seguridad Bancaria • Base de Datos Exclusiva por Conjunto
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-white/70 text-sm">Disponible en:</span>
                        <div class="flex space-x-2">
                            <div class="bg-white/10 rounded-lg px-3 py-1 text-xs text-white flex items-center">
                                🍎 App Store
                            </div>
                            <div class="bg-white/10 rounded-lg px-3 py-1 text-xs text-white flex items-center">
                                🤖 Google Play
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Keywords -->
                <div class="mt-6 text-center">
                    <p class="text-white/50 text-xs leading-relaxed">
                        Software administración conjuntos residenciales Colombia | Sistema gestión inmobiliaria |
                        Aplicación móvil portería residentes | Facturación electrónica DIAN |
                        Contabilidad conjuntos residenciales | PQR digital | Control acceso QR |
                        Gestión financiera condominios | Comunicados digitales | Mantenimiento predictivo
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
};
</script>
