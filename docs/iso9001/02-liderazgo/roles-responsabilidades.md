# Roles y Responsabilidades en el SGC

**Documento**: SGC-005-Roles-Responsabilidades
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este documento define los roles, responsabilidades y autoridades dentro del Sistema de Gestión de Calidad de [Nombre de la Empresa]. Cumple con el requisito de la cláusula 5.3 de ISO 9001:2015.

---

## Organigrama

```
┌─────────────────────────────────────┐
│      Director General / CEO         │
│  (Máxima autoridad del SGC)         │
└────────────────┬────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
┌───────▼──────────┐  ┌──▼──────────────────────┐
│ Product Owner    │  │ Representante del SGC   │
│                  │  │ (puede ser mismo rol)   │
└───────┬──────────┘  └─────────────────────────┘
        │
┌───────┴─────────────────────────────────┐
│                                         │
│    ┌──────────────┐  ┌───────────────┐ │
│    │ Tech Lead    │  │ Support Lead  │ │
│    │ / DevOps     │  │ / Customer    │ │
│    │              │  │   Success     │ │
│    └──────┬───────┘  └───────────────┘ │
│           │                             │
│    ┌──────▼───────────────┐            │
│    │  Developers (1-3)    │            │
│    │  QA / Testers        │            │
│    └──────────────────────┘            │
│                                         │
└─────────────────────────────────────────┘
```

**Nota**: En un equipo de 1-5 personas, es común que una persona tenga múltiples roles. Este documento especifica las responsabilidades de cada rol, independientemente de quién las ejecute.

---

## Roles y Responsabilidades Detallados

### 1. Director General / CEO

**Titular**: [Nombre - A COMPLETAR]

#### Responsabilidades en el SGC:

**Liderazgo y Compromiso (5.1 ISO 9001)**:
- ✅ Asumir la responsabilidad y rendir cuentas sobre la eficacia del SGC
- ✅ Asegurar que la política y los objetivos de calidad estén establecidos y sean compatibles con el contexto y la dirección estratégica
- ✅ Asegurar la integración de los requisitos del SGC en los procesos de negocio
- ✅ Promover el uso del enfoque basado en procesos y el pensamiento basado en riesgos
- ✅ Asegurar que los recursos necesarios para el SGC estén disponibles
- ✅ Comunicar la importancia de una gestión de calidad eficaz
- ✅ Asegurar que el SGC logre los resultados previstos
- ✅ Comprometer, dirigir y apoyar a las personas para contribuir a la eficacia del SGC
- ✅ Promover la mejora continua
- ✅ Apoyar otros roles pertinentes de la dirección

**Enfoque al Cliente (5.1.2 ISO 9001)**:
- ✅ Asegurar que se determinen y cumplan los requisitos del cliente
- ✅ Asegurar que se determinen y aborden los riesgos y oportunidades que puedan afectar la conformidad del producto y la satisfacción del cliente
- ✅ Asegurar el enfoque en aumentar la satisfacción del cliente

**Revisión por la Dirección (9.3 ISO 9001)**:
- ✅ Presidir y participar en las revisiones por la dirección (mínimo 2 al año)
- ✅ Tomar decisiones sobre mejoras del SGC y asignación de recursos

**Otras Responsabilidades**:
- Aprobar la política de calidad, objetivos de calidad y documentos críticos del SGC
- Designar al Representante del SGC
- Asegurar la comunicación de la política de calidad a toda la organización
- Tomar decisiones estratégicas sobre el producto y la empresa

**Autoridad**:
- Máxima autoridad en la organización
- Aprobación final de todos los documentos estratégicos del SGC
- Asignación de recursos (presupuesto, personal, herramientas)

---

### 2. Representante del SGC (Representative de la Dirección)

**Titular**: [Nombre - A COMPLETAR]

**Nota**: Este rol puede ser desempeñado por el Director General, el Tech Lead, o un miembro del equipo designado.

#### Responsabilidades en el SGC:

**Gestión del SGC**:
- ✅ Asegurar que el SGC se establece, implementa y mantiene conforme a ISO 9001:2015
- ✅ Coordinar todas las actividades relacionadas con el SGC
- ✅ Ser el punto focal para todas las cuestiones del SGC

**Reporte y Comunicación**:
- ✅ Informar a la alta dirección sobre el desempeño del SGC
- ✅ Informar sobre las oportunidades de mejora identificadas
- ✅ Asegurar que se promueva la toma de conciencia de los requisitos del cliente en toda la organización

