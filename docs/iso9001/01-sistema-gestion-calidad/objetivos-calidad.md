# Objetivos de Calidad

**Documento**: SGC-003-Objetivos
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Los objetivos de calidad establecen metas específicas, medibles y alineadas con nuestra [Política de Calidad](./politica-calidad.md). Estos objetivos se revisan periódicamente y se actualizan según sea necesario.

---

## Objetivos de Calidad 2025

### 1. Disponibilidad y Confiabilidad del Servicio

**Objetivo**: Mantener la plataforma Tavira disponible y funcionando de manera confiable para nuestros clientes.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Uptime (Disponibilidad)** | ≥ 99.5% | Monitoreo automatizado (UptimeRobot, Pingdom) | DevOps/Backend Lead | Mensual |
| **Tiempo de respuesta promedio** | ≤ 500ms | Application Performance Monitoring (APM) | Backend Lead | Mensual |
| **Incidentes críticos** | ≤ 2 por trimestre | Registro de incidentes | Representante SGC | Trimestral |
| **Tiempo medio de recuperación (MTTR)** | ≤ 2 horas | Registro de incidentes | DevOps Lead | Trimestral |

**Plan de Acción**:
- Implementar monitoreo 24/7 con alertas automáticas
- Configurar backups automáticos diarios con verificación de integridad
- Documentar y practicar procedimientos de disaster recovery
- Implementar auto-scaling para manejar picos de carga

---

### 2. Calidad del Código y Software

**Objetivo**: Entregar código limpio, mantenible y libre de defectos críticos.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Cobertura de tests** | ≥ 80% en código crítico | Herramientas CI (PHPUnit coverage, Playwright) | QA Lead | Semanal |
| **Bugs críticos en producción** | ≤ 1 por mes | Sistema de tickets | Product Owner | Mensual |
| **Tiempo de resolución bugs críticos** | ≤ 24 horas | Sistema de tickets | Development Lead | Mensual |
| **Tiempo de resolución bugs menores** | ≤ 1 semana | Sistema de tickets | Development Lead | Mensual |
| **Code review completado** | 100% de PRs | GitHub Pull Requests | Tech Lead | Semanal |
| **Deuda técnica** | ≤ 10% de sprints | Estimación en sprint planning | Tech Lead | Por sprint |

**Plan de Acción**:
- Todo código nuevo debe incluir tests
- Code reviews obligatorios antes de merge a main
- Usar linters y formatters automatizados (Laravel Pint, ESLint, Prettier)
- Dedicar 1 sprint por trimestre a reducción de deuda técnica
- Implementar análisis estático de código (PHPStan, TypeScript strict mode)

---

### 3. Satisfacción del Cliente

**Objetivo**: Garantizar que nuestros clientes estén satisfechos con el producto y el servicio.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Satisfacción general (NPS)** | ≥ 50 (Promoter Score) | Encuesta NPS trimestral | Customer Success | Trimestral |
| **Calificación del servicio** | ≥ 4.5/5 | Encuesta post-soporte | Support Lead | Trimestral |
| **Tasa de renovación** | ≥ 90% | CRM / Datos de subscripciones | Customer Success | Anual |
| **Tickets de soporte resueltos** | ≥ 95% en SLA | Sistema de tickets | Support Lead | Mensual |
| **Churn rate** | ≤ 5% anual | CRM / Analytics | Product Owner | Trimestral |

**Plan de Acción**:
- Enviar encuesta NPS cada 3 meses a todos los clientes activos
- Implementar encuesta automática post-resolución de tickets
- Programa de seguimiento proactivo a clientes
- Implementar chat en vivo durante horario de oficina
- Crear base de conocimiento y FAQs
- Webinars mensuales de capacitación

---

### 4. Velocidad y Eficiencia de Desarrollo

**Objetivo**: Mantener un ritmo sostenible de desarrollo y entrega de valor.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Velocity del equipo** | Mantener promedio ± 10% | Story points por sprint | Scrum Master | Por sprint |
| **Tiempo de despliegue** | ≤ 30 minutos | CI/CD pipeline | DevOps Lead | Por despliegue |
| **Frecuencia de despliegue** | ≥ 1 por semana | Git releases | Tech Lead | Mensual |
| **Lead time (idea a producción)** | ≤ 2 sprints | Jira/Linear/etc. | Product Owner | Mensual |
| **Tiempo de code review** | ≤ 4 horas | GitHub PR metrics | Tech Lead | Semanal |

**Plan de Acción**:
- Automatizar completamente el pipeline de CI/CD
- Establecer sprint duration de 2 semanas
- Daily standups de máximo 15 minutos
- Retrospectivas al final de cada sprint
- Priorización clara del backlog con Product Owner

---

### 5. Seguridad y Cumplimiento

**Objetivo**: Proteger los datos de nuestros clientes y cumplir con normativas aplicables.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Incidentes de seguridad** | 0 incidentes con exposición de datos | Registro de incidentes de seguridad | Security Lead | Mensual |
| **Vulnerabilidades críticas** | 0 sin resolver por más de 7 días | Dependabot, npm audit, Snyk | DevOps Lead | Semanal |
| **Backups verificados** | 100% exitosos | Sistema de backups | DevOps Lead | Semanal |
| **Cumplimiento GDPR/Ley 1581** | 100% | Auditoría interna | Legal/Compliance | Semestral |
| **Pentesting realizado** | Mínimo 1 vez al año | Informe externo | Security Lead | Anual |

