# 🏠 Tavira - Sistema Integral de Gestión para Propiedad Horizontal

**Tavira** es una plataforma digital todo-en-uno diseñada para la gestión moderna de conjuntos residenciales y edificios bajo el régimen de propiedad horizontal. El sistema está optimizado para la gestión eficiente de un conjunto residencial, facilitando a administradores, consejos y residentes la operación transparente y automatizada de la comunidad.

La plataforma incluye herramientas para la administración de residentes, finanzas completas con facturación automática, seguimiento de pagos, acuerdos de pago, gestión de apartamentos y mucho más. Está diseñada para ser usada tanto desde un panel web como desde dispositivos móviles.

## 📊 Estado Actual del Proyecto (Agosto 2025)

**Progreso General: 85% Implementado**

🔥 **Últimas Implementaciones:**
- ✅ Módulo Contable Completo (Decreto 2650 Colombia)
- ✅ Sistema de Gastos con Flujo de Aprobación
- ✅ App Móvil Nativa (iOS + Android)
- ✅ Sistema de Mantenimiento Integral
- ✅ Gestión de Espacios Comunes y Reservas
- ✅ Facturación Electrónica DIAN
- ✅ Sistema de Soporte y PQRS
- ✅ Comunicaciones Masivas
- ✅ Seguridad Avanzada (OWASP Top 10)

🎆 **Características Destacadas:**
- **Contabilidad Automatizada**: Partida doble con integración automática
- **Facturación Electrónica**: Cumplimiento DIAN con Factus
- **App Móvil Nativa**: iOS y Android con UX moderna
- **Seguridad Empresarial**: Cumplimiento OWASP con auditoría completa
- **Automatización IA**: 15+ comandos automatizados para operaciones

A continuación, se detallan las funcionalidades implementadas y por implementar:

---

## 🏢 Gestión de Conjuntos (Configuración Única)

- [x] **Configuración de conjunto único** - Sistema optimizado para un conjunto residencial
- [x] **Personalización completa** - Logo, colores y datos institucionales configurables
- [x] **Soporte para múltiples torres/edificios** - Gestión de torres A, B, C con pisos y apartamentos
- [x] **Configuración flexible de apartamentos** - Tipos de apartamento configurables (Tipo A, B, C, Penthouse)
- [x] **Dashboard integral** - Estadísticas completas con datos reales y mock data
- [x] **Configuración avanzada de pisos** - Soporte para configuración específica por piso
- [x] **Configuración de penthouses** - Detección automática y configuración de penthouses
- [x] **Metadatos de configuración** - Campo JSON extensible para configuraciones adicionales
- [ ] Dominio personalizado por conjunto
- [ ] Control central para múltiples conjuntos (multitenant futuro)

---

## 🧑‍💼 Administración de Residentes

- [x] **Registro completo de residentes** - Propietarios y arrendatarios con datos personales
- [x] **Asignación por apartamento** - Vinculación directa con torre, piso y apartamento
- [x] **Gestión de tipos de residente** - Propietario, arrendatario, familiar, etc.
- [x] **Estado activo/inactivo** - Control de residentes activos en el conjunto
- [x] **CRUD completo** - Crear, ver, editar y eliminar residentes
- [x] **Validaciones de seguridad** - Formularios con validación y sanitización
- [ ] Historial de ocupantes por unidad
- [ ] Adjuntar documentos por unidad o residente
- [ ] Notificaciones automáticas (correo, WhatsApp opcional)

---

## 💳 Finanzas y Cuotas