**Control de Documentos y Registros**:
- ✅ Gestionar el control de documentos del SGC (creación, revisión, aprobación, distribución)
- ✅ Asegurar que los registros de calidad se mantienen adecuadamente
- ✅ Mantener actualizado el listado maestro de documentos

**Auditorías y Mejora**:
- ✅ Coordinar las auditorías internas del SGC
- ✅ Designar auditores internos (si aplica)
- ✅ Hacer seguimiento a acciones correctivas y preventivas
- ✅ Coordinar la revisión por la dirección

**Objetivos de Calidad**:
- ✅ Coordinar el seguimiento de los objetivos de calidad
- ✅ Consolidar reportes mensuales y trimestrales de métricas
- ✅ Alertar cuando objetivos no se están cumpliendo

**Capacitación en SGC**:
- ✅ Asegurar que todo el equipo entiende el SGC y sus roles en él
- ✅ Capacitar a nuevos miembros del equipo en el SGC

**Autoridad**:
- Aprobar documentos operacionales del SGC (procedimientos, instructivos)
- Solicitar acciones correctivas a responsables de procesos
- Acceso a recursos para mantener el SGC

---

### 3. Product Owner

**Titular**: [Nombre - A COMPLETAR]

#### Responsabilidades en el SGC:

**Requisitos del Cliente**:
- ✅ Determinar y documentar los requisitos del cliente y del producto
- ✅ Priorizar el backlog de producto basado en valor para el cliente
- ✅ Asegurar que los requisitos legales y regulatorios aplicables sean considerados (ej: Decreto 2650)

**Planificación de Producto**:
- ✅ Definir el roadmap del producto
- ✅ Participar en sprint planning y definir user stories
- ✅ Aceptar o rechazar entregables según criterios de aceptación

**Enfoque al Cliente**:
- ✅ Recopilar feedback de clientes
- ✅ Priorizar mejoras y nuevas funcionalidades
- ✅ Medir satisfacción del cliente

**Gestión de Riesgos**:
- ✅ Identificar riesgos relacionados con el producto y requisitos del cliente
- ✅ Participar en la gestión de riesgos y oportunidades

**Autoridad**:
- Priorizar el backlog de producto
- Aceptar o rechazar funcionalidades desarrolladas
- Decidir qué se incluye en cada release

---

### 4. Tech Lead / Líder Técnico

**Titular**: [Nombre - A COMPLETAR]

#### Responsabilidades en el SGC:

**Diseño y Desarrollo**:
- ✅ Liderar el diseño técnico y arquitectura del software
- ✅ Asegurar que el desarrollo cumple con estándares de calidad de código
- ✅ Realizar y coordinar code reviews
- ✅ Definir estándares de programación y mejores prácticas

**Gestión del Equipo de Desarrollo**:
- ✅ Asignar tareas a developers
- ✅ Mentorear y capacitar a developers junior
- ✅ Facilitar retrospectivas y mejora continua del equipo técnico

**Control de Versiones y CI/CD**:
- ✅ Gestionar repositorio de código (GitHub)
- ✅ Configurar y mantener pipeline de CI/CD
- ✅ Asegurar que todo código pasa por tests antes de merge

**Calidad del Código**:
- ✅ Asegurar cobertura de tests ≥ 80%
- ✅ Monitorear métricas de calidad de código (bugs, deuda técnica)
- ✅ Implementar y mantener herramientas de linting y formateo automático

**Gestión de Riesgos Técnicos**:
- ✅ Identificar riesgos técnicos (deuda técnica, dependencias obsoletas, vulnerabilidades)
- ✅ Proponer soluciones técnicas a problemas

**Autoridad**:
- Aprobar o rechazar pull requests
- Definir estándares técnicos del equipo
- Decidir sobre arquitectura y stack tecnológico

---

### 5. DevOps Lead / Responsable de Infraestructura

**Titular**: [Nombre - A COMPLETAR] (puede ser el mismo que Tech Lead)

#### Responsabilidades en el SGC:

**Infraestructura y Disponibilidad**:
- ✅ Asegurar la disponibilidad del servicio ≥ 99.5%
- ✅ Gestionar servidores, bases de datos, redes
- ✅ Configurar monitoreo y alertas (uptime, performance, errores)

