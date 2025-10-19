<template>
    <footer class="border-t border-gray-100 bg-[#1D3557] px-6 py-16">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-5">
                <!-- Company Info -->
                <div class="lg:col-span-2">
                    <div class="mb-6 flex items-center space-x-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#ff6b6b] shadow-lg">
                            <span class="font-brand text-xl font-bold text-white">T</span>
                        </div>
                        <span class="font-brand text-3xl font-bold tracking-tight text-white">Tavira</span>
                    </div>
                    <p class="mb-6 max-w-lg text-lg leading-relaxed text-white/90">
                        Plataforma líder en administración de conjuntos residenciales en Colombia. Transformamos la gestión inmobiliaria con
                        tecnología de punta y seguridad bancaria.
                    </p>
                    <div class="mb-6">
                        <div class="mb-4 inline-flex items-center rounded-xl bg-[#ff6b6b] px-4 py-2 text-sm font-medium text-white shadow-md">
                            Gestión simple, comunidades fuertes
                        </div>
                        <div class="space-y-1 text-sm text-white/80">
                            <p>📧 hola@tavira.com.co</p>
                            <p>📱 WhatsApp: + +44 7447 313219</p>
                            <p style="display: none">🏢</p>
                        </div>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="mb-6 text-lg font-bold text-white">Producto</h4>
                    <ul class="space-y-3 text-sm text-white/80">
                        <li>
                            <Link href="/features" class="transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]">
                                Características</Link
                            >
                        </li>
                        <li>
                            <Link href="/security" class="transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]">Seguridad </Link>
                        </li>
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Apps Móviles</a
                            >
                        </li>
                        <li>
                            <a
                                href="#detailed-features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('detailed-features')"
                                >Integraciones</a
                            >
                        </li>
                        <li>
                            <a
                                href="#testimonials"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('testimonials')"
                                >Casos de Éxito</a
                            >
                        </li>
                    </ul>
                </div>

                <!-- Solutions -->
                <div>
                    <h4 class="mb-6 text-lg font-bold text-white">Soluciones</h4>
                    <ul class="space-y-3 text-sm text-white/80">
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Gestión Financiera</a
                            >
                        </li>
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Comunicación</a
                            >
                        </li>
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Control de Acceso</a
                            >
                        </li>
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Mantenimiento</a
                            >
                        </li>
                        <li>
                            <a
                                href="#features"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('features')"
                                >Marketplace</a
                            >
                        </li>
                    </ul>
                </div>

                <!-- Support & Legal -->
                <div>
                    <h4 class="mb-6 text-lg font-bold text-white">Soporte</h4>
                    <ul class="mb-8 space-y-3 text-sm text-white/80">
                        <li>
                            <a
                                href="#cta"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('cta')"
                                >Contacto</a
                            >
                        </li>
                        <li>
                            <a
                                href="#cta"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('cta')"
                                >Centro de Ayuda</a
                            >
                        </li>
                        <li>
                            <a
                                href="#how-it-works"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('how-it-works')"
                                >Capacitación</a
                            >
                        </li>
                        <li>
                            <a
                                href="#testimonials"
                                class="cursor-pointer transition-colors duration-200 hover:font-medium hover:text-[#06D6A0]"
                                @click="scrollToSection('testimonials')"
                                >Testimonios</a
                            >
                        </li>
                    </ul>

                    <h5 class="mb-4 text-sm font-semibold text-white">Legal</h5>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">Términos de Servicio</a></li>
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">Política de Privacidad</a></li>
                        <li><a href="#" class="transition-colors duration-200 hover:text-[#06D6A0]">RGPD & Ley 1581</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="mt-12 border-t border-white/20 pt-8">
                <div class="flex flex-col items-center justify-between space-y-4 lg:flex-row lg:space-y-0">
                    <div class="text-center text-sm text-white/70 lg:text-left">
                        <p>&copy; 2024 Tavira - Software para Administración de Conjuntos Residenciales en Colombia.</p>
                        <p class="mt-1">Cumplimiento DIAN • Seguridad Bancaria • Base de Datos Exclusiva por Conjunto</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-white/70">Disponible en:</span>
                        <div class="flex space-x-2">
                            <div class="flex items-center rounded-lg bg-white/10 px-3 py-1 text-xs text-white">🍎 App Store</div>
                            <div class="flex items-center rounded-lg bg-white/10 px-3 py-1 text-xs text-white">🤖 Google Play</div>
                        </div>
                    </div>
                </div>

                <!-- SEO Keywords -->
                <div class="mt-6 text-center">
                    <p class="text-xs leading-relaxed text-white/50">
                        Software administración conjuntos residenciales Colombia | Sistema gestión inmobiliaria | Aplicación móvil portería residentes
                        | Facturación electrónica DIAN | Contabilidad conjuntos residenciales | PQR digital | Control acceso QR | Gestión financiera
                        condominios | Comunicados digitales | Mantenimiento predictivo
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
            block: 'start',
        });
    }
};
</script>
