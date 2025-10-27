# 💳 Planes de Suscripción - Tavira

**Versión:** 1.0
**Última actualización:** Octubre 2025
**Modelo de negocio:** SaaS Multi-tenant

---

## 📊 Análisis de Mercado - Competencia en Colombia

### Plataformas Analizadas

#### Mercado Colombia

| Plataforma | Precio Base | Modelo de Pricing | Características |
|------------|-------------|-------------------|-----------------|
| **Daytona Intercloud** | COP $67,000 - $160,000/mes | Por unidades (50-200) | Software administrativo SaaS |
| **Conjunto Express** | COP $150,000/mes | Hasta 30 propiedades | Facturación y reportes básicos |
| **EXIMUS (EXSAPH)** | Gratis | 100% gratuito + soporte pago | Sin permanencia |
| **Kipo** | Gratis + Extras | Plan base gratis + add-ons | Sin permanencia |
| **Propiedata** | No publicado | Contacto directo | Integraciones avanzadas |
| **Copropiedad.co** | No publicado | Contacto directo | Líder en Colombia |

#### Mercado Internacional (Referencia)

| Plataforma | Precio Base (USD) | Precio COP Equivalente | Características |
|------------|-------------------|------------------------|-----------------|
| **AppFolio** | $0.80-$5.00/unidad + min $250 | ~$1,000,000 - $20,000,000 | Líder global, enterprise |
| **Buildium** | Desde $50/mes | ~$200,000+/mes | Mid-market USA |
| **Yardi Breeze** | $1-$2/unidad + min $100 | ~$400,000 - $800,000 | Enterprise residential |
| **Propertyware** | $1/unidad + min $250 | ~$1,000,000+/mes | Enterprise API |

**Tasa de cambio referencia:** 1 USD = ~4,000 COP

### 🎯 Posicionamiento de Tavira

Tavira se posiciona como una **solución premium** con:
- ✅ 19 módulos implementados vs. 8-12 de competidores
- ✅ App móvil nativa (iOS + Android)
- ✅ Arquitectura multitenancy moderna con DB aislada
- ✅ Facturación electrónica DIAN integrada
- ✅ Sistema contable completo (Decreto 2650)
- ✅ Seguridad OWASP Top 10 compliant
- ✅ Portal de proveedores con cotizaciones
- ✅ Sistema de asambleas y votaciones digitales
- ✅ Infraestructura cloud 100% (AWS/GCP)
- ✅ Backups automáticos y seguridad empresarial

**Valor diferencial:** 40-60% más funcionalidades que la competencia, con tecnología superior.

### 💰 Análisis de Costos Operativos por Tenant

| Componente | Costo Mensual (USD) | Costo COP | Descripción |
|------------|---------------------|-----------|-------------|
| **Base de Datos** | $30-50 | ~$120,000-200,000 | PostgreSQL dedicado por tenant |
| **Storage** | $5-15 | ~$20,000-60,000 | Almacenamiento archivos (5-20 GB) |
| **Bandwidth** | $10-25 | ~$40,000-100,000 | Transferencia de datos |
| **Backup** | $10-20 | ~$40,000-80,000 | Backups automáticos + DR |
| **CDN/Cache** | $5-10 | ~$20,000-40,000 | Redis cache + CDN assets |
| **Monitoring** | $5-10 | ~$20,000-40,000 | Logs, métricas, alertas |
| **Email Service** | $5-15 | ~$20,000-60,000 | SMTP transaccional (1k-3k emails) |
| **Total Infraestructura** | **$70-145** | **~$280,000-580,000** | Costo base por tenant |

**Nota:** No incluye costos de desarrollo, soporte, marketing, ni margen de ganancia.

---

## 🎯 Planes de Suscripción

### Estructura de Planes

```
┌─────────────┬──────────────────┬──────────────────┬──────────────────┐
│   Plan      │    BÁSICO        │  PROFESIONAL     │   ENTERPRISE     │
├─────────────┼──────────────────┼──────────────────┼──────────────────┤
│ Unidades    │   Hasta 50       │   51 - 150       │   151+           │
│ Precio base │ COP $320,000/mes │ COP $650,000/mes │ COP $1,280,000/mes│
│ Target      │ Edificios        │ Conjuntos        │ Macro-conjuntos  │
│             │ pequeños         │ medianos         │ y torres         │
│ Equiv. USD  │   ~$80/mes       │   ~$162/mes      │   ~$320/mes      │
└─────────────┴──────────────────┴──────────────────┴──────────────────┘
```

---

## 📦 Plan BÁSICO

