# 🏠 Habitta - Sistema Integral de Gestión para Propiedad Horizontal

**Habitta** es una plataforma digital todo-en-uno diseñada para la gestión moderna de conjuntos residenciales y edificios bajo el régimen de propiedad horizontal. Su arquitectura multitenant permite que múltiples conjuntos utilicen el sistema de forma aislada, segura y personalizable, facilitando a administradores, consejos y residentes la operación eficiente, transparente y automatizada de la comunidad.

La plataforma incluye herramientas para la administración de residentes, finanzas, correspondencia, visitas, proveedores, documentos oficiales y mucho más. Está diseñada para ser usada tanto desde un panel web como desde dispositivos móviles.

A continuación, se detallan los requerimientos funcionales y no funcionales por módulos:

---

## 🏢 Gestión de Conjuntos (Multitenancy)

- [ ] Crear múltiples conjuntos residenciales (tenants)
- [ ] Dominio/subdominio único por conjunto (`conjunto.habitta.app`)
- [ ] Personalización de logo, colores y datos institucionales por conjunto
- [ ] Separación de datos por conjunto (aislamiento total)
- [ ] Soporte para múltiples torres/edificios dentro de cada conjunto
- [ ] Soporte para múltiples administradores por conjunto
- [ ] Control central (Súper Admin) para monitoreo y soporte multitenant

---

## 🧑‍💼 Administración de Residentes

- [ ] Registro de propietarios y residentes
- [ ] Asignación por apartamento, torre y conjunto
- [ ] Historial de ocupantes por unidad
- [ ] Estado activo/inactivo del residente
- [ ] Adjuntar documentos por unidad o residente
- [ ] Notificaciones automáticas (correo, WhatsApp opcional)

---

## 💳 Finanzas y Cuotas

- [ ] Creación automática de facturas mensuales según unidad (por metraje o tarifa fija)
- [ ] Aplicación de pronto pago (ej: 5 primeros días)
- [ ] Cálculo automático de intereses por mora
- [ ] Registro de pagos y anticipos
- [ ] Generación de extracto o estado de cuenta
- [ ] Exportar informes financieros por periodo
- [ ] Reportes de cartera vencida
- [ ] Integración con pasarelas de pago (MercadoPago, Wompi, etc.)

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

- [ ] Roles predefinidos: Super Admin, Admin, Recepción, Residente, Revisor fiscal
- [ ] Sistema de permisos por módulo
- [ ] Historial de accesos
- [ ] 2FA opcional para admins

---

## 🧠 Reportes e Inteligencia

- [ ] Reporte de facturación mensual vs recaudado
- [ ] Porcentaje de mora por periodo
- [ ] Ranking de apartamentos morosos
- [ ] Estadísticas de visitas y correspondencia
- [ ] Exportar todo a Excel/PDF

---

## 📱 App Móvil / Versión Responsive

- [ ] Ver saldo y pagar desde el celular
- [ ] Ver QR de visitas
- [ ] Consultar comunicados, documentos y correspondencia
- [ ] Notificaciones push

---

## ⚙️ Requisitos No Funcionales

- [ ] Multitenancy con aislamiento seguro por conjunto
- [ ] Disponibilidad 99.9% (hosting en la nube)
- [ ] Backups automáticos diarios
- [ ] Soporte para idiomas (multilenguaje opcional)
- [ ] Escalable horizontalmente (microservicios o arquitectura modular)
- [ ] Compatible con dispositivos móviles
- [ ] Cumplimiento de Ley 1581 de protección de datos en Colombia
- [ ] Soporte técnico con SLA definido

## Tech stack
- Laravel 12 
- Vue Js composition API
- Intertia
