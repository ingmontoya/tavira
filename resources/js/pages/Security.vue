<template>
    <div class="min-h-screen bg-gray-50">
        <!-- SEO Meta Tags -->
        <Head 
            title="Seguridad Bancaria y Base de Datos Exclusiva por Conjunto | Tavira Colombia"
        >
            <meta name="description" content="Seguridad de nivel bancario para tu conjunto residencial. Base de datos exclusiva por conjunto, encriptación SSL 256-bit, backups automatizados, cumplimiento GDPR y Ley 1581 colombiana. Imposible filtración de datos entre conjuntos." />
            <meta name="keywords" content="seguridad conjuntos residenciales Colombia, base datos exclusiva conjunto, encriptación nivel bancario, SSL 256-bit condominios, backup automatizado inmobiliaria, cumplimiento GDPR Colombia, Ley 1581 protección datos, multitenancy seguro, aislamiento datos conjuntos, seguridad información residencial, audit logs inmobiliaria, monitoreo 24/7 conjuntos" />
            <meta property="og:title" content="Seguridad Bancaria y Base de Datos Exclusiva por Conjunto | Tavira Colombia" />
            <meta property="og:description" content="Base de datos exclusiva, encriptación bancaria, backups automatizados. La máxima seguridad para la información de tu conjunto residencial." />
            <meta property="og:type" content="website" />
            <link rel="canonical" href="https://tavira.com.co/security" />
        </Head>

        <!-- Navigation -->
        <LandingNavigation />

        <!-- Hero Section -->
        <section class="bg-gradient-to-br from-[#1D3557] via-[#1D3557] to-[#06D6A0] px-6 py-16 md:py-20">
            <div class="mx-auto max-w-4xl text-center">
                <div class="mb-6">
                    <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-white/20 text-6xl">
                        🔒
                    </div>
                </div>
                <h1 class="mb-6 text-4xl md:text-5xl lg:text-6xl font-bold text-white">
                    Seguridad de
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent">
                        Nivel Bancario
                    </span>
                </h1>
                <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto">
                    Tu información está protegida con los más altos estándares de seguridad de la industria. 
                    Base de datos exclusiva, encriptación total y cumplimiento normativo garantizado.
                </p>
            </div>
        </section>

        <!-- Main Security Features -->
        <section class="px-6 py-16 md:py-20">
            <div class="mx-auto max-w-7xl">
                <!-- Database Isolation -->
                <div class="mb-20">
                    <div class="grid gap-12 lg:grid-cols-2 items-center">
                        <div>
                            <div class="inline-flex items-center rounded-full bg-[#06D6A0]/10 px-4 py-2 text-[#06D6A0] font-medium mb-4">
                                🏢 Aislamiento Total
                            </div>
                            <h2 class="mb-6 text-3xl md:text-4xl font-bold text-gray-900">
                                Base de Datos Exclusiva
                            </h2>
                            <div class="space-y-4 text-lg text-gray-600 mb-8">
                                <p>
                                    <strong>Cada conjunto tiene su propia base de datos dedicada.</strong> No compartimos infraestructura de datos con otros clientes. 
                                    Tu información está completamente aislada y protegida.
                                </p>
                                <p>
                                    <strong>Arquitectura Multi-Tenant Avanzada:</strong> Utilizamos tecnología de punta (stancl/tenancy) que garantiza 
                                    separación total de datos a nivel de base de datos.
                                </p>
                                <p>
                                    <strong>Imposibilidad de Filtración:</strong> Es técnicamente imposible que los datos de un conjunto 
                                    se filtren o sean accesibles por otro conjunto.
                                </p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="rounded-lg border border-gray-200 bg-white p-4">
                                    <div class="flex items-center mb-2">
                                        <svg class="h-6 w-6 text-[#06D6A0] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Base de Datos Propia</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Cada conjunto tiene su BD exclusiva</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-white p-4">
                                    <div class="flex items-center mb-2">
                                        <svg class="h-6 w-6 text-[#06D6A0] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Aislamiento Total</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Cero posibilidad de filtración</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-white p-4">
                                    <div class="flex items-center mb-2">
                                        <svg class="h-6 w-6 text-[#06D6A0] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Backup Independiente</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Respaldos exclusivos por conjunto</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-white p-4">
                                    <div class="flex items-center mb-2">
                                        <svg class="h-6 w-6 text-[#06D6A0] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
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
                                <div class="rounded-3xl bg-gradient-to-br from-[#1D3557] to-[#06D6A0] p-8 text-white text-center">
                                    <h3 class="text-xl font-bold mb-6">Arquitectura de Aislamiento</h3>
                                    <div class="space-y-4">
                                        <div class="bg-white/20 rounded-lg p-4">
                                            <div class="text-2xl mb-2">🏢</div>
                                            <div class="font-semibold">Conjunto Torres del Norte</div>
                                            <div class="text-sm text-white/80">Base de Datos: tenant_001</div>
                                        </div>
                                        <div class="bg-white/20 rounded-lg p-4">
                                            <div class="text-2xl mb-2">🏢</div>
                                            <div class="font-semibold">Conjunto Villa Verde</div>
                                            <div class="text-sm text-white/80">Base de Datos: tenant_002</div>
                                        </div>
                                        <div class="bg-white/20 rounded-lg p-4">
                                            <div class="text-2xl mb-2">🏢</div>
                                            <div class="font-semibold">Tu Conjunto</div>
                                            <div class="text-sm text-white/80">Base de Datos: Exclusiva</div>
                                        </div>
                                    </div>
                                    <div class="mt-6 text-sm text-white/90">
                                        ✓ Cada conjunto = Base de datos independiente<br>
                                        ✓ Cero comunicación entre bases de datos<br>
                                        ✓ Imposible acceso cruzado
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Encryption -->
                <div class="mb-20">
                    <div class="grid gap-12 lg:grid-cols-2 items-center">
                        <div class="lg:order-2">
                            <div class="inline-flex items-center rounded-full bg-[#FF6B6B]/10 px-4 py-2 text-[#FF6B6B] font-medium mb-4">
                                🔐 Encriptación
                            </div>
                            <h2 class="mb-6 text-3xl md:text-4xl font-bold text-gray-900">
                                Encriptación de Grado Bancario
                            </h2>
                            <div class="space-y-4 text-lg text-gray-600 mb-8">
                                <p>
                                    <strong>SSL/TLS 256-bit:</strong> Toda la comunicación entre tu navegador y nuestros servidores 
                                    está protegida con el mismo nivel de encriptación que usan los bancos.
                                </p>
                                <p>
                                    <strong>Encriptación en Reposo:</strong> Todos los datos sensibles (contraseñas, información financiera, 
                                    documentos) están encriptados en nuestras bases de datos.
                                </p>
                                <p>
                                    <strong>Hashing Seguro:</strong> Las contraseñas nunca se almacenan en texto plano. Utilizamos 
                                    algoritmos de hashing bcrypt con salt aleatorio.
                                </p>
                                <p>
                                    <strong>Tokens de Sesión:</strong> Autenticación basada en tokens seguros con expiración automática 
                                    y renovación transparente.
                                </p>
                            </div>
                        </div>
                        <div class="lg:order-1">
                            <div class="rounded-3xl bg-gradient-to-br from-gray-900 to-gray-700 p-8 text-green-400">
                                <div class="font-mono text-sm space-y-2">
                                    <div class="text-gray-400">// Ejemplo de encriptación de datos</div>
                                    <div class="text-white">const userData = {</div>
                                    <div class="text-white pl-4">name: "María González",</div>
                                    <div class="text-white pl-4">apartment: "Torre A - 501",</div>
                                    <div class="text-white pl-4">payment: "$450.000"</div>
                                    <div class="text-white">};</div>
                                    <div class="text-gray-400 mt-4">// Datos encriptados en BD:</div>
                                    <div class="text-green-400 break-all">encrypt(userData) =></div>
                                    <div class="text-green-400 text-xs break-all">
                                        "AES256:8f2a9d7c4e1b6f0a3d8c9b2e5f4a7d1c<br>
                                        9e6b3f8a2d5c7b4e9f1a6d3c8b5e2f7a<br>
                                        4d1c6f9b2e8a5d7c3f6b9e2a8d5c7f1b"
                                    </div>
                                </div>
                                <div class="mt-6 text-center">
                                    <div class="inline-flex items-center bg-green-400/20 text-green-400 px-3 py-1 rounded-full text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Encriptación AES-256
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="mb-20">
                    <div class="grid gap-12 lg:grid-cols-2 items-center">
                        <div>
                            <div class="inline-flex items-center rounded-full bg-purple-100 px-4 py-2 text-purple-700 font-medium mb-4">
                                👥 Control de Acceso
                            </div>
                            <h2 class="mb-6 text-3xl md:text-4xl font-bold text-gray-900">
                                Permisos Granulares
                            </h2>
                            <div class="space-y-4 text-lg text-gray-600 mb-8">
                                <p>
                                    <strong>Roles Específicos:</strong> Administradores, miembros del consejo, residentes y personal 
                                    tienen acceso solo a las funciones que necesitan.
                                </p>
                                <p>
                                    <strong>Permisos Detallados:</strong> Control granular sobre cada función: ver, crear, editar, eliminar, 
                                    aprobar. Cada usuario tiene exactamente los permisos necesarios.
                                </p>
                                <p>
                                    <strong>Autenticación Multifactor (2FA):</strong> Capa adicional de seguridad opcional para cuentas 
                                    administrativas y financieras.
                                </p>
                                <p>
                                    <strong>Sesiones Seguras:</strong> Timeout automático, cierre de sesión en múltiples dispositivos, 
                                    detección de actividad sospechosa.
                                </p>
                            </div>
                        </div>
                        <div class="lg:pl-8">
                            <div class="space-y-4">
                                <!-- Admin Role -->
                                <div class="rounded-lg border-2 border-[#06D6A0] bg-[#06D6A0]/5 p-4">
                                    <div class="flex items-center mb-2">
                                        <div class="w-3 h-3 bg-[#06D6A0] rounded-full mr-3"></div>
                                        <span class="font-semibold text-gray-900">Administrador</span>
                                    </div>
                                    <div class="text-sm text-gray-600 grid grid-cols-2 gap-2">
                                        <div>✓ Gestión financiera</div>
                                        <div>✓ Reportes ejecutivos</div>
                                        <div>✓ Configuración sistema</div>
                                        <div>✓ Gestión usuarios</div>
                                    </div>
                                </div>

                                <!-- Council Role -->
                                <div class="rounded-lg border-2 border-blue-500 bg-blue-50 p-4">
                                    <div class="flex items-center mb-2">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                        <span class="font-semibold text-gray-900">Miembro del Consejo</span>
                                    </div>
                                    <div class="text-sm text-gray-600 grid grid-cols-2 gap-2">
                                        <div>✓ Ver reportes</div>
                                        <div>✓ Aprobar gastos</div>
                                        <div>✓ Comunicados</div>
                                        <div>✓ Correspondencia</div>
                                    </div>
                                </div>

                                <!-- Resident Role -->
                                <div class="rounded-lg border-2 border-gray-300 bg-gray-50 p-4">
                                    <div class="flex items-center mb-2">
                                        <div class="w-3 h-3 bg-gray-500 rounded-full mr-3"></div>
                                        <span class="font-semibold text-gray-900">Residente</span>
                                    </div>
                                    <div class="text-sm text-gray-600 grid grid-cols-2 gap-2">
                                        <div>✓ Estado de cuenta</div>
                                        <div>✓ Pagos en línea</div>
                                        <div>✓ Solicitudes</div>
                                        <div>✓ Reservas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance & Certifications -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <h2 class="mb-6 text-3xl md:text-4xl font-bold text-gray-900">
                            Cumplimiento Normativo
                        </h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Cumplimos con todas las regulaciones colombianas e internacionales de protección de datos
                        </p>
                    </div>

                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-white p-6 shadow-lg text-center border-2 border-green-200">
                            <div class="text-4xl mb-4">🇨🇴</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Ley 1581 de 2012</h3>
                            <p class="text-sm text-gray-600">Protección de datos personales en Colombia. Cumplimiento total con autorización y tratamiento de datos.</p>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-lg text-center border-2 border-blue-200">
                            <div class="text-4xl mb-4">🇪🇺</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">GDPR Compliant</h3>
                            <p class="text-sm text-gray-600">Reglamento General de Protección de Datos europeo. Estándares internacionales de privacidad.</p>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-lg text-center border-2 border-purple-200">
                            <div class="text-4xl mb-4">🏦</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">PCI DSS</h3>
                            <p class="text-sm text-gray-600">Estándar de seguridad para procesamiento de pagos. Protección de información financiera.</p>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-lg text-center border-2 border-orange-200">
                            <div class="text-4xl mb-4">🔍</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">ISO 27001</h3>
                            <p class="text-sm text-gray-600">Gestión de seguridad de la información. Procedimientos y controles certificados internacionalmente.</p>
                        </div>
                    </div>
                </div>

                <!-- Security Monitoring -->
                <div class="mb-20">
                    <div class="rounded-3xl bg-gradient-to-r from-[#1D3557] to-[#06D6A0] p-8 md:p-12">
                        <div class="text-center text-white">
                            <div class="text-6xl mb-6">👁️</div>
                            <h2 class="mb-6 text-3xl md:text-4xl font-bold">
                                Monitoreo 24/7
                            </h2>
                            <p class="text-xl mb-8 max-w-3xl mx-auto opacity-90">
                                Vigilancia constante de la seguridad con detección automática de amenazas y respuesta inmediata
                            </p>
                            
                            <div class="grid gap-6 md:grid-cols-3 mb-8">
                                <div class="bg-white/10 rounded-xl p-6 backdrop-blur-sm">
                                    <div class="text-3xl mb-3">🛡️</div>
                                    <h3 class="font-bold mb-2">Detección de Intrusiones</h3>
                                    <p class="text-sm opacity-90">Monitoreo automático de intentos de acceso no autorizado</p>
                                </div>
                                <div class="bg-white/10 rounded-xl p-6 backdrop-blur-sm">
                                    <div class="text-3xl mb-3">📊</div>
                                    <h3 class="font-bold mb-2">Analytics de Seguridad</h3>
                                    <p class="text-sm opacity-90">Análisis de patrones y comportamientos sospechosos</p>
                                </div>
                                <div class="bg-white/10 rounded-xl p-6 backdrop-blur-sm">
                                    <div class="text-3xl mb-3">⚡</div>
                                    <h3 class="font-bold mb-2">Respuesta Automática</h3>
                                    <p class="text-sm opacity-90">Bloqueo inmediato ante actividades maliciosas</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-center gap-6 text-white/90">
                                <div class="flex items-center">
                                    <svg class="mr-2 h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Monitoreo Continuo
                                </div>
                                <div class="flex items-center">
                                    <svg class="mr-2 h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Alertas Inmediatas
                                </div>
                                <div class="flex items-center">
                                    <svg class="mr-2 h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Logs de Auditoría
                                </div>
                                <div class="flex items-center">
                                    <svg class="mr-2 h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Backup Automático
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust Indicators -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <h2 class="mb-6 text-3xl md:text-4xl font-bold text-gray-900">
                            Indicadores de Confianza
                        </h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Métricas que demuestran nuestro compromiso con la seguridad
                        </p>
                    </div>

                    <div class="grid gap-8 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-5xl font-bold text-[#06D6A0] mb-2">99.9%</div>
                            <div class="text-lg font-semibold text-gray-900 mb-2">Uptime</div>
                            <div class="text-sm text-gray-600">Disponibilidad garantizada con SLA</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-[#06D6A0] mb-2">0</div>
                            <div class="text-lg font-semibold text-gray-900 mb-2">Brechas</div>
                            <div class="text-sm text-gray-600">Cero filtraciones de datos en 5 años</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-[#06D6A0] mb-2">24/7</div>
                            <div class="text-lg font-semibold text-gray-900 mb-2">Monitoreo</div>
                            <div class="text-sm text-gray-600">Vigilancia continua automatizada</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-[#06D6A0] mb-2">256</div>
                            <div class="text-lg font-semibold text-gray-900 mb-2">Bits SSL</div>
                            <div class="text-sm text-gray-600">Encriptación de grado militar</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security Guarantee -->
        <section class="bg-gradient-to-r from-[#1D3557] to-[#06D6A0] px-6 py-16">
            <div class="mx-auto max-w-4xl text-center">
                <div class="text-6xl mb-6">🛡️</div>
                <h2 class="mb-6 text-3xl md:text-4xl font-bold text-white">
                    Garantía de Seguridad Total
                </h2>
                <p class="mb-8 text-xl text-white/90 max-w-3xl mx-auto">
                    Estamos tan seguros de nuestro sistema de seguridad que ofrecemos garantía total. 
                    Si tu conjunto sufre alguna brecha de seguridad por falla de nuestro sistema, 
                    asumimos la responsabilidad completa.
                </p>
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                    <Link
                        href="/register"
                        class="inline-flex items-center justify-center rounded-xl bg-[#FF6B6B] px-8 py-4 text-lg font-semibold text-white transition-all hover:bg-[#ff5555] hover:scale-105"
                    >
                        Comenzar con Seguridad Total
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </Link>
                    <Link
                        href="/security-audit"
                        class="inline-flex items-center justify-center rounded-xl border-2 border-white/50 px-8 py-4 text-lg font-semibold text-white transition-all hover:bg-white/10"
                    >
                        Ver Auditoría de Seguridad
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <LandingFooter />
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import LandingFooter from '../components/landing/LandingFooter.vue';
import LandingNavigation from '../components/landing/LandingNavigation.vue';
</script>