- [x] **Sistema completo de facturación** - Creación, edición y gestión de facturas mensuales
- [x] **Generación automática de facturas** - Comando programado para generar facturas mensuales
- [x] **Conceptos de pago configurables** - Administración, mantenimiento, servicios públicos, etc.
- [x] **Aplicación de descuento por pronto pago** - Configuración de días y porcentajes de descuento
- [x] **Cálculo automático de intereses por mora** - Procesamiento automático de recargos por retraso
- [x] **Configuración de parámetros de pago** - Días de gracia, porcentajes de mora, descuentos
- [x] **Sistema de acuerdos de pago** - Creación y seguimiento de planes de pago especiales
- [x] **Seguimiento de pagos por apartamento** - Estado de pagos, saldos pendientes, historial
- [x] **Reportes de cartera vencida** - Apartamentos morosos con exportación a Excel/PDF
- [x] **Estados de apartamentos** - Al día, en mora, con acuerdo de pago, etc.
- [x] **Notificaciones por email** - Envío automático de facturas por correo electrónico
- [x] **Vistas de facturación PDF** - Generación de facturas en formato PDF profesional
- [x] **Sistema de pagos completo** - Registro, aplicación y seguimiento de pagos
- [x] **Aplicación automática FIFO** - Aplicación de pagos por orden de antigüedad
- [x] **Estados de pago dinámicos** - Pendiente, parcialmente aplicado, aplicado
- [x] **Conciliación bancaria** - Importación y conciliación de pagos desde Jelpit
- [x] **Mapeo de conceptos a cuentas** - Configuración automática contable
- [x] **Facturación electrónica DIAN** - Integración con Factus para facturación electrónica
- [x] **Configuración DIAN** - Campos para contribuyente especial, gran contribuyente, etc.
- [x] **Estados de cuenta por apartamento** - Vista detallada de movimientos financieros
- [ ] Integración con pasarelas de pago (MercadoPago, Wompi, etc.)

---

## 🏠 Gestión de Apartamentos

- [x] **CRUD completo de apartamentos** - Crear, ver, editar y eliminar apartamentos
- [x] **Organización por torres** - Gestión de apartamentos por torre (A, B, C)
- [x] **Tipos de apartamento configurables** - Tipo A, B, C, Penthouse con características específicas
- [x] **Seguimiento de estado de pagos** - Visualización del estado financiero por apartamento
- [x] **Vista de apartamentos morosos** - Listado especializado de apartamentos con deudas
- [x] **Información de acuerdos de pago** - Estado y detalles de acuerdos especiales
- [x] **Exportación de reportes** - Excel y PDF de apartamentos morosos
- [x] **Estadísticas por torre** - Resumen de ocupación y pagos por torre
- [x] **Validaciones de integridad** - Verificación de datos de apartamento únicos

---

## 📦 Correspondencia y Paquetería

- [x] **Sistema de correspondencia completo** - CRUD para gestión de correspondencia
- [x] **Adjuntos de correspondencia** - Soporte para archivos adjuntos
- [x] **Tipos de correspondencia** - Clasificación por tipo y prioridad
- [x] **Estado de correspondencia** - Seguimiento de entrega y recepción
- [x] **Política de acceso** - Control de permisos para correspondencia
- [ ] Notificación automática al residente al llegar un paquete
- [ ] Marcar como entregado con firma digital o código de seguridad
- [ ] Integración con sistema de QR para entrega

---

## 🚗 Registro de Visitas

- [x] **Sistema completo de visitas** - CRUD para registro de visitas
- [x] **Generación de códigos QR** - Códigos únicos para visitantes
- [x] **Tipos de visita** - Clasificación por tipo (visitante, delivery, servicio, etc.)
- [x] **Estados de visita** - Programada, en curso, finalizada
- [x] **Registro de horarios** - Entrada y salida con timestamps
- [x] **Validación de visitantes** - Sistema de autorización previa
- [x] **Historial completo** - Listado de visitas por unidad y fechas
- [x] **Integración con residentes** - Autorización desde panel web
- [x] **Control de acceso** - Validación automática de permisos
- [x] **Reportes de seguridad** - Estadísticas de acceso y visitantes
- [x] **Scanner de QR** - Interface para portería
- [ ] App móvil para generación de QR
- [ ] Notificaciones push de llegada de visitantes

---

## 💬 Comunicados y Anuncios