### 💰 Precio

| Período | Precio Mensual | Descuento | Total Anual | Ahorro Anual |
|---------|----------------|-----------|-------------|--------------|
| **Mensual** | COP $320,000 | 0% | COP $3,840,000 | - |
| **Anual** | COP $272,000 | 15% | COP $3,264,000 | COP $576,000 |
| **Bianual** | COP $256,000 | 20% | COP $6,144,000 | COP $1,536,000 |
| **Trianual** | COP $240,000 | 25% | COP $8,640,000 | COP $2,880,000 |

**Costo por unidad (50 apts):** ~COP $6,400/unidad/mes

### 👥 Perfil del Cliente

- **Unidades:** Hasta 50 apartamentos/casas
- **Tipo:** Edificios residenciales pequeños, conjuntos cerrados compactos
- **Facturación mensual:** Hasta COP $30M
- **Administrador:** 1 administrador + 1 portero/auxiliar

### ✅ Módulos Incluidos (11 features)

#### Core Modules (Siempre Activos)
- ✅ **Gestión de Conjuntos** - Configuración básica del conjunto
- ✅ **Administración de Residentes** - CRUD completo de residentes
- ✅ **Gestión de Apartamentos** - Organización por torres/edificios
- ✅ **Dashboard Básico** - Métricas esenciales

#### Feature Flags Habilitados
```php
'basic_features' => [
    'residents_management' => true,
    'apartments_management' => true,
    'basic_invoicing' => true,
    'payment_tracking' => true,
    'basic_announcements' => true,
    'basic_correspondence' => true,
    'basic_visits' => true,
    'basic_documents' => true,
    'basic_reports' => true,
    'mobile_app_access' => true,
    'email_notifications' => true,
]
```

#### Finanzas y Facturación
- ✅ **Facturación Básica** - Creación manual de facturas
- ✅ **Seguimiento de Pagos** - Registro y estados de pago
- ✅ **Conceptos de Pago** - Hasta 10 conceptos configurables
- ✅ **Reportes Básicos** - Cartera, pagos, estados de cuenta

#### Comunicación
- ✅ **Anuncios Básicos** - Comunicados a residentes (hasta 20/mes)
- ✅ **Correspondencia** - Registro de paquetes y correspondencia (hasta 50/mes)
- ✅ **Notificaciones Email** - Envío automático de facturas

#### Visitas y Acceso
- ✅ **Registro de Visitas** - Control de acceso básico (hasta 100/mes)
- ✅ **Generación de QR** - Códigos para visitantes

#### Documentos
- ✅ **Gestión Documental Básica** - Hasta 500 MB de almacenamiento

#### App Móvil
- ✅ **Acceso App Móvil** - iOS y Android para residentes
- ✅ **Funcionalidades básicas** - Dashboard, anuncios, pagos

### ❌ Módulos NO Incluidos

- ❌ Sistema Contable Completo
- ❌ Facturación Electrónica DIAN
- ❌ Acuerdos de Pago
- ❌ Conciliación Bancaria
- ❌ Sistema de Gastos con Aprobaciones
- ❌ Portal de Proveedores
- ❌ Asambleas y Votaciones
- ❌ Sistema de Mantenimiento
- ❌ Reservas de Espacios Comunes
- ❌ Sistema de Soporte (PQRS)
- ❌ Botón de Pánico
- ❌ Reportes Avanzados
- ❌ Integraciones API

### 📞 Soporte

- 📧 Email: Respuesta en 48 horas hábiles
- 📚 Base de conocimiento online
- 🎥 Tutoriales en video
- ⏰ Horario: Lunes a Viernes 9am - 6pm

---

## 🏢 Plan PROFESIONAL

### 💰 Precio

| Período | Precio Mensual | Descuento | Total Anual | Ahorro Anual |
|---------|----------------|-----------|-------------|--------------|
| **Mensual** | COP $650,000 | 0% | COP $7,800,000 | - |
| **Anual** | COP $552,500 | 15% | COP $6,630,000 | COP $1,170,000 |
| **Bianual** | COP $520,000 | 20% | COP $12,480,000 | COP $3,120,000 |
| **Trianual** | COP $487,500 | 25% | COP $17,550,000 | COP $5,850,000 |

**Costo por unidad (100 apts promedio):** ~COP $6,500/unidad/mes

### 👥 Perfil del Cliente

- **Unidades:** 51 - 150 apartamentos/casas
- **Tipo:** Conjuntos residenciales medianos, torres múltiples
- **Facturación mensual:** COP $30M - $100M
- **Administrador:** Equipo administrativo (2-4 personas)