**Despliegue y Liberación**:
- ✅ Automatizar procesos de despliegue (CI/CD)
- ✅ Ejecutar despliegues a producción
- ✅ Gestionar rollbacks en caso de problemas

**Seguridad**:
- ✅ Implementar medidas de seguridad en infraestructura
- ✅ Gestionar backups automáticos y verificar integridad
- ✅ Monitorear vulnerabilidades de seguridad
- ✅ Responder a incidentes de seguridad

**Gestión de Proveedores**:
- ✅ Evaluar y seleccionar proveedores de hosting, CDN, APIs
- ✅ Monitorear cumplimiento de SLAs de proveedores

**Respuesta a Incidentes**:
- ✅ Ser on-call para incidentes críticos
- ✅ Diagnosticar y resolver incidentes de infraestructura
- ✅ Documentar post-mortems de incidentes

**Autoridad**:
- Aprobar cambios en infraestructura de producción
- Decidir sobre proveedores de servicios técnicos
- Ejecutar rollbacks si es necesario

---

### 6. QA Lead / Responsable de Calidad

**Titular**: [Nombre - A COMPLETAR] (puede ser compartido por Tech Lead o Developer)

#### Responsabilidades en el SGC:

**Testing y Aseguramiento de Calidad**:
- ✅ Definir estrategia de testing (unitarios, integración, E2E, manuales)
- ✅ Escribir y mantener tests automatizados (Playwright, PHPUnit/Pest)
- ✅ Ejecutar tests manuales cuando sea necesario
- ✅ Reportar bugs identificados

**Validación Pre-Producción**:
- ✅ Validar en staging antes de despliegue a producción
- ✅ Aprobar o rechazar releases según calidad
- ✅ Realizar smoke tests post-despliegue

**Métricas de Calidad**:
- ✅ Monitorear cobertura de tests
- ✅ Rastrear bugs encontrados pre y post-producción
- ✅ Reportar tasa de regresión de bugs

**Mejora de Procesos de QA**:
- ✅ Proponer mejoras en procesos de testing
- ✅ Identificar áreas con bajo coverage o alto riesgo

**Autoridad**:
- Aprobar o rechazar releases basándose en calidad
- Bloquear deploys si hay bugs críticos sin resolver

---

### 7. Support Lead / Responsable de Atención al Cliente

**Titular**: [Nombre - A COMPLETAR]

#### Responsabilidades en el SGC:

**Atención al Cliente**:
- ✅ Responder a tickets de soporte (email, chat, teléfono)
- ✅ Resolver o escalar incidencias según sea necesario
- ✅ Asegurar cumplimiento de SLAs de soporte

**Gestión de Tickets**:
- ✅ Registrar y categorizar tickets en sistema de gestión
- ✅ Priorizar tickets según severidad
- ✅ Dar seguimiento hasta cierre

**Comunicación con Clientes**:
- ✅ Comunicar status de incidencias a clientes
- ✅ Notificar sobre mantenimientos programados
- ✅ Enviar comunicados sobre nuevas funcionalidades

**Satisfacción del Cliente**:
- ✅ Medir satisfacción del cliente (encuestas NPS, CSAT)
- ✅ Recopilar feedback para mejoras del producto
- ✅ Identificar problemas recurrentes y reportar al equipo técnico

**Capacitación de Usuarios**:
- ✅ Crear y mantener documentación de usuario
- ✅ Realizar capacitaciones para clientes nuevos
- ✅ Crear videos tutoriales y FAQs

**Autoridad**:
- Priorizar tickets de soporte
- Escalar issues críticos directamente a Tech Lead o DevOps
- Solicitar cambios en documentación o producto basados en feedback de clientes

---

### 8. Developers / Desarrolladores

**Titulares**: [Nombres - A COMPLETAR]

#### Responsabilidades en el SGC:

**Desarrollo de Software**:
- ✅ Programar funcionalidades según user stories y diseños técnicos
- ✅ Escribir tests unitarios para todo código nuevo
- ✅ Seguir estándares de código establecidos por Tech Lead
- ✅ Documentar código cuando sea necesario

**Code Review**:
- ✅ Participar en code reviews de otros developers
- ✅ Responder a comentarios en sus propios PRs

**Testing**:
- ✅ Ejecutar tests localmente antes de push
- ✅ Asegurar que CI pasa antes de solicitar merge