- [x] **Sistema completo de anuncios** - Creación, edición y gestión de comunicados
- [x] **Segmentación avanzada** - Por tipo de residente, apartamento específico, torre
- [x] **Estados de anuncio** - Borrador, publicado, archivado
- [x] **Confirmaciones de lectura** - Sistema de seguimiento de confirmaciones
- [x] **Tipos de anuncio** - Clasificación por categoría e importancia
- [x] **Targeting inteligente** - Dirigir a grupos específicos de residentes
- [x] **Gestión de confirmaciones** - Dashboard para ver quién ha confirmado
- [x] **Historial completo** - Archivo de todos los comunicados
- [x] **Notificaciones automáticas** - Alertas por email y sistema interno
- [x] **Vista de residentes** - Interface específica para lectura de anuncios
- [x] **Dashboard de administración** - Métricas de alcance y confirmaciones
- [ ] Notificaciones push móviles
- [ ] Integración con WhatsApp Business

---

## 🧾 Documentos y Actas

- [x] **Sistema de gestión documental** - Subida y organización de documentos
- [x] **Categorización avanzada** - Tipos de documento configurables
- [x] **Control de acceso** - Permisos por rol y documento
- [x] **Metadatos de documentos** - Información detallada y etiquetas
- [x] **Búsqueda avanzada** - Filtros por tipo, fecha, categoría
- [x] **Historial de accesos** - Seguimiento de descargas y visualizaciones
- [ ] Versionado de documentos
- [ ] Firma digital de actas
- [ ] Workflow de aprobación de documentos

---

## 👷 Proveedores y Gastos

- [x] **Sistema completo de proveedores** - Registro y gestión de proveedores
- [x] **Gestión integral de gastos** - CRUD completo para gastos y egresos
- [x] **Categorías de gastos** - Clasificación configurable por tipo
- [x] **Flujo de aprobación** - Sistema de aprobación con estados dinámicos
- [x] **Aprobación por consejo** - Flujo especial para gastos que requieren consejo
- [x] **Diagramas de flujo dinámicos** - Visualización Mermaid del proceso de aprobación
- [x] **Integración presupuestal** - Asociación automática con rubros presupuestales
- [x] **Comprobantes digitales** - Subida y gestión de documentos soporte
- [x] **Dashboard de aprobaciones** - Vista centralizada para aprobadores
- [x] **Alertas automáticas** - Notificaciones por montos y vencimientos
- [x] **Integración contable** - Generación automática de asientos contables
- [x] **Reportes de gastos** - Análisis por categoría, proveedor, período
- [x] **Estados configurables** - Borrador, pendiente, aprobado, pagado, rechazado
- [x] **Configuración de umbrales** - Montos que requieren aprobación especial

---

## 🔐 Seguridad y Acceso

- [x] **Sistema de autenticación Laravel Breeze** - Login, registro, recuperación de contraseña
- [x] **Middleware de seguridad completo** - Rate limiting, sanitización, headers de seguridad
- [x] **Validación de contraseñas seguras** - Reglas de complejidad de contraseñas
- [x] **Sesiones seguras** - Gestión segura de sesiones y tokens
- [x] **Subida segura de archivos** - Validación y sanitización de archivos
- [x] **Auditoría de acciones** - Logging de acciones de usuarios
- [x] **Configuración de seguridad** - Panel de configuración de parámetros de seguridad
- [x] **Servicio de autenticación de dos factores** - Implementación 2FA disponible
- [ ] Roles predefinidos específicos del dominio
- [ ] Sistema de permisos granular por módulo
- [ ] Historial detallado de accesos

---

## 🧠 Reportes e Inteligencia

- [x] **Dashboard con estadísticas clave** - Resumen de apartamentos, residentes, pagos
- [x] **Reportes de apartamentos morosos** - Listado detallado con exportación Excel/PDF
- [x] **Estadísticas por torre** - Datos de ocupación y morosidad por torre
- [x] **Datos de facturación** - Estados de pago y saldos pendientes
- [x] **Vista de acuerdos de pago** - Seguimiento de planes especiales de pago
- [ ] Reporte de facturación mensual vs recaudado
- [ ] Porcentaje de mora por periodo histórico
- [ ] Estadísticas de visitas y correspondencia
- [ ] Análisis predictivo de morosidad

---

## 🤖 Automatización y Comandos