### ✅ Módulos Incluidos (17 features)

#### Todo lo del Plan BÁSICO +

#### Feature Flags Habilitados
```php
'professional_features' => [
    // Todos los del plan básico +
    'advanced_invoicing' => true,
    'automatic_invoice_generation' => true,
    'payment_agreements' => true,
    'late_fees_processing' => true,
    'bank_reconciliation' => true,
    'electronic_invoicing_dian' => true,
    'accounting_system' => true,
    'budgets' => true,
    'expense_management' => true,
    'supplier_management' => true,
    'maintenance_system' => true,
    'reservations_system' => true,
    'advanced_announcements' => true,
    'pqrs_system' => true,
    'advanced_reports' => true,
    'api_integrations' => true,
]
```

#### Finanzas Avanzadas
- ✅ **Facturación Automática** - Generación mensual programada
- ✅ **Acuerdos de Pago** - Planes de pago con cuotas
- ✅ **Recargos por Mora** - Cálculo automático de intereses
- ✅ **Descuentos por Pronto Pago** - Configuración de incentivos
- ✅ **Conciliación Bancaria** - Integración Jelpit y bancos
- ✅ **Conceptos Ilimitados** - Sin límite de conceptos de pago

#### Sistema Contable
- ✅ **Contabilidad Completa** - Partida doble Decreto 2650
- ✅ **Plan de Cuentas** - 60+ cuentas colombianas
- ✅ **Transacciones Automáticas** - Desde facturas/pagos
- ✅ **Presupuestos** - Presupuesto anual con ejecución
- ✅ **Reportes Contables** - Balance, P&L, Libro Mayor
- ✅ **Fondo de Reserva** - Cálculo automático 30%

#### Facturación Electrónica DIAN
- ✅ **Integración Factus** - Facturación electrónica certificada
- ✅ **Configuración DIAN** - Cumplimiento normativo
- ✅ **Envío Automático** - Facturas electrónicas por email

#### Proveedores y Gastos
- ✅ **Gestión de Proveedores** - Base de datos completa
- ✅ **Registro de Gastos** - Control de egresos
- ✅ **Categorías de Gastos** - Clasificación configurable
- ✅ **Flujo de Aprobación Básico** - 2 niveles de aprobación
- ✅ **Comprobantes Digitales** - Adjuntos de facturas

#### Sistema de Mantenimiento
- ✅ **Solicitudes de Mantenimiento** - Tickets de residentes
- ✅ **Órdenes de Trabajo** - Asignación a personal
- ✅ **Categorías** - Tipos de mantenimiento
- ✅ **Personal Interno** - Registro de técnicos
- ✅ **Calendario** - Programación de trabajos

#### Espacios Comunes
- ✅ **Reservas** - Sistema de booking completo
- ✅ **Activos Reservables** - Salón social, BBQ, piscina, etc.
- ✅ **Tarifas** - Cobro por uso de espacios
- ✅ **Disponibilidad** - Calendario en tiempo real

#### Comunicación Avanzada
- ✅ **Anuncios Ilimitados** - Sin límite mensual
- ✅ **Segmentación Avanzada** - Por torre, tipo residente, etc.
- ✅ **Confirmaciones de Lectura** - Seguimiento de lectura
- ✅ **Correspondencia Ilimitada** - Sin límite mensual

#### Soporte (PQRS)
- ✅ **Tickets de Soporte** - Sistema completo PQRS
- ✅ **Mensajería Interna** - Chat bidireccional
- ✅ **Categorización** - Tipos de solicitud
- ✅ **SLA Básico** - Tiempos de respuesta

#### App Móvil Completa
- ✅ **Todas las Funcionalidades** - Acceso completo
- ✅ **Reservas desde App** - Booking mobile
- ✅ **PQRS desde App** - Solicitudes móviles
- ✅ **Mantenimiento desde App** - Reportes móviles

### ❌ Módulos NO Incluidos

- ❌ Portal de Proveedores con Cotizaciones
- ❌ Asambleas y Votaciones Electrónicas
- ❌ Flujo de Aprobación Avanzado (Consejo)
- ❌ Botón de Pánico
- ❌ Integración WhatsApp Business
- ❌ Soporte Prioritario 24/7
- ❌ Personalización de Marca (Whitelabel)
- ❌ API REST Completa
- ❌ Reportes Personalizados
- ❌ Almacenamiento Ilimitado

### 🗄️ Límites

