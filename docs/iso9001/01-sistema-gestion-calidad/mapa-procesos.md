# Mapa de Procesos del Sistema de Gestión de Calidad

**Documento**: SGC-004-Mapa-Procesos
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

El mapa de procesos es una representación visual y descriptiva de todos los procesos que conforman nuestro Sistema de Gestión de Calidad, sus interrelaciones y cómo generan valor para nuestros clientes.

---

## Mapa de Procesos Visual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         PROCESOS ESTRATÉGICOS                               │
│  ┌──────────────────┐  ┌──────────────────┐  ┌────────────────────────┐   │
│  │ Planificación    │  │ Revisión por la  │  │ Gestión de Riesgos     │   │
│  │ Estratégica      │  │ Dirección        │  │ y Oportunidades        │   │
│  └──────────────────┘  └──────────────────┘  └────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↓ Directrices
┌─────────────────────────────────────────────────────────────────────────────┐
│                         PROCESOS OPERACIONALES                              │
│                                                                             │
│  ┌─────────────┐   ┌──────────────┐   ┌─────────────┐   ┌──────────────┐ │
│  │ Análisis y  │ → │  Desarrollo  │ → │  Testing    │ → │  Despliegue  │ │
│  │ Diseño      │   │  de Software │   │  y QA       │   │  a Prod.     │ │
│  └─────────────┘   └──────────────┘   └─────────────┘   └──────────────┘ │
│         ↑                  ↓                                       ↓       │
│         │                  └───────────────────────────────────────┘       │
│         │                            Feedback                              │
│         │                                                                  │
│  ┌──────┴─────────────────────────────────────────────────────────────┐   │
│  │                  Atención al Cliente y Soporte                      │   │
│  └──────────────────────────────────────────────────────────────────┬─┘   │
│                                                                       ↓     │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↓ Valor entregado
┌─────────────────────────────────────────────────────────────────────────────┐
│                          CLIENTE (Conjuntos Residenciales)                  │
│                   Necesidades → [PROCESOS] → Satisfacción                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↑ Soporte y recursos
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PROCESOS DE SOPORTE                               │
│  ┌─────────────┐  ┌────────────┐  ┌─────────────┐  ┌────────────────────┐ │
│  │ Gestión de  │  │ Control de │  │ Gestión de  │  │ Infraestructura    │ │
│  │ Competencias│  │ Documentos │  │ Proveedores │  │ y Compras          │ │
│  └─────────────┘  └────────────┘  └─────────────┘  └────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↑ Medición y análisis
┌─────────────────────────────────────────────────────────────────────────────┐
│                    PROCESOS DE MEDICIÓN Y MEJORA                            │
│  ┌──────────────┐  ┌─────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │ Seguimiento  │  │ Auditorías  │  │ Gestión de No  │  │   Mejora     │  │
│  │ de Métricas  │  │ Internas    │  │ Conformidades  │  │   Continua   │  │
│  └──────────────┘  └─────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Clasificación de Procesos

### 1. Procesos Estratégicos
**Propósito**: Establecer la dirección y estrategia de la organización.

**Procesos incluidos**:
- **PE-01: Planificación Estratégica**: Definir visión, misión, objetivos estratégicos
- **PE-02: Revisión por la Dirección**: Evaluar desempeño del SGC y tomar decisiones estratégicas
- **PE-03: Gestión de Riesgos y Oportunidades**: Identificar, evaluar y gestionar riesgos y oportunidades

### 2. Procesos Operacionales (Core)
**Propósito**: Crear valor directamente para el cliente mediante el desarrollo y entrega de software.

**Procesos incluidos**:
- **PO-01: Análisis y Diseño de Funcionalidades**: Definir requisitos y arquitectura de nuevas features
- **PO-02: Desarrollo de Software**: Programar, code review, integrar código
- **PO-03: Testing y Aseguramiento de Calidad**: Ejecutar pruebas unitarias, integración, E2E
- **PO-04: Despliegue y Liberación**: CI/CD, deploy a producción, rollback si es necesario
- **PO-05: Atención al Cliente**: Soporte técnico, resolución de incidencias, capacitación