- [x] **Generación automática de facturas mensuales** - Comando programado para crear facturas
- [x] **Procesamiento automático de recargos por mora** - Cálculo y aplicación de intereses
- [x] **Verificación de cumplimiento de acuerdos de pago** - Monitoreo automático de planes
- [x] **Actualización de estados de pago** - Sincronización automática de estados de apartamentos
- [x] **Notificaciones por email** - Envío automático de facturas y recordatorios
- [x] **Seeders para datos de prueba** - Generación de data mock para desarrollo
- [x] **Apropiación automática de fondo de reserva** - Cálculo mensual del fondo obligatorio
- [x] **Verificación de sobrepresupuesto** - Alertas automáticas de ejecución presupuestal
- [x] **Evaluación de facturación electrónica** - Proceso automático de análisis
- [x] **Cierre mensual automatizado** - Proceso integral de cierre de período
- [x] **Recordatorios de gastos** - Notificaciones automáticas de vencimiento
- [x] **Configuración de datos de producción** - Setup automático para entornos
- [x] **Limpieza de datos financieros** - Comandos de mantenimiento
- [x] **Actualización de descripciones** - Mantenimiento automático de transacciones
- [ ] Backup automático programado de datos
- [ ] Informes programados por email

---

## 💰 Módulo Contable Completo (NUEVO)

- [x] **Sistema contable por partida doble** - Implementación completa según normas colombianas
- [x] **Plan de cuentas Decreto 2650** - 60+ cuentas implementadas siguiendo normativa
- [x] **Transacciones contables automáticas** - Generación automática desde facturas y pagos
- [x] **Presupuesto anual con ejecución** - Sistema completo de presupuestación
- [x] **Reportes financieros** - Balance General, Estado de Resultados, Libro Mayor
- [x] **Integración automática** - Eventos de facturación generan asientos contables
- [x] **Validaciones de partida doble** - Control automático debe = haber
- [x] **Fondo de reserva obligatorio** - Cálculo automático del 30%
- [x] **Sistema de alertas presupuestales** - Notificaciones de sobreejecución
- [x] **Trazabilidad completa** - Auditoría de todas las transacciones
- [x] **Estados contables** - Borrador, contabilizado, cancelado
- [x] **Conciliación bancaria** - Proceso de cuadre con bancos
- [x] **Dashboard contable** - Métricas financieras en tiempo real
- [x] **Mapeo automático** - Conceptos de pago a cuentas contables
- [x] **Ejecución presupuestal** - Seguimiento automático vs presupuesto

---

## 🏥 Sistema de Mantenimiento (NUEVO)

- [x] **Gestión completa de solicitudes** - CRUD para requests de mantenimiento
- [x] **Categorías de mantenimiento** - Clasificación por tipo de servicio
- [x] **Personal de mantenimiento** - Gestión de staff interno
- [x] **Órdenes de trabajo** - Sistema completo de work orders
- [x] **Items de trabajo detallados** - Desglose específico de tareas
- [x] **Estados de solicitudes** - Flujo completo de estados
- [x] **Documentos adjuntos** - Soporte para evidencias y comprobantes
- [x] **Soporte de proveedores externos** - Integración con terceros
- [x] **Calendario de mantenimiento** - Vista cronológica de actividades
- [x] **Dashboard de seguimiento** - Métricas y KPIs de mantenimiento
- [x] **Notificaciones automáticas** - Alertas de creación y actualización
- [x] **Historial completo** - Trazabilidad de todas las actividades
- [x] **Integración con gastos** - Conexión automática con sistema financiero
- [x] **Reportes especializados** - Análisis de costos y tiempos
- [ ] Mantenimiento preventivo programado
- [ ] App móvil para técnicos

---

## 🎫 Sistema de Soporte y PQRS (NUEVO)

