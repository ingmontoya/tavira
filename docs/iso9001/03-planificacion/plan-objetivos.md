# Planificación para el Logro de Objetivos de Calidad

**Documento**: SGC-008-Planificacion-Objetivos
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este documento establece la planificación para alcanzar los [Objetivos de Calidad](../01-sistema-gestion-calidad/objetivos-calidad.md) definidos para 2025.

Cumple con el requisito de la cláusula 6.2 de ISO 9001:2015.

---

## Requisitos de ISO 9001:2015 (Cláusula 6.2)

Cuando se planifican los objetivos de calidad, la organización debe determinar:

✅ **Qué se va a hacer**: Actividades específicas para lograr cada objetivo
✅ **Qué recursos se requerirán**: Personas, herramientas, presupuesto
✅ **Quién será responsable**: Rol o persona específica
✅ **Cuándo se finalizará**: Plazos y fechas límite
✅ **Cómo se evaluarán los resultados**: Métricas e indicadores de éxito

---

## Planificación de Objetivos 2025

### Objetivo 1: Disponibilidad y Confiabilidad del Servicio

**Meta principal**: Uptime ≥ 99.5% mensual

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Implementar monitoreo 24/7** | - UptimeRobot / Pingdom<br>- Configuración de alertas<br>- $50/mes | DevOps Lead | Q1 2025 (Enero) | Dashboard operativo con alertas funcionando |
| **Configurar backups automatizados** | - Scripts de backup<br>- Almacenamiento redundante<br>- $100/mes | DevOps Lead | Q1 2025 (Febrero) | Backups diarios exitosos + test de restore mensual |
| **Documentar disaster recovery** | - 8 horas de trabajo<br>- Template de runbook | DevOps Lead | Q1 2025 (Marzo) | Documento completo + drill ejecutado |
| **Implementar auto-scaling** | - Configuración de infraestructura<br>- 16 horas de trabajo | DevOps Lead | Q2 2025 | Escalado automático en picos de carga validado |
| **Monitoreo mensual de uptime** | - Dashboard automatizado<br>- 2 horas/mes | DevOps Lead | Continuo | Reporte mensual generado |

**Presupuesto total**: $1,800 anual ($150/mes) + 40 horas de desarrollo

---

### Objetivo 2: Calidad del Código y Software

**Meta principal**: Cobertura de tests ≥ 80% en código crítico

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Establecer estándares de code review** | - Documentación de estándares<br>- 4 horas de trabajo | Tech Lead | Q1 2025 (Enero) | Documento publicado + aplicado en todos los PRs |
| **Configurar CI con coverage reports** | - Configuración de GitHub Actions<br>- 8 horas de trabajo | Tech Lead | Q1 2025 (Enero) | CI ejecutando tests + badge de coverage en README |
| **Escribir tests para módulos críticos** | - 80 horas de desarrollo (20 horas/developer) | Developers | Q1-Q2 2025 | Coverage crítico alcanza 80% |
| **Implementar análisis estático** | - PHPStan configurado<br>- TypeScript strict mode<br>- 8 horas | Tech Lead | Q1 2025 (Febrero) | PHPStan y TS en CI sin errores |
| **Sprint dedicado a deuda técnica** | - 1 sprint completo por trimestre<br>- Priorización de backlog | Tech Lead | Trimestral | Deuda técnica reducida 20% por trimestre |

**Presupuesto total**: 100 horas de desarrollo

---

### Objetivo 3: Satisfacción del Cliente

**Meta principal**: NPS ≥ 50, Calificación ≥ 4.5/5

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Implementar sistema de tickets** | - Herramienta (ej: Zendesk, Freshdesk)<br>- $50/mes<br>- 8 horas configuración | Support Lead | Q1 2025 (Enero) | Sistema operativo con SLAs configurados |
| **Encuesta NPS trimestral** | - Herramienta de encuestas (TypeForm, Google Forms)<br>- 4 horas por trimestre | Support Lead | Trimestral | Encuesta enviada + resultados analizados |
| **Chat en vivo** | - Intercom / Crisp / Tawk.to<br>- $100/mes<br>- 8 horas configuración | Support Lead | Q2 2025 | Chat operativo en horario de oficina |
| **Base de conocimiento (FAQ)** | - 40 horas de creación de contenido<br>- Herramienta (ej: Notion) | Support Lead | Q1-Q2 2025 | 50+ artículos publicados |
| **Webinars mensuales de capacitación** | - 4 horas preparación + 1 hora ejecución por mes<br>- Herramienta de video | Support Lead | Mensual | Mínimo 1 webinar por mes realizado |

**Presupuesto total**: $1,800 anual + 80 horas de trabajo

---