### 3. Procesos de Soporte
**Propósito**: Proporcionar recursos y apoyo a los procesos operacionales.

**Procesos incluidos**:
- **PS-01: Gestión de Competencias**: Capacitación, desarrollo de habilidades del equipo
- **PS-02: Control de Documentos**: Crear, revisar, aprobar y mantener documentación
- **PS-03: Control de Registros**: Almacenar, proteger y recuperar registros de calidad
- **PS-04: Gestión de Proveedores**: Evaluar, seleccionar y monitorear proveedores (hosting, APIs)
- **PS-05: Gestión de Infraestructura**: Servidores, bases de datos, redes, seguridad
- **PS-06: Compras y Adquisiciones**: Adquirir herramientas, licencias, servicios

### 4. Procesos de Medición y Mejora
**Propósito**: Monitorear desempeño, identificar oportunidades de mejora y actuar.

**Procesos incluidos**:
- **PM-01: Seguimiento y Medición**: Recopilar y analizar KPIs y métricas
- **PM-02: Auditorías Internas**: Verificar cumplimiento del SGC
- **PM-03: Gestión de No Conformidades**: Identificar, analizar y corregir no conformidades
- **PM-04: Mejora Continua**: Implementar mejoras basadas en retrospectivas y análisis

---

## Descripción Detallada de Procesos

### PE-01: Planificación Estratégica

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Definir la dirección estratégica de la empresa y los objetivos de calidad |
| **Responsable** | Director General |
| **Entradas** | Análisis de mercado, feedback de clientes, tendencias tecnológicas, resultados de auditorías |
| **Actividades** | Definir visión/misión, establecer objetivos de calidad, asignar recursos, planificar roadmap del producto |
| **Salidas** | Objetivos de calidad documentados, plan estratégico anual, roadmap del producto |
| **Métricas** | Cumplimiento de objetivos estratégicos (%) |
| **Frecuencia** | Anual con revisiones trimestrales |
| **Documento relacionado** | [Objetivos de Calidad](./objetivos-calidad.md) |

---

### PE-02: Revisión por la Dirección

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Evaluar el desempeño del SGC y asegurar su continua eficacia |
| **Responsable** | Director General |
| **Entradas** | Resultados de auditorías, métricas de calidad, acciones correctivas, feedback de clientes, cambios externos |
| **Actividades** | Revisar cumplimiento de objetivos, evaluar riesgos, identificar mejoras, tomar decisiones estratégicas |
| **Salidas** | Acta de revisión, decisiones sobre el SGC, recursos asignados, actualizaciones a objetivos |
| **Métricas** | Cumplimiento de acuerdos de revisión anterior (%) |
| **Frecuencia** | Semestral (mínimo 2 veces al año) |
| **Documento relacionado** | [Revisión por la Dirección](../06-evaluacion-desempeno/revision-direccion.md) |

---

### PE-03: Gestión de Riesgos y Oportunidades

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Identificar, evaluar y gestionar riesgos que puedan afectar la calidad y oportunidades de mejora |
| **Responsable** | Representante del SGC |
| **Entradas** | Análisis de contexto, cambios tecnológicos, incidentes pasados, feedback del equipo |
| **Actividades** | Identificar riesgos, evaluar probabilidad e impacto, definir controles, monitorear riesgos |
| **Salidas** | Matriz de riesgos actualizada, planes de mitigación, oportunidades identificadas |
| **Métricas** | Riesgos materializados vs identificados, oportunidades aprovechadas |
| **Frecuencia** | Trimestral |
| **Documento relacionado** | [Gestión de Riesgos y Oportunidades](../03-planificacion/riesgos-oportunidades.md) |

---