**Plan de Acción**:
- Implementar escaneo automático de dependencias
- Configurar alertas de seguridad en GitHub
- Realizar auditorías de seguridad trimestrales
- Encriptar datos sensibles en base de datos
- Implementar autenticación de dos factores (2FA) para usuarios admin
- Documentar y seguir procedimientos de protección de datos (GDPR/Ley 1581)
- Contratar pentest externo anual

---

### 6. Formación y Competencia del Equipo

**Objetivo**: Mantener al equipo actualizado con las mejores prácticas y tecnologías.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Horas de capacitación por persona** | ≥ 40 horas anuales | Registro de capacitaciones | HR/Team Lead | Anual |
| **Certificaciones técnicas** | ≥ 1 por miembro del equipo | Registro de certificaciones | HR | Anual |
| **Knowledge sharing sessions** | ≥ 2 por mes | Calendario de eventos | Tech Lead | Mensual |
| **Documentación técnica creada/actualizada** | ≥ 4 docs por trimestre | Repositorio docs/ | Tech Writer | Trimestral |

**Plan de Acción**:
- Budget de capacitación anual por persona
- Tech talks internos quincenales
- Suscripciones a plataformas de aprendizaje (Pluralsight, Frontend Masters, Laracasts)
- Participación en conferencias (al menos 1 por año)
- Tiempo dedicado en sprint para aprendizaje (10% del tiempo)

---

### 7. Mejora Continua del SGC

**Objetivo**: Mejorar continuamente nuestro Sistema de Gestión de Calidad.

| Indicador | Meta 2025 | Medición | Responsable | Frecuencia |
|-----------|-----------|----------|-------------|------------|
| **Auditorías internas realizadas** | ≥ 2 por año | Registro de auditorías | Representante SGC | Semestral |
| **Acciones correctivas cerradas en plazo** | ≥ 90% | Registro de acciones correctivas | Representante SGC | Trimestral |
| **Revisiones por la dirección** | ≥ 2 por año | Actas de revisión | Director General | Semestral |
| **Mejoras implementadas de retrospectivas** | ≥ 80% | Registro de retrospectivas | Scrum Master | Por sprint |
| **Documentación SGC actualizada** | 100% vigente | Control de documentos | Representante SGC | Trimestral |

**Plan de Acción**:
- Programar auditorías internas cada 6 meses
- Revisión por la dirección cada 6 meses
- Retrospectivas obligatorias al final de cada sprint
- Seguimiento de acciones correctivas en reuniones de equipo
- Actualización continua de documentación del SGC

---

## Matriz de Objetivos vs Política de Calidad

Esta tabla muestra cómo cada objetivo está alineado con nuestra Política de Calidad:

| Objetivo | Calidad del Software | Disponibilidad | Seguridad | Atención al Cliente | Innovación | Cumplimiento |
|----------|---------------------|----------------|-----------|---------------------|------------|--------------|
| 1. Disponibilidad y Confiabilidad | ✅ | ✅✅ | ✅ | ✅ | | |
| 2. Calidad del Código | ✅✅ | ✅ | ✅ | ✅ | ✅ | |
| 3. Satisfacción del Cliente | ✅ | ✅ | | ✅✅ | ✅ | |
| 4. Velocidad de Desarrollo | ✅ | ✅ | | ✅ | ✅✅ | |
| 5. Seguridad y Cumplimiento | ✅ | ✅ | ✅✅ | ✅ | | ✅✅ |
| 6. Formación del Equipo | ✅ | | ✅ | ✅ | ✅ | |
| 7. Mejora Continua SGC | ✅ | ✅ | ✅ | ✅ | ✅✅ | ✅ |

✅✅ = Alineación fuerte | ✅ = Alineación moderada

---

## Responsabilidades

### Director General
- Aprobar los objetivos de calidad anualmente
- Proveer recursos necesarios para alcanzar los objetivos
- Revisar el desempeño en revisiones por la dirección

### Representante del SGC
- Coordinar el seguimiento de todos los objetivos
- Consolidar reportes mensuales/trimestrales
- Proponer ajustes a objetivos cuando sea necesario
- Reportar a la dirección sobre el cumplimiento

### Responsables de Indicadores (según tabla)
- Recopilar datos de medición según frecuencia establecida
- Reportar al Representante del SGC
- Proponer acciones correctivas cuando no se cumplan metas
- Documentar lecciones aprendidas

---

## Seguimiento y Reporte

### Dashboard de Objetivos
Se mantendrá un dashboard actualizado (puede ser en Notion, Google Sheets, o herramienta similar) con:
- Estado actual vs meta de cada indicador
- Tendencia (mejorando, estable, empeorando)
- Acciones en curso
- Próximas revisiones

**Ubicación del dashboard**: [URL/Ubicación - A COMPLETAR]

### Reportes Periódicos

**Reporte Mensual**:
- Indicadores con frecuencia mensual
- Alertas de indicadores fuera de meta
- Acciones correctivas en curso

**Reporte Trimestral**:
- Consolidado de todos los indicadores
- Análisis de tendencias
- Propuesta de ajustes si es necesario

**Reporte Anual**:
- Cumplimiento global de objetivos
- Propuesta de objetivos para el siguiente año
- Celebración de logros alcanzados

---

## Revisión de Objetivos

Los objetivos se revisarán:
- **Semestralmente** en las revisiones por la dirección
- **Anualmente** para establecer objetivos del siguiente año
- **Ad-hoc** si ocurren cambios significativos en el contexto de la organización

**Próxima revisión programada**: [Fecha +6 meses - A COMPLETAR]

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
| 1.0 | 2025-11-04 | Creación inicial de objetivos 2025 | [A COMPLETAR] |