- 📦 **Almacenamiento:** 5 GB
- 👥 **Usuarios Administrativos:** 5
- 📧 **Emails Mensuales:** 2,000
- 🔄 **Facturación Automática:** 150 facturas/mes

### 📞 Soporte

- 📧 Email: Respuesta en 24 horas hábiles
- 💬 Chat en vivo: Lunes a Viernes 9am - 6pm
- 📚 Base de conocimiento online
- 🎥 Tutoriales en video
- 📱 WhatsApp: Horario de oficina
- 🎓 Capacitación inicial incluida (2 horas)

---

## 🏆 Plan ENTERPRISE

### 💰 Precio

| Período | Precio Mensual | Descuento | Total Anual | Ahorro Anual |
|---------|----------------|-----------|-------------|--------------|
| **Mensual** | COP $1,280,000 | 0% | COP $15,360,000 | - |
| **Anual** | COP $1,088,000 | 15% | COP $13,056,000 | COP $2,304,000 |
| **Bianual** | COP $1,024,000 | 20% | COP $24,576,000 | COP $6,144,000 |
| **Trianual** | COP $960,000 | 25% | COP $34,560,000 | COP $11,520,000 |

**Costo por unidad (200 apts promedio):** ~COP $6,400/unidad/mes

### 👥 Perfil del Cliente

- **Unidades:** 151+ apartamentos/casas
- **Tipo:** Macro-conjuntos, múltiples torres, desarrollos grandes
- **Facturación mensual:** COP $100M+
- **Administrador:** Equipo completo (5+ personas)

### ✅ Módulos Incluidos (TODO - 22 features)

#### Todo lo del Plan PROFESIONAL +

#### Feature Flags Habilitados
```php
'enterprise_features' => [
    // Todos los del plan profesional +
    'provider_portal' => true,
    'quotation_system' => true,
    'assemblies_voting' => true,
    'vote_delegation' => true,
    'advanced_approval_workflow' => true,
    'panic_button' => true,
    'whatsapp_integration' => true,
    'priority_support' => true,
    'whitelabel' => true,
    'full_api_access' => true,
    'custom_reports' => true,
    'unlimited_storage' => true,
    'multi_property_management' => true,
    'advanced_security' => true,
    'sla_guaranteed' => true,
]
```

#### Portal de Proveedores (EXCLUSIVO)
- ✅ **Auto-registro de Proveedores** - Portal público
- ✅ **Categorías de Proveedores** - Clasificación por servicios
- ✅ **Solicitudes de Cotización (RFQ)** - Sistema completo
- ✅ **Respuestas de Proveedores** - Propuestas y ofertas
- ✅ **Dashboard de Proveedores** - Panel exclusivo
- ✅ **Gestión de Servicios** - Catálogo de servicios
- ✅ **Calificación de Proveedores** - Rating system
- ✅ **Aprobación de Registros** - Workflow de validación

#### Asambleas y Votaciones (EXCLUSIVO)
- ✅ **Gestión de Asambleas** - Creación y seguimiento
- ✅ **Votaciones Electrónicas** - Sistema completo de voting
- ✅ **Delegación de Votos** - Residentes delegan votos
- ✅ **Voto por Apartamento** - Votación ponderada
- ✅ **Asistencia a Asambleas** - Registro de asistencia
- ✅ **Cálculo de Quórum** - Automático
- ✅ **Reportes de Votación** - Resultados detallados
- ✅ **Actas Digitales** - Generación automática

#### Flujo de Aprobación Avanzado
- ✅ **Aprobación Multinivel** - Hasta 5 niveles
- ✅ **Aprobación por Consejo** - Workflow especial
- ✅ **Diagramas de Flujo** - Visualización Mermaid
- ✅ **Umbrales Configurables** - Por monto
- ✅ **Alertas Automáticas** - Notificaciones de vencimiento

#### Seguridad y Emergencias
- ✅ **Botón de Pánico** - Alertas de emergencia
- ✅ **Geolocalización** - Ubicación automática
- ✅ **Rate Limiting Especial** - Anti-abuso
- ✅ **Notificaciones Inmediatas** - A seguridad/admin

#### Integraciones Avanzadas
- ✅ **WhatsApp Business** - Integración completa
- ✅ **API REST Completa** - Endpoints full access
- ✅ **Webhooks** - Eventos en tiempo real
- ✅ **Integración Wompi** - Pagos online
- ✅ **Integración MercadoPago** - (Roadmap Q1 2026)
- ✅ **Integración PSE** - (Roadmap Q1 2026)