### PO-01: Análisis y Diseño de Funcionalidades

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Definir claramente qué se va a construir antes de desarrollar |
| **Responsable** | Product Owner / Tech Lead |
| **Entradas** | Solicitudes de clientes, roadmap del producto, análisis de mercado, bugs reportados |
| **Actividades** | Escribir user stories, definir requisitos funcionales, diseñar arquitectura, estimar esfuerzo |
| **Salidas** | User stories documentadas, diseños técnicos, tasks en backlog priorizadas |
| **Métricas** | Claridad de requisitos (% de stories sin ambigüedad), velocity predictability |
| **Frecuencia** | Continuo (sprint planning cada 2 semanas) |
| **Documento relacionado** | [Proceso de Desarrollo](../05-operacion/desarrollo-software.md) |

---

### PO-02: Desarrollo de Software

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Programar funcionalidades de calidad según los requisitos definidos |
| **Responsable** | Tech Lead / Developers |
| **Entradas** | User stories, diseños técnicos, estándares de código |
| **Actividades** | Programar (Laravel/Vue.js), escribir tests unitarios, code review, commit a repositorio (Git) |
| **Salidas** | Código funcional, testeado y revisado; pull request aprobado |
| **Métricas** | Cobertura de tests (%), bugs por 1000 líneas de código, tiempo de code review |
| **Frecuencia** | Continuo (daily) |
| **Documento relacionado** | [Proceso de Desarrollo](../05-operacion/desarrollo-software.md) |

---

### PO-03: Testing y Aseguramiento de Calidad

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Verificar que el software funciona correctamente y cumple requisitos |
| **Responsable** | QA Lead / Developers |
| **Entradas** | Código desarrollado, user stories, casos de prueba |
| **Actividades** | Ejecutar tests unitarios (PHPUnit/Pest), tests E2E (Playwright), tests manuales, reportar bugs |
| **Salidas** | Reporte de tests, bugs identificados, aprobación de QA |
| **Métricas** | Cobertura de tests (%), bugs encontrados pre-producción, tasa de regresión |
| **Frecuencia** | Continuo (con cada PR y antes de cada release) |
| **Documento relacionado** | [Testing y QA](../05-operacion/testing-qa.md) |

---

### PO-04: Despliegue y Liberación

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Llevar el código nuevo a producción de forma segura y eficiente |
| **Responsable** | DevOps Lead / Tech Lead |
| **Entradas** | Código aprobado en QA, release notes, checklist de despliegue |
| **Actividades** | Ejecutar pipeline CI/CD, deploy a staging, validar en staging, deploy a producción, smoke tests |
| **Salidas** | Nueva versión en producción, release notes publicados, rollback plan listo |
| **Métricas** | Tiempo de despliegue, tasa de éxito de deploys, frecuencia de rollbacks |
| **Frecuencia** | Semanal o según roadmap |
| **Documento relacionado** | [Despliegue y Liberación](../05-operacion/despliegue-liberacion.md) |

---

### PO-05: Atención al Cliente

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Resolver dudas, problemas e incidencias de clientes de forma rápida y efectiva |
| **Responsable** | Support Lead / Customer Success |
| **Entradas** | Tickets de clientes (email, chat, teléfono), bugs reportados, solicitudes de ayuda |
| **Actividades** | Registrar ticket, diagnosticar problema, resolver o escalar, comunicar solución, cerrar ticket |
| **Salidas** | Ticket resuelto, cliente satisfecho, documentación actualizada (si es recurrente) |
| **Métricas** | Tiempo de primera respuesta, tiempo de resolución, satisfacción del cliente (CSAT), SLA compliance |
| **Frecuencia** | Continuo (24/7 para issues críticos, horario de oficina para otros) |
| **Documento relacionado** | [Atención al Cliente](../05-operacion/atencion-cliente.md) |

---

### PS-01: Gestión de Competencias

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Asegurar que el equipo tiene las habilidades necesarias para realizar su trabajo |
| **Responsable** | HR / Team Lead |
| **Entradas** | Perfiles de puesto, evaluaciones de desempeño, gaps de competencias identificados |
| **Actividades** | Identificar necesidades de capacitación, planificar formación, ejecutar capacitaciones, evaluar efectividad |
| **Salidas** | Plan de capacitación anual, registros de capacitaciones realizadas, certificaciones obtenidas |
| **Métricas** | Horas de capacitación por persona, certificaciones obtenidas, mejora en desempeño |
| **Frecuencia** | Anual (planning) + continuo (ejecución) |
| **Documento relacionado** | [Gestión de Competencias](../04-soporte/competencias-personal.md) |