### Objetivo 4: Velocidad y Eficiencia de Desarrollo

**Meta principal**: Tiempo de despliegue ≤ 30 minutos

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Automatizar pipeline CI/CD completo** | - Configuración de GitHub Actions<br>- Tests automatizados<br>- 40 horas | DevOps Lead | Q1 2025 | Pipeline completamente automatizado |
| **Staging environment idéntico a prod** | - Servidor de staging<br>- $50/mes<br>- 16 horas configuración | DevOps Lead | Q1 2025 (Febrero) | Staging operativo + despliegues automáticos |
| **Implementar feature flags** | - Librería de feature flags<br>- 16 horas de implementación | Tech Lead | Q2 2025 | Feature flags operativos en producción |
| **Establecer sprints de 2 semanas** | - Capacitación en Scrum<br>- 8 horas de planning/retrospectivas por sprint | Product Owner | Q1 2025 (iniciando Enero) | Velocity estable por 3 sprints consecutivos |
| **Reducir tiempo de code review** | - Acuerdo de equipo (< 4 horas)<br>- Notificaciones automatizadas | Tech Lead | Inmediato | 90% de PRs revisados en < 4 horas |

**Presupuesto total**: $600 anual + 80 horas de desarrollo

---

### Objetivo 5: Seguridad y Cumplimiento

**Meta principal**: 0 incidentes de seguridad con exposición de datos

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Dependabot y escaneo automático** | - Configuración en GitHub<br>- 4 horas | DevOps Lead | Q1 2025 (Enero) | Dependabot habilitado + alertas activas |
| **Auditoría de seguridad interna** | - Checklist OWASP Top 10<br>- 16 horas | DevOps Lead | Trimestral | Auditoría completada + issues resueltos |
| **Pentesting externo** | - Contratar pentester<br>- $2,000-$5,000 | DevOps Lead | Q3 2025 | Reporte recibido + vulnerabilidades parcheadas |
| **Encriptación de datos sensibles** | - Implementar encryption at rest<br>- 24 horas de desarrollo | DevOps Lead | Q2 2025 | Datos sensibles encriptados en BD |
| **Procedimientos GDPR/Ley 1581** | - Documentar procedimientos<br>- Política de privacidad<br>- 16 horas | Director + DevOps | Q1 2025 | Documentos publicados + compliance verificado |
| **2FA para usuarios admin** | - Implementar 2FA<br>- 16 horas de desarrollo | Tech Lead | Q2 2025 | 2FA activo para todos los admins |

**Presupuesto total**: $3,500 + 76 horas de trabajo

---

### Objetivo 6: Formación y Competencia del Equipo

**Meta principal**: ≥ 40 horas de capacitación por persona/año

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Suscripciones a plataformas de aprendizaje** | - Laracasts, Frontend Masters, Pluralsight<br>- $300/persona/año | Director | Q1 2025 | Suscripciones activas para todo el equipo |
| **Budget de conferencias** | - Entrada + viáticos<br>- $1,000/persona/año | Director | Anual | Mínimo 1 conferencia/persona en 2025 |
| **Tech talks internos quincenales** | - 2 horas preparación + 1 hora presentación<br>- Rotación de equipo | Tech Lead | Quincenal | 24 tech talks en el año |
| **Certificaciones técnicas** | - Cursos de preparación<br>- Examen de certificación<br>- $500/persona | Director | Anual | Mínimo 1 certificación/persona obtenida |
| **Tiempo dedicado de aprendizaje** | - 10% del tiempo de sprint<br>- 4 horas/persona/semana | Tech Lead | Continuo | Registrado en time tracking |

**Presupuesto total**: $8,000 + 150 horas anuales del equipo

---

### Objetivo 7: Mejora Continua del SGC

**Meta principal**: ≥ 2 auditorías internas al año

| ¿Qué se hará? | Recursos Necesarios | Responsable | Cuándo | ¿Cómo se evaluará? |
|---------------|---------------------|-------------|--------|-------------------|
| **Auditoría interna H1** | - Auditor interno (8 horas)<br>- Checklist ISO 9001 | Rep. SGC | Q2 2025 (Junio) | Informe de auditoría generado |
| **Auditoría interna H2** | - Auditor interno (8 horas)<br>- Checklist ISO 9001 | Rep. SGC | Q4 2025 (Diciembre) | Informe de auditoría generado |
| **Revisiones por la dirección** | - 3 horas por revisión<br>- Consolidación de métricas | Director + Rep. SGC | Q2 y Q4 2025 | Acta de revisión con decisiones |
| **Retrospectivas de sprint** | - 1 hora por sprint<br>- Facilitación | Tech Lead | Cada 2 semanas | 80% de mejoras implementadas |
| **Actualización de documentación SGC** | - 8 horas por trimestre | Rep. SGC | Trimestral | Todos los docs vigentes y actualizados |