#### Reportes y Analytics
- ✅ **Reportes Personalizados** - Constructor custom
- ✅ **Reportes Programados** - Envío automático
- ✅ **Dashboard Ejecutivo** - Métricas avanzadas
- ✅ **Exportación Múltiple** - Excel, PDF, CSV
- ✅ **Analytics Avanzado** - Tendencias y predicciones

#### Personalización
- ✅ **Whitelabel Completo** - Logo, colores, dominio
- ✅ **Dominio Personalizado** - conjunto.sudominio.com
- ✅ **SSL Incluido** - Certificado automático
- ✅ **Email Corporativo** - notificaciones@sudominio.com

#### Gestión Multi-Propiedad
- ✅ **Múltiples Conjuntos** - Un solo dashboard
- ✅ **Consolidación de Reportes** - Cross-property
- ✅ **Gestión Centralizada** - Para administradoras

### 🗄️ Límites

- 📦 **Almacenamiento:** Ilimitado
- 👥 **Usuarios Administrativos:** Ilimitados
- 📧 **Emails Mensuales:** Ilimitados
- 🔄 **Facturación Automática:** Ilimitada
- 🏢 **Propiedades:** Hasta 5 conjuntos bajo misma administración

### 📞 Soporte PREMIUM

- 🚨 **Soporte Prioritario 24/7** - Email, Chat, WhatsApp
- 📞 **Línea Directa** - Teléfono dedicado
- 👨‍💼 **Account Manager** - Gerente de cuenta asignado
- 🎓 **Capacitación Completa** - Ilimitada para equipo
- 🔧 **Onboarding Personalizado** - Setup asistido
- 📊 **Reportes Mensuales** - Análisis de uso
- ⚡ **SLA Garantizado** - 99.9% uptime
- 🛠️ **Soporte Técnico Prioritario** - Respuesta inmediata

---

## 💎 Add-ons Opcionales (Todos los Planes)

### Funcionalidades Adicionales

| Add-on | Precio Mensual | Descripción |
|--------|----------------|-------------|
| **Almacenamiento Extra** | COP $45,000 / 10 GB | Storage adicional cloud |
| **Usuarios Extra** | COP $25,000 / usuario | Admin adicionales (solo Básico/Pro) |
| **Unidades Extra** | COP $6,000 / unidad | Sobre el límite del plan |
| **Emails Extra** | COP $35,000 / 2,000 | Emails transaccionales adicionales |
| **Capacitación** | COP $180,000 / hora | Capacitación personalizada on-site |
| **Personalización** | Desde COP $800,000 | Desarrollo custom de features |
| **Consultoría** | COP $250,000 / hora | Asesoría especializada senior |
| **Soporte 24/7** | COP $350,000 / mes | Upgrade a soporte premium (Básico/Pro) |

### Servicios Profesionales

| Servicio | Precio | Descripción |
|----------|--------|-------------|
| **Migración de Datos** | Desde COP $1,200,000 | Migración completa desde otra plataforma |
| **Integración Custom** | Desde COP $2,800,000 | API personalizada con sistema externo |
| **Reporte Personalizado** | COP $650,000 | Diseño de reporte custom con BI |
| **Setup Inicial Premium** | COP $950,000 | Configuración asistida + capacitación 8h |
| **Auditoría de Seguridad** | COP $1,500,000 | Pentesting y recomendaciones |
| **Consultoría Legal DIAN** | COP $800,000 | Asesoría facturación electrónica |

---

## 🎁 Promociones de Lanzamiento (2025)

### Descuento Early Adopters

**Válido hasta Diciembre 2025:**

- 🎯 **40% OFF** primer año para primeros 10 clientes (solo planes anuales)
- 🎯 **30% OFF** primer año para siguientes 20 clientes
- 🎯 **20% OFF** primer año para siguientes 50 clientes

**Beneficios adicionales Early Adopters:**
- ✅ Setup inicial premium gratuito (valor COP $950,000)
- ✅ Capacitación de 8 horas incluida (valor COP $1,440,000)
- ✅ 6 meses de soporte premium gratis
- ✅ Almacenamiento adicional 20 GB gratuito primer año
- ✅ Personalización completa de marca incluida
- ✅ Migración de datos sin costo
- ✅ Asesoría DIAN incluida (1 sesión)

### Programa de Referidos

**Gana comisiones recomendando Tavira:**

- 💰 **20%** de comisión recurrente por 12 meses
- 💰 **Bono** de COP $500,000 por cada referido que contrate plan anual
- 💰 **Descuento** de 1 mes gratis por cada 2 referidos activos
- 💰 **Bono especial** de COP $1,000,000 por referido Enterprise