---

### PS-02: Control de Documentos

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Asegurar que los documentos del SGC están actualizados, aprobados y accesibles |
| **Responsable** | Representante del SGC |
| **Entradas** | Documentos nuevos o actualizados, solicitudes de cambio |
| **Actividades** | Crear/actualizar documento, revisar, aprobar, publicar, archivar versiones obsoletas |
| **Salidas** | Documentos controlados, listado maestro de documentos actualizado |
| **Métricas** | Documentos vigentes (%), documentos desactualizados identificados |
| **Frecuencia** | Continuo (según necesidad) |
| **Documento relacionado** | [Control de Documentos](../04-soporte/control-documentos.md) |

---

### PS-03: Control de Registros

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Mantener registros de calidad que demuestren conformidad y eficacia del SGC |
| **Responsable** | Representante del SGC |
| **Entradas** | Registros generados por procesos (auditorías, capacitaciones, incidencias, etc.) |
| **Actividades** | Almacenar registros de forma segura, proteger de pérdida, mantener trazabilidad, permitir recuperación |
| **Salidas** | Registros archivados y accesibles cuando se necesiten |
| **Métricas** | Registros completos (%), registros recuperables en auditoría |
| **Frecuencia** | Continuo |
| **Documento relacionado** | [Control de Registros](../04-soporte/control-registros.md) |

---

### PS-04: Gestión de Proveedores

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Seleccionar y monitorear proveedores críticos para asegurar calidad de sus servicios |
| **Responsable** | Tech Lead / Operations |
| **Entradas** | Necesidad de servicio/producto externo, evaluación de proveedores, SLAs |
| **Actividades** | Identificar proveedores potenciales, evaluar y seleccionar, negociar contrato, monitorear desempeño |
| **Salidas** | Contrato con proveedor, evaluación de desempeño, lista de proveedores aprobados |
| **Métricas** | Cumplimiento de SLA por proveedor, incidentes causados por proveedor |
| **Frecuencia** | Anual (evaluación) + continuo (monitoreo) |
| **Documento relacionado** | Incluido en [Control de Documentos](../04-soporte/control-documentos.md) |

---

### PM-01: Seguimiento y Medición

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Monitorear métricas clave para evaluar el desempeño del SGC |
| **Responsable** | Representante del SGC + Responsables de métricas |
| **Entradas** | Datos de sistemas (monitoring, tickets, Git, CI/CD), encuestas de satisfacción |
| **Actividades** | Recopilar datos, calcular KPIs, analizar tendencias, generar reportes, alertar desviaciones |
| **Salidas** | Dashboard de métricas, reportes mensuales/trimestrales, alertas de problemas |
| **Métricas** | Cumplimiento de objetivos de calidad (%) |
| **Frecuencia** | Mensual (reporte) + continuo (monitoreo) |
| **Documento relacionado** | [Seguimiento y Medición](../06-evaluacion-desempeno/seguimiento-medicion.md) |

---

### PM-02: Auditorías Internas

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Verificar que el SGC cumple con los requisitos de ISO 9001 y es eficaz |
| **Responsable** | Auditor Interno (designado por la dirección) |
| **Entradas** | Plan de auditoría, checklist ISO 9001, documentos del SGC, registros |
| **Actividades** | Planificar auditoría, ejecutar auditoría, identificar no conformidades, reportar hallazgos |
| **Salidas** | Informe de auditoría, no conformidades identificadas, oportunidades de mejora |
| **Métricas** | No conformidades encontradas, acciones correctivas generadas |
| **Frecuencia** | Semestral (mínimo 2 auditorías al año) |
| **Documento relacionado** | [Auditorías Internas](../06-evaluacion-desempeno/auditorias-internas.md) |

---