**Mejora Continua**:
- ✅ Participar en retrospectivas y proponer mejoras
- ✅ Identificar y reportar deuda técnica
- ✅ Aprender nuevas tecnologías y mejores prácticas

**Soporte Técnico (rotativo)**:
- ✅ Participar en rotación de soporte de nivel 2 (técnico)
- ✅ Resolver bugs reportados por clientes

**Autoridad**:
- Tomar decisiones de implementación dentro de user stories asignadas
- Sugerir mejoras técnicas durante code review

---

### 9. Auditor Interno (cuando aplique)

**Titular**: [Nombre - A COMPLETAR] (puede ser externo o interno no involucrado directamente en el área auditada)

#### Responsabilidades en el SGC:

**Auditorías Internas**:
- ✅ Planificar y ejecutar auditorías internas del SGC (mínimo 2 al año)
- ✅ Verificar cumplimiento de requisitos de ISO 9001:2015
- ✅ Identificar no conformidades y oportunidades de mejora
- ✅ Generar informe de auditoría

**Independencia**:
- ✅ Mantener objetividad e imparcialidad
- ✅ No auditar su propio trabajo

**Nota**: En equipos pequeños (1-5 personas), el auditor puede ser un miembro del equipo que audita procesos en los que no está directamente involucrado, o se puede contratar un auditor externo.

**Autoridad**:
- Acceso a todos los documentos y registros del SGC
- Entrevistar a cualquier miembro del equipo
- Reportar hallazgos directamente a la dirección

---

## Matriz de Responsabilidades (RACI)

**R**esponsable | **A**probador | **C**onsultado | **I**nformado

| Actividad / Proceso | Director | Rep. SGC | Product Owner | Tech Lead | DevOps | QA | Support | Developers |
|---------------------|----------|----------|---------------|-----------|--------|----|---------|-----------|
| **Establecer política de calidad** | A/R | C | C | C | I | I | I | I |
| **Establecer objetivos de calidad** | A | R | C | C | C | C | C | I |
| **Revisión por la dirección** | A/R | R | C | C | C | C | C | I |
| **Control de documentos** | A (críticos) | R | C | C | I | I | I | I |
| **Auditorías internas** | A | R (coord.) | C | C | C | C | C | C |
| **Gestión de riesgos** | A | R | C | C | C | C | C | I |
| **Planificación de producto** | A | I | R | C | I | I | I | C |
| **Diseño y desarrollo** | I | I | A | R | C | C | I | R (ejecución) |
| **Code review** | I | I | I | A | C | C | I | R |
| **Testing y QA** | I | I | A | C | I | R | I | C |
| **Despliegue a producción** | I | I | A | C | R | C | I | I |
| **Atención al cliente** | I | I | C | I | I | I | R | C (nivel 2) |
| **Resolución de incidentes críticos** | I | I | I | C | R | I | R | C |
| **Gestión de no conformidades** | A (si es crítica) | R (seguimiento) | C | R (técnicas) | R (infra) | C | R (clientes) | C |
| **Mejora continua** | A (recursos) | R | C | C | C | C | C | R (proponer) |
| **Capacitación del equipo** | A (presupuesto) | R (coord.) | C | R | C | C | C | R (participar) |

---

## Comunicación de Roles y Responsabilidades

**Esta información debe ser comunicada mediante**:
1. **Inducción de nuevos miembros**: Revisión de este documento en onboarding
2. **Descripción de puestos**: Cada puesto tiene descripción formal que incluye responsabilidades en SGC
3. **Reuniones de equipo**: Clarificar roles cuando haya confusión
4. **Disponibilidad en repositorio**: Accesible en `docs/iso9001/` para consulta permanente

---

## Sustitución y Delegación

En caso de ausencia temporal de un rol crítico:

| Rol Ausente | Sustituto Principal | Sustituto Secundario |
|-------------|---------------------|----------------------|
| Director General | [A COMPLETAR] | [A COMPLETAR] |
| Representante SGC | [A COMPLETAR] | [A COMPLETAR] |
| Tech Lead | [A COMPLETAR] | [A COMPLETAR] |
| DevOps Lead | [A COMPLETAR] | [A COMPLETAR] |

---

## Revisión de Roles y Responsabilidades

Este documento debe revisarse:
- Anualmente
- Cuando haya cambios en el organigrama
- Cuando se agreguen nuevos roles

**Próxima revisión**: [Fecha +12 meses - A COMPLETAR]

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Director General | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