---

## 📋 Comparativa de Planes

| Característica | BÁSICO | PROFESIONAL | ENTERPRISE |
|----------------|--------|-------------|------------|
| **Unidades** | Hasta 50 | 51-150 | 151+ |
| **Precio/mes** | $320,000 | $650,000 | $1,280,000 |
| **Precio/unidad** | ~$6,400 | ~$6,500 | ~$6,400 |
| **Almacenamiento** | 2 GB | 10 GB | Ilimitado |
| **Usuarios Admin** | 3 | 8 | Ilimitados |
| **Módulos** | 11 | 17 | 22 (Todos) |
| | | | |
| **CORE** | | | |
| Gestión Residentes | ✅ | ✅ | ✅ |
| Gestión Apartamentos | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ Advanced |
| App Móvil | ✅ Básica | ✅ Completa | ✅ Completa |
| | | | |
| **FINANZAS** | | | |
| Facturación Básica | ✅ Manual | ✅ Automática | ✅ Automática |
| Acuerdos de Pago | ❌ | ✅ | ✅ |
| Recargos por Mora | ❌ | ✅ | ✅ |
| Conciliación Bancaria | ❌ | ✅ | ✅ |
| Facturación DIAN | ❌ | ✅ | ✅ |
| Pagos Online | ❌ | ✅ Wompi | ✅ Multi-gateway |
| | | | |
| **CONTABILIDAD** | | | |
| Sistema Contable | ❌ | ✅ Completo | ✅ Completo |
| Presupuestos | ❌ | ✅ | ✅ |
| Reportes Contables | ❌ | ✅ | ✅ Avanzados |
| Fondo de Reserva | ❌ | ✅ Auto | ✅ Auto |
| | | | |
| **OPERACIONES** | | | |
| Proveedores | ❌ | ✅ Básico | ✅ + Portal |
| Gastos | ❌ | ✅ + Aprobación | ✅ + Multi-nivel |
| Mantenimiento | ❌ | ✅ | ✅ |
| Reservas Espacios | ❌ | ✅ | ✅ |
| Cotizaciones (RFQ) | ❌ | ❌ | ✅ |
| | | | |
| **GOBERNANZA** | | | |
| Asambleas | ❌ | ❌ | ✅ |
| Votaciones | ❌ | ❌ | ✅ |
| Delegación Votos | ❌ | ❌ | ✅ |
| Actas Digitales | ❌ | ❌ | ✅ |
| | | | |
| **COMUNICACIÓN** | | | |
| Anuncios | ✅ 20/mes | ✅ Ilimitados | ✅ Ilimitados |
| Correspondencia | ✅ 50/mes | ✅ Ilimitada | ✅ Ilimitada |
| Visitas/QR | ✅ 100/mes | ✅ Ilimitadas | ✅ Ilimitadas |
| PQRS | ❌ | ✅ | ✅ |
| WhatsApp | ❌ | ❌ | ✅ |
| | | | |
| **SEGURIDAD** | | | |
| Botón de Pánico | ❌ | ❌ | ✅ |
| 2FA | ✅ | ✅ | ✅ |
| Auditoría | Básica | Completa | Completa |
| | | | |
| **PERSONALIZACIÓN** | | | |
| Whitelabel | ❌ | ❌ | ✅ |
| Dominio Custom | ❌ | ❌ | ✅ |
| API Access | ❌ | Limitado | ✅ Completo |
| | | | |
| **SOPORTE** | | | |
| Email | 48h | 24h | Inmediato |
| Chat | ❌ | ✅ | ✅ 24/7 |
| Teléfono | ❌ | ❌ | ✅ Dedicado |
| Capacitación | Videos | 2h incluidas | Ilimitada |
| Account Manager | ❌ | ❌ | ✅ |
| SLA Garantizado | ❌ | ❌ | 99.9% |

---

## 🔄 Política de Cambio de Plan

### Upgrade (Cambio a Plan Superior)

- ✅ **Inmediato** - Cambio efectivo al momento
- ✅ **Prorrateo** - Se cobra diferencia proporcional
- ✅ **Sin penalización** - Cambio sin costo adicional
- ✅ **Features activados** - Inmediatamente disponibles

### Downgrade (Cambio a Plan Inferior)

- ⏰ **Al final del período** - Efectivo en siguiente ciclo
- 📊 **Exportación de datos** - 30 días para descargar
- ⚠️ **Límites aplicables** - Ajustar a límites del nuevo plan
- 📧 **Notificación previa** - 15 días de anticipación

