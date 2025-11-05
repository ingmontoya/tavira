# Alcance del Sistema de Gestión de Calidad

**Documento**: SGC-001-Alcance
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## 1. Organización

**Nombre**: [Nombre de la Empresa - A COMPLETAR]
**NIT/RUT**: [A COMPLETAR]
**Dirección**: [A COMPLETAR]
**Sitio web**: [A COMPLETAR]

**Producto/Servicio principal**: Tavira - Plataforma SaaS para gestión integral de conjuntos residenciales en Colombia.

---

## 2. Alcance del Sistema de Gestión de Calidad

### 2.1 Descripción del Alcance

El Sistema de Gestión de Calidad (SGC) de [Nombre de la Empresa] aplica a:

> **"Desarrollo, mantenimiento, despliegue y soporte de software como servicio (SaaS) para la gestión integral de conjuntos residenciales, incluyendo módulos de contabilidad, gestión de residentes, apartamentos, comunicaciones y conexión con proveedores."**

### 2.2 Procesos Incluidos

El SGC abarca los siguientes procesos:

#### Procesos Estratégicos:
- Revisión por la dirección
- Planificación estratégica y objetivos de calidad
- Gestión de riesgos y oportunidades

#### Procesos Operacionales:
1. **Desarrollo de Software**
   - Análisis y diseño de funcionalidades
   - Programación (Backend Laravel + Frontend Vue.js)
   - Control de versiones (Git/GitHub)
   - Code review y calidad de código

2. **Testing y Aseguramiento de Calidad**
   - Pruebas unitarias (PHPUnit/Pest)
   - Pruebas de integración
   - Pruebas E2E (Playwright)
   - Pruebas manuales de aceptación

3. **Despliegue y Operaciones**
   - Integración continua (CI/CD)
   - Despliegue a producción
   - Monitoreo y observabilidad
   - Gestión de incidentes

4. **Atención al Cliente**
   - Soporte técnico
   - Gestión de tickets e incidencias
   - Capacitación a usuarios
   - Gestión de solicitudes de mejora

5. **Gestión de Infraestructura**
   - Servidores y bases de datos
   - Seguridad y backups
   - Escalabilidad y rendimiento

#### Procesos de Soporte:
- Gestión de recursos humanos y competencias
- Control de documentos y registros
- Comunicación interna
- Gestión de proveedores (hosting, servicios terceros)
- Compras de software y herramientas

#### Procesos de Medición y Mejora:
- Auditorías internas
- Seguimiento de métricas y KPIs
- Gestión de no conformidades
- Acciones correctivas y preventivas
- Mejora continua

---

## 3. Límites y Aplicabilidad

### 3.1 Límites del SGC

**El SGC incluye:**
- Todas las actividades relacionadas con el desarrollo, despliegue, operación y soporte de la plataforma Tavira
- Todas las instalaciones y ubicaciones donde opera el equipo (oficinas físicas y trabajo remoto)
- Todo el personal involucrado en el desarrollo y soporte del producto

**El SGC NO incluye:**
- Actividades comerciales y marketing (fuera del alcance técnico)
- Gestión financiera y contable de la empresa (excepto la funcionalidad del producto)
- Procesos de manufactura o producción física (no aplicable a software)

### 3.2 Exclusiones Permitidas

Según la cláusula 8 de ISO 9001:2015, se declaran las siguientes exclusiones:

| Cláusula ISO | Requisito | ¿Aplica? | Justificación |
|--------------|-----------|----------|---------------|
| 8.3 | Diseño y desarrollo de productos/servicios | ✅ Aplica | El desarrollo de software es nuestro proceso core |
| 8.5.2 | Identificación y trazabilidad | ✅ Aplica | Control de versiones (Git) y trazabilidad de features/bugs |
| 8.5.3 | Propiedad del cliente | ✅ Aplica | Datos de los clientes en bases de datos multitenancy |
| 8.5.5 | Actividades posteriores a la entrega | ✅ Aplica | Soporte técnico y mantenimiento |

**No se solicitan exclusiones de requisitos de ISO 9001:2015.**

---

## 4. Productos y Servicios Cubiertos

### 4.1 Producto Principal: Plataforma Tavira

**Descripción**: Sistema web multitenant para gestión de conjuntos residenciales en Colombia.

**Módulos incluidos en el alcance:**
1. **Gestión de Residentes y Apartamentos**
   - CRUD de residentes
   - Asignación de apartamentos
   - Tipos de apartamentos
   - Configuración del conjunto