- [x] **Tickets de soporte completos** - Sistema integral de PQRS
- [x] **Mensajería interna** - Comunicación bidireccional
- [x] **Estados de tickets** - Abierto, en progreso, resuelto, cerrado
- [x] **Prioridades configurables** - Clasificación por urgencia
- [x] **Asignación automática** - Distribución inteligente de casos
- [x] **Historial de conversaciones** - Seguimiento completo de interacciones
- [x] **Categorización avanzada** - Tipos de solicitud configurables
- [x] **SLA automático** - Seguimiento de tiempos de respuesta
- [x] **Dashboard de soporte** - Métricas de rendimiento
- [x] **Notificaciones automáticas** - Alertas de nuevos mensajes
- [x] **Escalamiento automático** - Proceso de escalación por tiempo
- [x] **Base de conocimiento** - Repositorio de soluciones comunes
- [x] **Reportes de satisfacción** - Encuestas post-resolución
- [ ] Chat en tiempo real
- [ ] Integración con WhatsApp Business

---

## 🏊‍♀️ Gestión de Espacios Comunes (NUEVO)

- [x] **Activos reservables** - Gestión completa de espacios y recursos
- [x] **Sistema de reservas** - Booking completo para residentes
- [x] **Disponibilidad en tiempo real** - Control de horarios y ocupación
- [x] **Estados de reserva** - Confirmada, en uso, completada, cancelada
- [x] **Políticas de uso** - Reglas configurables por espacio
- [x] **Tarifas diferenciadas** - Precios por tipo de espacio y horario
- [x] **Restricciones de usuario** - Límites por residente o tipo
- [x] **Dashboard de ocupación** - Métricas de uso de espacios
- [x] **Notificaciones de reserva** - Confirmaciones y recordatorios
- [x] **Historial de uso** - Seguimiento completo de reservas
- [x] **Integración financiera** - Cobro automático de tarifas
- [x] **Calendario integrado** - Vista cronológica de disponibilidad
- [x] **API para app móvil** - Endpoints listos para aplicación móvil
- [ ] Check-in/check-out automático
- [ ] Integración IoT para control de acceso

---

## 📧 Sistema de Comunicaciones (NUEVO)

- [x] **Plantillas de email** - Sistema completo de templates
- [x] **Personalización avanzada** - Variables dinámicas en plantillas
- [x] **Envío masivo de facturas** - Sistema de batches para facturación
- [x] **Seguimiento de entrega** - Estado de emails enviados
- [x] **Configuración SMTP** - Setup completo de servidor de correo
- [x] **Cola de envío** - Procesamiento asíncrono de emails
- [x] **Logs de email** - Auditoría completa de comunicaciones
- [x] **Segmentación de envíos** - Por torre, tipo de residente, etc.
- [x] **Programación de envíos** - Emails diferidos y recurrentes
- [x] **Métricas de apertura** - Estadísticas de engagement
- [x] **Configuración por conjunto** - Settings específicos por propiedad
- [x] **Integración con anuncios** - Envío automático de comunicados
- [x] **Backup de templates** - Versionado de plantillas
- [x] **Dashboard de comunicaciones** - Métricas centralizadas
- [ ] Integración con WhatsApp Business API
- [ ] SMS masivos

---

## 📱 App Móvil / Versión Responsive

- [x] **Interfaz completamente responsive** - Diseño adaptativo para móviles y tablets
- [x] **Componentes UI modernos** - Implementación con shadcn/ui Vue y Tailwind CSS
- [x] **SPA con Inertia.js** - Experiencia de aplicación de página única
- [x] **Layouts adaptativos** - Sistema de layouts anidados para diferentes secciones
- [x] **App móvil nativa** - Implementación completa con Nuxt 4 + Capacitor 6
- [x] **Arquitectura móvil moderna** - Vue 3, TypeScript, TailwindCSS
- [x] **Funcionalidades nativas** - Haptics, StatusBar, SplashScreen, Preferences
- [x] **Builds iOS y Android** - Proyectos nativos configurados
- [x] **Sistema de autenticación móvil** - Login con persistencia local
- [x] **Dashboard interactivo** - Vista personalizada con métricas
- [x] **Navegación bottom-tab** - UX optimizada para móviles
- [x] **Safe areas y notch support** - Compatibilidad con dispositivos modernos
- [x] **Integración con backend** - APIs REST configuradas
- [x] **Composables especializados** - useAuth, useApi, useNotifications, etc.
- [x] **Paleta de colores móvil** - Identidad visual adaptada
- [ ] Notificaciones push con FCM
- [ ] Modo offline con sincronización
- [ ] Biometría para login rápido
- [ ] Scanner QR para visitas