**Presupuesto total**: 100 horas de trabajo

---

## Resumen de Recursos Necesarios

### Presupuesto Anual Total

| Categoría | Costo Anual |
|-----------|-------------|
| Herramientas de monitoreo y uptime | $1,800 |
| Herramientas de soporte al cliente | $1,800 |
| Infraestructura de staging | $600 |
| Seguridad (pentesting, auditorías) | $3,500 |
| Capacitación y certificaciones | $8,000 |
| **TOTAL** | **$15,700** |

### Horas de Trabajo Requeridas

| Rol | Horas Anuales Estimadas |
|-----|-------------------------|
| DevOps Lead | 200 horas |
| Tech Lead | 180 horas |
| Support Lead | 150 horas |
| Developers (total) | 150 horas |
| Product Owner | 50 horas |
| Director | 50 horas |
| Representante SGC | 100 horas |
| **TOTAL** | **880 horas** |

**Promedio por persona** (equipo de 5): 176 horas/año = 3.4 horas/semana

---

## Calendario de Hitos 2025

### Q1 2025 (Enero - Marzo)

| Mes | Hitos Clave |
|-----|-------------|
| **Enero** | - Monitoreo 24/7 operativo<br>- CI con coverage configurado<br>- Sistema de tickets implementado<br>- Dependabot habilitado<br>- Sprints de 2 semanas iniciados |
| **Febrero** | - Backups automatizados operativos<br>- Análisis estático implementado<br>- Staging environment listo |
| **Marzo** | - Disaster recovery documentado<br>- Primera encuesta NPS enviada<br>- Procedimientos GDPR/Ley 1581 publicados |

### Q2 2025 (Abril - Junio)

| Mes | Hitos Clave |
|-----|-------------|
| **Abril** | - Auto-scaling implementado<br>- Chat en vivo operativo |
| **Mayo** | - 2FA para admins implementado<br>- Encriptación de datos sensibles |
| **Junio** | - **Primera auditoría interna**<br>- **Primera revisión por la dirección**<br>- Segunda encuesta NPS |

### Q3 2025 (Julio - Septiembre)

| Mes | Hitos Clave |
|-----|-------------|
| **Julio** | - Feature flags implementados |
| **Agosto** | - Pentesting externo ejecutado |
| **Septiembre** | - Tercera encuesta NPS<br>- Evaluación mid-year de objetivos |

### Q4 2025 (Octubre - Diciembre)

| Mes | Hitos Clave |
|-----|-------------|
| **Octubre** | - Preparación para segunda auditoría |
| **Noviembre** | - Cuarta encuesta NPS |
| **Diciembre** | - **Segunda auditoría interna**<br>- **Segunda revisión por la dirección**<br>- Evaluación final de objetivos 2025<br>- Planificación de objetivos 2026 |

---

## Seguimiento del Progreso

### Dashboard de Objetivos

Se mantendrá un dashboard actualizado mensualmente con:
- Estado actual vs meta de cada objetivo
- Progreso de actividades planificadas
- Alertas de desviaciones
- Próximos hitos

**Ubicación**: [A COMPLETAR - ej: Notion, Google Sheets, Grafana]

**Responsable**: Representante del SGC

### Reuniones de Seguimiento

| Frecuencia | Reunión | Participantes | Propósito |
|------------|---------|---------------|-----------|
| Semanal | Weekly team meeting | Todo el equipo | Sincronización general, bloqueos |
| Mensual | Revisión de métricas | Rep. SGC + Responsables | Analizar métricas, identificar desviaciones |
| Trimestral | Revisión de objetivos | Todo el equipo | Evaluar progreso, ajustar planificación |
| Semestral | Revisión por la dirección | Director + leads | Decisiones estratégicas sobre objetivos |

---

## Ajustes a la Planificación

Esta planificación es **dinámica** y puede ajustarse cuando:
- Cambien prioridades del negocio
- Se identifiquen nuevos riesgos u oportunidades
- Los recursos no estén disponibles como se planeó
- Se logre un objetivo antes de lo previsto
- Un objetivo resulte inalcanzable y deba ser revisado

**Cualquier ajuste significativo debe**:
1. Ser propuesto por el responsable
2. Evaluado por el Representante del SGC
3. Aprobado por el Director General
4. Comunicado al equipo
5. Documentado en este documento (historial de cambios)

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
| 1.0 | 2025-11-04 | Creación inicial de la planificación 2025 | [A COMPLETAR] |

---

**Próxima revisión completa**: Q2 2025 (durante Revisión por la Dirección)