2. **Contabilidad y Finanzas**
   - Plan de cuentas contable (Decreto 2650)
   - Contabilidad de doble partida
   - Gestión de facturas y pagos
   - Presupuestos y ejecución presupuestal
   - Reportes financieros

3. **Comunicaciones** (en desarrollo)
   - Anuncios y notificaciones
   - Correspondencia
   - Sistema de mensajería

4. **Seguridad y Control de Acceso** (planificado)
   - Control de visitantes
   - Gestión de accesos
   - Registro de eventos

5. **Conexión con Proveedores**
   - Directorio de proveedores
   - Solicitud de servicios
   - Evaluación de proveedores

### 4.2 Servicios Cubiertos

1. **Servicio SaaS**
   - Hosting y disponibilidad 24/7
   - Actualizaciones automáticas
   - Backups automáticos diarios
   - Seguridad y encriptación de datos

2. **Soporte Técnico**
   - Soporte por email
   - Chat en vivo (horario de oficina)
   - Resolución de incidencias
   - SLA definido según plan

3. **Capacitación**
   - Documentación en línea
   - Videos tutoriales
   - Capacitación inicial para nuevos clientes
   - Webinars periódicos

4. **Consultoría**
   - Asesoría en configuración inicial
   - Mejores prácticas de gestión
   - Personalización de reportes

---

## 5. Partes Interesadas

### 5.1 Identificación de Partes Interesadas

| Parte Interesada | Necesidades y Expectativas | Requisitos para el SGC |
|------------------|----------------------------|------------------------|
| **Clientes (Conjuntos Residenciales)** | Software confiable, disponible, seguro, fácil de usar | Alta disponibilidad (>99%), datos seguros, soporte rápido |
| **Usuarios Finales** | Interfaz intuitiva, rápida, accesible desde móviles | Usabilidad, rendimiento, compatibilidad |
| **Administradores de Conjuntos** | Información financiera precisa, reportes confiables | Exactitud contable, trazabilidad, auditoría |
| **Propietarios/Residentes** | Transparencia en información financiera | Acceso a información actualizada y precisa |
| **Equipo de Desarrollo** | Procesos claros, herramientas adecuadas, ambiente colaborativo | Documentación clara, estándares de código, comunicación efectiva |
| **Proveedores (Hosting, APIs)** | Integración estable, cumplimiento de SLAs | Gestión de proveedores, evaluación periódica |
| **Entes Reguladores** | Cumplimiento normativo contable colombiano | Implementación correcta Decreto 2650, ley de protección de datos |
| **Inversionistas/Dirección** | Rentabilidad, crecimiento, sostenibilidad | Eficiencia operativa, satisfacción del cliente, mejora continua |

---

## 6. Contexto de la Organización

### 6.1 Factores Externos

**Oportunidades:**
- Creciente digitalización en gestión de propiedades en Colombia
- Necesidad de transparencia financiera en conjuntos residenciales
- Tendencia a SaaS en el mercado empresarial
- Expansión potencial a otros países latinoamericanos

**Amenazas:**
- Competencia de soluciones establecidas
- Cambios regulatorios contables o fiscales
- Riesgos de ciberseguridad
- Dependencia de proveedores de infraestructura

### 6.2 Factores Internos

**Fortalezas:**
- Equipo técnico capacitado (1-5 personas)
- Stack tecnológico moderno (Laravel 12, Vue 3)
- Arquitectura multitenant escalable
- Conocimiento del mercado colombiano

**Debilidades:**
- Equipo pequeño con recursos limitados
- Marca nueva en proceso de posicionamiento
- Documentación en desarrollo

---

## 7. Referencias Normativas

Este SGC cumple con:
- **ISO 9001:2015** - Sistemas de gestión de la calidad. Requisitos
- **Decreto 2650 de 1993** - Plan Único de Cuentas para Colombia (implementado en el producto)
- **Ley 1581 de 2012** - Protección de Datos Personales en Colombia
- **Mejores prácticas de desarrollo de software**: ISO/IEC 25010 (Calidad de software)

---

## 8. Revisión del Alcance

Este documento debe revisarse:
- Anualmente como mínimo
- Cuando haya cambios significativos en la organización
- Cuando se agreguen nuevos productos o servicios
- Cuando se identifiquen nuevos requisitos

**Próxima revisión programada**: [Fecha +12 meses - A COMPLETAR]

---

## 9. Aprobaciones

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Director General | [A COMPLETAR] | | |
| Representante del SGC | [A COMPLETAR] | | |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