---

## ⚙️ Requisitos No Funcionales

- [x] **Arquitectura moderna y escalable** - Laravel 12 + Vue 3 + TypeScript
- [x] **Seguridad implementada** - Middleware de seguridad, validaciones, sanitización
- [x] **Compatible con dispositivos móviles** - Diseño responsive completo
- [x] **Validaciones tipo-seguras** - TypeScript en frontend para mayor robustez
- [x] **Testing implementado** - Tests unitarios (PHPUnit/Pest) y E2E (Playwright)
- [x] **Cumplimiento de buenas prácticas** - Código limpio, patrones de diseño
- [x] **Base de datos optimizada** - Migraciones, relaciones, índices adecuados
- [x] **Manejo de errores robusto** - Excepciones customizadas, logging de errores
- [ ] Disponibilidad 99.9% (hosting en la nube)
- [ ] Backups automáticos diarios
- [ ] Soporte para idiomas (multilenguaje)
- [ ] Escalabilidad horizontal con microservicios
- [ ] Cumplimiento Ley 1581 de protección de datos Colombia

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12** - Framework PHP moderno con arquitectura robusta
- **PHP 8.3+** - Últimas características del lenguaje
- **MySQL/SQLite** - Base de datos relacional con migraciones y seeders
- **Laravel Breeze** - Sistema de autenticación simplificado
- **Spatie Laravel Settings** - Gestión de configuraciones aplicación
- **Spatie Laravel Permission** - Sistema de roles y permisos granular
- **Laravel Excel** - Exportación de reportes Excel/PDF
- **Laravel Sanctum** - Autenticación API para app móvil
- **Laravel Telescope** - Debugging y monitoring de aplicación
- **Laravel Pail** - Monitoring de logs en tiempo real
- **DomPDF** - Generación de PDFs
- **SimpleQRCode** - Generación de códigos QR

### Frontend
- **Vue.js 3** - Framework JavaScript reactivo con Composition API
- **TypeScript** - Tipado estático para mayor robustez
- **Inertia.js** - SPA sin APIs, integración Laravel-Vue
- **shadcn/ui Vue** - Componentes UI modernos y accesibles
- **Tailwind CSS 4** - Framework CSS utilitario con óxido
- **Vite 6** - Build tool rápido con HMR
- **Chart.js** - Gráficos y visualizaciones
- **Mermaid** - Diagramas dinámicos
- **Vue I18n** - Internacionalización
- **VueUse** - Composables utilitarios
- **Lucide Icons** - Iconografía moderna

### Testing & Quality
- **PHPUnit/Pest** - Testing backend unitario y de integración
- **Playwright** - Testing E2E del frontend
- **Laravel Pint** - Formateador de código PHP
- **ESLint + Prettier** - Linting y formateo JavaScript/TypeScript
- **TypeScript ESLint** - Análisis estático para TypeScript
- **Vue TSC** - Verificación de tipos Vue

### Seguridad (OWASP Top 10 2021 Compliance)
- **Rate Limiting** - Protección contra spam/ataques (5 niveles configurables)
- **Input Sanitization** - Limpieza automática de datos de entrada
- **Security Headers** - Headers HTTP de seguridad (CSP, HSTS, etc.)
- **Audit Logging** - Registro completo de acciones de usuarios
- **File Upload Security** - Validación exhaustiva de archivos
- **Two-Factor Authentication** - TOTP con QR y backup codes
- **Session Security** - Gestión segura con validación IP/User-Agent
- **Password Policies** - Políticas de contraseñas robustas
- **CORS Security** - Configuración restrictiva de CORS
- **Exception Handling** - Manejo seguro de errores

### Mobile (App Nativa)
- **Nuxt 4** - Framework Vue.js para aplicaciones móviles
- **Capacitor 6** - Framework híbrido para iOS/Android
- **TypeScript** - Tipado estático completo
- **TailwindCSS** - Styling consistente con web
- **Pinia** - Gestión de estado reactiva
- **Capacitor Plugins** - App, StatusBar, Haptics, Preferences, etc.