### Cancelación

- 📝 **Aviso previo** - 30 días de anticipación
- 💾 **Exportación de datos** - Formato JSON/Excel
- 🔐 **Retención de datos** - 90 días después de cancelación
- 💰 **Sin penalización** - No hay cargos por cancelación
- ⚠️ **Contratos anuales** - Ver cláusula de compromiso

---

## ⚖️ Términos y Condiciones

### Compromisos Anuales

**Beneficios del compromiso:**
- 📉 15-25% de descuento según duración
- 🔒 Precio fijo durante vigencia del contrato
- 🎁 Add-ons gratuitos (según plan)
- 🚀 Prioridad en nuevas features

**Cláusulas importantes:**
- Pago adelantado o mensual según preferencia
- Renovación automática salvo notificación 60 días antes
- Cancelación anticipada: 50% del saldo restante
- Upgrade permitido sin penalización

### Forma de Pago

**Métodos aceptados:**
- 💳 Tarjeta de crédito/débito (recurrente)
- 🏦 Transferencia bancaria
- 📄 Factura mensual (solo planes anuales)
- 💰 PSE (Pagos Seguros en Línea)

**Ciclo de facturación:**
- Mensual: Cargo automático cada mes
- Anual: Pago único con descuento
- Factura: Generada 5 días antes de vencimiento

### Garantía de Satisfacción

**30 días money-back:**
- ✅ Reembolso completo primeros 30 días
- ✅ Sin preguntas, sin complicaciones
- ✅ Exportación completa de datos
- ✅ Migración asistida a otra plataforma

---

## 🚀 Proceso de Contratación

### 1. Registro y Trial (7 días gratis)

```
[Registro] → [Verificación Email] → [Setup Inicial] → [Trial 7 días]
```

- 🎯 Acceso completo al plan PROFESIONAL
- 🎯 No se requiere tarjeta de crédito
- 🎯 Capacitación inicial incluida
- 🎯 Soporte completo durante trial

### 2. Selección de Plan

- 📊 Recomendación automática según unidades
- 💬 Asesoría personalizada disponible
- 🧮 Calculadora de ROI
- 📈 Demo personalizado

### 3. Onboarding

**Incluido en todos los planes:**
- ✅ Importación de datos existentes
- ✅ Configuración del conjunto
- ✅ Creación de usuarios
- ✅ Capacitación básica

**Enterprise incluye además:**
- ✅ Setup asistido completo
- ✅ Migración de sistema anterior
- ✅ Capacitación extendida
- ✅ Account manager dedicado

### 4. Go Live

- 🎉 Activación oficial
- 📧 Comunicación a residentes
- 📱 Distribución de app móvil
- 🎓 Capacitación continua disponible

---

## 📞 Contacto Comercial

### Ventas

- 📧 Email: ventas@tavira.com.co
- 📱 WhatsApp: +57 300 123 4567
- 📞 Teléfono: +57 (1) 123 4567
- 🌐 Web: https://tavira.com.co
- 📅 Agendar Demo: https://tavira.com.co/demo

### Horario de Atención

- 🕐 Lunes a Viernes: 8:00 AM - 6:00 PM
- 🕐 Sábados: 9:00 AM - 1:00 PM
- 📧 Email: 24/7 (respuesta siguiente día hábil)

### Oficinas

**Bogotá (Principal):**
- 📍 Calle 100 #19-54, Oficina 801
- 🏢 Edificio Business Tower
- 🌆 Bogotá, Colombia

---

## 🎯 Casos de Uso por Plan

### BÁSICO - Edificio Santa María

**Perfil:**
- 35 apartamentos
- 1 administrador + 1 auxiliar
- Facturación mensual: COP $25M
- Sin empleados de mantenimiento (outsourcing)
- Cuota administración promedio: COP $280,000/apt

**Por qué BÁSICO:**
- Gestión simple de residentes y pagos
- No requiere contabilidad completa inicialmente
- Presupuesto de software: 1.28% de facturación
- Prioridad: comunicación y facturación básica
- ROI inmediato en ahorro de tiempo

---

### PROFESIONAL - Conjunto Torres del Parque

**Perfil:**
- 120 apartamentos en 3 torres
- 6 personas en administración
- Facturación mensual: COP $95M
- Personal de mantenimiento (2 personas)
- Zonas comunes (piscina, salón, gym, canchas)
- Cuota administración promedio: COP $350,000/apt