### PM-03: Gestión de No Conformidades

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Identificar, analizar y corregir problemas que no cumplen con los requisitos |
| **Responsable** | Responsable del proceso donde ocurrió la no conformidad |
| **Entradas** | No conformidad identificada (auditoría, cliente, interno), descripción del problema |
| **Actividades** | Registrar no conformidad, analizar causa raíz (5 whys, Ishikawa), definir acción correctiva, implementar, verificar eficacia |
| **Salidas** | Registro de no conformidad, acción correctiva implementada, verificación de eficacia |
| **Métricas** | No conformidades recurrentes, eficacia de acciones correctivas (%), tiempo de cierre |
| **Frecuencia** | Continuo (según ocurran) |
| **Documento relacionado** | [Gestión de No Conformidades](../07-mejora/no-conformidades.md) |

---

### PM-04: Mejora Continua

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Promover la innovación y mejora continua de procesos, productos y SGC |
| **Responsable** | Todo el equipo (liderado por Scrum Master / Representante SGC) |
| **Entradas** | Retrospectivas de sprint, sugerencias del equipo, benchmarking, nuevas tecnologías |
| **Actividades** | Realizar retrospectivas, identificar mejoras, priorizar, implementar, evaluar impacto |
| **Salidas** | Lista de mejoras implementadas, experimentos realizados, lecciones aprendidas |
| **Métricas** | Mejoras implementadas por trimestre, impacto medible de mejoras |
| **Frecuencia** | Continuo (retrospectivas cada sprint) |
| **Documento relacionado** | [Mejora Continua](../07-mejora/mejora-continua.md) |

---

## Interrelaciones entre Procesos

### Flujo Principal de Valor (Cliente → Cliente)

```
Cliente solicita funcionalidad/reporta bug
            ↓
[PO-01: Análisis y Diseño] → Define qué construir
            ↓
[PO-02: Desarrollo] → Programa la solución
            ↓
[PO-03: Testing] → Verifica calidad
            ↓
[PO-04: Despliegue] → Lleva a producción
            ↓
[PO-05: Atención al Cliente] → Confirma satisfacción
            ↓
Cliente recibe valor / problema resuelto
```

### Soporte Continuo a Procesos

- **PS-01 (Competencias)** → Capacita al equipo que ejecuta **PO-02, PO-03, PO-04**
- **PS-02 (Control de Documentos)** → Mantiene actualizado **todos los procesos documentados**
- **PS-04 (Gestión de Proveedores)** → Asegura servicios de calidad para **PO-04, PO-05**
- **PS-05 (Infraestructura)** → Proporciona servidores confiables para **PO-04, PO-05**

### Medición y Retroalimentación

- **PM-01 (Seguimiento)** → Monitorea métricas de **todos los procesos operacionales**
- **PM-02 (Auditorías)** → Verifica cumplimiento de **todos los procesos**
- **PM-03 (No Conformidades)** → Corrige problemas en **cualquier proceso**
- **PM-04 (Mejora Continua)** → Optimiza **todos los procesos**

### Dirección Estratégica

- **PE-01 (Planificación)** → Define objetivos para **todos los procesos**
- **PE-02 (Revisión Dirección)** → Evalúa eficacia de **todos los procesos** y toma decisiones
- **PE-03 (Gestión Riesgos)** → Identifica riesgos que afectan **todos los procesos**

---

## Indicadores de Procesos

Cada proceso tiene sus propias métricas definidas en los documentos específicos. Los indicadores clave se consolidan en el [Seguimiento y Medición](../06-evaluacion-desempeno/seguimiento-medicion.md).

---

## Revisión del Mapa de Procesos

Este mapa de procesos debe revisarse:
- Anualmente durante la revisión por la dirección
- Cuando se agreguen nuevos procesos
- Cuando se identifiquen interrelaciones no documentadas

**Próxima revisión**: [Fecha +12 meses - A COMPLETAR]

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Director General | [A COMPLETAR] | | 2025-11-04 |
| Representante del SGC | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del mapa de procesos | [A COMPLETAR] |
