# 🏠 Habitta - Sistema Integral de Gestión para Propiedad Horizontal

**Habitta** es una plataforma digital todo-en-uno diseñada para la gestión moderna de conjuntos residenciales y edificios bajo el régimen de propiedad horizontal. El sistema está optimizado para la gestión eficiente de un conjunto residencial, facilitando a administradores, consejos y residentes la operación transparente y automatizada de la comunidad.

La plataforma incluye herramientas para la administración de residentes, finanzas completas con facturación automática, seguimiento de pagos, acuerdos de pago, gestión de apartamentos y mucho más. Está diseñada para ser usada tanto desde un panel web como desde dispositivos móviles.

A continuación, se detallan las funcionalidades implementadas y por implementar:

---

## 🏢 Gestión de Conjuntos (Configuración Única)

- [x] **Configuración de conjunto único** - Sistema optimizado para un conjunto residencial
- [x] **Personalización completa** - Logo, colores y datos institucionales configurables
- [x] **Soporte para múltiples torres/edificios** - Gestión de torres A, B, C con pisos y apartamentos
- [x] **Configuración flexible de apartamentos** - Tipos de apartamento configurables (Tipo A, B, C, Penthouse)
- [x] **Dashboard integral** - Estadísticas completas con datos reales y mock data
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
- [ ] Registro manual de pagos
- [ ] Generación de extracto o estado de cuenta
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

- [ ] Registro de paquetes por portería/recepción
- [ ] Notificación automática al residente al llegar un paquete
- [ ] Marcar como entregado con firma digital o código de seguridad
- [ ] Historial de correspondencia por apartamento

---

## 🚗 Registro de Visitas

- [ ] Residentes generan código QR desde app/web
- [ ] Escaneo del QR por parte de portería
- [ ] Validación automática y registro de ingreso
- [ ] Registro de salida manual o automática
- [ ] Listado de visitas por unidad y rango de fechas
- [ ] Modo sin QR: registro manual en portería

---

## 💬 Comunicados y Anuncios

- [ ] Creación de comunicados por la administración
- [ ] Envío segmentado (a todos, a torre específica, a unidad)
- [ ] Notificación por app/web/correo
- [ ] Historial de comunicados leídos / no leídos
- [ ] Confirmación de lectura opcional

---

## 🧾 Documentos y Actas

- [ ] Subida y clasificación de documentos oficiales (actas, manuales, reglamentos)
- [ ] Permisos por rol: lectura / descarga / edición
- [ ] Versionado de documentos
- [ ] Categorías personalizables

---

## 👷 Proveedores y Gastos

- [ ] Registro de proveedores por conjunto
- [ ] Registro de facturas y egresos
- [ ] Asociar gasto a rubros o presupuestos
- [ ] Subida de comprobantes escaneados
- [ ] Informe de flujo de caja y balance por periodo

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
- [ ] Recordatorios automáticos de vencimiento de pagos
- [ ] Informes programados por email
- [ ] Backup automático de datos

---

## 📱 App Móvil / Versión Responsive

- [x] **Interfaz completamente responsive** - Diseño adaptativo para móviles y tablets
- [x] **Componentes UI modernos** - Implementación con shadcn/ui Vue y Tailwind CSS
- [x] **SPA con Inertia.js** - Experiencia de aplicación de página única
- [x] **Layouts adaptativos** - Sistema de layouts anidados para diferentes secciones
- [ ] Ver saldo y pagar desde el celular
- [ ] Ver QR de visitas
- [ ] Consultar comunicados, documentos y correspondencia
- [ ] Notificaciones push nativas

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
- **Laravel Excel** - Exportación de reportes Excel/PDF
- **Telescope** - Debugging y monitoring de aplicación

### Frontend
- **Vue.js 3** - Framework JavaScript reactivo con Composition API
- **TypeScript** - Tipado estático para mayor robustez
- **Inertia.js** - SPA sin APIs, integración Laravel-Vue
- **shadcn/ui Vue** - Componentes UI modernos y accesibles
- **Tailwind CSS** - Framework CSS utilitario
- **Vite** - Build tool rápido con HMR

### Testing & Quality
- **PHPUnit/Pest** - Testing backend unitario y de integración
- **Playwright** - Testing E2E del frontend
- **Laravel Pint** - Formateador de código PHP
- **ESLint + Prettier** - Linting y formateo JavaScript/TypeScript

### Seguridad
- **Rate Limiting** - Protección contra spam/ataques
- **Input Sanitization** - Limpieza automática de datos de entrada
- **Security Headers** - Headers HTTP de seguridad
- **Audit Logging** - Registro de acciones de usuarios
- **File Upload Security** - Validación segura de archivos
- **Two-Factor Authentication** - Autenticación de dos factores