**Por qué PROFESIONAL:**
- Requiere facturación electrónica DIAN obligatoria
- Necesita sistema contable completo Decreto 2650
- Gestión de mantenimiento activa con órdenes de trabajo
- Reservas de espacios comunes con cobro
- Múltiples proveedores requieren seguimiento
- Presupuesto de software: 0.68% de facturación
- Ahorro estimado: COP $2.4M/mes vs método tradicional

---

### ENTERPRISE - Ciudadela Residencial El Bosque

**Perfil:**
- 380 apartamentos en 8 torres
- 15 personas en administración
- Facturación mensual: COP $420M
- 20+ proveedores activos
- Asambleas trimestrales con 380 copropietarios
- Proyectos de obra en curso
- Cuota administración promedio: COP $480,000/apt

**Por qué ENTERPRISE:**
- Portal de proveedores reduce tiempo de cotización 80%
- Votaciones digitales ahorran COP $8M/asamblea en logística
- Gestión de múltiples proyectos simultáneos
- Equipo grande requiere whitelabel profesional
- Botón de pánico crítico para seguridad 24/7
- Necesita API para integración con sistema de acceso biométrico
- Presupuesto de software: 0.30% de facturación
- Ahorro estimado: COP $5M+/mes vs método tradicional
- Valor intangible en transparencia y gobernanza

---

## 📊 ROI Estimado

### Ahorro vs. Administración Tradicional (Plan Profesional)

| Concepto | Tradicional | Con Tavira | Ahorro Mensual |
|----------|-------------|------------|----------------|
| Software de contabilidad | COP $320,000 | Incluido | COP $320,000 |
| Software de facturación DIAN | COP $180,000 | Incluido | COP $180,000 |
| Envío facturas físicas/email | COP $150,000 | Incluido | COP $150,000 |
| Tiempo administrativo | 100 horas | 40 horas | COP $1,200,000* |
| Errores contables/reconciliación | COP $400,000 | COP $80,000 | COP $320,000 |
| Comunicación física/llamadas | COP $80,000 | Incluido | COP $80,000 |
| Recaudo manual/banco | 50 horas | 12 horas | COP $760,000* |
| Almacenamiento documentos | COP $50,000 | Incluido | COP $50,000 |
| App móvil desarrollo | COP $0 | Incluido | N/A |
| **TOTAL MENSUAL** | **COP $3,060,000** | **COP $650,000** | **COP $2,410,000** |

*Calculado a COP $20,000/hora administrativa

**ROI = 371% - Recuperación de inversión: <1 mes**

### Beneficios Intangibles Adicionales

- ✅ Reducción de morosidad: 15-25% (COP $1,500,000 - $2,500,000/mes)
- ✅ Mejora en satisfacción de residentes: Invaluable
- ✅ Transparencia y cumplimiento normativo: Invaluable
- ✅ Reducción de fraude/errores: COP $500,000/mes
- ✅ Profesionalización de la administración: Invaluable

---

## ❓ FAQ - Preguntas Frecuentes

### Generales

**¿Puedo cambiar de plan después?**
Sí, puedes hacer upgrade inmediatamente o downgrade al final de tu ciclo.

**¿Los precios incluyen IVA?**
No, todos los precios están antes de IVA (19%).

**¿Hay costos de implementación?**
No para planes Básico y Profesional. Enterprise incluye onboarding sin costo.

**¿Qué pasa si me paso del límite de unidades?**
Se cobra COP $500/mes por unidad adicional o puedes hacer upgrade.

### Técnicas

**¿Necesito infraestructura propia?**
No, es 100% cloud (SaaS).

**¿Funciona offline?**
La app móvil tiene modo offline básico (roadmap Q4 2025).

**¿Es seguro?**
Sí, cumplimos OWASP Top 10 y estándares de seguridad colombianos.

**¿Hacen backups?**
Sí, backups automáticos diarios incluidos en todos los planes.

### Soporte

**¿Incluye capacitación?**
Sí, todos los planes incluyen videos. Pro y Enterprise incluyen capacitación en vivo.

**¿Qué pasa si tengo un problema urgente?**
Plan Enterprise tiene soporte 24/7. Básico y Pro tienen horario de oficina.

**¿Puedo exportar mis datos?**
Sí, en cualquier momento en formatos Excel, PDF, JSON.

---

**Documento preparado por:** Equipo Comercial Tavira
**Aprobado por:** Dirección Comercial
**Fecha de vigencia:** Octubre 2025 - Diciembre 2025
**Próxima revisión:** Enero 2026

---

**Nota:** Los precios y características están sujetos a cambios. Los clientes con contratos anuales mantienen sus condiciones durante la vigencia del contrato.
